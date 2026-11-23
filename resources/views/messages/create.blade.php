<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                New Message
            </h2>
            <a href="{{ route('messages.index') }}" class="text-sm text-gray-600 hover:underline">&larr; All messages</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            {{-- The admin finds the person by search, faculty, department and role --}}
            @if ($isAdmin)
                <form method="GET" action="{{ route('messages.create') }}" class="mb-4 space-y-3">
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <x-icon name="magnifying-glass" class="h-5 w-5" />
                        </span>
                        <input id="recipient-search" type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                               placeholder="Search by name, email or roll number..."
                               class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @include('admin.partials.faculty-department')

                        <select name="role" data-auto-submit
                                class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Teachers and students</option>
                            <option value="teacher" @selected(request('role') === 'teacher')>Teachers only</option>
                            <option value="student" @selected(request('role') === 'student')>Students only</option>
                        </select>

                        @if (request('q') || request('faculty_id') || request('department_id') || request('role'))
                            <a href="{{ route('messages.create') }}" class="text-gray-600 hover:underline">Reset</a>
                        @endif

                        <span class="ms-auto text-gray-500">{{ $recipients->total() }} person(s) found</span>
                    </div>
                </form>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-200">
                    @forelse ($recipients as $recipient)
                        <li class="flex items-center justify-between gap-4 px-6 py-3">
                            <div class="min-w-0">
                                <div class="truncate font-medium text-gray-900">
                                    {{ $recipient->name }}
                                    <span class="ms-1 text-xs text-gray-500">({{ ucfirst($recipient->role) }})</span>
                                </div>
                                @if ($recipient->department)
                                    <div class="truncate text-xs text-gray-500">
                                        {{ $recipient->department->faculty?->name }} / {{ $recipient->department->name }}
                                        @if ($recipient->roll_number) | {{ $recipient->roll_number }} @endif
                                    </div>
                                @endif
                            </div>

                            <form method="POST" action="{{ route('messages.start') }}">
                                @csrf
                                <input type="hidden" name="recipient_id" value="{{ $recipient->id }}">
                                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm text-white hover:bg-indigo-700">
                                    Message
                                </button>
                            </form>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-sm text-gray-500">No one matches your search.</li>
                    @endforelse
                </ul>
            </div>

            <div class="mt-4">
                {{ $recipients->links() }}
            </div>
        </div>
    </div>

    @if ($isAdmin)
        <script>
            // The list is searched while you type (after a short pause)
            (function () {
                const input = document.getElementById('recipient-search');
                let timer = null;

                input.addEventListener('input', () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => input.form.submit(), 500);
                });

                // After the page reloads, the cursor goes back to the end of the text
                if (input.value) {
                    input.focus();
                    input.setSelectionRange(input.value.length, input.value.length);
                }
            })();
        </script>
    @endif
</x-app-layout>