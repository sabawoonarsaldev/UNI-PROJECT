@php($subject = $subject ?? null)

<div>
    <x-input-label for="name" value="Subject Name" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                  :value="old('name', $subject?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div>
    <x-input-label for="code" value="Subject Code (e.g. CS-101)" />
    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                  :value="old('code', $subject?->code)" required />
    <x-input-error :messages="$errors->get('code')" class="mt-2" />
</div>

<div>
    <x-input-label for="semester_id" value="Class and semester" />

    @if ($subject)
        <div class="mt-1 rounded-md bg-gray-50 px-3 py-2 text-sm text-gray-700">
            {{ $subject->semester->classGroup?->name }} - {{ $subject->semester->name }}
        </div>
        <p class="mt-1 text-xs text-gray-500">The semester of a subject cannot be changed.</p>
    @else
        <select id="semester_id" name="semester_id" required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">-- Select class and semester --</option>
            @foreach ($semesters as $semester)
                <option value="{{ $semester->id }}"
                    @selected(old('semester_id', $selectedSemester ?? null) == $semester->id)>
                    {{ $semester->classGroup->name }} - {{ $semester->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('semester_id')" class="mt-2" />

        @if ($semesters->isEmpty())
            <p class="mt-2 text-sm text-amber-700">
                There is no active class yet.
                <a href="{{ route('admin.classes.create') }}" class="underline">Create a class first</a>.
            </p>
        @else
            <p class="mt-1 text-xs text-gray-500">All students of the class are enrolled in the subject automatically.</p>
        @endif
    @endif
</div>

<div>
    <x-input-label for="teacher_id" value="Teacher (optional)" />
    <select id="teacher_id" name="teacher_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Not assigned --</option>
        @foreach ($teachers as $teacher)
            <option value="{{ $teacher->id }}"
                @selected(old('teacher_id', $subject?->teacher_id) == $teacher->id)>
                {{ $teacher->name }}@if ($teacher->department) ({{ $teacher->department->name }})@endif
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('teacher_id')" class="mt-2" />
</div>

<div>
    <x-input-label for="credit_hours" value="Credit Hours" />
    <x-text-input id="credit_hours" name="credit_hours" type="number" min="1" max="10"
                  class="mt-1 block w-full" :value="old('credit_hours', $subject?->credit_hours ?? 3)" required />
    <x-input-error :messages="$errors->get('credit_hours')" class="mt-2" />
</div>