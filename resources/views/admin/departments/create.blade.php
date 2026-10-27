<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Add Department
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('admin.departments.store') }}" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="faculty_id" value="Faculty" />
                        <select id="faculty_id" name="faculty_id" required
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select faculty --</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected(old('faculty_id') == $faculty->id)>
                                    {{ $faculty->name }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('faculty_id')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="name" value="Department Name" />
                        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                                      :value="old('name')" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Save</x-primary-button>
                        <a href="{{ route('admin.departments.index') }}" class="text-sm text-gray-600 hover:underline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>