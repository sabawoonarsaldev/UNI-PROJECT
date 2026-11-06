<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectCommentController extends Controller
{
    public function store(Request $request, Project $project)
    {
        Gate::authorize('comment', $project);

        $data = $request->validate(['body' => 'required|string|max:3000']);

        $project->comments()->create([
            'user_id' => $request->user()->id,
            'type' => 'comment',
            'body' => $data['body'],
        ]);

        return redirect()->to(route('projects.show', $project) . '#discussion');
    }
}