@php
    $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $withFaculty = $withFaculty ?? false;
@endphp

<form id="class-filter-form" method="GET" action="{{ $action }}" class="mb-4 flex flex-wrap items-center gap-2 text-sm">

    {{-- Only on the pages that ask for it --}}
    @if ($withFaculty)
        <select name="faculty_id" data-resets="department_id,class_group_id,semester_id" class="{{ $select }}">
            <option value="">All faculties</option>
            @foreach ($faculties as $faculty)
                <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>
                    {{ $faculty->name }}
                </option>
            @endforeach
        </select>
    @endif

    <select name="department_id" data-resets="class_group_id,semester_id" class="{{ $select }}">
        <option value="">All departments</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                {{ $department->name }}
            </option>
        @endforeach
    </select>

    {{-- Appears after a department is chosen: every class of the department --}}
    @if (request('department_id'))
        <select name="class_group_id" data-resets="semester_id" class="{{ $select }}">
            <option value="">All classes</option>
            @foreach ($classes as $group)
                <option value="{{ $group->id }}" @selected(request('class_group_id') == $group->id)>
                    {{ $group->name }}
                    ({{ $group->status === 'completed' ? 'completed' : 'now in semester ' . $group->current_semester_number }})
                </option>
            @endforeach
        </select>
    @endif

    {{-- Appears after a class is chosen: every semester of that class --}}
    @if (request('class_group_id') && $semesters->isNotEmpty())
        <select name="semester_id" class="{{ $select }}">
            <option value="">All semesters</option>
            @foreach ($semesters as $semester)
                <option value="{{ $semester->id }}" @selected(request('semester_id') == $semester->id)>
                    {{ $semester->name }}{{ $semester->is_active ? ' (current)' : '' }}
                </option>
            @endforeach
        </select>
    @endif

    @if (request('faculty_id') || request('department_id') || request('class_group_id') || request('semester_id'))
        <a href="{{ $action }}" class="text-gray-600 hover:underline">Reset</a>
    @endif
</form>

<script>
    document.querySelectorAll('#class-filter-form select').forEach(select => {
        select.addEventListener('change', () => {
            // The dropdowns that depend on this one are cleared, so they never keep an old value
            (select.dataset.resets || '').split(',').filter(Boolean).forEach(name => {
                const dependent = select.form.elements[name];
                if (dependent) dependent.value = '';
            });

            select.form.submit();
        });
    });
</script>