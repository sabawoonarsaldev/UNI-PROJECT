@props(['label', 'value', 'icon', 'color' => 'indigo', 'href' => null, 'hint' => null])

@php
    $colors = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'blue' => 'bg-blue-50 text-blue-600',
        'green' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
        'purple' => 'bg-purple-50 text-purple-600',
        'cyan' => 'bg-cyan-50 text-cyan-600',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif
    class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm {{ $href ? 'transition hover:border-indigo-200 hover:shadow-md' : '' }}">
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg {{ $colors[$color] ?? $colors['indigo'] }}">
        <x-icon :name="$icon" class="h-6 w-6" />
    </div>
    <div class="min-w-0">
        <div class="text-sm text-gray-500">{{ $label }}</div>
        <div class="text-2xl font-semibold text-gray-900">{{ $value }}</div>
        @if ($hint)
            <div class="text-xs text-gray-400">{{ $hint }}</div>
        @endif
    </div>
</{{ $tag }}>