<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Semester extends Model
{
    protected $fillable = [
        'name', 'department_id', 'class_group_id', 'number', 'semester_type_id', 'year', 'is_active',
    ];   

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function type()
{
    return $this->belongsTo(SemesterType::class, 'semester_type_id');
}

    // Used to sort semesters in time order: by year first, then by the order of the term
    protected function sortKey(): Attribute
    {
        return Attribute::get(
            fn () => ((int) $this->year) * 10 + (int) ($this->type?->sort_order ?? 0)
        );
    }

    public function scopeChronological($query, string $direction = 'asc')
    {
        return $query
            ->orderBy('year', $direction)
            ->orderBy(
                SemesterType::select('sort_order')->whereColumn('semester_types.id', 'semesters.semester_type_id'),
                $direction
            );
    }

    public function classGroup()
{
    return $this->belongsTo(ClassGroup::class);
}

// Example: "Semester 3 - Fall 1406"
public static function buildName(?int $number, string $term, int $year): string
{
    return ($number ? "Semester {$number} - " : '') . "{$term} {$year}";
}


}