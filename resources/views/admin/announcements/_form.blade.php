@php
    $announcement = $announcement ?? null;
    $selectedFaculty = old('faculty_id', $announcement?->faculty_id);
    $selectedDepartment = old('department_id', $announcement?->department_id);
@endphp

<div>
    <x-input-label for="title" value="Title" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  :value="old('title', $announcement?->title)" required autofocus />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div>
    <x-input-label for="body" value="Message" />
    <textarea id="body" name="body" rows="6" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body', $announcement?->body) }}</textarea>
    <x-input-error :messages="$errors->get('body')" class="mt-2" />
</div>

<div>
    <x-input-label for="target_role" value="Audience" />
    <select id="target_role" name="target_role" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach (['all' => 'Everyone', 'teacher' => 'Teachers only', 'student' => 'Students only'] as $value => $label)
            <option value="{{ $value }}" @selected(old('target_role', $announcement?->target_role ?? 'all') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('target_role')" class="mt-2" />
</div>

<div>
    <x-input-label for="faculty_id" value="Faculty" />
    <select id="faculty_id" name="faculty_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">All faculties</option>
        @foreach ($faculties as $faculty)
            <option value="{{ $faculty->id }}" @selected($selectedFaculty == $faculty->id)>{{ $faculty->name }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('faculty_id')" class="mt-2" />
    <p class="mt-1 text-xs text-gray-500">Choose a faculty to send the announcement only to it.</p>
</div>

<div id="department-field">
    <x-input-label for="department_id" value="Department" />
    <select id="department_id" name="department_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">All departments of the faculty</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" data-faculty="{{ $department->faculty_id }}"
                @selected($selectedDepartment == $department->id)>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
</div>

<script>
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');
    const departmentField = document.getElementById('department-field');

    // The department dropdown appears after a faculty is chosen and offers only its departments
    function filterDepartments() {
        const faculty = facultySelect.value;

        departmentField.style.display = faculty ? '' : 'none';
        departmentSelect.disabled = !faculty; // a disabled select is not sent, so the department stays empty

        Array.from(departmentSelect.options).forEach(option => {
            if (!option.dataset.faculty) return;
            const matches = option.dataset.faculty === faculty;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        const selected = departmentSelect.options[departmentSelect.selectedIndex];
        if (selected && selected.dataset.faculty && selected.dataset.faculty !== faculty) {
            departmentSelect.value = '';
        }
    }

    facultySelect.addEventListener('change', filterDepartments);
    filterDepartments();
</script>