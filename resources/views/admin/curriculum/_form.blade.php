@php
    $subject = $subject ?? null;
    $prefill = $prefill ?? [];
    $field = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="faculty_id" value="Faculty" />
        <select id="faculty_id" class="{{ $field }}">
            <option value="">-- Select faculty --</option>
            @foreach ($faculties as $faculty)
                <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <x-input-label for="department_id" value="Department" />
        <select id="department_id" name="department_id" required class="{{ $field }}">
            <option value="">-- Select department --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" data-faculty="{{ $department->faculty_id }}"
                    @selected(old('department_id', $subject?->department_id ?? ($prefill['department_id'] ?? null)) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="semester_number" value="Semester" />
    <select id="semester_number" name="semester_number" required class="{{ $field }}">
        <option value="">-- Select semester --</option>
        @for ($n = 1; $n <= \App\Models\CurriculumSubject::MAX_SEMESTERS; $n++)
            <option value="{{ $n }}" @selected(old('semester_number', $subject?->semester_number ?? ($prefill['semester_number'] ?? null)) == $n)>
                Semester {{ $n }}
            </option>
        @endfor
    </select>
    <x-input-error :messages="$errors->get('semester_number')" class="mt-2" />
    <p class="mt-1 text-xs text-gray-500">This is the semester number inside the program (1, 2, 3 ...), not a class.</p>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="name" value="Subject Name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $subject?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="code" value="Subject Code (e.g. CS-101)" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                      :value="old('code', $subject?->code)" required />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="credit_hours" value="Credit Hours" />
        <x-text-input id="credit_hours" name="credit_hours" type="number" min="1" max="10" class="mt-1 block w-full"
                      :value="old('credit_hours', $subject?->credit_hours ?? 3)" required />
        <x-input-error :messages="$errors->get('credit_hours')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="teacher_id" value="Teacher (default)" />
        <select id="teacher_id" name="teacher_id" class="{{ $field }}">
            <option value="">-- Not assigned --</option>
            @foreach ($teachers as $teacher)
                <option value="{{ $teacher->id }}" @selected(old('teacher_id', $subject?->teacher_id) == $teacher->id)>
                    {{ $teacher->name }}@if ($teacher->department) ({{ $teacher->department->name }})@endif
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
        <p class="mt-1 text-xs text-gray-500">Every class can choose another teacher when it selects the subject.</p>
    </div>
</div>

<script>
    const facultySelect = document.getElementById('faculty_id');
    const departmentSelect = document.getElementById('department_id');

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

    // When a department is already chosen, its faculty is chosen automatically
    function syncFaculty() {
        const selected = departmentSelect.options[departmentSelect.selectedIndex];
        if (selected && selected.dataset.faculty) facultySelect.value = selected.dataset.faculty;
    }

    facultySelect.addEventListener('change', filterDepartments);
    syncFaculty();
    filterDepartments();
</script>