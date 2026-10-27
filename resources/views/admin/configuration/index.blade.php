<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Configuration</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="mb-6 text-sm text-gray-600">
                The general settings of the university are managed here. Whatever you define in this section
                is used in the rest of the system.
            </p>

            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($sections as $section)
                    <div class="flex flex-col rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start gap-4">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                                <x-icon :name="$section['icon']" class="h-6 w-6" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-semibold text-gray-900">{{ $section['title'] }}</h3>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600">
                                        {{ $section['count'] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">{{ $section['description'] }}</p>
                            </div>
                        </div>

                        <div class="mt-5 flex gap-2">
                            <a href="{{ $section['manage'] }}"
                               class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                Manage
                            </a>
                            @if ($section['add'])
                                <a href="{{ $section['add'] }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <x-icon name="plus" class="h-4 w-4" /> {{ $section['add_label'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>