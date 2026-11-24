@php
    $schedule = $schedule ?? null;
    $field = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $selectedSubject = old('subject_id', $schedule?->subject_id);
@endphp

<div>
    <x-input-label for="faculty-select" value="Faculty" />
    <select id="faculty-select" class="{{ $field }}">
        <option value="">-- Select faculty --</option>
        @foreach ($faculties as $faculty)
            <option value="{{ $faculty->id }}">{{ $faculty->name }}</option>
        @endforeach
    </select>
</div>

<div id="department-box">
    <x-input-label for="department-select" value="Department" />
    <select id="department-select" class="{{ $field }}">
        <option value="">-- Select department --</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" data-group="{{ $department->faculty_id }}">{{ $department->name }}</option>
        @endforeach
    </select>
</div>

<div id="class-box">
    <x-input-label for="class-select" value="Class" />
    <select id="class-select" class="{{ $field }}">
        <option value="">-- Select class --</option>
        @foreach ($classes as $group)
            <option value="{{ $group->id }}" data-group="{{ $group->department_id }}">
                {{ $group->name }}
                ({{ $group->status === 'completed' ? 'completed' : 'now in semester ' . $group->current_semester_number }})
            </option>
        @endforeach
    </select>
</div>

<div id="subject-box">
    <x-input-label for="subject-select" value="Subject" />
    <select id="subject-select" name="subject_id" required class="{{ $field }}">
        <option value="">-- Select subject --</option>
        @foreach ($subjects as $subject)
            <option value="{{ $subject->id }}" data-group="{{ $subject->semester->class_group_id }}"
                @selected($selectedSubject == $subject->id)>
                {{ $subject->code }} - {{ $subject->name }} ({{ $subject->semester->name }})
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
    <p class="mt-1 text-xs text-gray-500">Only the subjects of the current semester of the class are listed.</p>
</div>

<div>
    <x-input-label for="day" value="Day" />
    <select id="day" name="day" required class="{{ $field }}">
        <option value="">-- Select day --</option>
        @foreach ($days as $day)
            <option value="{{ $day }}" @selected(old('day', $schedule?->day) === $day)>{{ $day }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('day')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="start_time" value="Start Time" />
        <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full"
                      :value="old('start_time', $schedule ? substr($schedule->start_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_time" value="End Time" />
        <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full"
                      :value="old('end_time', $schedule ? substr($schedule->end_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="room" value="Room (optional)" />
    <x-text-input id="room" name="room" type="text" class="mt-1 block w-full"
                  :value="old('room', $schedule?->room)" />
    <x-input-error :messages="$errors->get('room')" class="mt-2" />
</div>

<script>
    const facultySelect = document.getElementById('faculty-select');
    const departmentSelect = document.getElementById('department-select');
    const classSelect = document.getElementById('class-select');
    const subjectSelect = document.getElementById('subject-select');
    const departmentBox = document.getElementById('department-box');
    const classBox = document.getElementById('class-box');
    const subjectBox = document.getElementById('subject-box');

    // Only the options of the selected parent stay; a choice that no longer fits is cleared
    function filterOptions(select, group) {
        Array.from(select.options).forEach(option => {
            if (!option.dataset.group) return;
            const matches = option.dataset.group === group;
            option.hidden = !matches;
            option.disabled = !matches;
        });

        const selected = select.options[select.selectedIndex];
        if (selected && selected.dataset.group && selected.dataset.group !== group) {
            select.value = '';
        }
    }

    // Faculty -> department -> class -> subject: every dropdown appears after the one before it
    function update() {
        filterOptions(departmentSelect, facultySelect.value);
        departmentBox.style.display = facultySelect.value ? '' : 'none';

        filterOptions(classSelect, departmentSelect.value);
        classBox.style.display = departmentSelect.value ? '' : 'none';

        filterOptions(subjectSelect, classSelect.value);
        subjectBox.style.display = classSelect.value ? '' : 'none';
        subjectSelect.disabled = !classSelect.value; // a hidden subject is not sent
    }

    // When a subject is already chosen (edit form or after an error), its class, department and faculty are chosen
    function syncFromSubject() {
        const subject = subjectSelect.options[subjectSelect.selectedIndex];
        if (!subject || !subject.dataset.group) return;

        classSelect.value = subject.dataset.group;
        const group = classSelect.options[classSelect.selectedIndex];
        if (group && group.dataset.group) departmentSelect.value = group.dataset.group;

        const department = departmentSelect.options[departmentSelect.selectedIndex];
        if (department && department.dataset.group) facultySelect.value = department.dataset.group;
    }

    [facultySelect, departmentSelect, classSelect].forEach(select => select.addEventListener('change', update));

    syncFromSubject();
    update();
</script>