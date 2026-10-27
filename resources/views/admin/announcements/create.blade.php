<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            New Announcement
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.announcements._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Publish</x-primary-button>
                        <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>