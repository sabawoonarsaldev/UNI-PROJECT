<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl text-sm text-gray-600 space-y-1">
                    <h2 class="text-lg font-medium text-gray-900">Account Information</h2>
                    <p class="text-gray-500 mb-3">These details are managed by the administration.</p>

                    <p><span class="font-medium text-gray-800">Role:</span> {{ ucfirst(auth()->user()->role) }}</p>

                    @if (auth()->user()->department)
                        <p><span class="font-medium text-gray-800">Department:</span> {{ auth()->user()->department->name }}</p>
                    @endif

                    @if (auth()->user()->role === 'student')
                        <p><span class="font-medium text-gray-800">Roll No.:</span> {{ auth()->user()->roll_number ?? '-' }}</p>
                        <p><span class="font-medium text-gray-800">Current semester:</span> {{ auth()->user()->currentSemester?->name ?? 'Not assigned' }}</p>
                    @endif
                </div>
            </div>
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
