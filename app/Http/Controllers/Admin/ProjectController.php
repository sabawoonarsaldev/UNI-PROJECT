<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Models\ClassGroup;
use App\Models\User;

class ProjectController extends Controller
{
    public function index(Request $request)
{
    $departmentId = $request->query('department_id');
    $classId = $request->query('class_group_id');

    $projects = Project::with(['student.classGroup.department', 'student.classGroup.entryTerm', 'supervisor', 'department'])
        ->withCount('files')
        ->search($request->query('q'))
        ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
        ->when($departmentId, fn ($query, $id) => $query->where('department_id', $id))
        // The projects of the students of one class
        ->when($classId, function ($query, $id) {
            $query->whereHas('student', fn ($student) => $student->where('class_group_id', $id));
        })
        ->when($request->query('student_id'), fn ($query, $id) => $query->where('student_id', $id))
        ->latest('updated_at')
        ->paginate(10)
        ->withQueryString();

    // Every dropdown depends on the one before it
    $classes = $departmentId
        ? ClassGroup::with(['department', 'entryTerm'])->where('department_id', $departmentId)->orderByDesc('entry_year')->get()
        : collect();

    $students = $classId
        ? User::where('role', 'student')->where('class_group_id', $classId)->orderBy('name')->get(['id', 'name', 'roll_number'])
        : collect();

    $departments = Department::orderBy('name')->get();

    return view('admin.projects.index', compact('projects', 'departments', 'classes', 'students'));
}

    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        Storage::deleteDirectory("projects/{$project->id}");
        $project->delete();

        return redirect()->route('admin.projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}