<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Subjects
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Semester</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($subjects as $subject)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $subject->code }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ $subject->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->department->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $subject->semester->name }}
                                    @unless ($subject->semester->is_active)
                                        <span class="ms-1 px-2 py-0.5 text-xs rounded bg-gray-200 text-gray-700">Closed</span>
                                    @endunless
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->enrollments_count }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <a href="{{ route('teacher.subjects.show', $subject) }}"
                                       class="text-indigo-600 hover:underline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No subjects are assigned to you yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>