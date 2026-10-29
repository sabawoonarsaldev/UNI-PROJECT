<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    public const TOTAL_SEMESTERS = 8;

    protected $fillable = [
        'department_id', 'semester_type_id', 'entry_year', 'current_semester_number', 'status',
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

        if ($this->current_semester_number >= self::TOTAL_SEMESTERS) {
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

            if (! $latest->grade) {
                $hasOpenSubject = true; // a retake that is waiting for its mark
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