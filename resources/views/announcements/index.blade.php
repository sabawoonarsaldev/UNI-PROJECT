<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Announcements
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @forelse ($announcements as $announcement)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap justify-between gap-2">
                        <h3 class="font-semibold text-gray-900">{{ $announcement->title }}</h3>
                        <span class="text-xs text-gray-500">{{ $announcement->created_at->format('Y-m-d H:i') }}</span>
                    </div>

                    <div class="mt-1 text-xs text-gray-500">
                        {{ $announcement->department?->name ?? 'All departments' }}
                        @if ($announcement->creator) | By {{ $announcement->creator->name }} @endif
                    </div>

                    <p class="mt-3 text-sm text-gray-700 whitespace-pre-line">{{ $announcement->body }}</p>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500 text-sm">
                    There are no announcements for you right now.
                </div>
            @endforelse

            {{ $announcements->links() }}
        </div>
    </div>
</x-app-layout>