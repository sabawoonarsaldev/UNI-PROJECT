<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDownload;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProjectFileController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $user = $request->user();

        // The student uploads versions of the document, the supervisor uploads feedback files
        $kind = $project->student_id === $user->id ? 'document' : 'feedback';

        Gate::authorize($kind === 'document' ? 'uploadDocument' : 'uploadFeedback', $project);

        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $upload = $request->file('file');
        $version = (int) $project->files()->where('kind', $kind)->max('version') + 1;

        // Stored on the private disk: only the download route can give the file out
        $path = $upload->store("projects/{$project->id}");

        $project->files()->create([
            'uploaded_by' => $user->id,
            'kind' => $kind,
            'version' => $version,
            'original_name' => $upload->getClientOriginalName(),
            'path' => $path,
            'size' => $upload->getSize(),
            'note' => $request->input('note'),
        ]);

        return back()->with('success', 'File uploaded successfully.');
    }

    public function download(ProjectFile $file)
    {
        $project = $file->project;

        Gate::authorize('download', [$project, $file]);

        abort_unless(Storage::exists($file->path), 404);

        // Downloads of published projects are recorded
        if ($project->status === 'approved') {
            ProjectDownload::create([
                'project_id' => $project->id,
                'project_file_id' => $file->id,
                'user_id' => auth()->id(),
            ]);
        }

        return Storage::download($file->path, $file->original_name);
    }
}