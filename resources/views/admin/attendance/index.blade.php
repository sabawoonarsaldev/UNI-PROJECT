<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Attendance</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <p class="mb-4 text-sm text-gray-600">
                Choose faculty, department, class and semester, then open a subject to see its attendance
                and to lock the days that the teacher has taken.
            </p>

            @include('admin.partials.class-filters', ['action' => route('admin.attendance.index'), 'withFaculty' => true])

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days taken / planned</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Locked days</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Warning</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Barred</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($subjects as $subject)
                            @php
                                $rows = $subject->attendance['rows'];
                                $plan = $subject->attendance['plan'];
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $subject->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $subject->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->semester->classGroup?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->semester->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->teacher?->name ?? 'Not assigned' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->attendance['held'] }} / {{ $plan['planned'] }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->locked_count }} / {{ $subject->attendance['held'] }}</td>
                                <td class="px-6 py-4 text-sm text-amber-700">{{ $rows->where('state', 'warning')->count() }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-red-600">{{ $rows->where('state', 'barred')->count() }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <a href="{{ route('admin.attendance.show', $subject) }}" class="text-indigo-600 hover:underline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-8 text-center text-gray-500">No subjects found for this selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $subjects->links() }}
            </div>
        </div>
    </div>
</x-app-layout>