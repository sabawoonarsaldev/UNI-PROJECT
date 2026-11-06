<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupervisionController extends Controller
{
    public function index(Request $request)
    {
        $students = User::where('role', 'student')
            ->with(['department', 'supervisor', 'project', 'classGroup.department', 'classGroup.entryTerm'])
            ->when($request->query('department_id'), fn ($query, $id) => $query->where('department_id', $id))
            ->when($request->query('filter') === 'unassigned', fn ($query) => $query->whereNull('supervisor_id'))
            ->when($request->query('q'), function ($query, $term) {
                $query->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('roll_number', 'like', "%{$term}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        // Only teachers who can log in can answer their students
        $teachers = User::withAccount()
            ->where('role', 'teacher')
            ->with('department')
            ->withCount('supervisedStudents')
            ->orderBy('name')
            ->get();

        $departments = Department::orderBy('name')->get();

        return view('admin.supervision.index', compact('students', 'teachers', 'departments'));
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

        return back()->with('success', $supervisor
            ? "{$supervisor->name} is now the supervisor of {$student->name}."
            : "The supervisor of {$student->name} was removed.");
    }
}