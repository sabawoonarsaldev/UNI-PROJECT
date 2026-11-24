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
use App\Http\Controllers\Admin\Concerns\FiltersByClass;


class SubjectController extends Controller
{

use FiltersByClass;

   public function index(Request $request)
{
    $query = Subject::with([
        'semester.classGroup.department',
        'semester.classGroup.entryTerm',
        'teacher',
    ]);

    $subjects = $this->orderByClass($this->applyClassFilters($query, $request))
        ->paginate(10)
        ->withQueryString();

    return view('admin.subjects.index', ['subjects' => $subjects] + $this->classFilterOptions($request));
}


    // Subjects are created once in the curriculum and then chosen for a class from the class page
public function create()
{
    return redirect()->route('admin.curriculum.create')
        ->with('success', 'Subjects are created in the Curriculum. Choose them for a class from the class page.');
}

public function store()
{
    return redirect()->route('admin.curriculum.index');
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
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->where('semester_id', $subject?->semester_id)->ignore($subject?->id)],
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