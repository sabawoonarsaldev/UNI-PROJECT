<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $classGroup->name }}</h2>
            <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All classes</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @error('student_ids')
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">Select at least one student.</div>
            @enderror

            {{-- Header with the 8 semester steps --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Faculty:</span> {{ $classGroup->department->faculty->name }}
                        &nbsp;|&nbsp;
                        <span class="font-medium">Department:</span> {{ $classGroup->department->name }}
                        &nbsp;|&nbsp;
                        <span class="font-medium">Students:</span> {{ $students->count() }}
                    </div>

                    <div class="flex gap-2">
                        <a href="{{ route('admin.classes.results', $classGroup) }}"
                           class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Results
                        </a>

                        <form action="{{ route('admin.classes.process-results', $classGroup) }}" method="POST"
                            onsubmit="return confirm('Process the results? Failed subjects will move to the next chance.');">
                            @csrf
                            <button type="submit"
                                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Process results
                            </button>
                        </form>

                        @if ($classGroup->status === 'active')
                            <form action="{{ route('admin.classes.complete-semester', $classGroup) }}" method="POST"
                                  onsubmit="return confirm('Complete {{ $currentSemester->name }}? All grades of this semester will be locked and the class will move to the next semester.');">
                                @csrf
                                <button type="submit"
                                        class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Complete semester
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex gap-1.5">
                    @for ($i = 1; $i <= \App\Models\ClassGroup::TOTAL_SEMESTERS; $i++)
                        @php
                            if ($classGroup->status === 'completed' || $i < $classGroup->current_semester_number) {
                                $style = 'bg-emerald-100 text-emerald-700';
                            } elseif ($i === $classGroup->current_semester_number) {
                                $style = 'bg-indigo-600 text-white';
                            } else {
                                $style = 'bg-gray-100 text-gray-400';
                            }
                        @endphp
                        <div class="flex-1 rounded-md py-2 text-center text-xs font-semibold {{ $style }}">S{{ $i }}</div>
                    @endfor
                </div>

                @if ($classGroup->status === 'completed')
                    <p class="mt-3 text-sm text-gray-500">This class has finished all semesters.</p>
                @endif
            </div>

            {{-- Current semester and its subjects --}}
            @if ($currentSemester)
                <x-panel :title="'Current semester: ' . $currentSemester->name">
                    @if ($classGroup->status === 'active')
                        <x-slot name="action">
                            <a href="{{ route('admin.subjects.create', ['semester_id' => $currentSemester->id]) }}"
                               class="font-medium text-indigo-600 hover:underline">+ Add subject</a>
                        </x-slot>
                    @endif

                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Teacher</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Credits</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Graded</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($subjects as $subject)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $subject->code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $subject->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->teacher?->name ?? 'Not assigned' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->credit_hours }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $subject->grades_count }} / {{ $subject->enrollments_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                        No subjects in this semester yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-panel>
            @endif

            {{-- Students of the class --}}
<x-panel :title="'Students (' . $students->count() . ')'">
    @php($statusStyles = [
        'active' => ['Active', 'bg-green-100 text-green-800'],
        'graduated' => ['Graduated', 'bg-blue-100 text-blue-800'],
        'failed' => ['Complete failure', 'bg-red-100 text-red-700'],
    ])

    <table class="min-w-full divide-y divide-gray-100">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Retakes waiting</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($students as $student)
                @php([$statusLabel, $statusClass] = $statusStyles[$student->academic_status])
                <tr>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $student->roll_number ?? '-' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ $student->pending_retakes_count }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        @if ($student->grades_count === 0)
                            <form action="{{ route('admin.classes.students.remove', [$classGroup, $student]) }}" method="POST"
                                  class="inline" onsubmit="return confirm('Remove this student from the class?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Remove</button>
                            </form>
                        @else
                            <span class="text-xs text-gray-400">Has grades</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                        No students in this class yet.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-panel>


            {{-- Add students --}}
            @if ($classGroup->status === 'active')
                <x-panel :title="'Add students from ' . $classGroup->department->name . ' (' . $available->count() . ' available)'">
                    @if ($available->isEmpty())
                        <div class="px-6 py-8 text-center text-sm text-gray-500">
                            No free students in this department.
                            <a href="{{ route('admin.users.create') }}" class="text-indigo-600 underline">Create a student</a>
                            (you can choose this class in the form).
                        </div>
                    @else
                        <form action="{{ route('admin.classes.students.add', $classGroup) }}" method="POST">
                            @csrf
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="w-10 px-6 py-3">
                                            <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600">
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($available as $student)
                                        <tr>
                                            <td class="px-6 py-3">
                                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                                       class="student-checkbox rounded border-gray-300 text-indigo-600">
                                            </td>
                                            <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                            <td class="px-6 py-3 text-sm text-gray-500">{{ $student->roll_number ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="border-t border-gray-100 px-6 py-4">
                                <x-primary-button>Add selected students</x-primary-button>
                            </div>
                        </form>

                        <script>
                            document.getElementById('select-all').addEventListener('change', function () {
                                document.querySelectorAll('.student-checkbox').forEach(box => box.checked = this.checked);
                            });
                        </script>
                    @endif
                </x-panel>
            @endif

            {{-- Semester history --}}
            <x-panel title="Semesters of this class">
                <ul class="divide-y divide-gray-100">
                    @foreach ($classGroup->semesters as $semester)
                        <li class="flex items-center justify-between px-6 py-3 text-sm">
                            <span class="text-gray-900">{{ $semester->name }}</span>
                            <span class="flex items-center gap-4">
                                <a href="{{ route('admin.classes.results', [$classGroup, 'semester' => $semester->id]) }}"
                                   class="text-indigo-600 hover:underline">Results</a>
                                @if ($semester->is_active)
                                    <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800">Active</span>
                                @else
                                    <span class="rounded bg-gray-200 px-2 py-1 text-xs text-gray-700">Completed</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </x-panel>
        </div>
    </div>
</x-app-layout>