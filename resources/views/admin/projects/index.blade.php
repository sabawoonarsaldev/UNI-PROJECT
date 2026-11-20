<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">All Projects</h2>
    </x-slot>

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500')

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            <form id="project-filter-form" method="GET" action="{{ route('admin.projects.index') }}"
                  class="flex flex-wrap items-center gap-2 text-sm">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects" class="{{ $select }}">

                <select name="status" class="{{ $select }}">
                    <option value="">All statuses</option>
                    @foreach (['draft' => 'Draft', 'submitted' => 'Waiting for supervisor', 'revision' => 'Revision requested', 'supervisor_approved' => 'Waiting for admin approval', 'approved' => 'Approved'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="department_id" data-resets="class_group_id,student_id" class="{{ $select }}">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>

                {{-- Appears after a department is chosen --}}
                @if (request('department_id'))
                    <select name="class_group_id" data-resets="student_id" class="{{ $select }}">
                        <option value="">All classes</option>
                        @foreach ($classes as $group)
                            <option value="{{ $group->id }}" @selected(request('class_group_id') == $group->id)>
                                {{ $group->name }}
                                ({{ $group->status === 'completed' ? 'completed' : 'now in semester ' . $group->current_semester_number }})
                            </option>
                        @endforeach
                    </select>
                @endif

                {{-- Appears after a class is chosen: the students of this class --}}
                @if (request('class_group_id'))
                    <select name="student_id" class="{{ $select }}">
                        <option value="">All students of the class</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(request('student_id') == $student->id)>
                                {{ $student->name }} ({{ $student->roll_number ?? '-' }})
                            </option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-white hover:bg-gray-700">Search</button>

                @if (request()->hasAny(['q', 'status', 'department_id', 'class_group_id', 'student_id']))
                    <a href="{{ route('admin.projects.index') }}" class="text-gray-600 hover:underline">Reset</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Supervisor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Files</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Updated</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($projects as $project)
                            <tr>
                                <td class="px-6 py-4 text-sm">
                                    <div class="max-w-xs truncate font-medium text-gray-900">{{ $project->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $project->department?->name ?? '-' }} | {{ $project->year }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $project->student->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $project->student->classGroup?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $project->supervisor?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $project->files_count }}</td>
                                <td class="px-6 py-4"><x-project-status :status="$project->status" /></td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $project->updated_at->diffForHumans() }}</td>
                                <td class="space-x-2 px-6 py-4 text-right text-sm">
                                    <a href="{{ route('projects.show', $project) }}" class="text-indigo-600 hover:underline">Open</a>

                                    <form action="{{ route('admin.projects.destroy', $project) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this project with all its files and messages?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No projects found for this selection.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $projects->links() }}
        </div>
    </div>

    <script>
        document.querySelectorAll('#project-filter-form select').forEach(select => {
            select.addEventListener('change', () => {
                // The dropdowns that depend on this one are cleared, so they never keep an old value
                (select.dataset.resets || '').split(',').filter(Boolean).forEach(name => {
                    const dependent = select.form.elements[name];
                    if (dependent) dependent.value = '';
                });

                select.form.submit();
            });
        });
    </script>
</x-app-layout>