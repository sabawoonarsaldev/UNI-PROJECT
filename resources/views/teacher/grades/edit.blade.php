<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Grades: {{ $subject->code }} - {{ $subject->name }}
            </h2>
            <a href="{{ route('teacher.subjects.show', $subject) }}" class="text-sm text-gray-600 hover:underline">
                &larr; Back
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <p class="mb-4 text-sm text-gray-600">
                Every student gets four scores: exams (max 20), homework (max 10), class activity (max 10) and
                the final exam (max 60). The total (100) and the letter grade are calculated automatically.
                Pass mark: {{ \App\Models\Grade::PASS_MARK }}. Fill all four boxes of a student, or leave the row empty
                if the student is not graded yet. Rows in gray are locked.
            </p>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @include('partials.gradebook-form', [
                    'rows' => $rows,
                    'action' => route('teacher.grades.update', $subject),
                    'cancelUrl' => route('teacher.subjects.show', $subject),
                ])
            </div>
        </div>
    </div>
</x-app-layout>