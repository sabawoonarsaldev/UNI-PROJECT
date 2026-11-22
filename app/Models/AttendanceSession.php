<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceSession extends Model
{
    protected $fillable = ['subject_id', 'date', 'taken_by', 'locked_at', 'locked_by'];

    protected $casts = [
        'date' => 'date',
        'locked_at' => 'datetime',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function records()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function taker()
    {
        return $this->belongsTo(User::class, 'taken_by');
    }

    public function locker()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }
}