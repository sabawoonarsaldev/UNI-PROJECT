<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">My Students' Projects</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('teacher.projects.index') }}" class="flex items-center gap-2 text-sm">
                <select name="status" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All students</option>
                    <option value="submitted" @selected(request('status') === 'submitted')>Waiting for my review</option>
                    <option value="revision" @selected(request('status') === 'revision')>Revision requested</option>
                    <option value="supervisor_approved" @selected(request('status') === 'supervisor_approved')>Waiting for admin approval</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    <option value="none" @selected(request('status') === 'none')>Project not started</option>
                </select>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Project</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Last activity</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($students as $student)
                            <tr>
                                <td class="px-6 py-4 text-sm">
                                    <div class="font-medium text-gray-900">{{ $student->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $student->roll_number ?? '-' }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $student->classGroup?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="max-w-xs truncate">{{ $student->project?->title ?? 'Not started yet' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($student->project)
                                        <x-project-status :status="$student->project->status" />
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $student->project?->updated_at->diffForHumans() ?? '-' }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    @if ($student->project)
                                        <a href="{{ route('projects.show', $student->project) }}" class="text-indigo-600 hover:underline">Open</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No students are assigned to you as supervisor yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>