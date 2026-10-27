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
                            No messages yet. Click "New Message" to start a conversation.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>