<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">User Profile</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All users</a>
                <a href="{{ route('admin.users.edit', $person) }}"
                   class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Edit</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @include('partials.profile-card')
        </div>
    </div>
</x-app-layout>