@php($user = $user ?? null)

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

<div>
    <x-input-label for="password"
                   :value="$user ? 'New Password (leave empty to keep the current one)' : 'Password'" />
    <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                  :required="! $user" autocomplete="new-password" />
    <x-input-error :messages="$errors->get('password')" class="mt-2" />
</div>

<div>
    <x-input-label for="password_confirmation" value="Confirm Password" />
    <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                  class="mt-1 block w-full" :required="! $user" autocomplete="new-password" />
</div>

<script>
    const roleSelect = document.getElementById('role');
    const departmentSelect = document.getElementById('department_id');
    const classSelect = document.getElementById('class_group_id'); // missing when the user already has a class
    const departmentField = document.getElementById('department-field');
    const rollNumberField = document.getElementById('roll-number-field');
    const classField = document.getElementById('class-field');

    function toggleFields() {
        const role = roleSelect.value;
        departmentField.style.display = role === 'admin' ? 'none' : '';
        rollNumberField.style.display = role === 'student' ? '' : 'none';
        classField.style.display = role === 'student' ? '' : 'none';
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

    roleSelect.addEventListener('change', toggleFields);
    departmentSelect.addEventListener('change', filterClasses);
    toggleFields();
    filterClasses();
</script>