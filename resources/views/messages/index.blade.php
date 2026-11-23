<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Messages
            </h2>
            <a href="{{ route('messages.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                + New Message
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

            {{-- Search bar, then faculty and department (for the admin) --}}
            <form method="GET" action="{{ route('messages.index') }}" class="mb-4 space-y-3">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-icon name="magnifying-glass" class="h-5 w-5" />
                    </span>
                    <input id="message-search" type="text" name="q" value="{{ request('q') }}" autocomplete="off"
                           placeholder="Search by name or message..."
                           class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                @if ($isAdmin)
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        @include('admin.partials.faculty-department')

                        @if (request('q') || request('faculty_id') || request('department_id'))
                            <a href="{{ route('messages.index') }}" class="text-gray-600 hover:underline">Reset</a>
                        @endif
                    </div>
                @endif
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <ul class="divide-y divide-gray-200">
                    @forelse ($conversations as $conversation)
                        <li>
                            <a href="{{ route('messages.show', $conversation['user']) }}"
                               class="block px-6 py-4 hover:bg-gray-50">
                                <div class="flex justify-between items-center">
                                    <span class="font-medium text-gray-900">
                                        {{ $conversation['user']->name }}
                                        <span class="ms-1 text-xs text-gray-500">({{ ucfirst($conversation['user']->role) }})</span>
                                    </span>
                                    <span class="text-xs text-gray-500">
                                        {{ $conversation['latest']->created_at->diffForHumans() }}
                                    </span>
                                </div>

                                @if ($isAdmin && $conversation['user']->department)
                                    <div class="text-xs text-gray-400">
                                        {{ $conversation['user']->department->faculty?->name }} / {{ $conversation['user']->department->name }}
                                    </div>
                                @endif

                                <div class="mt-1 flex justify-between items-center text-sm text-gray-500">
                                    <span>{{ \Illuminate\Support\Str::limit($conversation['latest']->body, 70) }}</span>
                                    @if ($conversation['unread'] > 0)
                                        <span class="ms-2 px-2 py-0.5 text-xs rounded-full bg-red-500 text-white">
                                            {{ $conversation['unread'] }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-6 py-8 text-center text-gray-500 text-sm">
                            @if (request('q') || request('faculty_id') || request('department_id'))
                                No conversations match your search.
                            @else
                                No messages yet. Click "New Message" to start a conversation.
                            @endif
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <script>
        // The list is searched while you type (after a short pause)
        (function () {
            const input = document.getElementById('message-search');
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
</x-app-layout>