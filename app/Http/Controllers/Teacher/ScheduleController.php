<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;

class ScheduleController extends Controller
{
    public function index()
    {
        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $schedules = ClassSchedule::with(['subject.department', 'subject.semester'])
            ->whereHas('subject', fn ($query) => $query->where('teacher_id', auth()->id()))
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');

        return view('teacher.schedule', compact('days', 'schedules'));
    }
}