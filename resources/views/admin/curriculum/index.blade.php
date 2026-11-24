<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Curriculum</h2>
            <a href="{{ route('admin.curriculum.create', array_filter(['department_id' => request('department_id'), 'semester_number' => request('semester_number')])) }}"
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

            <p class="mb-4 text-sm text-gray-600">
                The subjects of every department, semester by semester. A class chooses the subjects it needs from here.
            </p>

            {{-- Faculty -> department -> semester --}}
            <form method="GET" action="{{ route('admin.curriculum.index') }}" class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                @include('admin.partials.faculty-department', ['extraResets' => 'semester_number'])

                @if (request('department_id'))
                    <select name="semester_number" data-auto-submit
                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All semesters</option>
                        @for ($n = 1; $n <= \App\Models\CurriculumSubject::MAX_SEMESTERS; $n++)
                            <option value="{{ $n }}" @selected(request('semester_number') == $n)>Semester {{ $n }}</option>
                        @endfor
                    </select>
                @endif

                @if (request('faculty_id') || request('department_id') || request('semester_number'))
                    <a href="{{ route('admin.curriculum.index') }}" class="text-gray-600 hover:underline">Reset</a>
                @endif

                <span class="ms-auto text-gray-500">{{ $total }} subject(s)</span>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default teacher</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Used in classes</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($groups as $items)
                            @php
                                $first = $items->first();
                            @endphp
                            <tr class="bg-indigo-50">
                                <td colspan="6" class="px-6 py-2 text-sm font-semibold text-indigo-900">
                                    {{ $first->department->faculty->name }} / {{ $first->department->name }}
                                    &mdash; Semester {{ $first->semester_number }}
                                    <span class="ms-2 text-xs font-normal text-indigo-700">
                                        {{ $items->count() }} subject(s), {{ $items->sum('credit_hours') }} credits
                                    </span>
                                </td>
                            </tr>
                            @foreach ($items as $item)
                                <tr>
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $item->code }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $item->name }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $item->credit_hours }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $item->teacher?->name ?? 'Not assigned' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $item->offerings_count }}</td>
                                    <td class="px-6 py-3 text-sm text-right space-x-2">
                                        <a href="{{ route('admin.curriculum.edit', $item) }}" class="text-indigo-600 hover:underline">Edit</a>

                                        <form action="{{ route('admin.curriculum.destroy', $item) }}" method="POST" class="inline"
                                              onsubmit="return confirm('Remove this subject from the curriculum? Classes that already chose it keep their copy.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    No subjects found for this selection. Click "Add Subject" to build the curriculum.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>