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

            <form method="GET" action="{{ route('admin.projects.index') }}" class="flex flex-wrap items-center gap-2 text-sm">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects" class="{{ $select }}">

                <select name="status" onchange="this.form.submit()" class="{{ $select }}">
                    <option value="">All statuses</option>
                    @foreach (['draft' => 'Draft', 'submitted' => 'Waiting for supervisor', 'revision' => 'Revision requested', 'supervisor_approved' => 'Waiting for admin approval', 'approved' => 'Approved'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="department_id" onchange="this.form.submit()" class="{{ $select }}">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                    @endforeach
                </select>

                <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-white hover:bg-gray-700">Search</button>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
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
                                <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">No projects found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>