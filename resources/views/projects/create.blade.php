<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">My Final Project</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('error'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            @if (! $supervisor)
                <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                    <x-icon name="exclamation-triangle" class="h-5 w-5 shrink-0" />
                    <div>
                        No supervisor is assigned to you yet. The administration must choose a supervisor for you
                        before you can create your project.
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <p class="mb-6 text-sm text-gray-600">
                        Your supervisor is <span class="font-semibold text-gray-900">{{ $supervisor->name }}</span>.
                        Fill in the details of your project. You will upload your document and talk to your supervisor on the next page.
                    </p>

                    <form method="POST" action="{{ route('student.project.store') }}" class="space-y-6">
                        @csrf

                        @include('projects._fields')

                        <x-primary-button>Create project</x-primary-button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>