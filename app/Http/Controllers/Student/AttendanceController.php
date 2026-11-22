<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;

class AttendanceController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $student->load('currentSemester');

        $enrollments = $student->enrollments()
            ->where('chance', 1)
            ->where('semester_id', $student->current_semester_id)
            ->with(['subject.teacher', 'subject.semester'])
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->subject->code);

        // The attendance of a subject is shown only when its teacher allowed it
        $cards = $enrollments->map(function ($enrollment) use ($student) {
            $subject = $enrollment->subject;
            $visible = (bool) $subject->attendance_visible;

            return [
                'subject' => $subject,
                'visible' => $visible,
                'data' => $visible ? AttendanceService::forStudent($subject, $student->id) : null,
            ];
        })->values();

        return view('student.attendance', [
            'student' => $student,
            'cards' => $cards,
            'minPercent' => AttendanceService::minPercent(),
        ]);
    }
}