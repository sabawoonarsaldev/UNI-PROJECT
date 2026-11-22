<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'department_id', 'semester_id', 'teacher_id', 'credit_hours'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // All enrollments of this subject (every chance of every student), with the information
// whether the grade may still be changed: $editable = true / false
public function gradebook(bool $asAdmin = false): Collection
{
    $this->loadMissing('semester');

    $enrollments = $this->enrollments()->with(['student', 'grade'])->get();
    $latest = $enrollments->groupBy('student_id')->map(fn ($rows) => $rows->max('chance'));
    $semesterIsOpen = (bool) $this->semester->is_active;

    foreach ($enrollments as $enrollment) {
        $isLatest = $enrollment->chance === $latest[$enrollment->student_id];

        // The admin may change the latest chance of any student.
        // A teacher may change chance 1 while the semester is open, and the latest retake of an active student.
        // The teacher can change a grade only while it is a draft: after "submit" it belongs to the administration
        $isDraft = ! $enrollment->grade || $enrollment->grade->status === 'draft';

        $editable = $asAdmin
            ? $isLatest
            : $isDraft
                && $enrollment->student->academic_status === 'active'
                && ($enrollment->chance === 1 ? $semesterIsOpen : $isLatest);

        $enrollment->setAttribute('editable', $editable);
    }

    return $enrollments
        ->sort(fn ($a, $b) => [$a->student->name, $a->chance] <=> [$b->student->name, $b->chance])
        ->values();
}


// Saves the scores of the editable rows. $scores is [enrollment id => [exam, homework, activity, final]].
// A row without any score deletes the grade (= not graded yet). Every change goes into the audit log.
public function saveGrades(Collection $rows, array $scores): void
{
    foreach ($rows->where('editable', true) as $enrollment) {
        $row = $scores[$enrollment->id] ?? [];
        $parts = [];

        foreach (Grade::COMPONENTS as $key => $component) {
            $value = $row[$key] ?? null;
            $parts[$component['column']] = ($value === null || $value === '') ? null : (float) $value;
        }

        $existing = $enrollment->grade;
        $label = "{$enrollment->student->name} in {$this->code}"
            . ($enrollment->chance > 1 ? " (chance {$enrollment->chance})" : '');

        if (collect($parts)->every(fn ($value) => $value === null)) {
            if ($existing) {
                AuditLog::record('grade.deleted', "Grade removed: {$label}.", $existing, self::gradeSnapshot($existing));
                $existing->delete();
            }

            continue;
        }

        // The total is always the sum of the four parts
        $total = round(array_sum($parts), 2);
        $old = $existing ? self::gradeSnapshot($existing) : [];

        $grade = Grade::updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            $parts + [
                'student_id' => $enrollment->student_id,
                'subject_id' => $this->id,
                'semester_id' => $enrollment->semester_id,
                'chance' => $enrollment->chance,
                'marks' => $total,
                'grade_letter' => Grade::letterFor($total),
            ]
        );

        $new = self::gradeSnapshot($grade);

        if (! $existing) {
            AuditLog::record('grade.created', "Grade entered: {$label} = {$new['total']}.", $grade, [], $new);
        } elseif ($old != $new) {
            AuditLog::record('grade.updated', "Grade changed: {$label} from {$old['total']} to {$new['total']}.", $grade, $old, $new);
        }
    }
}

// The four parts and the total of a grade, in a form that is easy to read in the log
private static function gradeSnapshot(Grade $grade): array
{
    return [
        'exams' => $grade->exam_score !== null ? (float) $grade->exam_score : null,
        'homework' => $grade->homework_score !== null ? (float) $grade->homework_score : null,
        'activity' => $grade->activity_score !== null ? (float) $grade->activity_score : null,
        'final' => $grade->final_score !== null ? (float) $grade->final_score : null,
        'total' => (float) $grade->marks,
    ];
}

public function attendanceSessions()
{
    return $this->hasMany(AttendanceSession::class);
}


}
