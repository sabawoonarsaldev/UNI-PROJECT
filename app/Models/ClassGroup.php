<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClassGroup extends Model
{
    public const TOTAL_SEMESTERS = 8;

    protected $fillable = [
        'department_id', 'semester_type_id', 'entry_year', 'current_semester_number', 'status', 'total_semesters',
    ];

    // Example: "Software Engineering - Fall 1405"
    protected function name(): Attribute
    {
        return Attribute::get(
            fn () => ($this->department?->name ?? '') . ' - ' . ($this->entryTerm?->name ?? '') . ' ' . $this->entry_year
        );
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function entryTerm()
    {
        return $this->belongsTo(SemesterType::class, 'semester_type_id');
    }

    public function semesters()
    {
        return $this->hasMany(Semester::class)->orderBy('number');
    }

    public function students()
    {
        return $this->hasMany(User::class)->where('role', 'student');
    }

    public function currentSemester(): ?Semester
    {
        return $this->semesters()->where('number', $this->current_semester_number)->first();
    }

    // Puts a student into this class and enrolls him in the subjects of the current semester
    public function addStudent(User $student): void
    {
        $semester = $this->currentSemester();

        $student->update([
            'class_group_id' => $this->id,
            'department_id' => $this->department_id,
            'current_semester_id' => $semester?->id,
        ]);

        if ($semester) {
            foreach ($semester->subjects as $subject) {
                Enrollment::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'semester_id' => $semester->id,
                ]);
            }
        }
    }

    public function removeStudent(User $student): void
    {
        $semester = $this->currentSemester();

        if ($semester) {
            Enrollment::where('student_id', $student->id)
                ->where('semester_id', $semester->id)
                ->delete();
        }

        $student->update(['class_group_id' => null, 'current_semester_id' => null]);
    }

    // Closes the current semester and moves the class to the next one.
    // Returns the new semester, or null when the class has finished all semesters.
    public function advance(): ?Semester
    {
        $current = $this->currentSemester();
        $current->update(['is_active' => false]);

        if ($this->current_semester_number >= $this->total_semesters) {
            $this->update(['status' => 'completed']);

            return null;
        }

        // The next term in the configured order; after the last term a new year begins
        $types = SemesterType::orderBy('sort_order')->get();
        $index = $types->search(fn ($type) => $type->id === $current->semester_type_id);
        $nextType = $types->get(($index === false ? 0 : $index) + 1);
        $year = $current->year;

        if (! $nextType) {
            $nextType = $types->first();
            $year++;
        }

        $number = $this->current_semester_number + 1;

        $next = Semester::create([
            'department_id' => $this->department_id,
            'class_group_id' => $this->id,
            'semester_type_id' => $nextType->id,
            'year' => $year,
            'number' => $number,
            'name' => Semester::buildName($number, $nextType->name, $year),
            'is_active' => true,
        ]);

        $this->update(['current_semester_number' => $number]);

        // All students of the class move to the new semester
        $this->students()->update(['current_semester_id' => $next->id]);

        return $next;
    }

    // The last completed semester: the one that can be reopened
public function lastCompletedNumber(): ?int
{
    $number = $this->status === 'completed' ? $this->total_semesters : $this->current_semester_number - 1;

    return $number >= 1 ? $number : null;
}

// Returns the reason why the last completed semester cannot be reopened, or null when it can
public function reopenProblem(): ?string
{
    $number = $this->lastCompletedNumber();

    if (! $number) {
        return 'No semester has been completed yet.';
    }

    $semester = $this->semesters()->where('number', $number)->first();

    if (! $semester || $semester->is_active) {
        return 'This semester is not completed.';
    }

    // A retake of this semester that already has a mark cannot be undone
    $subjectIds = $semester->subjects()->pluck('id');

    if (Grade::whereIn('subject_id', $subjectIds)->where('chance', '>', 1)->exists()) {
        return 'A retake of this semester already has a grade.';
    }

    // The semester that started after it must still be untouched
    if ($this->status === 'active') {
        $next = $this->currentSemester();

        if ($next) {
            $nextIds = $next->subjects()->pluck('id');

            if (Grade::whereIn('subject_id', $nextIds)->exists() || AttendanceSession::whereIn('subject_id', $nextIds)->exists()) {
                return 'The next semester already has grades or attendance.';
            }
        }
    }

    return null;
}

// Undoes "Complete semester": the last completed semester is open again
public function reopenLastSemester(): Semester
{
    $number = $this->lastCompletedNumber();
    $semester = $this->semesters()->where('number', $number)->firstOrFail();

    DB::transaction(function () use ($number, $semester) {
        // The empty semester that started after it is removed (with the subjects chosen for it)
        if ($this->status === 'active') {
            $this->currentSemester()?->delete();
        }

        // The retakes that were created when the semester was completed are removed
        Enrollment::whereIn('subject_id', $semester->subjects()->pluck('id'))->where('chance', '>', 1)->delete();

        $semester->update(['is_active' => true]);
        $this->update(['current_semester_number' => $number, 'status' => 'active']);

        // The results are not final any more; they are calculated again at the next "Complete semester"
        $this->students()->update(['current_semester_id' => $semester->id, 'academic_status' => 'active']);
    });

    return $semester->refresh();
}

    // Looks at the results of the completed semesters:
// - a failed subject gets the next chance
// - a failure in the last chance is a complete failure of the student
// - after the last semester, a student without open subjects graduates
public function evaluate(): array
{
    $summary = ['retakes' => 0, 'failed' => 0, 'graduated' => 0];

    $students = $this->students()->where('academic_status', 'active')->get();

    foreach ($students as $student) {
        $subjects = Enrollment::where('student_id', $student->id)
            ->whereHas('subject.semester', fn ($query) => $query->where('is_active', false))
            ->with('grade')
            ->get()
            ->groupBy('subject_id');

        $completelyFailed = false;
        $hasOpenSubject = false;

        foreach ($subjects as $enrollments) {
            $latest = $enrollments->sortByDesc('chance')->first();

            // A retake without a mark, or with a mark that is not published yet, is still open
if (! $latest->grade || $latest->grade->status !== 'published') {
    $hasOpenSubject = true;
    continue;
}


            if ($latest->grade->marks >= Grade::PASS_MARK) {
                continue;
            }

            if ($latest->chance < Grade::MAX_CHANCES) {
                Enrollment::create([
                    'student_id' => $student->id,
                    'subject_id' => $latest->subject_id,
                    'semester_id' => $latest->semester_id,
                    'chance' => $latest->chance + 1,
                ]);

                $summary['retakes']++;
                $hasOpenSubject = true;
            } else {
                $completelyFailed = true;
            }
        }

        if ($completelyFailed) {
            $student->update(['academic_status' => 'failed']);
            $summary['failed']++;
        } elseif ($this->status === 'completed' && ! $hasOpenSubject && $subjects->isNotEmpty()) {
            $student->update(['academic_status' => 'graduated']);
            $summary['graduated']++;
        }
    }

    return $summary;
}

}