@php($schedule = $schedule ?? null)

<div>
    <x-input-label for="subject_id" value="Subject" />
    <select id="subject_id" name="subject_id" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Select subject --</option>
        @foreach ($subjects as $subject)
            <option value="{{ $subject->id }}"
                @selected(old('subject_id', $schedule?->subject_id) == $subject->id)>
                {{ $subject->code }} - {{ $subject->name }}
                ({{ $subject->department->name }}, {{ $subject->semester->name }})
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('subject_id')" class="mt-2" />
</div>

<div>
    <x-input-label for="day" value="Day" />
    <select id="day" name="day" required
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">-- Select day --</option>
        @foreach ($days as $day)
            <option value="{{ $day }}" @selected(old('day', $schedule?->day) === $day)>{{ $day }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('day')" class="mt-2" />
</div>

<div class="grid grid-cols-2 gap-4">
    <div>
        <x-input-label for="start_time" value="Start Time" />
        <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full"
                      :value="old('start_time', $schedule ? substr($schedule->start_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_time" value="End Time" />
        <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full"
                      :value="old('end_time', $schedule ? substr($schedule->end_time, 0, 5) : '')" required />
        <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="room" value="Room (optional)" />
    <x-text-input id="room" name="room" type="text" class="mt-1 block w-full"
                  :value="old('room', $schedule?->room)" />
    <x-input-error :messages="$errors->get('room')" class="mt-2" />
</div>