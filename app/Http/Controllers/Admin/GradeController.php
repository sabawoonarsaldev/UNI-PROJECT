<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\AuditLog;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\Concerns\FiltersByClass;

class GradeController extends Controller
{
    use FiltersByClass;
    // All subjects with grading statistics
    public function index(Request $request)
{
    $query = Subject::with([
            'semester.classGroup.department',
            'semester.classGroup.entryTerm',
            'teacher',
        ])
        ->withCount('enrollments')
        ->withCount('grades')
        ->withCount(['grades as passed_count' => fn ($q) => $q->where('marks', '>=', Grade::PASS_MARK)])
        ->withAvg('grades', 'marks')
        ->withCount([
            'grades as draft_count' => fn ($q) => $q->where('status', 'draft'),
            'grades as submitted_count' => fn ($q) => $q->where('status', 'submitted'),
            'grades as published_count' => fn ($q) => $q->where('status', 'published'),
        ]);

    $subjects = $this->orderByClass($this->applyClassFilters($query, $request))
        ->paginate(10)
        ->withQueryString();

    return view('admin.grades.index', ['subjects' => $subjects] + $this->classFilterOptions($request));
}


    public function show(Subject $subject)
{
    $subject->load(['department', 'semester', 'teacher']);

    $rows = $subject->gradebook(true);

    // The statistics use the latest graded chance of every student
    $latestGrades = $rows->groupBy('student_id')
        ->map(fn ($attempts) => $attempts->sortByDesc('chance')->pluck('grade')->filter()->first())
        ->filter();

    $marks = $latestGrades->map(fn ($grade) => (float) $grade->marks);

    $stats = [
        'enrolled' => $rows->pluck('student_id')->unique()->count(),
        'graded' => $marks->count(),
        'average' => $marks->isNotEmpty() ? round($marks->avg(), 2) : null,
        'highest' => $marks->isNotEmpty() ? $marks->max() : null,
        'lowest' => $marks->isNotEmpty() ? $marks->min() : null,
        'pass_rate' => $marks->isNotEmpty()
            ? round($marks->filter(fn ($mark) => $mark >= Grade::PASS_MARK)->count() / $marks->count() * 100, 1)
            : null,
    ];

    return view('admin.grades.show', compact('subject', 'rows', 'stats'));
}

// The admin may change the latest chance of any student, even when the semester is closed
public function update(Request $request, Subject $subject)
{
    $scores = Grade::validateScores($request);
    $subject->saveGrades($subject->gradebook(true), $scores);

    return redirect()->route('admin.grades.show', $subject)
        ->with('success', 'Grades saved successfully.');
}

// Students see the grades from now on
public function publish(Subject $subject)
{
    $count = Grade::where('subject_id', $subject->id)
        ->where('status', '!=', 'published')
        ->update(['status' => 'published', 'published_at' => now(), 'published_by' => auth()->id()]);

    if ($count === 0) {
        return back()->with('error', 'There are no unpublished grades in this subject.');
    }

    AuditLog::record('grade.published', "{$count} grade(s) of {$subject->code} were published to the students.", $subject);

    return back()->with('success', "{$count} grade(s) were published. The students can see them now.");
}

// The students no longer see the grades; the teacher can change them again
public function unpublish(Subject $subject)
{
    $count = Grade::where('subject_id', $subject->id)
        ->where('status', 'published')
        ->update(['status' => 'draft', 'submitted_at' => null, 'published_at' => null, 'published_by' => null]);

    if ($count === 0) {
        return back()->with('error', 'There are no published grades in this subject.');
    }

    AuditLog::record('grade.unpublished', "{$count} grade(s) of {$subject->code} were unpublished.", $subject);

    return back()->with('success', "{$count} grade(s) were unpublished.");
}

// The administration sends the submitted grades back to the teacher
public function returnToTeacher(Subject $subject)
{
    $count = Grade::where('subject_id', $subject->id)
        ->where('status', 'submitted')
        ->update(['status' => 'draft', 'submitted_at' => null]);

    if ($count === 0) {
        return back()->with('error', 'There are no submitted grades in this subject.');
    }

    AuditLog::record('grade.returned', "{$count} grade(s) of {$subject->code} were returned to the teacher.", $subject);

    return back()->with('success', "{$count} grade(s) were returned to the teacher. Tell the teacher the reason in Messages.");
}

}