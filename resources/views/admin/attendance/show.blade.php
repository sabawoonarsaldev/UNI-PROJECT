<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Attendance: {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('admin.attendance.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All subjects</a>
        </div>
    </x-slot>

    @php
        $studentsCount = $summary['rows']->count();
        $lockedCount = $sessions->filter->isLocked()->count();
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 shadow-sm">
                <div class="flex flex-wrap gap-x-6 gap-y-1">
                    <span><span class="font-medium text-gray-800">Class:</span> {{ $subject->semester->classGroup?->name ?? '-' }}</span>
                    <span><span class="font-medium text-gray-800">Semester:</span> {{ $subject->semester->name }}</span>
                    <span><span class="font-medium text-gray-800">Teacher:</span> {{ $subject->teacher?->name ?? 'Not assigned' }}</span>
                    <span><span class="font-medium text-gray-800">Students see attendance:</span> {{ $subject->attendance_visible ? 'Yes' : 'No' }}</span>
                </div>

                <div class="flex gap-2">
                    <form method="POST" action="{{ route('admin.attendance.lock-all', $subject) }}"
                          onsubmit="return confirm('Lock all open days? The teacher will not be able to change them.');">
                        @csrf
                        <button type="submit" class="rounded-md bg-gray-800 px-3 py-1.5 text-sm text-white hover:bg-gray-700">Lock all days</button>
                    </form>
                    <form method="POST" action="{{ route('admin.attendance.unlock-all', $subject) }}"
                          onsubmit="return confirm('Unlock all locked days?');">
                        @csrf
                        <button type="submit" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">Unlock all days</button>
                    </form>
                </div>
            </div>

            <x-panel :title="'Days of attendance (' . $lockedCount . ' of ' . $sessions->count() . ' locked)'">
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
                                            <span class="rounded-full bg-gray-200 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                                Locked {{ $session->locked_at->format('Y-m-d') }}
                                            </span>
                                        @else
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Open</span>
                                        @endif
                                    </td>
                                    <td class="space-x-2 px-4 py-3 text-right text-sm">
                                        <a href="{{ route('admin.attendance.session', $session) }}" class="text-indigo-600 hover:underline">View</a>

                                        @if ($session->isLocked())
                                            <form action="{{ route('admin.attendance.unlock', $session) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-amber-700 hover:underline">Unlock</button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.attendance.lock', $session) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-gray-800 hover:underline">Lock</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">The teacher has not taken attendance yet.</td>
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