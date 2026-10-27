@php($announcement = $announcement ?? null)

<div>
    <x-input-label for="title" value="Title" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  :value="old('title', $announcement?->title)" required autofocus />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div>
    <x-input-label for="body" value="Message" />
    <textarea id="body" name="body" rows="6" required
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body', $announcement?->body) }}</textarea>
    <x-input-error :messages="$errors->get('body')" class="mt-2" />
</div>

<div>
    <x-input-label for="target_role" value="Audience" />
    <select id="target_role" name="target_role" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach (['all' => 'Everyone', 'teacher' => 'Teachers only', 'student' => 'Students only'] as $value => $label)
            <option value="{{ $value }}" @selected(old('target_role', $announcement?->target_role ?? 'all') === $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('target_role')" class="mt-2" />
</div>

<div>
    <x-input-label for="department_id" value="Department" />
    <select id="department_id" name="department_id"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">All departments</option>
        @foreach ($departments as $department)
            <option value="{{ $department->id }}"
                @selected(old('department_id', $announcement?->department_id) == $department->id)>
                {{ $department->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('department_id')" class="mt-2" />
</div>