<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="bg-gray-100 font-sans text-gray-900 antialiased">
        @php
            $authUser = auth()->user();
            $roleBadge = [
                'admin' => 'bg-purple-100 text-purple-700',
                'teacher' => 'bg-blue-100 text-blue-700',
                'student' => 'bg-emerald-100 text-emerald-700',
            ];
        @endphp

        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">

            @include('layouts.sidebar')

            <div class="flex min-w-0 flex-1 flex-col">

                {{-- Top bar --}}
                <div class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 lg:hidden">
                        <x-icon name="bars" class="h-6 w-6" />
                    </button>

                    <div class="hidden text-sm text-gray-500 lg:block">{{ now()->format('l, d F Y') }}</div>

                    <div class="ms-auto flex items-center gap-3">
                        <span class="hidden rounded-full px-3 py-1 text-xs font-semibold sm:inline {{ $roleBadge[$authUser->role] ?? '' }}">
                            {{ ucfirst($authUser->role) }}
                        </span>

                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 font-semibold text-indigo-700">
                                        {{ strtoupper(mb_substr($authUser->name, 0, 1)) }}
                                    </span>
                                    <span class="hidden sm:block">{{ $authUser->name }}</span>
                                    <x-icon name="chevron-down" class="h-4 w-4" />
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        Log Out
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>

                {{-- Page header --}}
                @isset($header)
                    <div class="border-b border-gray-200 bg-white">
                        <div class="px-4 py-5 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </div>
                @endisset

                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>