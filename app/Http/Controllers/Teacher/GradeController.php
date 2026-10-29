<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function edit(Subject $subject)
    {
        abort_unless($subject->teacher_id == auth()->id(), 403);

        $subject->load(['department', 'semester']);
        $rows = $subject->gradebook();

        if (! $rows->contains('editable', true)) {
            return redirect()->route('teacher.subjects.show', $subject)
                ->with('error', 'There is nothing to grade right now. The semester is closed and no retake is waiting.');
        }

        return view('teacher.grades.edit', compact('subject', 'rows'));
    }

    public function update(Request $request, Subject $subject)
    {
        abort_unless($subject->teacher_id == auth()->id(), 403);

        $request->validate([
            'marks' => 'array',
            'marks.*' => 'nullable|numeric|min:0|max:100',
        ]);

        // Only the rows that are editable are saved, so ids sent from outside are ignored
        $subject->saveGrades($subject->gradebook(), $request->input('marks', []));

        return redirect()->route('teacher.subjects.show', $subject)
            ->with('success', 'Grades saved successfully.');
    }
}