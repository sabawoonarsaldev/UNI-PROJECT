<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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
use App\Models\Enrollment;
use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;


class ClassGroupController extends Controller
{
    use HasFacultyFilter;


    public function index(Request $request)
{
    $facultyId = $request->query('faculty_id');
    $departmentId = $request->query('department_id');

    // The classes of the selected faculty, department and year
    $scope = function ($query) use ($facultyId, $departmentId) {
        return $query
            ->when($facultyId, function ($q, $id) {
                $q->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
            })
            ->when($departmentId, fn ($q, $id) => $q->where('department_id', $id));
    };

    $classes = $scope(ClassGroup::with(['department.faculty', 'entryTerm'])->withCount('students'))
        // The classes that started in this year, even if they are in a higher semester now
        ->when($request->query('entry_year'), fn ($query, $year) => $query->where('entry_year', $year))
        ->orderBy('department_id')
        ->orderByDesc('entry_year')
        ->get();

    // The years in which a class started in the selected faculty and department
    $years = $scope(ClassGroup::query())->distinct()->orderByDesc('entry_year')->pluck('entry_year');

    return view('admin.classes.index', ['classes' => $classes, 'years' => $years] + $this->facultyOptions($request));
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

        $students = $classGroup->students()
        ->withCount([
            'grades',
            'enrollments as pending_retakes_count' => fn ($query) => $query->where('chance', '>', 1)->doesntHave('grade'),
        ])
        ->orderBy('name')
        ->get();

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

    if ($classGroup->students()->where('academic_status', 'active')->count() === 0) {
        return $back->with('error', 'There are no active students in this class.');
    }

    $subjectIds = $semester->subjects()->pluck('id');

    if ($subjectIds->isEmpty()) {
        return $back->with('error', 'Add subjects to this semester first.');
    }

    // Every active student must have a grade in every subject of this semester
    $missing = Enrollment::whereIn('subject_id', $subjectIds)
        ->whereHas('student', fn ($query) => $query->where('academic_status', 'active'))
        ->whereDoesntHave('grade')
        ->count();

if ($missing > 0) {
    return $back->with('error', "{$missing} grade(s) are still missing. All grades must be entered before the semester can be completed.");
}

// The results must be official before the semester is closed
$unpublished = Grade::whereIn('subject_id', $subjectIds)->where('status', '!=', 'published')->count();

if ($unpublished > 0) {
    return $back->with('error', "{$unpublished} grade(s) are not published yet. Publish them first (Publish all grades, or Academic > Grades).");
}

    [$next, $summary] = DB::transaction(function () use ($classGroup) {
        $next = $classGroup->advance();

        return [$next, $classGroup->evaluate()];
    });

    $message = $next
        ? "{$semester->name} completed. The class moved to {$next->name}. Now add the subjects of the new semester."
        : "{$semester->name} completed. The class has finished all " . ClassGroup::TOTAL_SEMESTERS . ' semesters.';

    return $back->with('success', $message . $this->summaryMessage($summary));
}

// Publishes the grades of all subjects of the current semester at once
public function publishGrades(ClassGroup $classGroup)
{
    $semester = $classGroup->currentSemester();

    $count = Grade::whereIn('subject_id', $semester->subjects()->pluck('id'))
        ->where('status', '!=', 'published')
        ->update(['status' => 'published', 'published_at' => now(), 'published_by' => auth()->id()]);

    if ($count > 0) {
        AuditLog::record(
            'grade.published',
            "{$count} grade(s) of {$semester->name} ({$classGroup->name}) were published to the students.",
            $classGroup
        );
    }

    return redirect()->route('admin.classes.show', $classGroup)->with(
        $count > 0 ? 'success' : 'error',
        $count > 0 ? "{$count} grade(s) were published." : 'There are no unpublished grades in this semester.'
    );
}


// Used after the marks of retakes were entered
public function processResults(ClassGroup $classGroup)
{
    $summary = $classGroup->evaluate();
    $text = $this->summaryMessage($summary);

    return redirect()->route('admin.classes.show', $classGroup)
        ->with('success', $text !== '' ? 'Results processed.' . $text : 'Results processed. Nothing new to update.');
}

private function summaryMessage(array $summary): string
{
    $parts = [];

    if ($summary['retakes'] > 0) {
        $parts[] = "{$summary['retakes']} failed subject(s) moved to the next chance.";
    }
    if ($summary['failed'] > 0) {
        $parts[] = "{$summary['failed']} student(s) failed completely after the last chance.";
    }
    if ($summary['graduated'] > 0) {
        $parts[] = "{$summary['graduated']} student(s) graduated.";
    }

    return $parts ? ' ' . implode(' ', $parts) : '';
}


public function results(Request $request, ClassGroup $classGroup)
{
    $classGroup->load(['department', 'entryTerm', 'semesters']);

    $students = $classGroup->students()->with('grades.subject')->orderBy('name')->get();

    $rows = $students->map(function ($student) use ($classGroup) {
        $perSemester = [];

        foreach ($classGroup->semesters as $semester) {
            $perSemester[$semester->id] = GpaCalculator::gpa($student->grades->where('semester_id', $semester->id));
        }

        return [
            'student' => $student,
            'semesters' => $perSemester,
            'cgpa' => GpaCalculator::gpa($student->grades),
            'average' => GpaCalculator::average($student->grades),
            // Only the latest chance of each subject is counted
            'failed' => GpaCalculator::latest($student->grades)
                ->filter(fn ($grade) => $grade->marks < Grade::PASS_MARK)
                ->count(),
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
            ->map(fn ($grades) => $grades->sortBy('chance')->keyBy('subject_id'))
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