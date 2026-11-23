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
use App\Models\Faculty;
use App\Models\AuditLog;
use App\Services\ProfileData;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{
public function index(Request $request)
{
    $users = User::with('department.faculty')
        ->when($request->query('role'), fn ($query, $role) => $query->where('role', $role))
        ->when($request->query('faculty_id'), function ($query, $id) {
            $query->whereHas('department', fn ($department) => $department->where('faculty_id', $id));
        })
        ->when($request->query('department_id'), fn ($query, $id) => $query->where('department_id', $id))
        ->search($request->query('q'))
        ->latest()
        ->paginate(10)
        ->withQueryString();

    return view('admin.users.index', [
        'users' => $users,
        'faculties' => Faculty::orderBy('name')->get(),
        'departments' => Department::orderBy('name')->get(['id', 'name', 'faculty_id']),
    ]);
}

    public function create()
    {
        return view('admin.users.create', $this->formData());
    }




    public function edit(User $user)
    {
        $user->load(['classGroup.department', 'classGroup.entryTerm']);

        return view('admin.users.edit', $this->formData() + ['user' => $user]);
    }


// The profile page of a user (the admin sees everything and can edit)
public function show(User $user)
{
    return view('admin.users.show', ProfileData::for($user));
}

public function store(Request $request)
{
    // An admin always needs a login account; for the others it is optional
    $wantsAccount = $request->boolean('create_account') || $request->input('role') === 'admin';

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|max:255|unique:users,email',
        // The password is checked only when a login account is requested
        'password' => $wantsAccount ? ['required', 'confirmed', Password::defaults()] : ['nullable'],
        'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
        'department_id' => ['nullable', 'required_if:role,teacher,student', 'exists:departments,id'],
        'roll_number' => ['nullable', 'required_if:role,student', 'unique:users,roll_number'],
        'class_group_id' => ['nullable', Rule::exists('class_groups', 'id')->where('status', 'active')],
    ] + $this->profileRules());

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

    // Without an account the password stays empty and the person cannot log in
    if (! $wantsAccount) {
        unset($validated['password']);
    }

    // The photo is stored and the fields that belong only to teachers are cleaned
    $validated = $this->prepareProfile($request, $validated);

    $user = new User($validated);

    if ($wantsAccount) {
        // Accounts created by the admin are trusted, so the email is marked as verified
        $user->email_verified_at = now();
    }

    $user->save();

    $classGroup?->addStudent($user);

    AuditLog::record(
        'user.created',
        "User {$user->name} ({$user->role}) was created" . ($wantsAccount ? ' with a login account.' : ' without a login account.'),
        $user
    );

    return redirect()->route('admin.users.show', $user)->with(
        'success',
        $wantsAccount
            ? 'User and login account created successfully.'
            : 'User created without a login account. You can add the account later by editing the user.'
    );
}

public function update(Request $request, User $user)
{
    $hadAccount = $user->hasAccount();
    $wantsAccount = $hadAccount || $request->boolean('create_account') || $request->input('role') === 'admin';

    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        // A new account needs a password. For an existing account the field may stay empty.
        'password' => $wantsAccount
            ? [$hadAccount ? 'nullable' : 'required', 'confirmed', Password::defaults()]
            : ['nullable'],
        'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
        'department_id' => ['nullable', 'required_if:role,teacher,student', 'exists:departments,id'],
        'roll_number' => ['nullable', 'required_if:role,student', Rule::unique('users', 'roll_number')->ignore($user->id)],
    ] + $this->profileRules());

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

    // Keep the old password when the field is left empty (or when there is no account)
    if (! $wantsAccount || empty($validated['password'])) {
        unset($validated['password']);
    }

    // An admin must not change his own role (to avoid locking himself out)
    if ($user->id === auth()->id()) {
        $validated['role'] = $user->role;
    }

    $validated = $this->prepareProfile($request, $validated, $user);

    $user->fill($validated);

    // What changed, for the audit log (the password is never written to the log)
    $old = [];
    $new = [];

    foreach ($user->getDirty() as $field => $value) {
        if (in_array($field, ['password', 'email_verified_at'], true)) {
            continue;
        }

        if ($field === 'department_id') {
            $old['department'] = Department::find($user->getOriginal('department_id'))?->name;
            $new['department'] = Department::find($value)?->name;

            continue;
        }

        $old[$field] = $user->getOriginal($field);
        $new[$field] = $value;
    }

    // The first password turns the person into a user who can log in
    $createdAccount = ! $hadAccount && isset($validated['password']);
    if ($createdAccount) {
        $user->email_verified_at = now();
    }

    $user->save();

    if ($new) {
        if (array_key_exists('role', $new)) {
            AuditLog::record('user.role_changed', "Role of {$user->name} changed from {$old['role']} to {$new['role']}.", $user, $old, $new);
        } else {
            AuditLog::record('user.updated', "User {$user->name} was updated (" . implode(', ', array_keys($new)) . ').', $user, $old, $new);
        }
    }

    if ($createdAccount) {
        AuditLog::record('user.account_created', "A login account was created for {$user->name}.", $user);
    }

    return redirect()->route('admin.users.show', $user)->with(
        'success',
        $createdAccount ? 'User updated and the login account was created.' : 'User updated successfully.'
    );
}

public function destroy(User $user)
{
    if ($user->id === auth()->id()) {
        return redirect()->route('admin.users.index')
            ->with('error', 'You cannot delete your own account.');
    }

    if ($user->project()->exists()) {
        return redirect()->route('admin.users.index')
            ->with('error', 'This student has a project. Delete the project first.');
    }

    // The log is written before the delete, because afterwards nothing is left to describe
    AuditLog::record(
        'user.deleted',
        "User {$user->name} ({$user->role}, {$user->email}) was deleted.",
        $user,
        ['name' => $user->name, 'email' => $user->email, 'role' => $user->role]
    );

    if ($user->photo_path) {
        Storage::delete($user->photo_path);
    }

    $user->delete();

    return redirect()->route('admin.users.index')
        ->with('success', 'User deleted successfully.');
}

private function formData(): array
{
    return [
        'faculties' => Faculty::orderBy('name')->get(),
        'departments' => Department::orderBy('name')->get(),
        'classes' => ClassGroup::with(['department', 'entryTerm'])
            ->where('status', 'active')
            ->get()
            ->sortBy('name')
            ->values(),
    ];
}

// The details of the profile. A teacher must have an academic rank and an education degree.
private function profileRules(): array
{
    return [
        'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        'remove_photo' => ['nullable', 'boolean'],
        'father_name' => ['nullable', 'string', 'max:100'],
        'date_of_birth' => ['nullable', 'date', 'before:today'],
        'gender' => ['nullable', Rule::in(array_keys(User::GENDERS))],
        'phone' => ['nullable', 'string', 'max:30'],
        'address' => ['nullable', 'string', 'max:255'],
        'academic_rank' => ['nullable', 'required_if:role,teacher', Rule::in(array_keys(User::ACADEMIC_RANKS))],
        'education_degree' => ['nullable', 'required_if:role,teacher', Rule::in(array_keys(User::EDUCATION_DEGREES))],
        'specialization' => ['nullable', 'string', 'max:150'],
        'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
    ];
}

// Stores the photo (and removes the old one) and clears the fields that belong only to teachers
private function prepareProfile(Request $request, array $data, ?User $user = null): array
{
    unset($data['photo'], $data['remove_photo']);

    if ($request->hasFile('photo')) {
        if ($user?->photo_path) {
            Storage::delete($user->photo_path);
        }

        $data['photo_path'] = $request->file('photo')->store('photos/users');
    } elseif ($user && $request->boolean('remove_photo') && $user->photo_path) {
        Storage::delete($user->photo_path);
        $data['photo_path'] = null;
    }

    if ($data['role'] !== 'teacher') {
        $data['academic_rank'] = null;
        $data['education_degree'] = null;
        $data['specialization'] = null;
        $data['experience_years'] = null;
    }

    return $data;
}

}