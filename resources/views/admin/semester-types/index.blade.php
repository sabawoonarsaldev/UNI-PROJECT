<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Academic Terms</h2>
            <a href="{{ route('admin.configuration.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; Configuration
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <p class="text-sm text-gray-600">
                These terms are offered when you create a semester (for example Spring and Fall).
                The order decides which term comes first inside a year.
            </p>

            <x-panel title="Add a new term">
                <form method="POST" action="{{ route('admin.semester-types.store') }}" class="flex flex-wrap items-end gap-4 p-6">
                    @csrf

                    <div class="flex-1">
                        <x-input-label for="new_name" value="Term name" />
                        <x-text-input id="new_name" name="name" type="text" class="mt-1 block w-full" required />
                    </div>

                    <div class="w-28">
                        <x-input-label for="new_order" value="Order" />
                        <x-text-input id="new_order" name="sort_order" type="number" min="1" max="10"
                                      class="mt-1 block w-full" :value="$types->max('sort_order') + 1" required />
                    </div>

                    <x-primary-button>Add</x-primary-button>
                </form>
            </x-panel>

            <x-panel title="Configured terms">
                <ul class="divide-y divide-gray-100">
                    @forelse ($types as $type)
                        <li class="flex flex-wrap items-center justify-between gap-4 px-6 py-4">
                            <form method="POST" action="{{ route('admin.semester-types.update', $type) }}"
                                  class="flex flex-1 flex-wrap items-center gap-3">
                                @csrf
                                @method('PUT')

                                <input type="text" name="name" value="{{ $type->name }}" required maxlength="50"
                                       class="w-40 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <input type="number" name="sort_order" value="{{ $type->sort_order }}" min="1" max="10" required
                                       class="w-20 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                                <button type="submit" class="text-sm font-medium text-indigo-600 hover:underline">Save</button>
                                <span class="text-xs text-gray-500">{{ $type->semesters_count }} semester(s)</span>
                            </form>

                            <form method="POST" action="{{ route('admin.semester-types.destroy', $type) }}"
                                  onsubmit="return confirm('Delete this term?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Delete</button>
                            </form>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-500">No terms yet. Add the first one above.</li>
                    @endforelse
                </ul>
            </x-panel>
        </div>
    </div>
</x-app-layout>