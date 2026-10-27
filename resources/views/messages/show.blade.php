<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $user->name }} <span class="text-sm text-gray-500">({{ ucfirst($user->role) }})</span>
            </h2>
            <a href="{{ route('messages.index') }}" class="text-sm text-gray-600 hover:underline">
                &larr; All messages
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-4">

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-3">
                @forelse ($messages as $message)
                    @php($mine = $message->sender_id === auth()->id())
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-md px-4 py-2 rounded-lg text-sm {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                            <p class="whitespace-pre-line">{{ $message->body }}</p>
                            <div class="mt-1 text-xs {{ $mine ? 'text-indigo-200' : 'text-gray-500' }}">
                                {{ $message->created_at->format('Y-m-d H:i') }}
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-500 text-sm">No messages yet. Write the first one below.</p>
                @endforelse
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('messages.store', $user) }}" class="space-y-4">
                    @csrf

                    <div>
                        <textarea name="body" rows="3" required placeholder="Write your message..."
                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('body') }}</textarea>
                        <x-input-error :messages="$errors->get('body')" class="mt-2" />
                    </div>

                    <x-primary-button>Send</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>