<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('admin.grades.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back to grades
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 space-y-1">
                <div>
                    <span class="font-medium">Department:</span> {{ $subject->department->name }}
                    &nbsp;|&nbsp;
                    <span class="font-medium">Semester:</span> {{ $subject->semester->name }}
                    @unless ($subject->semester->is_active)
                        <span class="ms-1 px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">Closed</span>
                    @endunless
                    &nbsp;|&nbsp;
                    <span class="font-medium">Teacher:</span> {{ $subject->teacher?->name ?? 'Not assigned' }}
                </div>
                @unless ($subject->semester->is_active)
                    <div class="text-yellow-700">
                        This semester is closed. Teachers cannot change these grades, but you can.
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Graded</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['graded'] }} / {{ $stats['enrolled'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Average</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['average'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Highest</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['highest'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Lowest</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['lowest'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Pass rate</div>
                    <div class="text-2xl font-semibold text-gray-900">
                        {{ $stats['pass_rate'] !== null ? $stats['pass_rate'] . '%' : '-' }}
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($enrollments->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-500 text-sm">
                        No students are enrolled in this subject.
                    </div>
                @else
                    <form method="POST" action="{{ route('admin.grades.update', $subject) }}">
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
                                        <td class="px-6 py-4 text-sm font-medium {{ $grade && $grade->marks < \App\Models\Grade::PASS_MARK ? 'text-red-600' : 'text-gray-700' }}">
                                            {{ $grade?->grade_letter ?? '-' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="px-6 py-4 border-t border-gray-200 flex items-center gap-4">
                            <x-primary-button>Save Grades</x-primary-button>
                            <a href="{{ route('admin.grades.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>