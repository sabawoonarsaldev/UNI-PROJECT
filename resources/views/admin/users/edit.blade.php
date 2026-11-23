<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit User
            </h2>
            <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-gray-600 hover:underline">&larr; Back to the profile</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('admin.users._form', ['user' => $user])

                    <div class="flex items-center gap-4">
                        <x-primary-button>Update</x-primary-button>
                        <a href="{{ route('admin.users.show', $user) }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>