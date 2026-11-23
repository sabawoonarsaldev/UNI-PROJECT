<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;
use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Grade;
use App\Models\User;
use App\Services\GpaCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    use HasFacultyFilter;

    public function index(Request $request)
    {
        $facultyId = $request->query('faculty_id');
        $departmentId = $request->query('department_id');

        // Only the latest chance of every student in every subject counts
        $latest = fn () => Grade::query()->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('grades as newer')
                ->whereColumn('newer.student_id', 'grades.student_id')
                ->whereColumn('newer.subject_id', 'grades.subject_id')
                ->whereColumn('newer.chance', '>', 'grades.chance');
        });

        // The grades of the selected faculty and department (nothing selected = everything)
        $grades = fn () => $latest()
            ->when($facultyId, fn ($q, $id) => $q->whereHas('subject.department', fn ($d) => $d->where('faculty_id', $id)))
            ->when($departmentId, fn ($q, $id) => $q->whereHas('subject', fn ($s) => $s->where('department_id', $id)));

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
            ->with(['student.department.faculty', 'subject', 'semester'])
            ->orderBy('marks')
            ->paginate(10)
            ->withQueryString();

        // Top 10 students by CGPA
        $topStudents = $this->limitUsersToFaculty(User::where('role', 'student'), $request)
            ->whereHas('grades')
            ->with(['department.faculty', 'grades.subject'])
            ->get()
            ->map(fn ($student) => ['student' => $student, 'cgpa' => GpaCalculator::gpa($student->grades)])
            ->filter(fn ($item) => $item['cgpa'] !== null)
            ->sortByDesc('cgpa')
            ->take(10)
            ->values();

        // The report of every faculty, one row for each (it does not depend on the selection)
        $byFaculty = Faculty::orderBy('name')->get()->map(function ($faculty) use ($latest) {
            $inFaculty = fn ($query) => $query->whereHas('subject.department', fn ($d) => $d->where('faculty_id', $faculty->id));
            $ofFaculty = fn ($query) => $query->whereHas('department', fn ($d) => $d->where('faculty_id', $faculty->id));

            $graded = $inFaculty($latest())->count();
            $passed = $inFaculty($latest())->where('marks', '>=', Grade::PASS_MARK)->count();

            return [
                'faculty' => $faculty,
                'departments' => Department::where('faculty_id', $faculty->id)->count(),
                'classes' => $ofFaculty(ClassGroup::query())->count(),
                'students' => $ofFaculty(User::where('role', 'student'))->count(),
                'teachers' => $ofFaculty(User::where('role', 'teacher'))->count(),
                'graded' => $graded,
                'average' => $graded > 0 ? round($inFaculty($latest())->avg('marks'), 1) : null,
                'pass_rate' => $graded > 0 ? round($passed / $graded * 100, 1) : null,
                'failed' => $graded - $passed,
            ];
        });

        // All faculties together
        $allGraded = $latest()->count();
        $allPassed = $latest()->where('marks', '>=', Grade::PASS_MARK)->count();

        $overall = [
            'departments' => Department::count(),
            'classes' => ClassGroup::count(),
            'students' => User::where('role', 'student')->count(),
            'teachers' => User::where('role', 'teacher')->count(),
            'graded' => $allGraded,
            'average' => $allGraded > 0 ? round($latest()->avg('marks'), 1) : null,
            'pass_rate' => $allGraded > 0 ? round($allPassed / $allGraded * 100, 1) : null,
            'failed' => $allGraded - $allPassed,
        ];

        return view('admin.reports.index', compact('summary', 'failing', 'topStudents', 'byFaculty', 'overall')
            + $this->facultyOptions($request));
    }
}