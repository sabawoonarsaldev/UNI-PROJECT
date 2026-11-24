<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Subject to the Curriculum</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <p class="mb-6 text-sm text-gray-600">
                    The curriculum belongs to a department and a semester, not to a class. Later every class chooses
                    the subjects it needs from this list.
                </p>

                <form method="POST" action="{{ route('admin.curriculum.store') }}" class="space-y-6">
                    @csrf

                    @include('admin.curriculum._form')

                    <div class="flex items-center gap-4">
                        <x-primary-button>Save</x-primary-button>
                        <a href="{{ route('admin.curriculum.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>