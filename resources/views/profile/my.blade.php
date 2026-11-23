<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">My Profile</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif

            @include('partials.profile-card')

            <x-panel title="Update my photo and contact details">
                <form method="POST" action="{{ route('my-profile.update') }}" enctype="multipart/form-data" class="space-y-5 p-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="photo" value="Profile photo" />
                        <input type="file" id="photo" name="photo" accept="image/*"
                               class="mt-1 block text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-gray-500">JPG, PNG or WEBP, maximum 2 MB.</p>
                        <x-input-error :messages="$errors->get('photo')" class="mt-2" />

                        @if ($person->photo_path)
                            <label class="mt-2 inline-flex items-center gap-2 text-sm text-gray-600">
                                <input type="checkbox" name="remove_photo" value="1" class="rounded border-gray-300 text-indigo-600">
                                Remove my current photo
                            </label>
                        @endif
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $person->phone)" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="address" value="Address" />
                            <x-text-input id="address" name="address" type="text" class="mt-1 block w-full" :value="old('address', $person->address)" />
                            <x-input-error :messages="$errors->get('address')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Save</x-primary-button>
                        <span class="text-xs text-gray-500">
                            Other details are managed by the administration. Your name, email and password are in Account Settings.
                        </span>
                    </div>
                </form>
            </x-panel>
        </div>
    </div>
</x-app-layout>