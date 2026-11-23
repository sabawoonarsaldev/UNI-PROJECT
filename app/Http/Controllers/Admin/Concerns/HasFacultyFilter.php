<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;

// The faculty -> department dropdowns of the pages that are not about classes
trait HasFacultyFilter
{
    // The faculties, and the departments of the selected faculty (all departments when no faculty is selected)
    protected function facultyOptions(Request $request): array
    {
        $departments = Department::orderBy('name');

        if ($request->query('faculty_id')) {
            $departments->where('faculty_id', $request->query('faculty_id'));
        }

        return [
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => $departments->get(),
        ];
    }

    // Limits a query of users (students or teachers) to the selected faculty and department
    protected function limitUsersToFaculty($query, Request $request)
    {
        return $query
            ->when($request->query('faculty_id'), function ($q, $id) {
                $q->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
            })
            ->when($request->query('department_id'), fn ($q, $id) => $q->where('department_id', $id));
    }
}