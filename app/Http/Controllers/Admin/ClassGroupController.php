<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Grade;
use App\Models\Semester;
use App\Models\SemesterType;
use App\Models\User;
use App\Services\GpaCalculator;
use App\Support\HijriShamsi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClassGroupController extends Controller
{
    public function index(Request $request)
    {
        $classes = ClassGroup::with(['department.faculty', 'entryTerm'])
            ->withCount('students')
            ->when($request->department_id, fn ($query, $id) => $query->where('department_id', $id))
            ->orderBy('department_id')
            ->orderByDesc('entry_year')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('admin.classes.index', compact('classes', 'departments'));
    }

    public function create()
    {
        return view('admin.classes.create', [
            'departments' => Department::with('faculty')->orderBy('name')->get(),
            'types' => SemesterType::orderBy('sort_order')->get(),
            'years' => HijriShamsi::years(),
            'currentYear' => HijriShamsi::currentYear(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'semester_type_id' => 'required|exists:semester_types,id',
            'entry_year' => ['required', 'integer', Rule::in(HijriShamsi::years())],
        ]);

        if (ClassGroup::where($data)->exists()) {
            throw ValidationException::withMessages([
                'entry_year' => 'This class already exists for the selected department.',
            ]);
        }

        $term = SemesterType::findOrFail($data['semester_type_id']);

        // The class and its first semester are created together
        $classGroup = DB::transaction(function () use ($data, $term) {
            $classGroup = ClassGroup::create($data + ['current_semester_number' => 1, 'status' => 'active']);

            Semester::create([
                'department_id' => $classGroup->department_id,
                'class_group_id' => $classGroup->id,
                'semester_type_id' => $term->id,
                'year' => $data['entry_year'],
                'number' => 1,
                'name' => Semester::buildName(1, $term->name, $data['entry_year']),
                'is_active' => true,
            ]);

            return $classGroup;
        });

        return redirect()->route('admin.classes.show', $classGroup)
            ->with('success', 'Class created with its first semester. Now add students and subjects.');
    }

    public function show(ClassGroup $classGroup)
    {
        $classGroup->load(['department.faculty', 'entryTerm', 'semesters.type']);

        $currentSemester = $classGroup->currentSemester();

        $subjects = $currentSemester
            ? $currentSemester->subjects()->with('teacher')->withCount(['enrollments', 'grades'])->orderBy('code')->get()
            : collect();

        $students = $classGroup->students()->withCount('grades')->orderBy('name')->get();

        // Students of the same department who are not in any class yet
        $available = $classGroup->status === 'active'
            ? User::where('role', 'student')
                ->where('department_id', $classGroup->department_id)
                ->whereNull('class_group_id')
                ->orderBy('name')
                ->get()
            : collect();

        return view('admin.classes.show', compact('classGroup', 'currentSemester', 'subjects', 'students', 'available'));
    }

    public function addStudents(Request $request, ClassGroup $classGroup)
    {
        if ($classGroup->status !== 'active') {
            return back()->with('error', 'This class has completed all semesters.');
        }

        $data = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer',
        ]);

        $students = User::where('role', 'student')
            ->where('department_id', $classGroup->department_id)
            ->whereNull('class_group_id')
            ->whereIn('id', $data['student_ids'])
            ->get();

        foreach ($students as $student) {
            $classGroup->addStudent($student);
        }

        return redirect()->route('admin.classes.show', $classGroup)
            ->with('success', $students->count() . ' student(s) added to the class.');
    }

    public function removeStudent(ClassGroup $classGroup, User $student)
    {
        abort_unless($student->class_group_id === $classGroup->id, 404);

        if ($student->grades()->exists()) {
            return back()->with('error', 'This student already has grades and cannot be removed from the class.');
        }

        $classGroup->removeStudent($student);

        return redirect()->route('admin.classes.show', $classGroup)
            ->with('success', 'Student removed from the class.');
    }

    public function completeSemester(ClassGroup $classGroup)
    {
        $back = redirect()->route('admin.classes.show', $classGroup);

        if ($classGroup->status !== 'active') {
            return $back->with('error', 'This class has already completed all semesters.');
        }

        $semester = $classGroup->currentSemester();

        if ($classGroup->students()->count() === 0) {
            return $back->with('error', 'Add students to the class first.');
        }

        $subjects = $semester->subjects()->withCount(['enrollments', 'grades'])->get();

        if ($subjects->isEmpty()) {
            return $back->with('error', 'Add subjects to this semester first.');
        }

        $missing = $subjects->sum(fn ($subject) => max(0, $subject->enrollments_count - $subject->grades_count));

        if ($missing > 0) {
            return $back->with('error', "{$missing} grade(s) are still missing. All grades must be entered before the semester can be completed.");
        }

        $failedStudents = Grade::where('semester_id', $semester->id)
            ->where('marks', '<', Grade::PASS_MARK)
            ->distinct()
            ->count('student_id');

        $next = DB::transaction(fn () => $classGroup->advance());

        $message = $next
            ? "{$semester->name} completed. The class moved to {$next->name}. Now add the subjects of the new semester."
            : "{$semester->name} completed. The class has finished all " . ClassGroup::TOTAL_SEMESTERS . ' semesters.';

        if ($failedStudents > 0) {
            $message .= " {$failedStudents} student(s) have failed subjects in the completed semester.";
        }

        return $back->with('success', $message);
    }

    public function results(Request $request, ClassGroup $classGroup)
    {
        $classGroup->load(['department', 'entryTerm', 'semesters']);

        $students = $classGroup->students()->with('grades.subject')->orderBy('name')->get();

        $failedIn = fn ($grades) => $grades->filter(fn ($grade) => $grade->marks < Grade::PASS_MARK)->count();

        $rows = $students->map(function ($student) use ($classGroup, $failedIn) {
            $perSemester = [];

            foreach ($classGroup->semesters as $semester) {
                $perSemester[$semester->id] = GpaCalculator::gpa($student->grades->where('semester_id', $semester->id));
            }

            return [
                'student' => $student,
                'semesters' => $perSemester,
                'cgpa' => GpaCalculator::gpa($student->grades),
                'average' => GpaCalculator::average($student->grades),
                'failed' => $failedIn($student->grades),
            ];
        });

        // Subject by subject marks of one semester
        $selected = $classGroup->semesters->firstWhere('id', (int) $request->query('semester'))
            ?? $classGroup->currentSemester()
            ?? $classGroup->semesters->first();

        $detailSubjects = $selected ? $selected->subjects()->orderBy('code')->get() : collect();

        $detailGrades = $selected
            ? Grade::where('semester_id', $selected->id)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->groupBy('student_id')
                ->map(fn ($grades) => $grades->keyBy('subject_id'))
            : collect();

        return view('admin.classes.results', compact(
            'classGroup', 'rows', 'selected', 'detailSubjects', 'detailGrades'
        ));
    }

    public function destroy(ClassGroup $classGroup)
    {
        $hasGrades = Grade::whereIn('semester_id', $classGroup->semesters()->pluck('id'))->exists();

        if ($hasGrades) {
            return redirect()->route('admin.classes.index')
                ->with('error', 'This class already has grades and cannot be deleted.');
        }

        $classGroup->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class deleted successfully.');
    }
}