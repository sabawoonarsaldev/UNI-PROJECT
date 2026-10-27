<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function edit(Subject $subject)
    {
        abort_unless($subject->teacher_id == auth()->id(), 403);
        $subject->load(['department', 'semester']);

        if (! $subject->semester->is_active) {
            return redirect()->route('teacher.subjects.show', $subject)
                ->with('error', 'This semester is closed, grades cannot be changed.');
        }

        $enrollments = $subject->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name);

        $grades = $subject->grades()->get()->keyBy('student_id');

        return view('teacher.grades.edit', compact('subject', 'enrollments', 'grades'));
    }

    public function update(Request $request, Subject $subject)
    {
        abort_unless($subject->teacher_id == auth()->id(), 403);
        $subject->load('semester');

        if (! $subject->semester->is_active) {
            return redirect()->route('teacher.subjects.show', $subject)
                ->with('error', 'This semester is closed, grades cannot be changed.');
        }

        $request->validate([
            'marks' => 'array',
            'marks.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Loop over the real enrollments, so ids sent from outside are ignored
        foreach ($subject->enrollments as $enrollment) {
            $marks = $request->input("marks.{$enrollment->student_id}");

            $keys = [
                'student_id' => $enrollment->student_id,
                'subject_id' => $subject->id,
                'semester_id' => $subject->semester_id,
            ];

            if ($marks === null || $marks === '') {
                // An empty box means "not graded yet"
                Grade::where($keys)->delete();
                continue;
            }

            Grade::updateOrCreate($keys, [
                'marks' => $marks,
                'grade_letter' => Grade::letterFor((float) $marks),
            ]);
        }

        return redirect()->route('teacher.subjects.show', $subject)
            ->with('success', 'Grades saved successfully.');
    }
}