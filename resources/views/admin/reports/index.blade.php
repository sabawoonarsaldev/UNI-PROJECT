<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Reports
        </h2>
    </x-slot>

    @php
        $selectedDepartment = request('department_id') ? $departments->firstWhere('id', (int) request('department_id'))?->name : null;
        $selectedFaculty = request('faculty_id') ? $faculties->firstWhere('id', (int) request('faculty_id'))?->name : null;
        $scopeText = $selectedDepartment
            ? ($selectedFaculty . ' / ' . $selectedDepartment)
            : ($selectedFaculty ?: 'All faculties together');
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-center gap-2 text-sm">
                @include('admin.partials.faculty-department')

                @if (request('faculty_id') || request('department_id'))
                    <a href="{{ route('admin.reports.index') }}" class="text-gray-600 hover:underline">Reset</a>
                @endif
            </form>

            <div class="text-sm text-gray-600">Showing: <span class="font-semibold text-gray-900">{{ $scopeText }}</span></div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Grades entered</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $summary['total'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Average marks</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $summary['average'] ?? '-' }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Pass rate</div>
                    <div class="text-3xl font-semibold text-gray-900">
                        {{ $summary['pass_rate'] !== null ? $summary['pass_rate'] . '%' : '-' }}
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Failed subjects</div>
                    <div class="text-3xl font-semibold text-red-600">{{ $summary['failed'] }}</div>
                </div>
            </div>

            {{-- Every faculty separately, and all of them together --}}
            <x-panel title="By faculty">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Departments</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Classes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Students</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Teachers</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Grades</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Average</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Pass rate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Failed</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($byFaculty as $row)
                                <tr class="{{ request('faculty_id') == $row['faculty']->id ? 'bg-indigo-50' : '' }}">
                                    <td class="px-4 py-3 text-sm font-medium">
                                        <a href="{{ route('admin.reports.index', ['faculty_id' => $row['faculty']->id]) }}"
                                           class="text-indigo-600 hover:underline">{{ $row['faculty']->name }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['departments'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['classes'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['students'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['teachers'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['graded'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['average'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $row['pass_rate'] !== null ? $row['pass_rate'] . '%' : '-' }}</td>
                                    <td class="px-4 py-3 text-sm font-semibold text-red-600">{{ $row['failed'] }}</td>
                                </tr>
                            @endforeach

                            <tr class="bg-gray-50 font-semibold">
                                <td class="px-4 py-3 text-sm text-gray-900">All faculties (total)</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['departments'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['classes'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['students'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['teachers'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['graded'] }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['average'] ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $overall['pass_rate'] !== null ? $overall['pass_rate'] . '%' : '-' }}</td>
                                <td class="px-4 py-3 text-sm text-red-600">{{ $overall['failed'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </x-panel>

            <x-panel :title="'Failed subjects (marks below ' . \App\Models\Grade::PASS_MARK . ')'">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty / Department</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Semester</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Marks</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($failing as $grade)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $grade->student->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $grade->student->roll_number ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $grade->student->department?->faculty?->name ?? '-' }} / {{ $grade->student->department?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $grade->subject->code }} - {{ $grade->subject->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $grade->semester->name }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-red-600">{{ (float) $grade->marks }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">No failed subjects.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3">
                    {{ $failing->links() }}
                </div>
            </x-panel>

            <x-panel title="Top students by CGPA">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Rank</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty / Department</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">CGPA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($topStudents as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['student']->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">{{ $item['student']->roll_number ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $item['student']->department?->faculty?->name ?? '-' }} / {{ $item['student']->department?->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ number_format($item['cgpa'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">No graded students yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>
    </div>
</x-app-layout>