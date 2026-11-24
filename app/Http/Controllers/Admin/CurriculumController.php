<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;
use App\Http\Controllers\Controller;
use App\Models\CurriculumSubject;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    use HasFacultyFilter;

    // Faculty -> department -> semester number
    public function index(Request $request)
    {
        $items = CurriculumSubject::with(['department.faculty', 'teacher'])
            ->withCount('offerings')
            ->when($request->query('faculty_id'), function ($query, $id) {
                $query->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
            })
            ->when($request->query('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->when($request->query('semester_number'), fn ($query, $number) => $query->where('semester_number', $number))
            ->orderBy('department_id')
            ->orderBy('semester_number')
            ->orderBy('code')
            ->get();

        return view('admin.curriculum.index', [
            'groups' => $items->groupBy(fn ($item) => $item->department_id . '-' . $item->semester_number),
            'total' => $items->count(),
        ] + $this->facultyOptions($request));
    }

    public function create(Request $request)
    {
        return view('admin.curriculum.create', $this->formData() + [
            'prefill' => $request->only(['department_id', 'semester_number']),
        ]);
    }

    public function store(Request $request)
    {
        $item = CurriculumSubject::create($this->validated($request));

        return $this->backToList($item)->with('success', 'Subject added to the curriculum.');
    }

    public function edit(CurriculumSubject $curriculum)
    {
        return view('admin.curriculum.edit', $this->formData() + ['subject' => $curriculum]);
    }

    public function update(Request $request, CurriculumSubject $curriculum)
    {
        $curriculum->update($this->validated($request, $curriculum));

        return $this->backToList($curriculum)->with(
            'success',
            'Curriculum subject updated. Classes that already chose it keep their own copy.'
        );
    }

    public function destroy(CurriculumSubject $curriculum)
    {
        $curriculum->delete();

        return redirect()->route('admin.curriculum.index')
            ->with('success', 'Subject removed from the curriculum. Classes that already chose it keep their copy.');
    }

    private function backToList(CurriculumSubject $item)
    {
        $item->loadMissing('department');

        return redirect()->route('admin.curriculum.index', [
            'faculty_id' => $item->department->faculty_id,
            'department_id' => $item->department_id,
            'semester_number' => $item->semester_number,
        ]);
    }

    private function formData(): array
    {
        return [
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'faculty_id']),
            'teachers' => User::where('role', 'teacher')->with('department')->orderBy('name')->get(),
        ];
    }

    private function validated(Request $request, ?CurriculumSubject $item = null): array
    {
        return $request->validate([
            'department_id' => 'required|exists:departments,id',
            'semester_number' => ['required', 'integer', 'min:1', 'max:' . CurriculumSubject::MAX_SEMESTERS],
            'name' => 'required|string|max:255',
            // The code is unique inside one department
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('curriculum_subjects', 'code')
                    ->where('department_id', $request->input('department_id'))
                    ->ignore($item?->id),
            ],
            'credit_hours' => 'required|integer|min:1|max:10',
            'teacher_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'teacher')],
        ]);
    }
}