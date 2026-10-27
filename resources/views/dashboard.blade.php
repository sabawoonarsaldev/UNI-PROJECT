<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Teacher Dashboard</h2>
    </x-slot>

    @php($nowTime = now()->format('H:i:s'))

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div class="rounded-2xl bg-indigo-600 p-6 text-white shadow-sm">
                <h3 class="text-xl font-semibold">Welcome, {{ auth()->user()->name }}</h3>
                <p class="mt-1 text-sm text-indigo-100">
                    @if ($todayClasses->count() > 0)
                        You have {{ $todayClasses->count() }} class(es) today.
                    @else
                        You have no classes today.
                    @endif
                </p>
            </div>

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
                <x-stat-card label="My subjects" :value="$subjects->count()" icon="book-open" color="indigo"
                             :href="route('teacher.subjects.index')" />
                <x-stat-card label="Students" :value="$studentsCount" icon="users" color="blue" />
                <x-stat-card label="Classes today" :value="$todayClasses->count()" icon="clock" color="amber"
                             :href="route('teacher.schedule')" />
                <x-stat-card label="Grading progress" :value="$progress . '%'" icon="pencil-square" color="green"
                             hint="Grades entered for enrolled students" />
            </div>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- My subjects --}}
                <x-panel title="My subjects" class="lg:col-span-2">
                    <x-slot name="action">
                        <a href="{{ route('teacher.subjects.index') }}" class="font-medium text-indigo-600 hover:underline">View all</a>
                    </x-slot>

                    @if ($subjects->isEmpty())
                        <div class="px-6 py-8 text-center text-sm text-gray-500">
                            No subjects are assigned to you yet.
                        </div>
                    @else
                        <div class="grid gap-4 p-6 sm:grid-cols-2">
                            @foreach ($subjects as $subject)
                                @php($pct = $subject->enrollments_count > 0 ? round($subject->grades_count / $subject->enrollments_count * 100) : 0)
                                <div class="rounded-lg border border-gray-200 p-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <div class="text-xs font-semibold text-indigo-600">{{ $subject->code }}</div>
                                            <div class="truncate font-medium text-gray-900">{{ $subject->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $subject->semester->name }}</div>
                                        </div>
                                        @unless ($subject->semester->is_active)
                                            <span class="shrink-0 rounded bg-gray-200 px-2 py-0.5 text-xs text-gray-700">Closed</span>
                                        @endunless
                                    </div>

                                    <div class="mt-4">
                                        <div class="mb-1 flex justify-between text-xs text-gray-500">
                                            <span>{{ $subject->enrollments_count }} student(s)</span>
                                            <span>{{ $subject->grades_count }} / {{ $subject->enrollments_count }} graded</span>
                                        </div>
                                        <div class="h-2 rounded-full bg-gray-100">
                                            <div class="h-2 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : 'bg-indigo-500' }}"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex gap-3 text-sm">
                                        <a href="{{ route('teacher.subjects.show', $subject) }}"
                                           class="font-medium text-indigo-600 hover:underline">Students</a>
                                        @if ($subject->semester->is_active && $subject->enrollments_count > 0)
                                            <a href="{{ route('teacher.grades.edit', $subject) }}"
                                               class="font-medium text-indigo-600 hover:underline">Enter grades</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
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
                                        <span class="font-medium text-gray-900">{{ $class->subject->code }}</span>
                                        @if ($isNow)
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700">Now</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ substr($class->start_time, 0, 5) }} - {{ substr($class->end_time, 0, 5) }}
                                        @if ($class->room) | Room {{ $class->room }} @endif
                                    </div>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-sm text-gray-500">No classes today.</li>
                            @endforelse
                        </ul>
                    </x-panel>

                    {{-- Announcements --}}
                    <x-panel title="Announcements">
                        <x-slot name="action">
                            <a href="{{ route('announcements.index') }}" class="font-medium text-indigo-600 hover:underline">View all</a>
                        </x-slot>

                        <ul class="divide-y divide-gray-100">
                            @forelse ($announcements as $announcement)
                                <li class="px-6 py-3 text-sm">
                                    <div class="flex justify-between gap-2">
                                        <span class="font-medium text-gray-900">{{ $announcement->title }}</span>
                                        <span class="shrink-0 text-xs text-gray-500">{{ $announcement->created_at->format('M d') }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($announcement->body, 80) }}</p>
                                </li>
                            @empty
                                <li class="px-6 py-8 text-center text-sm text-gray-500">No announcements.</li>
                            @endforelse
                        </ul>
                    </x-panel>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>