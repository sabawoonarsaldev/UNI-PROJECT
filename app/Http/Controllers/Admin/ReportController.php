<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Grade;
use App\Models\User;
use App\Services\GpaCalculator;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $departmentId = $request->department_id;

        // A fresh query each time, optionally limited to one department
        $grades = fn () => Grade::query()
            ->when($departmentId, fn ($query) => $query->whereHas(
                'subject', fn ($subject) => $subject->where('department_id', $departmentId)
            ));

        $totalGrades = $grades()->count();
        $passedGrades = $grades()->where('marks', '>=', Grade::PASS_MARK)->count();

        $summary = [
            'total' => $totalGrades,
            'average' => $totalGrades > 0 ? round($grades()->avg('marks'), 2) : null,
            'pass_rate' => $totalGrades > 0 ? round($passedGrades / $totalGrades * 100, 1) : null,
            'failed' => $totalGrades - $passedGrades,
        ];

        // Every failed subject of every student
        $failing = $grades()
            ->where('marks', '<', Grade::PASS_MARK)
            ->with(['student.department', 'subject', 'semester'])
            ->orderBy('marks')
            ->paginate(10)
            ->withQueryString();

        // Top 10 students by CGPA
        $topStudents = User::where('role', 'student')
            ->when($departmentId, fn ($query) => $query->where('department_id', $departmentId))
            ->whereHas('grades')
            ->with(['department', 'grades.subject'])
            ->get()
            ->map(fn ($student) => ['student' => $student, 'cgpa' => GpaCalculator::gpa($student->grades)])
            ->filter(fn ($item) => $item['cgpa'] !== null)
            ->sortByDesc('cgpa')
            ->take(10)
            ->values();

        $departments = Department::orderBy('name')->get();

        return view('admin.reports.index', compact('summary', 'failing', 'topStudents', 'departments'));
    }
}