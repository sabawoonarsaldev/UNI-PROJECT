@php
    $select = $select ?? 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    $extraResets = $extraResets ?? '';
    $facultyResets = 'department_id' . ($extraResets !== '' ? ',' . $extraResets : '');
@endphp

<select name="faculty_id" data-auto-submit data-resets="{{ $facultyResets }}" class="{{ $select }}">
    <option value="">All faculties</option>
    @foreach ($faculties as $faculty)
        <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
    @endforeach
</select>

{{-- The department dropdown appears after a faculty is chosen, and offers only the departments of that faculty --}}
@if (request('faculty_id'))
    <select name="department_id" data-auto-submit data-resets="{{ $extraResets }}" class="{{ $select }}">
        <option value="">All departments of the faculty</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
        @endforeach
    </select>
@endif

@once
    <script>
        // Every select with data-auto-submit sends the form when it changes.
        // The selects named in data-resets depend on it, so they are cleared first.
        document.addEventListener('change', event => {
            const select = event.target.closest('select[data-auto-submit]');
            if (!select) return;

            (select.dataset.resets || '').split(',').filter(Boolean).forEach(name => {
                const dependent = select.form.elements[name];
                if (dependent) dependent.value = '';
            });

            select.form.submit();
        });
    </script>
@endonce