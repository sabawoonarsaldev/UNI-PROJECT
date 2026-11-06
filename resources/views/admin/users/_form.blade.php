@php($user = $user ?? null)
@php($hasAccount = $user?->hasAccount() ?? false)
@php($accountOpen = $hasAccount || old('create_account') == '1')

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
    <select id="role" name="role" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach (['admin' => 'Admin', 'teacher' => 'Teacher', 'student' => 'Student'] as $value => $label)
            <option value="{{ $value }}" @selected(old('role', $user?->role ?? 'student') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('role')" class="mt-2" />
</div>

<div id="department-field">
    <x-input-label for="department_id" value="Department" />
    <select id="department_id" name="department_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Select department --</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}"
                @selected(old('department_id', $user?->department_id) == $department->id)>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
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
        <select id="class_group_id" name="class_group_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
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
    const departmentSelect = document.getElementById('department_id');
    const classSelect = document.getElementById('class_group_id'); // missing when the user already has a class
    const emailInput = document.getElementById('email');
    const departmentField = document.getElementById('department-field');
    const rollNumberField = document.getElementById('roll-number-field');
    const classField = document.getElementById('class-field');

    const hasAccount = {{ $hasAccount ? 'true' : 'false' }};
    const accountFlag = document.getElementById('create_account');
    const accountFields = document.getElementById('account-fields');
    const accountToggle = document.getElementById('account-toggle'); // missing when the user already has an account
    const accountHint = document.getElementById('account-hint');
    const lockedEmail = document.getElementById('account-email');
    let accountOpen = {{ $accountOpen ? 'true' : 'false' }};

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
        departmentField.style.display = role === 'admin' ? 'none' : '';
        rollNumberField.style.display = role === 'student' ? '' : 'none';
        classField.style.display = role === 'student' ? '' : 'none';

        // An admin always needs a login account
        if (accountToggle) {
            accountToggle.style.display = role === 'admin' ? 'none' : '';
            if (role === 'admin') setAccountOpen(true);
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

    if (accountToggle) {
        accountToggle.addEventListener('click', () => setAccountOpen(!accountOpen));
    }
    roleSelect.addEventListener('change', toggleFields);
    departmentSelect.addEventListener('change', filterClasses);
    emailInput.addEventListener('input', syncEmail);

    setAccountOpen(accountOpen);
    toggleFields();
    filterClasses();
    syncEmail();
</script>