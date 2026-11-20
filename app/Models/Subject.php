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


// Saves the scores of the editable rows. $scores is [enrollment id => [exam, homework, activity, final]].
// A row without any score deletes the grade (= not graded yet).
public function saveGrades(Collection $rows, array $scores): void
{
    foreach ($rows->where('editable', true) as $enrollment) {
        $row = $scores[$enrollment->id] ?? [];
        $parts = [];

        foreach (Grade::COMPONENTS as $key => $component) {
            $value = $row[$key] ?? null;
            $parts[$component['column']] = ($value === null || $value === '') ? null : (float) $value;
        }

        if (collect($parts)->every(fn ($value) => $value === null)) {
            $enrollment->grade?->delete();
            continue;
        }

        // The total is always the sum of the four parts
        $total = round(array_sum($parts), 2);

        Grade::updateOrCreate(
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
    }
}

}
