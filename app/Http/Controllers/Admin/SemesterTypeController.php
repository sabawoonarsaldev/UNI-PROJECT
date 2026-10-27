<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SemesterType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\ClassGroup;
use App\Models\Semester;

class SemesterTypeController extends Controller
{
    public function index()
    {
        $types = SemesterType::withCount('semesters')->orderBy('sort_order')->get();

        return view('admin.semester-types.index', compact('types'));
    }

    public function store(Request $request)
    {
        SemesterType::create($this->validated($request));

        return redirect()->route('admin.semester-types.index')
            ->with('success', 'Term added successfully.');
    }

    public function update(Request $request, SemesterType $semesterType)
    {
        $semesterType->update($this->validated($request, $semesterType));

        // Keep the names of the existing semesters in sync with the new term name
        $semesterType->semesters()->get()->each(
            fn ($semester) => $semester->update([
                'name' => Semester::buildName($semester->number, $semesterType->name, $semester->year),
            ])
        );

        return redirect()->route('admin.semester-types.index')
            ->with('success', 'Term updated successfully.');
    }

    public function destroy(SemesterType $semesterType)
    {
            if ($semesterType->semesters()->exists() || ClassGroup::where('semester_type_id', $semesterType->id)->exists()) {            return redirect()->route('admin.semester-types.index')
                ->with('error', 'This term is used by existing semesters and cannot be deleted.');
        }

        $semesterType->delete();

        return redirect()->route('admin.semester-types.index')
            ->with('success', 'Term deleted successfully.');
    }

    private function validated(Request $request, ?SemesterType $type = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('semester_types', 'name')->ignore($type?->id)],
            'sort_order' => 'required|integer|min:1|max:10',
        ]);
    }
}