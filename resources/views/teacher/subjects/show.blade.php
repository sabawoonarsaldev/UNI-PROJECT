<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('teacher.subjects.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back to subjects
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 space-y-2">
                <div>
                    <span class="font-medium">Department:</span> {{ $subject->department->name }}
                    &nbsp;|&nbsp;
                    <span class="font-medium">Semester:</span> {{ $subject->semester->name }}
                    &nbsp;|&nbsp;
                    <span class="font-medium">Credit hours:</span> {{ $subject->credit_hours }}
                </div>
                <div>
                    <span class="font-medium">Schedule:</span>
                    @forelse ($subject->schedules as $schedule)
                        {{ $schedule->day }} {{ substr($schedule->start_time, 0, 5) }}-{{ substr($schedule->end_time, 0, 5) }}@if ($schedule->room) ({{ $schedule->room }})@endif{{ ! $loop->last ? ',' : '' }}
                    @empty
                        Not set yet
                    @endforelse
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <span class="font-semibold text-gray-800">Students ({{ $enrollments->count() }})</span>

                    @if ($subject->semester->is_active && $enrollments->isNotEmpty())
                        <a href="{{ route('teacher.grades.edit', $subject) }}"
                           class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Enter / Edit Grades
                        </a>
                    @endif
                </div>

                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($enrollments as $enrollment)
                            @php($grade = $grades->get($enrollment->student_id))
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment->student->email }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $grade ? (float) $grade->marks : '-' }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $grade?->grade_letter ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No students are enrolled in this subject yet. Please contact the admin.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>