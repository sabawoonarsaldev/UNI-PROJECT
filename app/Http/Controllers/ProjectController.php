<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\HijriShamsi;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    // Student: opens his project, or the form to create it
    public function mine()
    {
        $student = auth()->user();

        if ($student->project) {
            return redirect()->route('projects.show', $student->project);
        }

        return view('projects.create', [
            'supervisor' => $student->supervisor,
            'years' => HijriShamsi::years(),
            'currentYear' => HijriShamsi::currentYear(),
        ]);
    }

    public function store(Request $request)
    {
        $student = auth()->user();

        if ($student->project) {
            return redirect()->route('projects.show', $student->project);
        }

        if (! $student->supervisor_id) {
            return back()->withInput()
                ->with('error', 'No supervisor is assigned to you yet. Please contact the administration.');
        }

        $data = $this->validated($request);

        $project = Project::create(Arr::except($data, 'keywords') + [
            'student_id' => $student->id,
            'supervisor_id' => $student->supervisor_id,
            'department_id' => $student->department_id,
            'status' => 'draft',
        ]);

        $project->syncKeywords($data['keywords'] ?? null);
        $project->comments()->create([
            'user_id' => $student->id,
            'type' => 'status',
            'status' => 'draft',
            'body' => 'Project created.',
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created. Now upload your document and submit it to your supervisor.');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load([
            'student.department',
            'supervisor',
            'department',
            'keywords',
            'files' => fn ($query) => $query->latest('id'),
            'files.uploader',
            'comments' => fn ($query) => $query->orderBy('id'),
            'comments.user',
        ]);

        // The admin also sees the download records
        $downloads = auth()->user()->role === 'admin'
            ? $project->downloads()->with(['user', 'file'])->latest('id')->take(10)->get()
            : collect();

        return view('projects.show', [
            'project' => $project,
            'downloads' => $downloads,
            'downloadsTotal' => $project->downloads()->count(),
            'years' => HijriShamsi::years(),
            'currentYear' => HijriShamsi::currentYear(),
        ]);
    }

    public function update(Request $request, Project $project)
    {
        Gate::authorize('update', $project);

        $data = $this->validated($request);

        $project->update(Arr::except($data, 'keywords'));
        $project->syncKeywords($data['keywords'] ?? null);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project details updated.');
    }

    // Student -> supervisor
    public function submit(Project $project)
    {
        Gate::authorize('submit', $project);

        if (! $project->files()->where('kind', 'document')->exists()) {
            return back()->with('error', 'Upload your project document before submitting it.');
        }

        $project->transition('submitted', 'Submitted to the supervisor for review.');

        return back()->with('success', 'Project submitted to your supervisor.');
    }

    // Supervisor: approves, the admin gets the project next
    public function supervisorApprove(Request $request, Project $project)
    {
        Gate::authorize('supervisorReview', $project);

        $note = $request->validate(['note' => 'nullable|string|max:2000'])['note'] ?? null;

        $project->transition('supervisor_approved', $note ?: 'Approved by the supervisor.');

        return back()->with('success', 'Project approved and sent to the administration.');
    }

    // Supervisor: asks the student for changes
    public function requestRevision(Request $request, Project $project)
    {
        Gate::authorize('supervisorReview', $project);

        $note = $request->validate(['note' => 'required|string|max:2000'])['note'];

        $project->transition('revision', $note);

        return back()->with('success', 'The student was asked to revise the project.');
    }

    // Admin: publishes the project in the repository
    public function finalApprove(Request $request, Project $project)
    {
        Gate::authorize('finalApprove', $project);

        $note = $request->validate(['note' => 'nullable|string|max:2000'])['note'] ?? null;

        $project->transition('approved', $note ?: 'Approved and published in the repository.');

        return back()->with('success', 'Project approved and published in the repository.');
    }

    // Admin: sends the project back to the student
    public function returnToStudent(Request $request, Project $project)
    {
        Gate::authorize('returnToStudent', $project);

        $note = $request->validate(['note' => 'required|string|max:2000'])['note'];

        $project->transition('revision', $note);

        return back()->with('success', 'The project was returned to the student.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'type' => ['required', Rule::in(array_keys(Project::TYPES))],
            'year' => ['required', 'integer', Rule::in(HijriShamsi::years())],
            'abstract' => 'required|string|min:20|max:5000',
            'keywords' => 'nullable|string|max:500',
        ]);
    }
}