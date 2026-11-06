@php($project = $project ?? null)

<div>
    <x-input-label for="title" value="Project Title" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  :value="old('title', $project?->title)" required />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="type" value="Type" />
        <select id="type" name="type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (\App\Models\Project::TYPES as $value => $label)
                <option value="{{ $value }}" @selected(old('type', $project?->type ?? 'thesis') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="year" value="Year (Hijri Shamsi)" />
        <select id="year" name="year" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($years as $year)
                <option value="{{ $year }}" @selected(old('year', $project?->year ?? $currentYear) == $year)>{{ $year }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('year')" class="mt-2" />
    </div>
</div>

<div>
    <x-input-label for="abstract" value="Abstract" />
    <textarea id="abstract" name="abstract" rows="7" required
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('abstract', $project?->abstract) }}</textarea>
    <x-input-error :messages="$errors->get('abstract')" class="mt-2" />
</div>

<div>
    <x-input-label for="keywords" value="Keywords (separated by commas)" />
    <x-text-input id="keywords" name="keywords" type="text" class="mt-1 block w-full"
                  :value="old('keywords', $project?->keywordsText())" placeholder="machine learning, education, mobile apps" />
    <x-input-error :messages="$errors->get('keywords')" class="mt-2" />
</div>