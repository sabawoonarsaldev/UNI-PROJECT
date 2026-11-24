<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CurriculumSubject extends Model
{
    // The highest semester number a department can have
    public const MAX_SEMESTERS = 12;

    protected $fillable = ['department_id', 'semester_number', 'name', 'code', 'credit_hours', 'teacher_id'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // The default teacher; every class can choose another teacher
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // The copies of this subject in the classes
    public function offerings()
    {
        return $this->hasMany(Subject::class, 'curriculum_subject_id');
    }
}