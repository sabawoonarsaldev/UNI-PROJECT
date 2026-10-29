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
        $editable = $asAdmin
            ? $isLatest
            : $enrollment->student->academic_status === 'active'
                && ($enrollment->chance === 1 ? $semesterIsOpen : $isLatest);

        $enrollment->setAttribute('editable', $editable);
    }

    return $enrollments
        ->sort(fn ($a, $b) => [$a->student->name, $a->chance] <=> [$b->student->name, $b->chance])
        ->values();
}

// Saves the marks of the editable rows. $marks is [enrollment id => marks]; an empty value deletes the grade.
public function saveGrades(Collection $rows, array $marks): void
{
    foreach ($rows->where('editable', true) as $enrollment) {
        $value = $marks[$enrollment->id] ?? null;

        if ($value === null || $value === '') {
            $enrollment->grade?->delete();
            continue;
        }

        Grade::updateOrCreate(
            ['enrollment_id' => $enrollment->id],
            [
                'student_id' => $enrollment->student_id,
                'subject_id' => $this->id,
                'semester_id' => $enrollment->semester_id,
                'chance' => $enrollment->chance,
                'marks' => $value,
                'grade_letter' => Grade::letterFor((float) $value),
            ]
        );
    }
}

}
