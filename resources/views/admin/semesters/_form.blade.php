@php($semester = $semester ?? null)

<div>
    <x-input-label for="department_id" value="Department" />
    <select id="department_id" name="department_id" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Select department --</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}"
                @selected(old('department_id', $semester?->department_id) == $department->id)>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="semester_type_id" value="Term" />
        <select id="semester_type_id" name="semester_type_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Select term --</option>
            @foreach ($types as $type)
                <option value="{{ $type->id }}"
                    @selected(old('semester_type_id', $semester?->semester_type_id) == $type->id)>
                    {{ $type->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('semester_type_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="year" value="Year (Hijri Shamsi)" />
        <select id="year" name="year" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected(old('year', $semester?->year ?? $currentYear) == $year)>
                    {{ $year }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('year')" class="mt-2" />
    </div>
</div>

@if ($types->isEmpty())
    <p class="text-sm text-amber-700">
        No terms are configured yet.
        <a href="{{ route('admin.semester-types.index') }}" class="underline">Add terms in Configuration</a>.
    </p>
@endif

<div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
    Semester name: <span id="name-preview" class="font-semibold text-gray-900">-</span>
</div>

<div>
    <input type="hidden" name="is_active" value="0">
    <label class="inline-flex items-center">
        <input type="checkbox" name="is_active" value="1"
               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
               @checked(old('is_active', $semester?->is_active ?? true))>
        <span class="ms-2 text-sm text-gray-600">Active semester</span>
    </label>
</div>

<script>
    const typeSelect = document.getElementById('semester_type_id');
    const yearSelect = document.getElementById('year');
    const namePreview = document.getElementById('name-preview');

    // Shows the name that will be created, for example "Spring 1405"
    function updatePreview() {
        if (typeSelect.value && yearSelect.value) {
            namePreview.textContent = typeSelect.options[typeSelect.selectedIndex].text.trim() + ' ' + yearSelect.value;
        } else {
            namePreview.textContent = '-';
        }
    }

    typeSelect.addEventListener('change', updatePreview);
    yearSelect.addEventListener('change', updatePreview);
    updatePreview();
</script>