<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Semesters
            </h2>
            <a href="{{ route('admin.semesters.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                + Add Semester
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subjects</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($semesters as $semester)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $semester->department->name }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $semester->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $semester->type?->name ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $semester->year ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $semester->subjects_count }}</td>
                                <td class="px-6 py-4 text-sm">
                                    @if ($semester->is_active)
                                        <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Active</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">Closed</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right space-x-2">
                                    <a href="{{ route('admin.semesters.edit', $semester) }}"
                                       class="text-indigo-600 hover:underline">Edit</a>

                                    <form action="{{ route('admin.semesters.destroy', $semester) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Delete this semester and its subjects?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No semesters yet. Click "Add Semester" to create the first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>