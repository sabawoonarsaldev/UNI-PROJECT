<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Departments
            </h2>
            <a href="{{ route('admin.departments.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                + Add Department
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.departments.index') }}" class="mb-4 flex items-center gap-2 text-sm">
                <select name="faculty_id" onchange="this.form.submit()"
                        class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All faculties</option>
                    @foreach ($faculties as $faculty)
                        <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                    @endforeach
                </select>

                @if (request('faculty_id'))
                    <a href="{{ route('admin.departments.index') }}" class="text-gray-600 hover:underline">Reset</a>
                @endif

                <span class="ms-auto text-gray-500">{{ $departments->count() }} department(s)</span>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($departments as $department)
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $department->faculty->name }}</td>
                                <td class="px-6 py-4 text-sm text-right space-x-2">
                                    <a href="{{ route('admin.departments.edit', $department) }}"
                                       class="text-indigo-600 hover:underline">Edit</a>

                                    <form action="{{ route('admin.departments.destroy', $department) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Delete this department and its semesters and subjects?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No departments found for this selection.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>