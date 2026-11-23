<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Keyword;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;

class RepositoryController extends Controller
{
    use HasFacultyFilter;

   
    public function index(Request $request)
{
    $projects = Project::approved()
        ->with(['student', 'supervisor', 'department', 'keywords'])
        ->withCount('downloads')
        ->search($request->query('q'))
        ->when($request->query('faculty_id'), function ($query, $id) {
            $query->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
        })
        ->when($request->query('department_id'), fn ($query, $id) => $query->where('department_id', $id))
        ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
        ->when($request->query('year'), fn ($query, $year) => $query->where('year', $year))
        ->when($request->query('supervisor_id'), fn ($query, $id) => $query->where('supervisor_id', $id))
        ->when($request->query('keyword'), function ($query, $name) {
            $query->whereHas('keywords', fn ($keyword) => $keyword->where('name', $name));
        });

    match ($request->query('sort')) {
        'title' => $projects->orderBy('title'),
        'downloads' => $projects->orderByDesc('downloads_count'),
        default => $projects->orderByDesc('approved_at'),
    };

    return view('repository.index', [
        'projects' => $projects->paginate(10)->withQueryString(),
        'years' => Project::approved()->distinct()->orderByDesc('year')->pluck('year'),
        'supervisors' => User::whereIn('id', Project::approved()->whereNotNull('supervisor_id')->select('supervisor_id'))
            ->orderBy('name')->get(['id', 'name']),
        'popularKeywords' => Keyword::withCount(['projects' => fn ($query) => $query->approved()])
            ->having('projects_count', '>', 0)
            ->orderByDesc('projects_count')
            ->take(12)
            ->get(),
    ] + $this->facultyOptions($request));
}

    public function show(Project $project)
    {
        Gate::authorize('viewRepository', $project);

        $project->load(['student', 'supervisor', 'department', 'keywords']);

        return view('repository.show', [
            'project' => $project,
            'document' => $project->repositoryDocument(),
            'downloadsTotal' => $project->downloads()->count(),
        ]);
    }
}