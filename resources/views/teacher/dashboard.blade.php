<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Teacher Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-800">Welcome, {{ auth()->user()->name }}.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('teacher.subjects.index') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">My Subjects</a>
                    <a href="{{ route('teacher.schedule') }}"
                       class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">My Schedule</a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Subjects</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $subjects->count() }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Students</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $studentsCount }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Classes today</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $todayClasses->count() }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                    Today's classes ({{ now()->format('l') }})
                </div>
                <ul class="divide-y divide-gray-200">
                    @forelse ($todayClasses as $class)
                        <li class="px-6 py-4 flex justify-between text-sm">
                            <span class="text-gray-900">{{ $class->subject->code }} - {{ $class->subject->name }}</span>
                            <span class="text-gray-500">
                                {{ substr($class->start_time, 0, 5) }} - {{ substr($class->end_time, 0, 5) }}
                                @if ($class->room) | Room {{ $class->room }} @endif
                            </span>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-500 text-sm">No classes today.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>