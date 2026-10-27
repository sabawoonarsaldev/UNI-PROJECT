<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;

class SubjectController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $student->load('currentSemester');

        $enrollments = $student->enrollments()
            ->where('semester_id', $student->current_semester_id)
            ->with(['subject.teacher', 'subject.schedules'])
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->subject->code);

        $grades = $student->grades()
            ->where('semester_id', $student->current_semester_id)
            ->get()
            ->keyBy('subject_id');

        return view('student.subjects', compact('student', 'enrollments', 'grades'));
    }
}