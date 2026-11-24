@php
    $user = $user ?? null;
    $hasAccount = $user?->hasAccount() ?? false;
    $accountOpen = $hasAccount || old('create_account') == '1';
    $field = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

{{-- Profile photo --}}
<div>
    <x-input-label for="photo" value="Profile photo" />
    <div class="mt-2 flex items-center gap-4">
        <img id="photo-preview" src="{{ $user?->photoUrl() }}" alt=""
             class="h-20 w-20 rounded-full object-cover ring-2 ring-gray-200 {{ $user?->photo_path ? '' : 'hidden' }}">
        <div id="photo-placeholder"
             class="flex h-20 w-20 items-center justify-center rounded-full bg-gray-100 text-gray-400 {{ $user?->photo_path ? 'hidden' : '' }}">
            <x-icon name="user" class="h-10 w-10" />
        </div>

        <div>
            <input type="file" id="photo" name="photo" accept="image/*"
                   class="block text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
            <p class="mt-1 text-xs text-gray-500">JPG, PNG or WEBP, maximum 2 MB.</p>

            @if ($user?->photo_path)
                <label class="mt-1 inline-flex items-center gap-2 text-sm text-gray-600">
                    <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-indigo-600">
                    Remove the current photo
                </label>
            @endif
        </div>
    </div>
    <x-input-error :messages="$errors->get('photo')" class="mt-2" />
</div>

<div>
    <x-input-label for="name" value="Full Name" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $user?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="email" value="Email" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                  :value="old('email', $user?->email)" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div>
    <x-input-label for="role" value="Role" />
    <select id="role" name="role" required class="{{ $field }}">
        @foreach (['admin' => 'Admin', 'teacher' => 'Teacher', 'student' => 'Student'] as $value => $label)
            <option value="{{ $value }}" @selected(old('role', $user?->role ?? 'student') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>

<div id="department-field" class="space-y-4">
    <div>
        <x-input-label for="faculty_id" value="Faculty" />
        <select id="faculty_id" class="{{ $field }}">
            <option value="">-- Select faculty --</option>
            @foreach ($faculties as $faculty)
                <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-gray-500">Choose the faculty first, then the department.</p>
    </div>

    <div>
        <x-input-label for="department_id" value="Department" />
        <select id="department_id" name="department_id" class="{{ $field }}">
            <option value="">-- Select department --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" data-faculty="{{ $department->faculty_id }}"
                    @selected(old('department_id', $user?->department_id) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
    </div>
</div>

<div id="roll-number-field">
    <x-input-label for="roll_number" value="Roll Number" />
    <x-text-input id="roll_number" name="roll_number" type="text" class="mt-1 block w-full"
                  :value="old('roll_number', $user?->roll_number)" />
    <x-input-error :messages="$errors->get('roll_number')" class="mt-2" />
</div>

<div id="class-field">
    <x-input-label for="class_group_id" value="Class" />

    @if ($user?->class_group_id)
        <div class="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
            {{ $user->classGroup->name }}
        </div>
        <p class="mt-1 text-xs text-gray-500">The class is managed from the class page.</p>
    @else
        <select id="class_group_id" name="class_group_id" class="{{ $field }}">
            <option value="">-- No class yet --</option>
            @foreach ($classes as $group)
                <option value="{{ $group->id }}" data-department="{{ $group->department_id }}"
                    @selected(old('class_group_id') == $group->id)>
                    {{ $group->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('class_group_id')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">Optional. You can also add the student from the class page later.</p>
    @endif
</div>

{{-- Personal details: teachers and students --}}
<div id="personal-fields" class="space-y-4 rounded-lg border border-gray-200 p-4">
    <div class="font-medium text-gray-800">Personal details</div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="father_name" value="Father name" />
            <x-text-input id="father_name" name="father_name" type="text" class="mt-1 block w-full"
                          :value="old('father_name', $user?->father_name)" />
            <x-input-error :messages="$errors->get('father_name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="date_of_birth" value="Date of birth" />
            <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
                          :value="old('date_of_birth', $user?->date_of_birth)" max="{{ now()->subDay()->format('Y-m-d') }}" />
            <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="gender" value="Gender" />
            <select id="gender" name="gender" class="{{ $field }}">
                <option value="">-- Select --</option>
                @foreach (\App\Models\User::GENDERS as $value => $label)
                    <option value="{{ $value }}" @selected(old('gender', $user?->gender) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" value="Phone" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                          :value="old('phone', $user?->phone)" placeholder="0700123456" inputmode="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>
    </div>

    <div>
        <x-input-label for="address" value="Address" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
                      :value="old('address', $user?->address)" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
</div>

{{-- Teacher details: only for teachers --}}
<div id="teacher-fields" class="space-y-4 rounded-lg border border-indigo-200 bg-indigo-50/40 p-4">
    <div class="font-medium text-gray-800">Teacher details</div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <x-input-label for="academic_rank" value="Academic rank" />
            <select id="academic_rank" name="academic_rank" required class="{{ $field }}">
                <option value="">-- Select rank --</option>
                @foreach (\App\Models\User::ACADEMIC_RANKS as $value => $label)
                    <option value="{{ $value }}" @selected(old('academic_rank', $user?->academic_rank) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('academic_rank')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="education_degree" value="Education degree" />
            <select id="education_degree" name="education_degree" required class="{{ $field }}">
                <option value="">-- Select degree --</option>
                @foreach (\App\Models\User::EDUCATION_DEGREES as $value => $label)
                    <option value="{{ $value }}" @selected(old('education_degree', $user?->education_degree) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('education_degree')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="specialization" value="Specialization (field of study)" />
            <x-text-input id="specialization" name="specialization" type="text" class="mt-1 block w-full"
                          :value="old('specialization', $user?->specialization)" placeholder="For example: Software Engineering" />
            <x-input-error :messages="$errors->get('specialization')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="experience_years" value="Teaching experience (years)" />
            <x-text-input id="experience_years" name="experience_years" type="number" min="0" max="60" class="mt-1 block w-full"
                          :value="old('experience_years', $user?->experience_years)" />
            <x-input-error :messages="$errors->get('experience_years')" class="mt-2" />
        </div>
    </div>
</div>

{{-- Login account (optional, except for admins) --}}
<div class="rounded-lg border border-gray-200 p-4">
    <div class="flex items-center justify-between gap-4">
        <div>
            <div class="font-medium text-gray-800">Login account</div>
            <div id="account-hint" class="text-sm text-gray-500">
                @if ($hasAccount)
                    This user can log in with the email below.
                @endif
            </div>
        </div>

        @if ($hasAccount)
            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Account active</span>
        @else
            <button type="button" id="account-toggle"
                    class="rounded-md border border-indigo-600 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:bg-indigo-50">
                Add user account
            </button>
        @endif
    </div>

    <input type="hidden" name="create_account" id="create_account" value="{{ $accountOpen ? 1 : 0 }}">

    <div id="account-fields" class="mt-4 space-y-4">
        <div>
            <x-input-label for="account-email" value="Login email" />
            <input id="account-email" type="email" readonly tabindex="-1"
                   class="mt-1 block w-full cursor-not-allowed rounded-md border-gray-300 bg-gray-100 text-gray-500 shadow-sm">
            <p class="mt-1 text-xs text-gray-500">This is the email above. To change it, edit the Email field.</p>
        </div>

        <div>
            <x-input-label for="password"
                           :value="$hasAccount ? 'New Password (leave empty to keep the current one)' : 'Password'" />
            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                          :required="! $hasAccount" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirm Password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                          class="mt-1 block w-full" :required="! $hasAccount" autocomplete="new-password" />
        </div>
    </div>
</div>

<script>
    const roleSelect = document.getElementById('role');
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    const classSelect = document.getElementById('class_group_id'); // missing when the user already has a class
    const emailInput = document.getElementById('email');
    const departmentField = document.getElementById('department-field');
    const rollNumberField = document.getElementById('roll-number-field');
    const classField = document.getElementById('class-field');
    const personalFields = document.getElementById('personal-fields');
    const teacherFields = document.getElementById('teacher-fields');

    const hasAccount = {{ $hasAccount ? 'true' : 'false' }};
    const accountFlag = document.getElementById('create_account');
    const accountFields = document.getElementById('account-fields');
    const accountToggle = document.getElementById('account-toggle'); // missing when the user already has an account
    const accountHint = document.getElementById('account-hint');
    const lockedEmail = document.getElementById('account-email');
    let accountOpen = {{ $accountOpen ? 'true' : 'false' }};

    // Shows a block and enables its fields, or hides it and disables them (disabled fields are not sent)
    function showBlock(block, visible) {
        block.style.display = visible ? '' : 'none';
        block.querySelectorAll('input, select, textarea').forEach(control => control.disabled = !visible);
    }

    // Opens or closes the password fields. Closed fields are disabled, so they are not sent.
    function setAccountOpen(open) {
        accountOpen = open;
        accountFlag.value = open ? '1' : '0';
        accountFields.style.display = open ? '' : 'none';
        accountFields.querySelectorAll('input[name]').forEach(input => input.disabled = !open);

        if (accountToggle) {
            accountToggle.textContent = open ? 'Cancel account' : 'Add user account';
        }
        if (accountHint && !hasAccount) {
            accountHint.textContent = open
                ? 'A login account will be created for this user.'
                : 'This user has no login account yet and cannot log in. You can add it now or later.';
        }
    }

    function toggleFields() {
        const role = roleSelect.value;

        showBlock(departmentField, role !== 'admin');
        showBlock(rollNumberField, role === 'student');
        showBlock(classField, role === 'student');
        showBlock(personalFields, role !== 'admin');
        showBlock(teacherFields, role === 'teacher');

        // An admin always needs a login account
        if (accountToggle) {
            accountToggle.style.display = role === 'admin' ? 'none' : '';
            if (role === 'admin') setAccountOpen(true);
        }
    }

    // Only the departments of the selected faculty can be chosen
    function filterDepartments() {
        const faculty = facultySelect.value;

        Array.from(departmentSelect.options).forEach(option => {
            if (!option.dataset.faculty) return;
            const matches = !faculty || option.dataset.faculty === faculty;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        const selected = departmentSelect.options[departmentSelect.selectedIndex];
        if (selected && selected.dataset.faculty && faculty && selected.dataset.faculty !== faculty) {
            departmentSelect.value = '';
        }
    }

    // When a department is already chosen (edit form or after an error), its faculty is chosen automatically
    function syncFacultyFromDepartment() {
        const selected = departmentSelect.options[departmentSelect.selectedIndex];

        if (selected && selected.dataset.faculty) {
            facultySelect.value = selected.dataset.faculty;
        }
    }

    // Show only the classes that belong to the selected department
    function filterClasses() {
        if (!classSelect) return;

        const department = departmentSelect.value;
        let selectedIsHidden = false;

        Array.from(classSelect.options).forEach(option => {
            if (!option.dataset.department) return;
            const matches = option.dataset.department === department;
            option.hidden = !matches;
            option.disabled = !matches;
            if (!matches && option.selected) selectedIsHidden = true;
        });

        if (selectedIsHidden) classSelect.value = '';
    }

    // The login email is locked here and always follows the Email field above
    function syncEmail() {
        lockedEmail.value = emailInput.value;
    }

    // The chosen photo is shown before saving
    document.getElementById('photo').addEventListener('change', event => {
        const file = event.target.files[0];
        if (!file) return;

        const preview = document.getElementById('photo-preview');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('hidden');
        document.getElementById('photo-placeholder').classList.add('hidden');
    });

    if (accountToggle) {
        accountToggle.addEventListener('click', () => setAccountOpen(!accountOpen));
    }
    roleSelect.addEventListener('change', toggleFields);
    facultySelect.addEventListener('change', () => {
        filterDepartments();
        filterClasses();
    });
    departmentSelect.addEventListener('change', filterClasses);
    emailInput.addEventListener('input', syncEmail);

    syncFacultyFromDepartment();
    filterDepartments();
    setAccountOpen(accountOpen);
    toggleFields();
    filterClasses();
    syncEmail();
</script>