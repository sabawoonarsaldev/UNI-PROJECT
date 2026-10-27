<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Semester;
use App\Models\SemesterType;
use App\Support\HijriShamsi;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SemesterController extends Controller
{
    public function index()
    {
        $semesters = Semester::with(['department', 'type'])
            ->withCount('subjects')
            ->orderBy('department_id')
            ->chronological('desc')
            ->get();

        return view('admin.semesters.index', compact('semesters'));
    }

    public function create()
    {
        return view('admin.semesters.create', $this->formData());
    }

    public function store(Request $request)
    {
        Semester::create($this->validated($request));

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semester created successfully.');
    }

    public function edit(Semester $semester)
    {
        return view('admin.semesters.edit', $this->formData() + ['semester' => $semester]);
    }

    public function update(Request $request, Semester $semester)
    {
        $semester->update($this->validated($request, $semester));

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semester updated successfully.');
    }

    public function destroy(Semester $semester)
    {
        $semester->delete();

        return redirect()->route('admin.semesters.index')
            ->with('success', 'Semester deleted successfully.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'types' => SemesterType::orderBy('sort_order')->get(),
            'years' => HijriShamsi::years(),
            'currentYear' => HijriShamsi::currentYear(),
        ];
    }

    private function validated(Request $request, ?Semester $semester = null): array
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'semester_type_id' => 'required|exists:semester_types,id',
            'year' => ['required', 'integer', Rule::in(HijriShamsi::years())],
        ]);

        $exists = Semester::where('department_id', $data['department_id'])
            ->where('semester_type_id', $data['semester_type_id'])
            ->where('year', $data['year'])
            ->when($semester, fn ($query) => $query->where('id', '!=', $semester->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'year' => 'This semester already exists for the selected department.',
            ]);
        }

        // The name is always built from the term and the year
        $data['name'] = SemesterType::findOrFail($data['semester_type_id'])->name . ' ' . $data['year'];
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}