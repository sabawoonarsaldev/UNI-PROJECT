<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'target_role', 'faculty_id', 'department_id', 'created_by'];

    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // The announcements a user may see:
    // - no faculty = every faculty
    // - a faculty without department = every department of that faculty
    // - a faculty with a department = only that department
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $facultyId = $user->department?->faculty_id;

        return $query
            ->whereIn('target_role', ['all', $user->role])
            ->where(function ($q) use ($facultyId, $user) {
                $q->whereNull('faculty_id')
                    ->orWhere(function ($faculty) use ($facultyId, $user) {
                        $faculty->where('faculty_id', $facultyId)
                            ->where(function ($department) use ($user) {
                                $department->whereNull('department_id')
                                    ->orWhere('department_id', $user->department_id);
                            });
                    });
            });
    }

    // Who gets this announcement, as a text
    public function audienceLabel(): string
    {
        if (! $this->faculty_id) {
            return 'All faculties';
        }

        return $this->department_id
            ? $this->faculty?->name . ' / ' . $this->department?->name
            : $this->faculty?->name . ' (all departments)';
    }
}