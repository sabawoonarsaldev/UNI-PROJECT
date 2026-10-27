<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSchedule;
use App\Models\Enrollment;

class DashboardController extends Controller
{
    public function index()
    {
        $teacher = auth()->user();

        $subjects = $teacher->subjectsTaught()
            ->with('semester')
            ->withCount(['enrollments', 'grades'])
            ->orderBy('semester_id')
            ->get();

        $studentsCount = Enrollment::whereIn('subject_id', $subjects->pluck('id'))
            ->distinct()
            ->count('student_id');

        // Grading progress over all subjects: entered grades / enrolled students
        $enrolledTotal = $subjects->sum('enrollments_count');
        $gradedTotal = $subjects->sum('grades_count');
        $progress = $enrolledTotal > 0 ? round($gradedTotal / $enrolledTotal * 100) : 0;

        $todayClasses = ClassSchedule::with('subject')
            ->whereHas('subject', fn ($query) => $query->where('teacher_id', $teacher->id))
            ->where('day', now()->format('l'))
            ->orderBy('start_time')
            ->get();

        $announcements = Announcement::visibleTo($teacher)->latest()->take(3)->get();
        $unreadMessages = $teacher->unreadMessagesCount();

        return view('teacher.dashboard', compact(
            'subjects', 'studentsCount', 'progress', 'todayClasses', 'announcements', 'unreadMessages'
        ));
    }
}