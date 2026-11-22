<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Models\AuditLog;
use App\Models\Grade;

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

    // Only the rows that are editable are saved, so ids sent from outside are ignored
    $scores = Grade::validateScores($request);
    $subject->saveGrades($subject->gradebook(), $scores);

    return redirect()->route('teacher.subjects.show', $subject)
        ->with('success', 'Grades saved successfully.');
}

// The teacher sends the draft grades of the subject to the administration.
// After this they are locked for the teacher until the administration returns them.
public function submit(Subject $subject)
{
    abort_unless($subject->teacher_id == auth()->id(), 403);

    $count = Grade::where('subject_id', $subject->id)
        ->where('status', 'draft')
        ->update(['status' => 'submitted', 'submitted_at' => now()]);

    if ($count === 0) {
        return back()->with('error', 'There are no draft grades to submit.');
    }

    AuditLog::record('grade.submitted', "{$count} grade(s) of {$subject->code} were submitted for review.", $subject);

    return back()->with('success', "{$count} grade(s) were submitted to the administration.");
}
}