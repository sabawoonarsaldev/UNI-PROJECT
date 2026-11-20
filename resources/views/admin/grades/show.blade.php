<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('admin.grades.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back to grades
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600 space-y-1">
                <div>
                    <span class="font-medium">Department:</span> {{ $subject->department->name }}
                    &nbsp;|&nbsp;
                    <span class="font-medium">Semester:</span> {{ $subject->semester->name }}
                    @unless ($subject->semester->is_active)
                        <span class="ms-1 px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">Closed</span>
                    @endunless
                    &nbsp;|&nbsp;
                    <span class="font-medium">Teacher:</span> {{ $subject->teacher?->name ?? 'Not assigned' }}
                </div>
                @unless ($subject->semester->is_active)
                    <div class="text-yellow-700">
                        This semester is closed. Teachers cannot change these grades, but you can.
                    </div>
                @endunless
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Graded</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['graded'] }} / {{ $stats['enrolled'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Average</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['average'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Highest</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['highest'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Lowest</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['lowest'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Pass rate</div>
                    <div class="text-2xl font-semibold text-gray-900">
                        {{ $stats['pass_rate'] !== null ? $stats['pass_rate'] . '%' : '-' }}
                    </div>
                </div>
            </div>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    @if ($rows->isEmpty())
        <div class="px-6 py-8 text-center text-gray-500 text-sm">
            No students are enrolled in this subject.
        </div>
    @else
        @include('partials.gradebook-form', [
            'rows' => $rows,
            'action' => route('admin.grades.update', $subject),
            'cancelUrl' => route('admin.grades.index'),
        ])
    @endif
</div>


        </div>
    </div>
</x-app-layout>