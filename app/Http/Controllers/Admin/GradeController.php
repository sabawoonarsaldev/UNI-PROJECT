<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
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
        ->withAvg('grades', 'marks');

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

}