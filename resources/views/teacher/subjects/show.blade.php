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

    @php
        $gradeList = $rows->pluck('grade')->filter();
        $counts = $gradeList->countBy('status');
        $draftCount = $counts['draft'] ?? 0;
        $submittedCount = $counts['submitted'] ?? 0;
        $publishedCount = $counts['published'] ?? 0;
        $withoutGrade = $rows->filter(fn ($row) => ! $row->grade)->count();
        $statuses = \App\Models\Grade::STATUSES;
        $components = \App\Models\Grade::COMPONENTS;
    @endphp

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

            {{-- Status of the results and the Submit button --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="font-semibold text-gray-800">Results:</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statuses['draft'][1] }}">{{ $draftCount }} draft</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statuses['submitted'][1] }}">{{ $submittedCount }} waiting for admin</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statuses['published'][1] }}">{{ $publishedCount }} published</span>
                    @if ($withoutGrade > 0)
                        <span class="text-xs text-gray-500">{{ $withoutGrade }} student(s) without a grade</span>
                    @endif
                </div>

                <p class="text-xs text-gray-500">
                    Draft: only you and the administration see it. After you submit, the grades are locked for you and the
                    administration publishes them. Students see only the published grades.
                </p>

                <div class="flex flex-wrap gap-2">
                    @if ($rows->contains('editable', true))
                        <a href="{{ route('teacher.grades.edit', $subject) }}"
                           class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Enter / Edit Grades
                        </a>
                    @endif

                    @if ($draftCount > 0)
                        <form method="POST" action="{{ route('teacher.grades.submit', $subject) }}"
                              onsubmit="return confirm('Submit {{ $draftCount }} draft grade(s) to the administration? You cannot change them after this.');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
                                Submit grades to administration
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                    Students ({{ $rows->pluck('student_id')->unique()->count() }})
                </div>

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
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($rows as $enrollment)
                                @php
                                    $grade = $enrollment->grade;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        @if ($enrollment->chance > 1)
                                            <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                                                Chance {{ $enrollment->chance }}
                                            </span>
                                        @else
                                            1
                                        @endif
                                    </td>
                                    @foreach ($components as $component)
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $grade && $grade->{$component['column']} !== null ? (float) $grade->{$component['column']} : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-sm font-semibold {{ $grade && $grade->marks < \App\Models\Grade::PASS_MARK ? 'text-red-600' : 'text-gray-900' }}">
                                        {{ $grade ? (float) $grade->marks : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $grade?->grade_letter ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($grade)
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statuses[$grade->status][1] }}">
                                                {{ $statuses[$grade->status][0] }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 7 + count($components) }}" class="px-6 py-8 text-center text-gray-500">
                                        No students are enrolled in this subject yet. Please contact the admin.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>