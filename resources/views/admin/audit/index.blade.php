<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Audit Log</h2>
    </x-slot>

    @php
        $select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
        $colors = [
            'grade' => 'bg-indigo-100 text-indigo-800',
            'user' => 'bg-purple-100 text-purple-800',
            'supervisor' => 'bg-cyan-100 text-cyan-800',
            'attendance' => 'bg-amber-100 text-amber-800',
            'settings' => 'bg-gray-200 text-gray-800',
        ];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">

            <p class="text-sm text-gray-600">
                Every change of a grade, every deleted user, changed role or supervisor and every lock of attendance is
                written here with the person who did it, the time and the old and new values. The log cannot be edited.
            </p>

            <form method="GET" action="{{ route('admin.audit-log.index') }}" class="flex flex-wrap items-center gap-2 text-sm">
                <select name="category" onchange="this.form.submit()" class="{{ $select }}">
                    <option value="">All kinds</option>
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>
                    @endforeach
                </select>

                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search text or person" class="{{ $select }}">

                <label class="inline-flex items-center gap-1 text-gray-600">From
                    <input type="date" name="from" value="{{ request('from') }}" class="{{ $select }}">
                </label>
                <label class="inline-flex items-center gap-1 text-gray-600">To
                    <input type="date" name="to" value="{{ request('to') }}" class="{{ $select }}">
                </label>

                <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-white hover:bg-gray-700">Filter</button>

                @if (request()->hasAny(['category', 'q', 'from', 'to']))
                    <a href="{{ route('admin.audit-log.index') }}" class="text-gray-600 hover:underline">Reset</a>
                @endif
            </form>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">When</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Who</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Kind</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">What happened</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($logs as $log)
                            @php
                                $category = explode('.', $log->action)[0];
                                $keys = collect(array_keys(($log->old_values ?? []) + ($log->new_values ?? [])));
                            @endphp
                            <tr class="align-top">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                    {{ $log->created_at->format('Y-m-d H:i') }}
                                    <div class="text-xs text-gray-400">{{ $log->ip_address }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $log->user?->name ?? 'System' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $colors[$category] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ $log->description }}

                                    @if ($keys->isNotEmpty())
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-xs text-indigo-600">Show old and new values</summary>
                                            <table class="mt-2 text-xs">
                                                <thead>
                                                    <tr class="text-gray-500">
                                                        <th class="pe-4 text-left font-medium">Field</th>
                                                        <th class="pe-4 text-left font-medium">Old</th>
                                                        <th class="text-left font-medium">New</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($keys as $key)
                                                        @php
                                                            $old = ($log->old_values ?? [])[$key] ?? null;
                                                            $new = ($log->new_values ?? [])[$key] ?? null;
                                                        @endphp
                                                        <tr>
                                                            <td class="pe-4 text-gray-600">{{ $key }}</td>
                                                            <td class="pe-4 text-red-600">{{ is_array($old) ? json_encode($old) : ($old ?? '-') }}</td>
                                                            <td class="text-green-700">{{ is_array($new) ? json_encode($new) : ($new ?? '-') }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        </div>
    </div>
</x-app-layout>