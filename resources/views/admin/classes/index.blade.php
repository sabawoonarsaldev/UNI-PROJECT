<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Classes</h2>
            <a href="{{ route('admin.classes.create') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                + Add Class
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.classes.index') }}" class="flex items-center gap-2 text-sm">
                <select name="department_id" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>
                            {{ $department->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Class</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Faculty</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Students</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($classes as $group)
                            @php
                                $total = \App\Models\ClassGroup::TOTAL_SEMESTERS;
                                $done = $group->status === 'completed' ? $total : $group->current_semester_number - 1;
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $group->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $group->department->faculty->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $group->students_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="mb-1 text-xs">
                                        @if ($group->status === 'completed')
                                            All {{ $total }} semesters done
                                        @else
                                            Semester {{ $group->current_semester_number }} of {{ $total }}
                                        @endif
                                    </div>
                                    <div class="h-2 w-32 rounded-full bg-gray-100">
                                        <div class="h-2 rounded-full bg-indigo-500" style="width: {{ round($done / $total * 100) }}%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($group->status === 'completed')
                                        <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700">Completed</span>
                                    @else
                                        <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800">Active</span>
                                    @endif
                                </td>
                                <td class="space-x-2 px-6 py-4 text-right text-sm">
                                    <a href="{{ route('admin.classes.show', $group) }}" class="text-indigo-600 hover:underline">Open</a>
                                    <a href="{{ route('admin.classes.results', $group) }}" class="text-indigo-600 hover:underline">Results</a>

                                    <form action="{{ route('admin.classes.destroy', $group) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete this class with its semesters and subjects?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
                                    No classes yet. Click "Add Class" to create the first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>