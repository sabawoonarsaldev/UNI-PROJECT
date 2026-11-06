<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::with(['student', 'supervisor', 'department'])
            ->withCount('files')
            ->search($request->query('q'))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->latest('updated_at')
            ->paginate(10)
            ->withQueryString();

        $departments = Department::orderBy('name')->get();

        return view('admin.projects.index', compact('projects', 'departments'));
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