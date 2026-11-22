<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            My Attendance - {{ $student->currentSemester?->name ?? 'No semester' }}
        </h2>
    </x-slot>

    @php
        $labels = \App\Models\AttendanceRecord::STATUSES;
        $stateText = [
            'ok' => ['You are within the allowed absences.', 'bg-green-50 border-green-200 text-green-800'],
            'warning' => ['Warning: you reached the limit. One more absence and you will be barred from the exam.', 'bg-amber-50 border-amber-200 text-amber-900'],
            'barred' => ['You have too many absences and are barred from the exam of this subject.', 'bg-red-50 border-red-200 text-red-800'],
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            <p class="text-sm text-gray-600">
                To sit in the exam you must attend at least {{ $minPercent }}% of the lessons. Days with an accepted reason
                (excused) are not counted as absences.
            </p>

            @forelse ($cards as $card)
                @php
                    $subject = $card['subject'];
                @endphp

                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <div class="font-semibold text-gray-900">{{ $subject->code }} - {{ $subject->name }}</div>
                        <div class="text-xs text-gray-500">Teacher: {{ $subject->teacher?->name ?? 'Not assigned' }}</div>
                    </div>

                    @if (! $card['visible'])
                        <div class="px-6 py-6 text-sm text-gray-500">
                            Your teacher has not made the attendance of this subject visible yet.
                        </div>
                    @else
                        @php
                            $data = $card['data'];
                            $summary = $data['summary'];
                        @endphp

                        <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-4">
                            <div><div class="text-sm text-gray-500">Present</div><div class="text-2xl font-semibold text-green-700">{{ $summary['present'] }}</div></div>
                            <div><div class="text-sm text-gray-500">Absent</div><div class="text-2xl font-semibold text-red-600">{{ $summary['absent'] }}</div></div>
                            <div><div class="text-sm text-gray-500">Excused</div><div class="text-2xl font-semibold text-blue-700">{{ $summary['excused'] }}</div></div>
                            <div><div class="text-sm text-gray-500">Attendance so far</div>
                                <div class="text-2xl font-semibold text-gray-900">{{ $summary['percent'] !== null ? $summary['percent'] . '%' : '-' }}</div></div>
                        </div>

                        <div class="mx-6 mb-4 rounded-lg border px-4 py-3 text-sm {{ $stateText[$summary['state']][1] }}">
                            Absences: <span class="font-semibold">{{ $summary['absent'] }}</span> of
                            <span class="font-semibold">{{ $summary['allowed'] }}</span> allowed.
                            {{ $stateText[$summary['state']][0] }}
                        </div>

                        <details class="border-t border-gray-100">
                            <summary class="cursor-pointer px-6 py-3 text-sm font-medium text-indigo-600">
                                Show every day ({{ $data['held'] }})
                            </summary>
                            <ul class="divide-y divide-gray-100">
                                @forelse ($data['days'] as $day)
                                    <li class="flex items-center justify-between px-6 py-2 text-sm">
                                        <span class="text-gray-700">
                                            {{ $day['date']->format('Y-m-d') }}
                                            <span class="text-xs text-gray-500">{{ $day['date']->format('l') }}</span>
                                        </span>
                                        <span class="flex items-center gap-3">
                                            @if ($day['note'])
                                                <span class="text-xs text-gray-500">{{ $day['note'] }}</span>
                                            @endif
                                            @if ($day['status'])
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $labels[$day['status']][1] }}">{{ $labels[$day['status']][0] }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">Not counted</span>
                                            @endif
                                        </span>
                                    </li>
                                @empty
                                    <li class="px-6 py-4 text-sm text-gray-500">No attendance was taken yet.</li>
                                @endforelse
                            </ul>
                        </details>
                    @endif
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-sm text-gray-500 shadow-sm">
                    You are not enrolled in any subject this semester.
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>