<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Class</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
            <div class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">

                <p class="text-sm text-gray-600">
                    A class goes through 8 semesters together. Semester 1 is created automatically with the class,
                    and the next ones are created when you complete each semester.
                </p>

                <form method="POST" action="{{ route('admin.classes.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="department_id" value="Department" />
                        <select id="department_id" name="department_id" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select department --</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" data-name="{{ $department->name }}"
                                    @selected(old('department_id') == $department->id)>
                                    {{ $department->name }} ({{ $department->faculty->name }})
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="semester_type_id" value="First semester term" />
                            <select id="semester_type_id" name="semester_type_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select term --</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected(old('semester_type_id') == $type->id)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('semester_type_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="entry_year" value="Year (Hijri Shamsi)" />
                            <select id="entry_year" name="entry_year" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($years as $year)
                                    <option value="{{ $year }}" @selected(old('entry_year', $currentYear) == $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('entry_year')" class="mt-2" />
                        </div>
                    </div>

                    @if ($types->isEmpty())
                        <p class="text-sm text-amber-700">
                            No terms are configured yet.
                            <a href="{{ route('admin.semester-types.index') }}" class="underline">Add terms first</a>.
                        </p>
                    @endif

                    <div class="rounded-md bg-gray-50 px-4 py-3 text-sm text-gray-600">
                        Class name: <span id="name-preview" class="font-semibold text-gray-900">-</span>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Create class</x-primary-button>
                        <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const departmentSelect = document.getElementById('department_id');
        const typeSelect = document.getElementById('semester_type_id');
        const yearSelect = document.getElementById('entry_year');
        const preview = document.getElementById('name-preview');

        // Shows the class name that will be created
        function updatePreview() {
            if (departmentSelect.value && typeSelect.value && yearSelect.value) {
                const department = departmentSelect.options[departmentSelect.selectedIndex].dataset.name;
                const term = typeSelect.options[typeSelect.selectedIndex].text.trim();
                preview.textContent = department + ' - ' + term + ' ' + yearSelect.value;
            } else {
                preview.textContent = '-';
            }
        }

        [departmentSelect, typeSelect, yearSelect].forEach(select => select.addEventListener('change', updatePreview));
        updatePreview();
    </script>
</x-app-layout>