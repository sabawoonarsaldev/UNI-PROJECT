<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Grades & Transcript
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="text-gray-800 font-medium">{{ $student->name }}</p>
                <p class="text-sm text-gray-500">
                    {{ $student->department?->name ?? 'No department' }} | Roll No.: {{ $student->roll_number ?? '-' }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">CGPA</div>
                    <div class="text-3xl font-semibold text-gray-900">
                        {{ $cgpa !== null ? number_format($cgpa, 2) : '-' }}
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Overall percentage</div>
                    <div class="text-3xl font-semibold text-gray-900">
                        {{ $overallAverage !== null ? number_format($overallAverage, 2) . '%' : '-' }}
                    </div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Total credit hours</div>
                    <div class="text-3xl font-semibold text-gray-900">{{ $totalCredits }}</div>
                </div>
            </div>

            @forelse ($semesters as $item)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200 flex flex-wrap justify-between gap-2">
                        <span class="font-semibold text-gray-800">{{ $item['semester']->name }}</span>
                        <span class="text-sm text-gray-600">
                            GPA: <strong>{{ $item['gpa'] !== null ? number_format($item['gpa'], 2) : '-' }}</strong>
                            | Percentage: <strong>{{ $item['average'] !== null ? number_format($item['average'], 2) . '%' : '-' }}</strong>
                            | Credits: <strong>{{ $item['credits'] }}</strong>
                        </span>
                    </div>

                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Credits</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($item['grades'] as $grade)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $grade->subject->code }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ $grade->subject->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $grade->subject->credit_hours }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ (float) $grade->marks }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $grade->grade_letter }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ number_format(\App\Models\Grade::pointsFor($grade->grade_letter), 1) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500 text-sm">
                    No grades have been entered for you yet.
                </div>
            @endforelse

            <p class="text-xs text-gray-400">
                GPA is calculated on a 4.0 scale and weighted by credit hours. Subjects without a grade are not counted.
            </p>
        </div>
    </div>
</x-app-layout>