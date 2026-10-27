<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Subjects
            </h2>
            <a href="{{ route('admin.subjects.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                + Add Subject
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <form method="GET" action="{{ route('admin.subjects.index') }}" class="mb-4 flex items-center gap-2 text-sm">
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($subjects as $subject)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $subject->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $subject->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->semester->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->teacher?->name ?? 'Not assigned' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->credit_hours }}</td>
                                <td class="px-6 py-4 text-sm text-right space-x-2">
                                    <a href="{{ route('admin.subjects.edit', $subject) }}"
                                       class="text-indigo-600 hover:underline">Edit</a>

                                    <form action="{{ route('admin.subjects.destroy', $subject) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('Delete this subject? Its enrollments and grades will also be deleted.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                    No subjects found. Click "Add Subject" to create the first one.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $subjects->links() }}
            </div>
        </div>
    </div>
</x-app-layout>