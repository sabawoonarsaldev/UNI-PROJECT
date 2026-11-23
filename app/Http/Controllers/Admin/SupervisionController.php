<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\AuditLog;
use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;


class SupervisionController extends Controller
{

use HasFacultyFilter;
    

public function index(Request $request)
{
    $students = $this->limitUsersToFaculty(User::where('role', 'student'), $request)
        ->with(['department', 'supervisor.department.faculty', 'project', 'classGroup.department', 'classGroup.entryTerm'])
        ->when($request->query('filter') === 'unassigned', fn ($query) => $query->whereNull('supervisor_id'))
        ->when($request->query('q'), function ($query, $term) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('roll_number', 'like', "%{$term}%"));
        })
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

    // Only teachers who can log in can answer their students; they follow the selected faculty and department
    $teachers = $this->limitUsersToFaculty(User::withAccount()->where('role', 'teacher'), $request)
        ->with('department.faculty')
        ->withCount('supervisedStudents')
        ->orderBy('name')
        ->get();

    return view('admin.supervision.index', ['students' => $students, 'teachers' => $teachers] + $this->facultyOptions($request));
}

public function update(Request $request, User $student)
{
    abort_unless($student->role === 'student', 404);

    $data = $request->validate([
        'supervisor_id' => [
            'nullable',
            Rule::exists('users', 'id')->where(
                fn ($query) => $query->where('role', 'teacher')->whereNotNull('password')
            ),
        ],
    ]);

    $supervisorId = $data['supervisor_id'] ?? null;
    $supervisor = $supervisorId ? User::find($supervisorId) : null;

    // Read before the change, for the audit log
    $oldSupervisor = $student->supervisor?->name;

    DB::transaction(function () use ($student, $supervisorId, $supervisor) {
        $student->update(['supervisor_id' => $supervisorId]);

        // The project follows the new supervisor, unless it is already published
        $project = $student->project;

        if ($project && $project->status !== 'approved') {
            $project->update(['supervisor_id' => $supervisorId]);

            $project->comments()->create([
                'user_id' => auth()->id(),
                'type' => 'status',
                'status' => $project->status,
                'body' => $supervisor ? "Supervisor changed to {$supervisor->name}." : 'Supervisor removed.',
            ]);
        }
    });

    AuditLog::record(
        'supervisor.changed',
        "Supervisor of {$student->name} changed from " . ($oldSupervisor ?? 'nobody') . ' to ' . ($supervisor?->name ?? 'nobody') . '.',
        $student,
        ['supervisor' => $oldSupervisor],
        ['supervisor' => $supervisor?->name]
    );

    return back()->with('success', $supervisor
        ? "{$supervisor->name} is now the supervisor of {$student->name}."
        : "The supervisor of {$student->name} was removed.");
}

}