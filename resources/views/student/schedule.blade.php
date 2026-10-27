<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Weekly Schedule - {{ $student->currentSemester?->name ?? 'No semester' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @foreach ($days as $day)
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 font-semibold {{ $day === now()->format('l') ? 'bg-indigo-600 text-white' : 'bg-gray-50 text-gray-800' }}">
                        {{ $day }}
                    </div>

                    <ul class="divide-y divide-gray-200">
                        @forelse ($schedules->get($day, collect()) as $class)
                            <li class="px-6 py-3 flex justify-between text-sm">
                                <span class="text-gray-900">
                                    {{ $class->subject->code }} - {{ $class->subject->name }}
                                    <span class="text-gray-500">({{ $class->subject->teacher?->name ?? 'No teacher' }})</span>
                                </span>
                                <span class="text-gray-500">
                                    {{ substr($class->start_time, 0, 5) }} - {{ substr($class->end_time, 0, 5) }}
                                    @if ($class->room) | Room {{ $class->room }} @endif
                                </span>
                            </li>
                        @empty
                            <li class="px-6 py-3 text-sm text-gray-400">No classes</li>
                        @endforelse
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>