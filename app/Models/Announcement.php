<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Announcement extends Model
{
    protected $fillable = ['title', 'body', 'target_role', 'department_id', 'created_by'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query
            ->whereIn('target_role', ['all', $user->role])
            ->where(function ($q) use ($user) {
                $q->whereNull('department_id')
                ->orWhere('department_id', $user->department_id);
            });
    }
}