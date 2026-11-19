<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Semester;
use Illuminate\Http\Request;

// Department -> class -> semester filters for the pages that list subjects
trait FiltersByClass
{
    protected function applyClassFilters($query, Request $request)
    {
        return $query
            ->when($request->query('department_id'), fn ($q, $id) => $q->where('department_id', $id))
            ->when($request->query('class_group_id'), function ($q, $id) {
                $q->whereHas('semester', fn ($semester) => $semester->where('class_group_id', $id));
            })
            ->when($request->query('semester_id'), fn ($q, $id) => $q->where('semester_id', $id));
    }

    // Subjects of one class stay together, ordered by semester number
    protected function orderByClass($query)
    {
        return $query
            ->orderBy('department_id')
            ->orderBy(Semester::select('class_group_id')->whereColumn('semesters.id', 'subjects.semester_id'))
            ->orderBy(Semester::select('number')->whereColumn('semesters.id', 'subjects.semester_id'))
            ->orderBy('code');
    }

    // The options of the three dropdowns. Every dropdown depends on the one before it.
    protected function classFilterOptions(Request $request): array
    {
        $departmentId = $request->query('department_id');
        $classId = $request->query('class_group_id');

        return [
            'departments' => Department::orderBy('name')->get(),
            'classes' => $departmentId
                ? ClassGroup::with(['department', 'entryTerm'])
                    ->where('department_id', $departmentId)
                    ->orderByDesc('entry_year')
                    ->get()
                : collect(),
            'semesters' => $classId
                ? Semester::where('class_group_id', $classId)->orderBy('number')->get()
                : collect(),
        ];
    }
}