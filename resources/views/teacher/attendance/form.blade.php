<x-app-layout>
    @php
        $isEdit = (bool) $attendanceSession;
        $readonly = $isEdit && ($attendanceSession->isLocked() || ! $subject->semester->is_active);
        $action = $isEdit
            ? route('teacher.attendance.update', $attendanceSession)
            : route('teacher.attendance.store', $subject);
        $labels = \App\Models\AttendanceRecord::STATUSES;
    @endphp

    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $isEdit ? 'Attendance of ' . $date : 'Take attendance' }}: {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('teacher.attendance.show', $subject) }}" class="text-sm text-gray-600 hover:underline">&larr; Back</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if ($readonly)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700">
                    This day cannot be changed
                    ({{ $attendanceSession->isLocked() ? 'it is locked by the administration' : 'the semester is closed' }}).
                </div>
            @endif

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">

                @if ($readonly)
                    <div class="px-6 py-4 text-sm text-gray-700">Date: <span class="font-semibold">{{ $date }}</span></div>
                @else
                    <form method="POST" action="{{ $action }}">
                        @csrf
                        @if ($isEdit)
                            @method('PUT')
                        @endif

                        <div class="space-y-2 border-b border-gray-100 p-6">
                            <x-input-label for="date" value="Date" />
                            <input type="date" id="date" name="date" value="{{ old('date', $date) }}" required
                                   @if ($minDate) min="{{ $minDate }}" @endif max="{{ $maxDate }}"
                                   class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('date')" class="mt-1" />
                            <p class="text-xs text-gray-500">
                                Lesson days of this subject: {{ $scheduledDays->implode(', ') ?: 'no timetable yet' }}.
                                Weekly holidays ({{ implode(', ', $holidays) ?: 'none' }}) and future dates cannot be used.
                            </p>
                        </div>
                @endif

                @unless ($readonly)
                    <div class="flex flex-wrap items-center gap-2 border-b border-gray-100 px-6 py-3 text-sm">
                        <span class="text-gray-500">For all students:</span>
                        <button type="button" data-mark="present" class="rounded-md border border-gray-300 px-3 py-1 hover:bg-gray-50">All present</button>
                        <button type="button" data-mark="absent" class="rounded-md border border-gray-300 px-3 py-1 hover:bg-gray-50">All absent</button>
                        <button type="button" data-mark="" class="rounded-md border border-gray-300 px-3 py-1 hover:bg-gray-50">Clear (blank)</button>
                    </div>
                @endunless

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Attendance</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Reason (optional)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($enrollments as $enrollment)
                                @php
                                    $studentId = $enrollment->student_id;
                                    $saved = $records->get($studentId);
                                    $current = old('status.' . $studentId, $saved?->status ?? ($isEdit ? '' : 'present'));
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>

                                    @if ($readonly)
                                        <td class="px-4 py-3 text-sm">
                                            @if ($saved)
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $labels[$saved->status][1] }}">{{ $labels[$saved->status][0] }}</span>
                                            @else
                                                <span class="text-gray-400">Blank</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $saved?->note ?? '-' }}</td>
                                    @else
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex flex-wrap items-center gap-4">
                                                @foreach (['present' => 'Present', 'absent' => 'Absent', 'excused' => 'Excused', '' => 'Blank'] as $value => $label)
                                                    <label class="inline-flex items-center gap-1">
                                                        <input type="radio" name="status[{{ $studentId }}]" value="{{ $value }}"
                                                               class="text-indigo-600 focus:ring-indigo-500"
                                                               @checked((string) $current === (string) $value)>
                                                        <span>{{ $label }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="note[{{ $studentId }}]" maxlength="255"
                                                   value="{{ old('note.' . $studentId, $saved?->note) }}"
                                                   placeholder="For example: sick, urgent work"
                                                   class="w-56 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @unless ($readonly)
                        <div class="flex items-center gap-4 border-t border-gray-100 px-6 py-4">
                            <x-primary-button>Save attendance</x-primary-button>
                            <a href="{{ route('teacher.attendance.show', $subject) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                            <span class="text-xs text-gray-500">Blank = the student is not counted for this day.</span>
                        </div>
                    </form>
                @endunless
            </div>
        </div>
    </div>

    <script>
        // The buttons above the list mark all students at once
        document.querySelectorAll('button[data-mark]').forEach(button => {
            button.addEventListener('click', () => {
                const value = button.dataset.mark;
                document.querySelectorAll('input[type="radio"][value="' + value + '"]').forEach(radio => radio.checked = true);
            });
        });
    </script>
</x-app-layout>