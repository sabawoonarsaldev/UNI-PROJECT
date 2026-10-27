<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;

class ScheduleController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $student->load('currentSemester');

        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $schedules = ClassSchedule::with('subject.teacher')
            ->whereHas('subject.enrollments', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where('semester_id', $student->current_semester_id);
            })
            ->orderBy('start_time')
            ->get()
            ->groupBy('day');

        return view('student.schedule', compact('student', 'days', 'schedules'));
    }
}