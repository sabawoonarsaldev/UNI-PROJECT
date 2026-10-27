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

    // The grades of one subject, with a form to correct them
    public function show(Subject $subject)
    {
        $subject->load(['department', 'semester', 'teacher']);

        $enrollments = $subject->enrollments()
            ->with('student')
            ->get()
            ->sortBy(fn ($enrollment) => $enrollment->student->name);

        $grades = $subject->grades()->get()->keyBy('student_id');

        // Statistics are calculated only from the enrolled students
        $marks = $enrollments
            ->map(fn ($enrollment) => $grades->get($enrollment->student_id))
            ->filter()
            ->map(fn ($grade) => (float) $grade->marks);

        $stats = [
            'enrolled' => $enrollments->count(),
            'graded' => $marks->count(),
            'average' => $marks->isNotEmpty() ? round($marks->avg(), 2) : null,
            'highest' => $marks->isNotEmpty() ? $marks->max() : null,
            'lowest' => $marks->isNotEmpty() ? $marks->min() : null,
            'pass_rate' => $marks->isNotEmpty()
                ? round($marks->filter(fn ($mark) => $mark >= Grade::PASS_MARK)->count() / $marks->count() * 100, 1)
                : null,
        ];

        return view('admin.grades.show', compact('subject', 'enrollments', 'grades', 'stats'));
    }

    // The admin may correct grades even when the semester is closed
    public function update(Request $request, Subject $subject)
    {
        $request->validate([
            'marks' => 'array',
            'marks.*' => 'nullable|numeric|min:0|max:100',
        ]);

        foreach ($subject->enrollments as $enrollment) {
            $marks = $request->input("marks.{$enrollment->student_id}");

            $keys = [
                'student_id' => $enrollment->student_id,
                'subject_id' => $subject->id,
                'semester_id' => $subject->semester_id,
            ];

            if ($marks === null || $marks === '') {
                Grade::where($keys)->delete();
                continue;
            }

            Grade::updateOrCreate($keys, [
                'marks' => $marks,
                'grade_letter' => Grade::letterFor((float) $marks),
            ]);
        }

        return redirect()->route('admin.grades.show', $subject)
            ->with('success', 'Grades saved successfully.');
    }
}