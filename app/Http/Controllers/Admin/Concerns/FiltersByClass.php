<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;

// Faculty -> department -> class -> semester filters for the pages that list subjects
trait FiltersByClass
{
    protected function applyClassFilters($query, Request $request)
    {
        return $query
            ->when($request->query('faculty_id'), function ($q, $id) {
                $q->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
            })
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

    // The options of the dropdowns. Every dropdown depends on the one before it.
    // $scope (optional) returns a fresh query of the allowed subjects, for example the subjects
    // of one teacher. Then the dropdowns offer only the options where those subjects exist.
    protected function classFilterOptions(Request $request, ?Closure $scope = null): array
    {
        $departmentId = $request->query('department_id');
        $classId = $request->query('class_group_id');

        $departments = Department::orderBy('name');
        if ($request->query('faculty_id')) {
            $departments->where('faculty_id', $request->query('faculty_id'));
        }
        if ($scope) {
            $departments->whereIn('id', $scope()->select('department_id'));
        }

        $classes = collect();
        if ($departmentId) {
            $classes = ClassGroup::with(['department', 'entryTerm'])
                ->where('department_id', $departmentId)
                ->orderByDesc('entry_year');

            if ($scope) {
                $classes->whereIn('id', Semester::whereIn('id', $scope()->select('semester_id'))->select('class_group_id'));
            }

            $classes = $classes->get();
        }

        $semesters = collect();
        if ($classId) {
            $semesters = Semester::where('class_group_id', $classId)->orderBy('number');

            if ($scope) {
                $semesters->whereIn('id', $scope()->select('semester_id'));
            }

            $semesters = $semesters->get();
        }

        return [
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => $departments->get(),
            'classes' => $classes,
            'semesters' => $semesters,
        ];
    }
}