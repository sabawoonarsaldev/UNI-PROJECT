<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ClassSchedule;
use App\Models\Grade;
use App\Services\GpaCalculator;

class DashboardController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $student->load(['department', 'currentSemester']);

        $grades = $student->grades()->with(['subject', 'semester.type'])->get();

        $cgpa = GpaCalculator::gpa($grades);

        // GPA of each semester, for the small progress chart
        $semesterGpas = $grades->groupBy('semester_id')
            ->map(fn ($group) => [
                'name' => $group->first()->semester->name,
                'sort_key' => $group->first()->semester->sort_key,
                'gpa' => GpaCalculator::gpa($group),
            ])
            ->sortBy('sort_key')
            ->values();

        $passedCredits = $grades
            ->filter(fn ($grade) => $grade->marks >= Grade::PASS_MARK)
            ->sum(fn ($grade) => $grade->subject->credit_hours);

        $failedCount = $grades->filter(fn ($grade) => $grade->marks < Grade::PASS_MARK)->count();

        // Subjects of the current semester, with the grade (if any)
        $enrollments = $student->enrollments()
            ->where('semester_id', $student->current_semester_id)
            ->with('subject.teacher')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->subject->code);

        $currentGrades = $grades->where('semester_id', $student->current_semester_id)->keyBy('subject_id');

        $todayClasses = ClassSchedule::with('subject.teacher')
            ->whereHas('subject.enrollments', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where('semester_id', $student->current_semester_id);
            })
            ->where('day', now()->format('l'))
            ->orderBy('start_time')
            ->get();

        $announcements = Announcement::visibleTo($student)->latest()->take(3)->get();
        $unreadMessages = $student->unreadMessagesCount();

        return view('student.dashboard', compact(
            'student', 'cgpa', 'semesterGpas', 'passedCredits', 'failedCount',
            'enrollments', 'currentGrades', 'todayClasses', 'announcements', 'unreadMessages'
        ));
    }
}