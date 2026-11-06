<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;

class ProjectPolicy
{
    // The student, the supervisor and the admin can open the workspace of a project
    public function view(User $user, Project $project): bool
    {
        return $user->role === 'admin'
            || $project->student_id === $user->id
            || $project->supervisor_id === $user->id;
    }

    // The student changes details and uploads documents only in draft and revision
    public function update(User $user, Project $project): bool
    {
        return $project->student_id === $user->id && $project->isEditable();
    }

    public function uploadDocument(User $user, Project $project): bool
    {
        return $this->update($user, $project);
    }

    public function submit(User $user, Project $project): bool
    {
        return $this->update($user, $project) && $project->supervisor_id !== null;
    }

    public function uploadFeedback(User $user, Project $project): bool
    {
        return $project->supervisor_id === $user->id && $project->status !== 'approved';
    }

    public function comment(User $user, Project $project): bool
    {
        return $this->view($user, $project) && $project->status !== 'approved';
    }

    // First level: the supervisor
    public function supervisorReview(User $user, Project $project): bool
    {
        return $project->supervisor_id === $user->id && $project->status === 'submitted';
    }

    // Second level: the admin
    public function finalApprove(User $user, Project $project): bool
    {
        return $user->role === 'admin' && $project->status === 'supervisor_approved';
    }

    public function returnToStudent(User $user, Project $project): bool
    {
        return $user->role === 'admin' && in_array($project->status, ['submitted', 'supervisor_approved']);
    }

    // The repository shows only approved projects
    public function viewRepository(User $user, Project $project): bool
    {
        return $project->status === 'approved';
    }

    // The people of the project can download every file. All the others only the document of an approved project.
    public function download(User $user, Project $project, ProjectFile $file): bool
    {
        if ($file->project_id !== $project->id) {
            return false;
        }

        if ($this->view($user, $project)) {
            return true;
        }

        return $project->status === 'approved' && $file->id === $project->repositoryDocument()?->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->role === 'admin';
    }
}