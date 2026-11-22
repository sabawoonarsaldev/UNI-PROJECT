
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

            {{-- Results status: Added --}}
            @php
                $statusCounts = $rows->pluck('grade')->filter()->countBy('status');
                $draftCount = $statusCounts['draft'] ?? 0;
                $submittedCount = $statusCounts['submitted'] ?? 0;
                $publishedCount = $statusCounts['published'] ?? 0;
                $statusBadges = \App\Models\Grade::STATUSES;
            @endphp

            @if (session('error'))
                <div class="p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex flex-wrap items-center gap-3 text-sm">
                    <span class="font-semibold text-gray-800">Results status:</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadges['draft'][1] }}">{{ $draftCount }} draft</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadges['submitted'][1] }}">{{ $submittedCount }} waiting for admin</span>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadges['published'][1] }}">{{ $publishedCount }} published</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    @if ($draftCount + $submittedCount > 0)
                        <form method="POST" action="{{ route('admin.grades.publish', $subject) }}"
                              onsubmit="return confirm('Publish all grades of this subject? The students will see them.');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm bg-emerald-600 text-white rounded-md hover:bg-emerald-700">Publish all grades</button>
                        </form>
                    @endif

                    @if ($submittedCount > 0)
                        <form method="POST" action="{{ route('admin.grades.return', $subject) }}"
                              onsubmit="return confirm('Return the submitted grades to the teacher?');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">Return to teacher</button>
                        </form>
                    @endif

                    @if ($publishedCount > 0)
                        <form method="POST" action="{{ route('admin.grades.unpublish', $subject) }}"
                              onsubmit="return confirm('Unpublish all grades of this subject? The students will not see them any more.');">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 text-sm border border-red-300 text-red-700 rounded-md hover:bg-red-50">Unpublish all grades</button>
                        </form>
                    @endif
                </div>

                <p class="text-xs text-gray-500">
                    You can still correct a grade in the table below at any time. A correction of a published grade stays published
                    and is written into the audit log.
                </p>
            </div>
            {{-- End Results status --}}

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
