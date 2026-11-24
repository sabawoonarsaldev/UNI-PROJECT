<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $classGroup->name }}</h2>
            <a href="{{ route('admin.classes.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All classes</a>
        </div>
    </x-slot>

    @php
        $total = $classGroup->total_semesters;
        $isActive = $classGroup->status === 'active';
        $isCurrent = $semester && $semester->is_active && $isActive;
        $stripStyles = [
            'done' => 'bg-emerald-100 text-emerald-700',
            'current' => 'bg-indigo-600 text-white',
            'future' => 'bg-gray-100 text-gray-400',
        ];
        $field = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    @endphp

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
            @if ($errors->any() && ! $errors->has('student_ids'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- Header with the semester strip --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Faculty:</span> {{ $classGroup->department->faculty->name }}
                        &nbsp;|&nbsp;
                        <span class="font-medium">Department:</span> {{ $classGroup->department->name }}
                        &nbsp;|&nbsp;
                        <span class="font-medium">Students:</span> {{ $members->count() }}
                        &nbsp;|&nbsp;
                        <span class="font-medium">Semesters:</span> {{ $total }}
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.classes.results', $classGroup) }}"
                           class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Results
                        </a>

                        <form action="{{ route('admin.classes.publish-grades', $classGroup) }}" method="POST"
                              onsubmit="return confirm('Publish all grades of the current semester? The students will see them.');">
                            @csrf
                            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Publish all grades
                            </button>
                        </form>

                        <form action="{{ route('admin.classes.process-results', $classGroup) }}" method="POST"
                              onsubmit="return confirm('Process the results? Failed subjects will move to the next chance.');">
                            @csrf
                            <button type="submit" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Process results
                            </button>
                        </form>

                        @if ($isActive)
                            <form action="{{ route('admin.classes.complete-semester', $classGroup) }}" method="POST"
                                  onsubmit="return confirm('Complete semester {{ $classGroup->current_semester_number }}? All its grades will be locked and the class will move to the next semester.');">
                                @csrf
                                <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                    Complete semester {{ $classGroup->current_semester_number }}
                                </button>
                            </form>
                        @endif

                        @if ($reopenNumber && ! $reopenProblem)
                            <form action="{{ route('admin.classes.reopen-semester', $classGroup) }}" method="POST"
                                  onsubmit="return confirm('Reopen semester {{ $reopenNumber }}? The semester after it is removed (it must be empty) and the retakes created at its completion are deleted.');">
                                @csrf
                                <button type="submit" class="rounded-lg border border-amber-400 bg-amber-50 px-3 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100">
                                    Reopen semester {{ $reopenNumber }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-1.5">
                    @for ($i = 1; $i <= $total; $i++)
                        @php
                            $item = $classGroup->semesters->firstWhere('number', $i);
                            $state = ! $item ? 'future' : ($item->is_active ? 'current' : 'done');
                            $ring = $i === $number ? 'ring-2 ring-indigo-500 ring-offset-2' : '';
                        @endphp
                        <a href="{{ route('admin.classes.show', [$classGroup, 'semester' => $i]) }}"
                           class="flex-1 rounded-md py-2 text-center text-xs font-semibold {{ $stripStyles[$state] }} {{ $ring }}">
                            S{{ $i }}
                        </a>
                    @endfor
                </div>

                <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500">
                    <span><span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span> Completed</span>
                    <span><span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span> Current</span>
                    <span><span class="inline-block h-2 w-2 rounded-full bg-gray-300"></span> Not started</span>
                    <span>Click a semester to see its details.</span>
                </div>
            </div>

            {{-- The selected semester --}}
            <div class="space-y-6">
                <div class="flex flex-wrap items-center gap-3">
                    <h3 class="text-lg font-semibold text-gray-900">Semester {{ $number }}</h3>
                    @if (! $semester)
                        <span class="rounded-full bg-gray-200 px-3 py-0.5 text-xs font-semibold text-gray-700">Not started</span>
                    @elseif ($semester->is_active)
                        <span class="rounded-full bg-indigo-100 px-3 py-0.5 text-xs font-semibold text-indigo-800">In progress</span>
                        <span class="text-sm text-gray-500">{{ $semester->name }}</span>
                    @else
                        <span class="rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-800">Completed</span>
                        <span class="text-sm text-gray-500">{{ $semester->name }}</span>
                    @endif
                </div>

                @if ($reopenNumber === $number && $reopenProblem)
                    <div class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">This semester cannot be reopened: {{ $reopenProblem }}</div>
                @endif

                <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Students</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $semester ? $stats['students'] : '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Subjects</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $semester ? $subjects->count() : '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Total credits</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $semester ? $subjects->sum('credit_hours') : '-' }}</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm text-gray-500">Grades entered</div>
                        <div class="text-2xl font-semibold text-gray-900">{{ $semester ? $stats['graded'] . ' / ' . $stats['expected'] : '-' }}</div>
                    </div>
                </div>

                @if (! $semester)
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                        Semester {{ $number }} has not started yet. It starts when semester {{ $number - 1 }} is completed.
                        There are no subjects, students or grades here yet.
                    </div>
                @else



                {{-- Subjects of the semester, with the windows to add and to edit them --}}
<x-panel :title="'Subjects of semester ' . $number . ' (' . $subjects->count() . ')'">
    @if ($isCurrent)
        <x-slot name="action">
            <div x-data="{
                    open: {{ $errors->has('items') ? 'true' : 'false' }},
                    selected: {},
                    ids: @js($available->pluck('id')->values()),
                    setAll(value) { this.ids.forEach(id => this.selected[id] = value) },
                    count() { return Object.values(this.selected).filter(Boolean).length }
                 }"
                 x-on:keydown.escape.window="open = false">

                <button type="button" x-on:click="open = true"
                        class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                    + Add subject
                </button>

                {{-- The window with the subjects of the curriculum --}}
                <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                    <div class="fixed inset-0 bg-gray-900/50" x-on:click="open = false"></div>

                    <div class="relative mx-auto my-8 w-full max-w-4xl rounded-xl bg-white text-left shadow-xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                Add subjects: {{ $classGroup->department->name }}, semester {{ $number }}
                            </h3>
                            <button type="button" x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                                <x-icon name="x-mark" class="h-6 w-6" />
                            </button>
                        </div>

                        @if ($curriculumTotal === 0)
                            <div class="px-6 py-10 text-center text-sm text-gray-500">
                                The curriculum of {{ $classGroup->department->name }} has no subject for semester {{ $number }} yet.
                                <a href="{{ route('admin.curriculum.create', ['department_id' => $classGroup->department_id, 'semester_number' => $number]) }}"
                                   class="text-indigo-600 underline">Add subjects to the curriculum</a>.
                            </div>
                        @elseif ($available->isEmpty())
                            <div class="px-6 py-10 text-center text-sm text-gray-500">
                                All {{ $curriculumTotal }} subject(s) of the curriculum are already in this semester.
                            </div>
                        @else
                            <form method="POST" action="{{ route('admin.classes.subjects.store', [$classGroup, $semester]) }}">
                                @csrf

                                <div class="flex flex-wrap items-center justify-between gap-2 px-6 pt-4 text-sm">
                                    <span class="text-gray-600">
                                        Tick the subjects this class studies. For every ticked subject choose the credit hours and the teacher.
                                    </span>
                                    <span class="flex gap-3 text-xs">
                                        <button type="button" x-on:click="setAll(true)" class="text-indigo-600 hover:underline">Select all</button>
                                        <button type="button" x-on:click="setAll(false)" class="text-gray-600 hover:underline">Clear</button>
                                    </span>
                                </div>

                                <div class="max-h-[60vh] overflow-y-auto px-6 py-3">
                                    <table class="min-w-full">
                                        <tbody class="divide-y divide-gray-100">
                                            @foreach ($available as $item)
                                                <tr class="align-top">
                                                    <td class="py-3 pe-3">
                                                        <input type="checkbox" x-model="selected[{{ $item->id }}]"
                                                               class="rounded border-gray-300 text-indigo-600">
                                                        <input type="hidden" name="items[{{ $item->id }}][selected]" value="1"
                                                               x-bind:disabled="! selected[{{ $item->id }}]">
                                                    </td>
                                                    <td class="py-3 pe-3 text-sm font-medium text-gray-900">{{ $item->code }}</td>
                                                    <td class="py-3 pe-3 text-sm text-gray-900">{{ $item->name }}</td>
                                                    <td class="py-3 text-sm">
                                                        <div x-show="! selected[{{ $item->id }}]" class="text-xs text-gray-400">
                                                            Curriculum: {{ $item->credit_hours }} credits{{ $item->teacher ? ', ' . $item->teacher->name : '' }}. Tick to choose.
                                                        </div>

                                                        <div x-show="selected[{{ $item->id }}]" x-cloak class="flex flex-wrap items-center gap-3">
                                                            <label class="text-xs text-gray-500">Credits
                                                                <input type="number" min="1" max="10" value="{{ $item->credit_hours }}"
                                                                       name="items[{{ $item->id }}][credit_hours]"
                                                                       x-bind:disabled="! selected[{{ $item->id }}]"
                                                                       x-bind:required="selected[{{ $item->id }}]"
                                                                       class="ms-1 w-20 {{ $field }}">
                                                            </label>

                                                            <label class="text-xs text-gray-500">Teacher
                                                                <select name="items[{{ $item->id }}][teacher_id]"
                                                                        x-bind:disabled="! selected[{{ $item->id }}]"
                                                                        x-bind:required="selected[{{ $item->id }}]"
                                                                        class="ms-1 {{ $field }}">
                                                                    <option value="">-- Select teacher --</option>
                                                                    @foreach ($teachers as $teacher)
                                                                        <option value="{{ $teacher->id }}" @selected($item->teacher_id === $teacher->id)>{{ $teacher->name }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </label>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex items-center justify-between gap-3 border-t border-gray-100 px-6 py-4">
                                    <span class="text-xs text-gray-500" x-text="count() + ' subject(s) selected'"></span>
                                    <div class="flex gap-2">
                                        <button type="button" x-on:click="open = false"
                                                class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                                        <button type="submit" x-bind:disabled="count() === 0"
                                                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                                            Add selected subjects
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </x-slot>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Subject</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Teacher</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Credits</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Graded</th>
                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Days taken</th>
                    @if ($isCurrent)
                        <th class="px-4 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($subjects as $subject)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $subject->code }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $subject->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $subject->teacher?->name ?? 'Not assigned' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $subject->credit_hours }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $subject->grades_count }} / {{ $subject->enrollments_count }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $sessionCounts[$subject->id] ?? 0 }}</td>

                        @if ($isCurrent)
                            <td class="px-4 py-3 text-right text-sm">
                                {{-- The window to edit the credits and the teacher --}}
                                <div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="inline">
                                    <button type="button" x-on:click="open = true" class="text-indigo-600 hover:underline">Edit</button>

                                    <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
                                        <div class="fixed inset-0 bg-gray-900/50" x-on:click="open = false"></div>

                                        <div class="relative mx-auto my-16 w-full max-w-md rounded-xl bg-white text-left shadow-xl">
                                            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Edit {{ $subject->code }}</h3>
                                                <button type="button" x-on:click="open = false" class="text-gray-400 hover:text-gray-600">
                                                    <x-icon name="x-mark" class="h-6 w-6" />
                                                </button>
                                            </div>

                                            <form method="POST" action="{{ route('admin.classes.subjects.update', [$classGroup, $subject]) }}" class="space-y-4 p-6">
                                                @csrf
                                                @method('PUT')

                                                <div class="rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
                                                    {{ $subject->code }} - {{ $subject->name }}
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Credit hours</label>
                                                    <input type="number" min="1" max="10" name="credit_hours" required
                                                           value="{{ $subject->credit_hours }}" class="mt-1 w-full {{ $field }}">
                                                </div>

                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700">Teacher</label>
                                                    <select name="teacher_id" required class="mt-1 w-full {{ $field }}">
                                                        <option value="">-- Select teacher --</option>
                                                        @foreach ($teachers as $teacher)
                                                            <option value="{{ $teacher->id }}" @selected($subject->teacher_id === $teacher->id)>{{ $teacher->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="flex justify-end gap-2">
                                                    <button type="button" x-on:click="open = false"
                                                            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                                                    <button type="submit"
                                                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" class="ms-3 inline"
                                      action="{{ route('admin.classes.subjects.destroy', [$classGroup, $subject]) }}"
                                      onsubmit="return confirm('Remove this subject from the semester?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Remove</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
                            No subjects were chosen for this semester yet.
                            @if ($isCurrent) Click "+ Add subject" to choose them from the curriculum. @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-panel>


                    {{-- Students of the semester with their attendance in every subject --}}
                    <x-panel :title="'Students and attendance (' . $stats['students'] . ')'">
                        <form method="GET" action="{{ route('admin.classes.show', $classGroup) }}" class="flex gap-2 border-b border-gray-100 px-6 py-4">
                            <input type="hidden" name="semester" value="{{ $number }}">
                            <div class="relative flex-1">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                    <x-icon name="magnifying-glass" class="h-5 w-5" />
                                </span>
                                <input id="student-search" type="text" name="q" value="{{ $term }}" autocomplete="off"
                                       placeholder="Search by name or roll number..."
                                       class="block w-full pl-10 {{ $field }}">
                            </div>
                            @if ($term !== '')
                                <a href="{{ route('admin.classes.show', [$classGroup, 'semester' => $number]) }}"
                                   class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Clear</a>
                            @endif
                        </form>

                        <div class="px-6 py-2 text-xs text-gray-500">
                            In every subject: <span class="font-semibold text-green-700">present</span> /
                            <span class="font-semibold text-red-600">absent</span>, and the excused days in gray.
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Student</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Roll No.</th>
                                        @foreach ($subjects as $subject)
                                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500" title="{{ $subject->name }}">
                                                {{ $subject->code }}
                                                <div class="normal-case text-gray-400">{{ $sessionCounts[$subject->id] ?? 0 }} day(s)</div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($semesterStudents as $student)
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $student->name }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $student->roll_number ?? '-' }}</td>
                                            @foreach ($subjects as $subject)
                                                @php
                                                    $counts = $attendance[$student->id][$subject->id] ?? [];
                                                    $present = $counts['present'] ?? 0;
                                                    $absent = $counts['absent'] ?? 0;
                                                    $excused = $counts['excused'] ?? 0;
                                                @endphp
                                                <td class="px-4 py-3 text-sm">
                                                    @if (($sessionCounts[$subject->id] ?? 0) === 0)
                                                        <span class="text-gray-300">-</span>
                                                    @else
                                                        <span class="font-semibold text-green-700">{{ $present }}</span>
                                                        <span class="text-gray-400">/</span>
                                                        <span class="font-semibold text-red-600">{{ $absent }}</span>
                                                        @if ($excused > 0)
                                                            <span class="ms-1 text-xs text-gray-400">({{ $excused }} excused)</span>
                                                        @endif
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ 2 + $subjects->count() }}" class="px-6 py-8 text-center text-sm text-gray-500">
                                                @if ($term !== '')
                                                    No student matches "{{ $term }}".
                                                @else
                                                    No students are enrolled in this semester yet.
                                                @endif
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </x-panel>
                @endif
            </div>

            {{-- Members of the class --}}
            <x-panel :title="'Class members (' . $members->count() . ')'">
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
                        @forelse ($members as $member)
                            @php
                                [$statusLabel, $statusClass] = [
                                    'active' => ['Active', 'bg-green-100 text-green-800'],
                                    'graduated' => ['Graduated', 'bg-blue-100 text-blue-800'],
                                    'failed' => ['Complete failure', 'bg-red-100 text-red-700'],
                                ][$member->academic_status];
                            @endphp
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $member->name }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $member->roll_number ?? '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $member->pending_retakes_count }}</td>
                                <td class="px-6 py-4 text-right text-sm">
                                    @if ($member->grades_count === 0)
                                        <form action="{{ route('admin.classes.students.remove', [$classGroup, $member]) }}" method="POST"
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
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No students in this class yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-panel>

            @if ($isActive)
                <x-panel :title="'Add students from ' . $classGroup->department->name . ' (' . $freeStudents->count() . ' available)'">
                    @if ($freeStudents->isEmpty())
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
                                    @foreach ($freeStudents as $student)
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
        </div>
    </div>

    <script>
        // The student list is searched while you type (after a short pause)
        (function () {
            const input = document.getElementById('student-search');
            if (!input) return;

            let timer = null;
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(() => input.form.submit(), 500);
            });

            // After the reload the cursor goes back to the end of the text
            if (input.value) {
                input.focus();
                input.setSelectionRange(input.value.length, input.value.length);
            }
        })();
    </script>
</x-app-layout>