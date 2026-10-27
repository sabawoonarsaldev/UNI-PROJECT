<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin Dashboard</h2>
    </x-slot>

    @php
        $roleBadge = [
            'admin' => 'bg-purple-100 text-purple-700',
            'teacher' => 'bg-blue-100 text-blue-700',
            'student' => 'bg-emerald-100 text-emerald-700',
        ];
        $barColors = [
            'A+' => 'bg-emerald-500', 'A' => 'bg-emerald-400',
            'B+' => 'bg-blue-500', 'B' => 'bg-blue-400',
            'C+' => 'bg-amber-500', 'C' => 'bg-amber-400',
            'D' => 'bg-orange-500', 'F' => 'bg-red-500',
        ];
        $totalGrades = $gradeDistribution->sum();
        $maxStudents = max(1, $departments->max('students_count') ?? 0);

        $alerts = [];
        if ($warnings['subjects_without_teacher'] > 0) {
            $alerts[] = [$warnings['subjects_without_teacher'] . ' subject(s) have no teacher', route('admin.subjects.index'), 'Assign teachers'];
        }
        if ($warnings['subjects_without_schedule'] > 0) {
            $alerts[] = [$warnings['subjects_without_schedule'] . ' subject(s) have no class schedule', route('admin.schedules.create'), 'Add schedule'];
        }
        if ($warnings['students_without_class'] > 0) {
            $alerts[] = [$warnings['students_without_class'] . ' student(s) are not assigned to a class', route('admin.users.index', ['role' => 'student']), 'Open students'];
        }
        if ($unreadMessages > 0) {
            $alerts[] = [$unreadMessages . ' unread message(s)', route('messages.index'), 'Read messages'];
        }
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- Welcome banner with the most common actions --}}
            <div class="rounded-2xl bg-indigo-600 p-6 text-white shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold">Welcome back, {{ auth()->user()->name }}</h3>
                        <p class="mt-1 text-sm text-indigo-100">Here is an overview of your university today.</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.users.create') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50">
                            <x-icon name="plus" class="h-4 w-4" /> Add User
                        </a>
                        <a href="{{ route('admin.subjects.create') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-400">
                            <x-icon name="plus" class="h-4 w-4" /> Add Subject
                        </a>
                        <a href="{{ route('admin.announcements.create') }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-400">
                            <x-icon name="plus" class="h-4 w-4" /> New Announcement
                        </a>
                    </div>
                </div>
            </div>

            {{-- Statistics --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <x-stat-card label="Students" :value="$stats['Students']" icon="users" color="blue"
                             :href="route('admin.users.index', ['role' => 'student'])" />
                <x-stat-card label="Teachers" :value="$stats['Teachers']" icon="academic-cap" color="purple"
                             :href="route('admin.users.index', ['role' => 'teacher'])" />
                <x-stat-card label="Subjects" :value="$stats['Subjects']" icon="book-open" color="amber"
                             :href="route('admin.subjects.index')" />
                <x-stat-card label="Faculties" :value="$stats['Faculties']" icon="building-library" color="indigo"
                             :href="route('admin.faculties.index')" />
                <x-stat-card label="Departments" :value="$stats['Departments']" icon="squares" color="cyan"
                             :href="route('admin.departments.index')" />
                <x-stat-card label="Enrollments" :value="$stats['Enrollments']" icon="user-plus" color="green"
                             :href="route('admin.enrollments.index')" />
            </div>

            {{-- Needs attention --}}
            <x-panel title="Needs attention">
                @if (count($alerts) > 0)
                    <ul class="divide-y divide-gray-100">
                        @foreach ($alerts as [$text, $url, $action])
                            <li class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                                <span class="flex items-center gap-3 text-gray-700">
                                    <x-icon name="exclamation-triangle" class="h-5 w-5 shrink-0 text-amber-500" />
                                    {{ $text }}
                                </span>
                                <a href="{{ $url }}" class="shrink-0 font-medium text-indigo-600 hover:underline">{{ $action }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="flex items-center gap-3 px-6 py-4 text-sm text-gray-600">
                        <x-icon name="check-circle" class="h-5 w-5 text-emerald-500" />
                        Everything is in order. Nothing needs your attention.
                    </div>
                @endif
            </x-panel>

            <div class="grid gap-6 lg:grid-cols-3">

                {{-- Departments overview --}}
                <x-panel title="Departments overview" class="lg:col-span-2">
                    <x-slot name="action">
                        <a href="{{ route('admin.departments.index') }}" class="font-medium text-indigo-600 hover:underline">Manage</a>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Teachers</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Subjects</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Students</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($departments as $department)
                                    <tr>
                                        <td class="px-6 py-4 text-sm">
                                            <div class="font-medium text-gray-900">{{ $department->name }}</div>
                                            <div class="text-xs text-gray-500">{{ $department->faculty->name }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $department->teachers_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">{{ $department->subjects_count }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <div class="flex items-center gap-3">
                                                <span class="w-6">{{ $department->students_count }}</span>
                                                <div class="h-2 w-24 rounded-full bg-gray-100">
                                                    <div class="h-2 rounded-full bg-indigo-500"
                                                         style="width: {{ round($department->students_count / $maxStudents * 100) }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No departments yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-panel>

                {{-- Grade distribution --}}
                <x-panel title="Grade distribution">
                    <x-slot name="action">
                        <a href="{{ route('admin.reports.index') }}" class="font-medium text-indigo-600 hover:underline">Reports</a>
                    </x-slot>

                    @if ($totalGrades > 0)
                        <ul class="space-y-3 p-6">
                            @foreach ($gradeDistribution as $letter => $count)
                                <li class="flex items-center gap-3 text-sm">
                                    <span class="w-8 font-medium text-gray-700">{{ $letter }}</span>
                                    <div class="h-2.5 flex-1 rounded-full bg-gray-100">
                                        <div class="h-2.5 rounded-full {{ $barColors[$letter] }}"
                                             style="width: {{ round($count / $totalGrades * 100) }}%"></div>
                                    </div>
                                    <span class="w-8 text-right text-gray-500">{{ $count }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="px-6 py-8 text-center text-sm text-gray-500">No grades have been entered yet.</div>
                    @endif
                </x-panel>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">

                {{-- Recently added users --}}
                <x-panel title="Recently added users">
                    <x-slot name="action">
                        <a href="{{ route('admin.users.index') }}" class="font-medium text-indigo-600 hover:underline">View all</a>
                    </x-slot>

                    <ul class="divide-y divide-gray-100">
                        @forelse ($recentUsers as $recentUser)
                            <li class="flex items-center justify-between gap-4 px-6 py-3 text-sm">
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-gray-900">{{ $recentUser->name }}</div>
                                    <div class="truncate text-xs text-gray-500">
                                        {{ $recentUser->department?->name ?? 'No department' }}
                                        | {{ $recentUser->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $roleBadge[$recentUser->role] ?? '' }}">
                                    {{ ucfirst($recentUser->role) }}
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-sm text-gray-500">No users yet.</li>
                        @endforelse
                    </ul>
                </x-panel>

                {{-- Latest announcements --}}
                <x-panel title="Latest announcements">
                    <x-slot name="action">
                        <a href="{{ route('admin.announcements.index') }}" class="font-medium text-indigo-600 hover:underline">View all</a>
                    </x-slot>

                    <ul class="divide-y divide-gray-100">
                        @forelse ($recentAnnouncements as $announcement)
                            <li class="px-6 py-3 text-sm">
                                <div class="flex justify-between gap-4">
                                    <span class="font-medium text-gray-900">{{ $announcement->title }}</span>
                                    <span class="shrink-0 text-xs text-gray-500">{{ $announcement->created_at->format('Y-m-d') }}</span>
                                </div>
                                <div class="mt-0.5 text-xs text-gray-500">
                                    {{ ['all' => 'Everyone', 'teacher' => 'Teachers', 'student' => 'Students'][$announcement->target_role] }}
                                </div>
                            </li>
                        @empty
                            <li class="px-6 py-8 text-center text-sm text-gray-500">No announcements yet.</li>
                        @endforelse
                    </ul>
                </x-panel>
            </div>
        </div>
    </div>
</x-app-layout>