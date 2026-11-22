<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    // status => [label, badge style]
    public const STATUSES = [
        'present' => ['Present', 'bg-green-100 text-green-800'],
        'absent' => ['Absent', 'bg-red-100 text-red-700'],
        'excused' => ['Excused', 'bg-blue-100 text-blue-800'],
    ];

    protected $fillable = ['attendance_session_id', 'student_id', 'status', 'note'];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'attendance_session_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}