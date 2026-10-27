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
                Enter marks from 0 to 100. The letter grade is calculated automatically.
                Leave a box empty if the student is not graded yet.
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks (0-100)</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current grade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($enrollments as $enrollment)
                                @php($grade = $grades->get($enrollment->student_id))
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                    <td class="px-6 py-4">
                                        <input type="number" step="0.01" min="0" max="100"
                                               name="marks[{{ $enrollment->student_id }}]"
                                               value="{{ old('marks.' . $enrollment->student_id, $grade ? (float) $grade->marks : '') }}"
                                               class="w-28 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <x-input-error :messages="$errors->get('marks.' . $enrollment->student_id)" class="mt-1" />
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $grade?->grade_letter ?? '-' }}</td>
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