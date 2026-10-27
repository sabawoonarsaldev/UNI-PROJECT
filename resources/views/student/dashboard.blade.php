<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Student Dashboard</h2>
    </x-slot>

    @php($nowTime = now()->format('H:i:s'))

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-2xl bg-indigo-600 p-6 text-white shadow-sm">
                <h3 class="text-xl font-semibold">Welcome, {{ $student->name }}</h3>
                <p class="mt-1 text-sm text-indigo-100">
                    {{ $student->department?->name ?? 'No department' }}
                    | Roll No.: {{ $student->roll_number ?? '-' }}
                    | {{ $student->currentSemester?->name ?? 'No semester assigned' }}
                </p>
            </div>

            @unless ($student->current_semester_id)
                <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                    No semester has been assigned to you yet. Please contact the administration.
                </div>
            @endunless

            @if ($failedCount > 0)
                <div class="flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-800">
                    <x-icon name="exclamation-triangle" class="h-5 w-5" />
                    You have {{ $failedCount }} failed subject(s). Check "Grades & Transcript" for details.
                </div>
            @endif

            @if ($unreadMessages > 0)
                <a href="{{ route('messages.index') }}"
                   class="flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-900 hover:bg-amber-100">
                    <span class="flex items-center gap-3">
                        <x-icon name="envelope" class="h-5 w-5" />
                        You have {{ $unreadMessages }} unread message(s).
                    </span>
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            @endif

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <x-stat-card label="CGPA" :value="$cgpa !== null ? number_format($cgpa, 2) : '-'" icon="chart-bar" color="indigo"
                             hint="Out of 4.0" :href="route('student.grades')" />
                <x-stat-card label="Credits passed" :value="$passedCredits" icon="check-circle" color="green" />
                <x-stat-card label="Subjects this semester" :value="$enrollments->count()" icon="book-open" color="blue"
                             :href="route('student.subjects')" />
                <x-stat-card label="Classes today" :value="$todayClasses->count()" icon="clock" color="amber"
                             :href="route('student.schedule')" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Current semester results --}}
                <x-panel title="Current semester" class="lg:col-span-2">
                    <x-slot name="action">
                        <a href="{{ route('student.grades') }}" class="font-medium text-indigo-600 hover:underline">Full transcript</a>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Teacher</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Credits</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Marks</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($enrollments as $enrollment)
                                    @php($subject = $enrollment->subject)
                                    @php($grade = $currentGrades->get($subject->id))
                                    <tr>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="font-medium text-gray-900">{{ $subject->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $subject->code }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subject->teacher?->name ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $subject->credit_hours }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $grade ? (float) $grade->marks : '-' }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            @if ($grade)
                                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold
                                                    {{ $grade->marks < \App\Models\Grade::PASS_MARK ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $grade->grade_letter }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">Pending</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                            You are not enrolled in any subject this semester.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-panel>

                <div class="space-y-6">

                    {{-- Today's classes --}}
                    <x-panel title="Today's classes">
                        <x-slot name="action">
                            <span class="text-gray-500">{{ now()->format('l') }}</span>
                        </x-slot>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($todayClasses as $class)
                                @php($isNow = $class->start_time <= $nowTime && $class->end_time > $nowTime)
                                <li class="px-6 py-3 text-sm">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-medium text-gray-900">{{ $class->subject->name }}</span>
                                        @if ($isNow)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Now</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ substr($class->start_time, 0, 5) }} - {{ substr($class->end_time, 0, 5) }}
                                        @if ($class->room) | Room {{ $class->room }} @endif
                                        | {{ $class->subject->teacher?->name ?? 'No teacher' }}
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-sm text-gray-500">No classes today.</li>
                            @endforelse
                        </ul>
                    </x-panel>

                    {{-- GPA per semester --}}
                    <x-panel title="GPA by semester">
                        @if ($semesterGpas->isNotEmpty())
                            <ul class="space-y-3 p-6">
                                @foreach ($semesterGpas as $item)
                                    <li class="text-sm">
                                        <div class="mb-1 flex justify-between text-gray-600">
                                            <span>{{ $item['name'] }}</span>
                                            <span class="font-medium text-gray-900">{{ number_format($item['gpa'], 2) }}</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full bg-indigo-500"
                                                 style="width: {{ round($item['gpa'] / 4 * 100) }}%"></div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="px-6 py-8 text-center text-sm text-gray-500">No grades yet.</div>
                        @endif
                    </x-panel>
                </div>
            </div>

            {{-- Announcements --}}
            <x-panel title="Announcements">
                <x-slot name="action">
                    <a href="{{ route('announcements.index') }}" class="font-medium text-indigo-600 hover:underline">View all</a>
                </x-slot>

                <ul class="divide-y divide-gray-100">
                    @forelse ($announcements as $announcement)
                        <li class="px-6 py-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="font-medium text-gray-900">{{ $announcement->title }}</span>
                                <span class="shrink-0 text-xs text-gray-500">{{ $announcement->created_at->format('Y-m-d') }}</span>
                            </div>
                            <p class="mt-0.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($announcement->body, 140) }}</p>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-500">There are no announcements for you right now.</li>
                    @endforelse
                </ul>
            </x-panel>
        </div>
    </div>
</x-app-layout>