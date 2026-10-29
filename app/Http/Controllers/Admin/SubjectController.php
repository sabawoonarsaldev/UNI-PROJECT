<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::with(['department', 'semester', 'teacher'])
            ->when($request->department_id, fn ($query, $id) => $query->where('department_id', $id))
            ->orderBy('department_id')
            ->orderBy('semester_id')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('admin.subjects.index', compact('subjects', 'departments'));
    }

    public function create(Request $request)
    {
        return view('admin.subjects.create', $this->formData() + [
            'selectedSemester' => $request->query('semester_id'),
        ]);
    }

    public function store(Request $request)
    {
        $subject = Subject::create($this->validated($request));

        // Every student of the class is enrolled in the new subject automatically
    $studentIds = User::where('role', 'student')
        ->where('academic_status', 'active')
        ->where('class_group_id', $subject->semester->class_group_id)
        ->pluck('id');

        foreach ($studentIds as $studentId) {
            Enrollment::firstOrCreate([
                'student_id' => $studentId,
                'subject_id' => $subject->id,
                'semester_id' => $subject->semester_id,
            ]);
        }

        return redirect()->route('admin.subjects.index')
            ->with('success', "Subject created and {$studentIds->count()} student(s) enrolled.");
    }

    public function edit(Subject $subject)
    {
        return view('admin.subjects.edit', $this->formData() + ['subject' => $subject]);
    }

    public function update(Request $request, Subject $subject)
    {
        $subject->update($this->validated($request, $subject));

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject updated successfully.');
    }

    public function destroy(Subject $subject)
    {
        $subject->delete();

        return redirect()->route('admin.subjects.index')
            ->with('success', 'Subject deleted successfully.');
    }

    private function formData(): array
    {
        return [
            // Only the current (active) semester of each class can receive new subjects
            'semesters' => Semester::with(['classGroup.department', 'classGroup.entryTerm'])
                ->where('is_active', true)
                ->whereNotNull('class_group_id')
                ->get()
                ->sortBy(fn ($semester) => $semester->classGroup->name)
                ->values(),
            'teachers' => User::with('department')->where('role', 'teacher')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?Subject $subject = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($subject?->id)],
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
            'credit_hours' => 'required|integer|min:1|max:10',
        ];

        // The semester is chosen only when the subject is created
        if (! $subject) {
            $rules['semester_id'] = [
                'required',
                Rule::exists('semesters', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->whereNotNull('class_group_id')
                ),
            ];
        }

        $data = $request->validate($rules);

        if (! $subject) {
            $data['department_id'] = Semester::findOrFail($data['semester_id'])->department_id;
        }

        return $data;
    }
}