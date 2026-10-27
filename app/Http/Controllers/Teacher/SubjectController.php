<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;

class SubjectController extends Controller
{
    public function index()
    {
        $subjects = auth()->user()->subjectsTaught()
            ->with(['department', 'semester'])
            ->withCount('enrollments')
            ->orderBy('semester_id')
            ->get();

        return view('teacher.subjects.index', compact('subjects'));
    }

    public function show(Subject $subject)
    {
        // A teacher can only open his or her own subjects
        abort_unless($subject->teacher_id == auth()->id(), 403);

        $subject->load(['department', 'semester', 'schedules']);

        $enrollments = $subject->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name);

        $grades = $subject->grades()->get()->keyBy('student_id');

        return view('teacher.subjects.show', compact('subject', 'enrollments', 'grades'));
    }
}