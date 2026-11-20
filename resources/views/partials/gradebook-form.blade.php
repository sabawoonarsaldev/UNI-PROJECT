@php
    $components = \App\Models\Grade::COMPONENTS;
@endphp

<form method="POST" action="{{ $action }}" id="gradebook-form">
    @csrf
    @method('PUT')

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chance</th>
                    @foreach ($components as $component)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                            {{ $component['label'] }}
                            <span class="normal-case text-gray-400">/ {{ $component['max'] }}</span>
                        </th>
                    @endforeach
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total / 100</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($rows as $enrollment)
                    @php
                        $grade = $enrollment->grade;
                        $rowErrors = \Illuminate\Support\Arr::flatten([
                            $errors->get('scores.' . $enrollment->id),
                            $errors->get('scores.' . $enrollment->id . '.*'),
                        ]);
                    @endphp

                    <tr data-row class="{{ $enrollment->editable ? '' : 'bg-gray-50 text-gray-400' }}">
                        <td class="px-4 py-3 text-sm font-medium">{{ $enrollment->student->name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $enrollment->student->roll_number ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm">
                            @if ($enrollment->chance > 1)
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                    Chance {{ $enrollment->chance }}
                                </span>
                            @else
                                1
                            @endif
                        </td>

                        @foreach ($components as $key => $component)
                            @php
                                $saved = $grade && $grade->{$component['column']} !== null
                                    ? (float) $grade->{$component['column']}
                                    : null;
                            @endphp
                            <td class="px-4 py-3 text-sm">
                                @if ($enrollment->editable)
                                    <input type="number" step="0.01" min="0" max="{{ $component['max'] }}"
                                           name="scores[{{ $enrollment->id }}][{{ $key }}]"
                                           value="{{ old('scores.' . $enrollment->id . '.' . $key, $saved) }}"
                                           class="score-input w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @else
                                    {{ $saved ?? '-' }}
                                @endif
                            </td>
                        @endforeach

                        <td data-total class="px-4 py-3 text-sm font-semibold {{ $grade && $grade->marks < \App\Models\Grade::PASS_MARK ? 'text-red-600' : '' }}">
                            {{ $grade ? (float) $grade->marks : '-' }}
                        </td>
                        <td data-letter class="px-4 py-3 text-sm font-medium">{{ $grade?->grade_letter ?? '-' }}</td>
                    </tr>

                    @if (count($rowErrors) > 0)
                        <tr>
                            <td colspan="{{ 5 + count($components) }}" class="bg-red-50 px-4 py-2 text-xs text-red-700">
                                {{ $enrollment->student->name }}: {{ implode(' ', $rowErrors) }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex items-center gap-4 border-t border-gray-200 px-6 py-4">
        <x-primary-button>Save Grades</x-primary-button>
        <a href="{{ $cancelUrl }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
    </div>
</form>

<script>
    (function () {
        const pass = {{ \App\Models\Grade::PASS_MARK }};
        const scale = [[90, 'A+'], [80, 'A'], [75, 'B+'], [70, 'B'], [65, 'C+'], [60, 'C'], [pass, 'D']];

        function letterFor(total) {
            for (const [min, name] of scale) {
                if (total >= min) return name;
            }
            return 'F';
        }

        // The total and the letter change while the teacher types
        document.querySelectorAll('#gradebook-form tr[data-row]').forEach(row => {
            const inputs = row.querySelectorAll('.score-input');
            if (inputs.length === 0) return;

            const totalCell = row.querySelector('[data-total]');
            const letterCell = row.querySelector('[data-letter]');

            function update() {
                const values = Array.from(inputs).map(input => input.value);

                totalCell.classList.remove('text-red-600');

                if (values.every(value => value === '')) {
                    totalCell.textContent = '-';
                    letterCell.textContent = '-';
                    return;
                }

                // All four boxes are needed
                if (values.some(value => value === '')) {
                    totalCell.textContent = '...';
                    letterCell.textContent = '-';
                    return;
                }

                const total = Math.round(values.reduce((sum, value) => sum + parseFloat(value), 0) * 100) / 100;

                totalCell.textContent = total;
                letterCell.textContent = letterFor(total);
                if (total < pass) totalCell.classList.add('text-red-600');
            }

            inputs.forEach(input => input.addEventListener('input', update));
            update();
        });
    })();
</script>