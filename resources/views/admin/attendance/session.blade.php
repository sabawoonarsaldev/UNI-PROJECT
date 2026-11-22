<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $subject->code }} - {{ $attendanceSession->date->format('Y-m-d') }} ({{ $attendanceSession->date->format('l') }})
            </h2>
            <a href="{{ route('admin.attendance.show', $subject) }}" class="text-sm text-gray-600 hover:underline">&larr; Back to the subject</a>
        </div>
    </x-slot>

    @php($labels = \App\Models\AttendanceRecord::STATUSES)

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">

            <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-600 shadow-sm">
                Taken by <span class="font-medium text-gray-800">{{ $attendanceSession->taker?->name ?? '-' }}</span>.
                @if ($attendanceSession->isLocked())
                    Locked by {{ $attendanceSession->locker?->name ?? '-' }} on {{ $attendanceSession->locked_at->format('Y-m-d H:i') }}.
                @else
                    This day is open: the teacher can still change it.
                @endif
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Attendance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Reason</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($enrollments as $enrollment)
                            @php($record = $records->get($enrollment->student_id))
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($record)
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $labels[$record->status][1] }}">{{ $labels[$record->status][0] }}</span>
                                    @else
                                        <span class="text-gray-400">Blank</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $record?->note ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>