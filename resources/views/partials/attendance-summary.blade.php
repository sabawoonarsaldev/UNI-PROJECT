@php
    $rows = $summary['rows'];
    $plan = $summary['plan'];
    $stateBadge = [
        'ok' => ['OK', 'bg-green-100 text-green-800'],
        'warning' => ['Warning', 'bg-amber-100 text-amber-800'],
        'barred' => ['Barred from exam', 'bg-red-100 text-red-700'],
    ];
@endphp

<div class="px-6 py-3 text-xs text-gray-500">
    Minimum attendance: {{ \App\Services\AttendanceService::minPercent() }}%.
    Planned sessions: {{ $plan['planned'] }} ({{ $plan['slots'] }} per week x {{ $plan['weeks'] }} weeks).
    A student may be absent at most {{ $plan['allowed'] }} time(s). Excused days are not counted as absences.
</div>

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Present</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Absent</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Excused</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Attendance so far</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Absences (used / allowed)</th>
                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse ($rows as $row)
                @php
                    $rowClass = match ($row['state']) {
                        'barred' => 'bg-red-50 text-red-800',
                        'warning' => 'bg-amber-50 text-amber-900',
                        default => 'text-gray-700',
                    };
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="px-4 py-3 text-sm font-medium">{{ $row['student']->name }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row['student']->roll_number ?? '-' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row['present'] }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row['absent'] }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row['excused'] }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row['percent'] !== null ? $row['percent'] . '%' : '-' }}</td>
                    <td class="px-4 py-3 text-sm font-semibold">{{ $row['absent'] }} / {{ $row['allowed'] }}</td>
                    <td class="px-4 py-3 text-sm">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $stateBadge[$row['state']][1] }}">
                            {{ $stateBadge[$row['state']][0] }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No students are enrolled in this subject.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>