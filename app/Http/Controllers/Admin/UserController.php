<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassGroup;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('department')
            ->when($request->role, fn ($query, $role) => $query->where('role', $role))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'department_id' => ['nullable', 'required_if:role,teacher,student', 'exists:departments,id'],
            'roll_number' => ['nullable', 'required_if:role,student', 'unique:users,roll_number'],
            'class_group_id' => ['nullable', Rule::exists('class_groups', 'id')->where('status', 'active')],
        ]);

        // Only students have a roll number and a class, and admins belong to no department
        if ($validated['role'] !== 'student') {
            $validated['roll_number'] = null;
            $validated['class_group_id'] = null;
        }
        if ($validated['role'] === 'admin') {
            $validated['department_id'] = null;
        }

        $classGroup = null;
        if (! empty($validated['class_group_id'])) {
            $classGroup = ClassGroup::findOrFail($validated['class_group_id']);

            if ((int) $validated['department_id'] !== $classGroup->department_id) {
                throw ValidationException::withMessages([
                    'class_group_id' => 'The selected class belongs to another department.',
                ]);
            }
        }

        // The class is assigned below, so the student is also enrolled in its subjects
        unset($validated['class_group_id']);

        $user = new User($validated);
        // Accounts created by the admin are trusted, so the email is marked as verified
        $user->email_verified_at = now();
        $user->save();

        $classGroup?->addStudent($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load(['classGroup.department', 'classGroup.entryTerm']);

        return view('admin.users.edit', $this->formData() + ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'department_id' => ['nullable', 'required_if:role,teacher,student', 'exists:departments,id'],
            'roll_number' => ['nullable', 'required_if:role,student', Rule::unique('users', 'roll_number')->ignore($user->id)],
        ]);

        if ($validated['role'] !== 'student') {
            $validated['roll_number'] = null;
        }
        if ($validated['role'] === 'admin') {
            $validated['department_id'] = null;
        }

        // A student of a class keeps the department of that class
        if ($user->class_group_id) {
            if ($validated['role'] !== 'student') {
                throw ValidationException::withMessages([
                    'role' => 'This user belongs to a class. Remove the student from the class first.',
                ]);
            }

            $validated['department_id'] = $user->classGroup->department_id;
        }

        // Keep the old password when the field is left empty
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // An admin must not change his own role (to avoid locking himself out)
        if ($user->id === auth()->id()) {
            $validated['role'] = $user->role;
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function formData(): array
    {
        return [
            'departments' => Department::orderBy('name')->get(),
            'classes' => ClassGroup::with(['department', 'entryTerm'])
                ->where('status', 'active')
                ->get()
                ->sortBy('name')
                ->values(),
        ];
    }
}