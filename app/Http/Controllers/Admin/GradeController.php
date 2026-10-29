<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Grade;
use App\Models\Subject;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    // All subjects with grading statistics
    public function index(Request $request)
    {
        $subjects = Subject::with(['department', 'semester', 'teacher'])
            ->withCount('enrollments')
            ->withCount('grades')
            ->withCount(['grades as passed_count' => fn ($query) => $query->where('marks', '>=', Grade::PASS_MARK)])
            ->withAvg('grades', 'marks')
            ->when($request->department_id, fn ($query, $id) => $query->where('department_id', $id))
            ->orderBy('department_id')
            ->orderBy('semester_id')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('admin.grades.index', compact('subjects', 'departments'));
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
    $request->validate([
        'marks' => 'array',
        'marks.*' => 'nullable|numeric|min:0|max:100',
    ]);

    $subject->saveGrades($subject->gradebook(true), $request->input('marks', []));

    return redirect()->route('admin.grades.show', $subject)
        ->with('success', 'Grades saved successfully.');
}

}