@php
    $styles = [
        'admin' => ['banner' => 'from-purple-600 to-fuchsia-600', 'badge' => 'bg-purple-600 text-white', 'avatar' => 'bg-purple-100 text-purple-700', 'label' => 'ADMINISTRATOR'],
        'teacher' => ['banner' => 'from-indigo-600 to-blue-500', 'badge' => 'bg-indigo-600 text-white', 'avatar' => 'bg-indigo-100 text-indigo-700', 'label' => 'TEACHER'],
        'student' => ['banner' => 'from-emerald-600 to-teal-500', 'badge' => 'bg-emerald-600 text-white', 'avatar' => 'bg-emerald-100 text-emerald-700', 'label' => 'STUDENT'],
    ];
    $style = $styles[$person->role];
    $photo = $person->photoUrl();
    $faculty = $person->department?->faculty?->name;
    $department = $person->department?->name;

    $subtitle = match ($person->role) {
        'student' => collect(['Roll No. ' . ($person->roll_number ?? '-'), $department, $faculty])->filter()->implode(' | '),
        'teacher' => collect([
            \App\Models\User::ACADEMIC_RANKS[$person->academic_rank] ?? null,
            \App\Models\User::EDUCATION_DEGREES[$person->education_degree] ?? null,
            $department,
        ])->filter()->implode(' | '),
        default => $person->email,
    };
@endphp

<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
    <div class="h-32 bg-gradient-to-r {{ $style['banner'] }}"></div>

    <div class="px-6 pb-6">
        <div class="-mt-16 flex flex-wrap items-end gap-5">
            @if ($photo)
                <img src="{{ $photo }}" alt="{{ $person->name }}"
                     class="h-32 w-32 rounded-full object-cover shadow ring-4 ring-white">
            @else
                <div class="flex h-32 w-32 items-center justify-center rounded-full text-4xl font-bold shadow ring-4 ring-white {{ $style['avatar'] }}">
                    {{ $person->initials() }}
                </div>
            @endif

            <div class="pb-2">
                <span class="inline-block rounded-full px-4 py-1 text-sm font-extrabold tracking-widest {{ $style['badge'] }}">
                    {{ $style['label'] }}
                </span>
                <h1 class="mt-2 text-3xl font-bold text-gray-900">{{ $person->name }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ $subtitle }}</p>
            </div>
        </div>
    </div>
</div>

@if (count($stats) > 0)
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        @foreach ($stats as [$label, $value])
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</div>
                <div class="mt-1 truncate text-xl font-semibold text-gray-900">{{ ($value !== null && $value !== '') ? $value : '-' }}</div>
            </div>
        @endforeach
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-2">
    @foreach ($sections as $title => $rows)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="border-b border-gray-100 px-6 py-3 font-semibold text-gray-800">{{ $title }}</div>
            <dl class="divide-y divide-gray-100">
                @foreach ($rows as [$label, $value])
                    <div class="flex justify-between gap-4 px-6 py-3 text-sm">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="text-right font-medium text-gray-900">{{ ($value !== null && $value !== '') ? $value : '-' }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    @endforeach
</div>