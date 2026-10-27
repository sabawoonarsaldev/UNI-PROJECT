<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Reports
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-center gap-2 text-sm">
                <select name="department_id" onchange="this.form.submit()"
                        class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </form>

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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                    Failed subjects (marks below {{ \App\Models\Grade::PASS_MARK }})
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($failing as $grade)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $grade->student->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $grade->student->roll_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $grade->student->department?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $grade->subject->code }} - {{ $grade->subject->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $grade->semester->name }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-red-600">{{ (float) $grade->marks }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    No failed subjects.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                <div class="px-6 py-3">
                    {{ $failing->links() }}
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                    Top students by CGPA
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CGPA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($topStudents as $item)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item['student']->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item['student']->roll_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $item['student']->department?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ number_format($item['cgpa'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500 text-sm">
                                    No graded students yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>