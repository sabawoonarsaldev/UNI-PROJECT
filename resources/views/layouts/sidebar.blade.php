@php
    $user = auth()->user();
    $unread = $user->unreadMessagesCount();

    $menus = [
            'admin' => [
                ['title' => null, 'items' => [
                    ['Dashboard', 'admin.dashboard', 'admin.dashboard', 'home'],
                ]],
                ['title' => 'Academic', 'items' => [
                    ['Enrollments', 'admin.enrollments.index', 'admin.enrollments.*', 'user-plus'],
                    ['Schedules', 'admin.schedules.index', 'admin.schedules.*', 'calendar'],
                    ['Grades', 'admin.grades.index', 'admin.grades.*', 'pencil-square'],
                    ['Reports', 'admin.reports.index', 'admin.reports.*', 'chart-bar'],
                ]],
                ['title' => 'People', 'items' => [
                    ['Users', 'admin.users.index', 'admin.users.*', 'users'],
                ]],
                ['title' => 'Communication', 'items' => [
                    ['Announcements', 'admin.announcements.index', 'admin.announcements.*', 'bell'],
                    ['Messages', 'messages.index', 'messages.*', 'envelope'],
                ]],
                ['title' => 'Configuration', 'items' => [
                    ['Overview', 'admin.configuration.index', 'admin.configuration.*', 'adjustments'],
                    ['Faculties', 'admin.faculties.index', 'admin.faculties.*', 'building-library'],
                    ['Departments', 'admin.departments.index', 'admin.departments.*', 'squares'],
                    ['Classes', 'admin.classes.index', 'admin.classes.*', 'academic-cap'],
                    ['Subjects', 'admin.subjects.index', 'admin.subjects.*', 'book-open'],
                    ['Academic Terms', 'admin.semester-types.index', 'admin.semester-types.*', 'calendar'],
                ]],
            ],
        'teacher' => [
            ['title' => null, 'items' => [
                ['Dashboard', 'teacher.dashboard', 'teacher.dashboard', 'home'],
            ]],
            ['title' => 'Teaching', 'items' => [
                ['My Subjects', 'teacher.subjects.index', ['teacher.subjects.*', 'teacher.grades.*'], 'book-open'],
                ['My Schedule', 'teacher.schedule', 'teacher.schedule', 'calendar'],
            ]],
            ['title' => 'Communication', 'items' => [
                ['Announcements', 'announcements.index', 'announcements.*', 'bell'],
                ['Messages', 'messages.index', 'messages.*', 'envelope'],
            ]],
        ],
        'student' => [
            ['title' => null, 'items' => [
                ['Dashboard', 'student.dashboard', 'student.dashboard', 'home'],
            ]],
            ['title' => 'Academics', 'items' => [
                ['My Subjects', 'student.subjects', 'student.subjects', 'book-open'],
                ['Grades & Transcript', 'student.grades', 'student.grades', 'document-text'],
                ['My Schedule', 'student.schedule', 'student.schedule', 'calendar'],
            ]],
            ['title' => 'Communication', 'items' => [
                ['Announcements', 'announcements.index', 'announcements.*', 'bell'],
                ['Messages', 'messages.index', 'messages.*', 'envelope'],
            ]],
        ],
    ];

    $menu = $menus[$user->role] ?? [];
@endphp

{{-- Dark overlay behind the sidebar on small screens --}}
<div x-show="sidebarOpen" x-cloak x-transition.opacity @click="sidebarOpen = false"
     class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 transform flex-col bg-gray-900 text-gray-300 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">

    <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-800 px-5">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <x-icon name="academic-cap" class="h-7 w-7 text-indigo-400" />
            <span class="text-lg font-semibold text-white">University System</span>
        </a>
        <button @click="sidebarOpen = false" class="text-gray-400 hover:text-white lg:hidden">
            <x-icon name="x-mark" class="h-6 w-6" />
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($menu as $section)
            <div>
                @if ($section['title'])
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        {{ $section['title'] }}
                    </p>
                @endif

                <ul class="space-y-1">
                    @foreach ($section['items'] as [$label, $routeName, $pattern, $icon])
                        @php($active = request()->routeIs(...(array) $pattern))
                        <li>
                            <a href="{{ route($routeName) }}"
                               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition
                                      {{ $active ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                                <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
                                <span class="flex-1">{{ $label }}</span>

                                @if ($routeName === 'messages.index' && $unread > 0)
                                    <span class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-semibold text-white">{{ $unread }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</aside>