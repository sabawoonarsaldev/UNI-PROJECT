<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Attendance Settings</h2>
            <a href="{{ route('admin.configuration.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Configuration</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <x-panel title="Rules">
                <form method="POST" action="{{ route('admin.attendance-settings.update') }}" class="space-y-6 p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <div class="text-sm font-medium text-gray-800">Weekly holidays</div>
                        <p class="mb-2 text-xs text-gray-500">No attendance is taken and no lesson is planned on these days.</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($days as $day)
                                <label class="inline-flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="holidays[]" value="{{ $day }}"
                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                           @checked(in_array($day, old('holidays', $holidays), true))>
                                    {{ $day }}
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <x-input-label for="min_percent" value="Minimum attendance to sit in the exam (%)" />
                        <x-text-input id="min_percent" name="min_percent" type="number" min="1" max="100"
                                      class="mt-1 block w-32" :value="old('min_percent', $minPercent)" required />
                        <p class="mt-1 text-xs text-gray-500">
                            A student whose absences are more than the rest of the percent (for 75%: more than 25% of the planned sessions)
                            is barred from the exam.
                        </p>
                    </div>

                    <x-primary-button>Save rules</x-primary-button>
                </form>
            </x-panel>

            <x-panel title="Semester dates (current semester of every active class)">
                <p class="px-6 pt-4 text-xs text-gray-500">
                    The start date limits the dates the teacher can choose. The number of weeks (normally 16) decides how many
                    sessions are planned, so a longer or shorter semester changes the allowed absences automatically.
                </p>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Semester</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Start date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Weeks</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">End date</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($semesters as $semester)
                                @php
                                    $formId = 'semester-form-' . $semester->id;
                                    $start = $semester->start_date ? \Carbon\Carbon::parse($semester->start_date) : null;
                                @endphp
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $semester->classGroup->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $semester->name }}</td>
                                    <td class="px-4 py-3">
                                        <input form="{{ $formId }}" type="date" name="start_date" value="{{ $start?->format('Y-m-d') }}"
                                               class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3">
                                        <input form="{{ $formId }}" type="number" name="weeks" min="1" max="30" value="{{ $semester->weeks }}" required
                                               class="w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $start ? $start->copy()->addWeeks($semester->weeks)->subDay()->format('Y-m-d') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <form id="{{ $formId }}" method="POST" action="{{ route('admin.attendance-settings.semester', $semester) }}">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="text-sm font-medium text-indigo-600 hover:underline">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">There is no active class yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-panel>
        </div>
    </div>
</x-app-layout>