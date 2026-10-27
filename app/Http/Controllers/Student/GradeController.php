<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\GpaCalculator;

class GradeController extends Controller
{
    public function index()
    {
        $student = auth()->user();
        $student->load(['department', 'currentSemester']);

        $grades = $student->grades()->with(['subject', 'semester.type'])->get();

        // One block per semester, each with its own GPA and average
        $semesters = $grades->groupBy('semester_id')
            ->map(fn ($semesterGrades) => [
                'semester' => $semesterGrades->first()->semester,
                'grades' => $semesterGrades->sortBy(fn ($grade) => $grade->subject->code),
                'gpa' => GpaCalculator::gpa($semesterGrades),
                'average' => GpaCalculator::average($semesterGrades),
                'credits' => $semesterGrades->sum(fn ($grade) => $grade->subject->credit_hours),
            ])
            ->sortBy(fn ($item) => $item['semester']->sort_key)            
            ->values();

        $cgpa = GpaCalculator::gpa($grades);
        $overallAverage = GpaCalculator::average($grades);
        $totalCredits = $semesters->sum('credits');

        return view('student.grades', compact(
            'student', 'semesters', 'cgpa', 'overallAverage', 'totalCredits'
        ));
    }
}