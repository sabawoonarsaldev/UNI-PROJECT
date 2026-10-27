@props(['title' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || isset($action))
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            <h3 class="font-semibold text-gray-800">{{ $title }}</h3>
            @isset($action)
                <div class="text-sm">{{ $action }}</div>
            @endisset
        </div>
    @endif

    {{ $slot }}
</div>