<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('admin.enrollments.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back to subjects
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            @if (session('success'))
                <div class="p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @error('student_ids')
                <div class="p-4 bg-red-100 text-red-800 rounded-md">Select at least one student.</div>
            @enderror

            <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-600">
                <span class="font-medium">Department:</span> {{ $subject->department->name }}
                &nbsp;|&nbsp;
                <span class="font-medium">Semester:</span> {{ $subject->semester->name }}
                &nbsp;|&nbsp;
                <span class="font-medium">Teacher:</span> {{ $subject->teacher?->name ?? 'Not assigned' }}
            </div>

            {{-- Enrolled students --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 font-semibold text-gray-800">
                    Enrolled Students ({{ $enrollments->count() }})
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($enrollments as $enrollment)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $enrollment->student->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment->student->roll_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $enrollment->student->email }}</td>
                                <td class="px-6 py-4 text-sm text-right">
                                    <form action="{{ route('admin.enrollments.destroy', $enrollment) }}" method="POST"
                                          class="inline"
                                            onsubmit="return confirm('Remove this student from the subject? Their grade in this subject will also be deleted.');"                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                    No students enrolled yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Eligible students --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <span class="font-semibold text-gray-800">
                        Students who can be enrolled ({{ $eligibleStudents->count() }})
                    </span>

                    @if ($eligibleStudents->isNotEmpty())
                        <form action="{{ route('admin.enrollments.enroll-all', $subject) }}" method="POST"
                              onsubmit="return confirm('Enroll all {{ $eligibleStudents->count() }} students?');">
                            @csrf
                            <button type="submit"
                                    class="px-3 py-1 text-sm bg-gray-800 text-white rounded-md hover:bg-gray-700">
                                Enroll all
                            </button>
                        </form>
                    @endif
                </div>

                @if ($eligibleStudents->isEmpty())
                    <div class="px-6 py-8 text-center text-gray-500 text-sm">
                        No students available. A student appears here only when the role is Student and the
                        department and current semester are the same as this subject's.
                    </div>
                @else
                    <form action="{{ route('admin.enrollments.store', $subject) }}" method="POST">
                        @csrf
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 w-10">
                                        <input type="checkbox" id="select-all"
                                               class="rounded border-gray-300 text-indigo-600">
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach ($eligibleStudents as $student)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                                   class="student-checkbox rounded border-gray-300 text-indigo-600">
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $student->roll_number ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $student->email }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div class="px-6 py-4 border-t border-gray-200">
                            <x-primary-button>Enroll selected</x-primary-button>
                        </div>
                    </form>

                    <script>
                        document.getElementById('select-all').addEventListener('change', function () {
                            document.querySelectorAll('.student-checkbox').forEach(box => box.checked = this.checked);
                        });
                    </script>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>