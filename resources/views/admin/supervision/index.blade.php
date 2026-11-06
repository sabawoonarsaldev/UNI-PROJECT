<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Supervision</h2>
    </x-slot>

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500')

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <p class="text-sm text-gray-600">
                Every student has one supervisor, and a teacher can supervise many students.
                Only teachers who have a login account can be chosen.
            </p>

            <x-panel title="Supervisors">
                <div class="grid gap-3 p-6 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($teachers as $teacher)
                        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 text-sm">
                            <div>
                                <div class="font-medium text-gray-900">{{ $teacher->name }}</div>
                                <div class="text-xs text-gray-500">{{ $teacher->department?->name ?? 'No department' }}</div>
                            </div>
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                                {{ $teacher->supervised_students_count }} student(s)
                            </span>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500">No teachers with a login account yet.</div>
                    @endforelse
                </div>
            </x-panel>

            <form method="GET" action="{{ route('admin.supervision.index') }}" class="flex flex-wrap items-center gap-2 text-sm">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search student or roll number" class="{{ $select }}">

                <select name="department_id" onchange="this.form.submit()" class="{{ $select }}">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>

                <label class="inline-flex items-center gap-2 text-gray-600">
                    <input type="checkbox" name="filter" value="unassigned" onchange="this.form.submit()"
                           class="rounded border-gray-300 text-indigo-600" @checked(request('filter') === 'unassigned')>
                    Without supervisor only
                </label>

                <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-white hover:bg-gray-700">Search</button>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Department / Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Supervisor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->roll_number ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $student->department?->name ?? '-' }}
                                    @if ($student->classGroup)
                                        <div class="text-xs">{{ $student->classGroup->name }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($student->project)
                                        <a href="{{ route('projects.show', $student->project) }}" class="block max-w-xs truncate text-indigo-600 hover:underline">
                                            {{ $student->project->title }}
                                        </a>
                                        <x-project-status :status="$student->project->status" class="mt-1" />
                                    @else
                                        <span class="text-gray-400">Not started</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="{{ route('admin.supervision.update', $student) }}">
                                        @csrf
                                        @method('PUT')
                                        <select name="supervisor_id" onchange="this.form.submit()" class="{{ $select }}">
                                            <option value="">-- No supervisor --</option>
                                            @foreach ($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" @selected($student->supervisor_id === $teacher->id)>
                                                    {{ $teacher->name }} ({{ $teacher->supervised_students_count }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No students found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $students->links() }}
        </div>
    </div>
</x-app-layout>