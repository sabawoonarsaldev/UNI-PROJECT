<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Attendance: {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('teacher.attendance.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All subjects</a>
        </div>
    </x-slot>

    @php
        $start = \App\Services\AttendanceService::semesterStart($subject);
        $end = \App\Services\AttendanceService::semesterEnd($subject);
        $studentsCount = $summary['rows']->count();
        $semesterOpen = (bool) $subject->semester->is_active;
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    <span><span class="font-medium text-gray-800">Class:</span> {{ $subject->semester->classGroup?->name ?? '-' }}</span>
                    <span><span class="font-medium text-gray-800">Semester:</span> {{ $subject->semester->name }}</span>
                    <span><span class="font-medium text-gray-800">Lesson days:</span> {{ $subject->schedules->pluck('day')->unique()->implode(', ') ?: 'No timetable yet' }}</span>
                    <span>
                        <span class="font-medium text-gray-800">Semester dates:</span>
                        {{ $start ? $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') : 'not set by the administration' }}
                    </span>
                </div>

                {{-- The teacher decides whether the students see their attendance --}}
                <form method="POST" action="{{ route('teacher.attendance.visibility', $subject) }}" class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="attendance_visible" value="0">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" name="attendance_visible" value="1"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                               @checked($subject->attendance_visible)>
                        <span>Students can see their attendance of this subject</span>
                    </label>
                    <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Save</button>
                </form>
            </div>

            <x-panel title="Days of attendance">
                <x-slot name="action">
                    @if ($semesterOpen)
                        <a href="{{ route('teacher.attendance.create', $subject) }}"
                           class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                            + Take attendance
                        </a>
                    @else
                        <span class="text-gray-500">The semester is closed</span>
                    @endif
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Present</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Absent</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Excused</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Blank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($sessions as $session)
                                @php
                                    $blank = max(0, $studentsCount - ($session->present_count + $session->absent_count + $session->excused_count));
                                    $editable = $semesterOpen && ! $session->isLocked();
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ $session->date->format('Y-m-d') }}
                                        <span class="text-xs text-gray-500">{{ $session->date->format('l') }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-green-700">{{ $session->present_count }}</td>
                                    <td class="px-4 py-3 text-sm text-red-600">{{ $session->absent_count }}</td>
                                    <td class="px-4 py-3 text-sm text-blue-700">{{ $session->excused_count }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $blank }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($session->isLocked())
                                            <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Locked by admin</span>
                                        @else
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Open</span>
                                        @endif
                                    </td>
                                    <td class="space-x-2 px-4 py-3 text-right text-sm">
                                        <a href="{{ route('teacher.attendance.edit', $session) }}" class="text-indigo-600 hover:underline">
                                            {{ $editable ? 'Edit' : 'View' }}
                                        </a>

                                        @if ($editable)
                                            <form action="{{ route('teacher.attendance.destroy', $session) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('Delete the attendance of this day?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No attendance was taken yet. Click "Take attendance" to start.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <x-panel title="Students: attendance summary">
                @include('partials.attendance-summary', ['summary' => $summary])
            </x-panel>
        </div>
    </div>
</x-app-layout>