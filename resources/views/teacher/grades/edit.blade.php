<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Grades: {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('teacher.subjects.show', $subject) }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <p class="mb-4 text-sm text-gray-600">
                Enter marks from 0 to 100 (pass mark: {{ \App\Models\Grade::PASS_MARK }}). The letter grade is calculated automatically.
                Rows in gray are locked. Leave a box empty if the student is not graded yet.
            </p>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('teacher.grades.update', $subject) }}">
                    @csrf
                    @method('PUT')

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Chance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks (0-100)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($rows as $enrollment)
                                @php($grade = $enrollment->grade)
                                <tr class="{{ $enrollment->editable ? '' : 'bg-gray-50 text-gray-400' }}">
                                    <td class="px-6 py-4 text-sm font-medium">{{ $enrollment->student->name }}</td>
                                    <td class="px-6 py-4 text-sm">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($enrollment->chance > 1)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                                Chance {{ $enrollment->chance }}
                                            </span>
                                        @else
                                            1
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($enrollment->editable)
                                            <input type="number" step="0.01" min="0" max="100"
                                                   name="marks[{{ $enrollment->id }}]"
                                                   value="{{ old('marks.' . $enrollment->id, $grade ? (float) $grade->marks : '') }}"
                                                   class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <x-input-error :messages="$errors->get('marks.' . $enrollment->id)" class="mt-1" />
                                        @else
                                            {{ $grade ? (float) $grade->marks : '-' }}
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium">{{ $grade?->grade_letter ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div class="px-6 py-4 border-t border-gray-200 flex items-center gap-4">
                        <x-primary-button>Save Grades</x-primary-button>
                        <a href="{{ route('teacher.subjects.show', $subject) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>