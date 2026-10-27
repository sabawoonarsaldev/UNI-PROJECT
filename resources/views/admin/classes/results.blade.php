<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Results: {{ $classGroup->name }}</h2>
            <a href="{{ route('admin.classes.show', $classGroup) }}" class="text-sm text-gray-600 hover:underline">&larr; Back to class</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            {{-- GPA of every semester and the final result --}}
            <x-panel title="Summary: GPA of each semester and final result">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                @foreach ($classGroup->semesters as $semester)
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">S{{ $semester->number }}</th>
                                @endforeach
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">CGPA</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Percentage</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Failed subjects</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rows as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['student']->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $row['student']->roll_number ?? '-' }}</td>
                                    @foreach ($classGroup->semesters as $semester)
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $row['semesters'][$semester->id] !== null ? number_format($row['semesters'][$semester->id], 2) : '-' }}
                                        </td>
                                    @endforeach
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                        {{ $row['cgpa'] !== null ? number_format($row['cgpa'], 2) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $row['average'] !== null ? number_format($row['average'], 1) . '%' : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        @if ($row['failed'] > 0)
                                            <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">{{ $row['failed'] }}</span>
                                        @else
                                            <span class="text-gray-400">0</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 5 + $classGroup->semesters->count() }}" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No students in this class yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>

            {{-- Subject by subject marks of one semester --}}
            @if ($selected)
                <x-panel :title="'Marks of ' . $selected->name">
                    <x-slot name="action">
                        <form method="GET" action="{{ route('admin.classes.results', $classGroup) }}">
                            <select name="semester" onchange="this.form.submit()"
                                    class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($classGroup->semesters as $semester)
                                    <option value="{{ $semester->id }}" @selected($selected->id === $semester->id)>{{ $semester->name }}</option>
                                @endforeach
                            </select>
                        </form>
                    </x-slot>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                    @foreach ($detailSubjects as $subject)
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500" title="{{ $subject->name }}">
                                            {{ $subject->code }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['student']->name }}</td>
                                        @foreach ($detailSubjects as $subject)
                                            @php($grade = $detailGrades->get($row['student']->id)?->get($subject->id))
                                            <td class="px-4 py-3 text-sm {{ $grade && $grade->marks < \App\Models\Grade::PASS_MARK ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                                {{ $grade ? (float) $grade->marks : '-' }}
                                            </td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 1 + $detailSubjects->count() }}" class="px-6 py-8 text-center text-sm text-gray-500">
                                            No students in this class yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <p class="px-6 py-3 text-xs text-gray-400">
                        Marks below {{ \App\Models\Grade::PASS_MARK }} are failed and shown in red.
                    </p>
                </x-panel>
            @endif
        </div>
    </div>
</x-app-layout>