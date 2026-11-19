<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Users
            </h2>
            <a href="{{ route('admin.users.create') }}"
               class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                + Add User
            </a>
        </div>
    </x-slot>

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500')

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-800 rounded-md">{{ session('error') }}</div>
            @endif

            {{-- Live search and filters: the results below change while you type or choose --}}
            <form id="user-search-form" method="GET" action="{{ route('admin.users.index') }}" class="mb-4 space-y-3">
                @if (request('role'))
                    <input type="hidden" name="role" value="{{ request('role') }}">
                @endif

                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <x-icon name="magnifying-glass" class="h-5 w-5" />
                        </span>
                        <input id="user-search-input" type="text" name="q" value="{{ request('q') }}"
                               autocomplete="off" autofocus
                               placeholder="Search by name, email, roll number or department..."
                               class="block w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <button type="button" id="user-search-clear"
                            class="{{ request('q') ? '' : 'hidden' }} items-center rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Clear
                    </button>
                </div>

                {{-- Admins belong to no faculty, so the filters are hidden for them --}}
                @if (request('role') !== 'admin')
                    <div class="flex flex-wrap items-center gap-2">
                        <select name="faculty_id" id="faculty-filter" class="{{ $select }}">
                            <option value="">All faculties</option>
                            @foreach ($faculties as $faculty)
                                <option value="{{ $faculty->id }}" @selected(request('faculty_id') == $faculty->id)>{{ $faculty->name }}</option>
                            @endforeach
                        </select>

                        <select name="department_id" id="department-filter" class="{{ $select }}">
                            <option value="">All departments</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}" data-faculty="{{ $department->faculty_id }}"
                                    @selected(request('department_id') == $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" id="reset-filters"
                                class="{{ request('faculty_id') || request('department_id') ? '' : 'hidden' }} text-sm text-gray-600 hover:underline">
                            Reset filters
                        </button>
                    </div>
                @endif
            </form>

            {{-- Everything inside this box is replaced by the live search --}}
            <div id="users-results">
                @php($keep = array_filter([
                    'q' => request('q'),
                    'faculty_id' => request('faculty_id'),
                    'department_id' => request('department_id'),
                ]))

                {{-- Role filter (keeps the search text and the filters) --}}
                <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
                    <a href="{{ route('admin.users.index', $keep) }}"
                       class="px-3 py-1 rounded-md {{ request('role') ? 'bg-gray-200 text-gray-700' : 'bg-indigo-600 text-white' }}">All</a>

                    <a href="{{ route('admin.users.index', array_filter(['role' => 'admin', 'q' => request('q')])) }}"
                       class="px-3 py-1 rounded-md {{ request('role') === 'admin' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">Admins</a>

                    @foreach (['teacher' => 'Teachers', 'student' => 'Students'] as $value => $label)
                        <a href="{{ route('admin.users.index', $keep + ['role' => $value]) }}"
                           class="px-3 py-1 rounded-md {{ request('role') === $value ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }}">{{ $label }}</a>
                    @endforeach

                    <span class="ms-auto text-gray-500">{{ $users->total() }} user(s) found</span>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Faculty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roll No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($users as $user)
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->email }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ ucfirst($user->role) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->department?->faculty?->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->department?->name ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ $user->roll_number ?? '-' }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        @if ($user->hasAccount())
                                            <span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Has account</span>
                                        @else
                                            <a href="{{ route('admin.users.edit', $user) }}" title="Click to create a login account"
                                               class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-600 hover:bg-gray-200">
                                                No account
                                            </a>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-right space-x-2">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="text-indigo-600 hover:underline">Edit</a>

                                        @if ($user->id !== auth()->id())
                                            <form action="{{ route('admin.users.destroy', $user) }}"
                                                  method="POST" class="inline"
                                                  onsubmit="return confirm('Delete this user?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                        @if (request('q'))
                                            No users match "{{ request('q') }}".
                                        @else
                                            No users found.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('user-search-form');
            const input = document.getElementById('user-search-input');
            const clearButton = document.getElementById('user-search-clear');
            const results = document.getElementById('users-results');
            const facultySelect = document.getElementById('faculty-filter');       // missing in the admin list
            const departmentSelect = document.getElementById('department-filter');
            const resetButton = document.getElementById('reset-filters');

            let timer = null;
            let controller = null;

            // Only the departments of the selected faculty can be chosen
            function filterDepartments() {
                if (!facultySelect) return;

                const faculty = facultySelect.value;

                Array.from(departmentSelect.options).forEach(option => {
                    if (!option.dataset.faculty) return;
                    const matches = !faculty || option.dataset.faculty === faculty;
                    option.hidden = !matches;
                    option.disabled = !matches;
                });

                const selected = departmentSelect.options[departmentSelect.selectedIndex];
                if (selected && selected.dataset.faculty && faculty && selected.dataset.faculty !== faculty) {
                    departmentSelect.value = '';
                }
            }

            // The address of the current search: every field of the form that is not empty
            function currentUrl() {
                const params = new URLSearchParams();

                new FormData(form).forEach((value, key) => {
                    const text = String(value).trim();
                    if (text !== '') params.set(key, text);
                });

                const query = params.toString();

                return form.action + (query ? '?' + query : '');
            }

            function toggleButtons() {
                clearButton.classList.toggle('hidden', input.value.trim() === '');

                if (resetButton) {
                    resetButton.classList.toggle('hidden', !facultySelect.value && !departmentSelect.value);
                }
            }

            async function search() {
                toggleButtons();

                const url = currentUrl();

                // A newer search cancels the older one that is still running
                if (controller) controller.abort();
                controller = new AbortController();
                results.classList.add('opacity-50');

                try {
                    const response = await fetch(url, {
                        signal: controller.signal,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    });

                    if (!response.ok) throw new Error('Search failed');

                    const page = new DOMParser().parseFromString(await response.text(), 'text/html');
                    results.innerHTML = page.getElementById('users-results').innerHTML;

                    // The address always shows the current search, so a refresh keeps it
                    history.replaceState(null, '', url);
                    results.classList.remove('opacity-50');
                } catch (error) {
                    if (error.name === 'AbortError') return;

                    // Something went wrong (for example the session ended): load the page normally
                    window.location.href = url;
                }
            }

            // Wait 0.3 seconds after the last key, so fast typing sends only one request
            input.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(search, 300);
            });

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                clearTimeout(timer);
                search();
            });

            clearButton.addEventListener('click', () => {
                input.value = '';
                search();
                input.focus();
            });

            if (facultySelect) {
                facultySelect.addEventListener('change', () => {
                    filterDepartments();
                    search();
                });
                departmentSelect.addEventListener('change', search);

                resetButton.addEventListener('click', () => {
                    facultySelect.value = '';
                    departmentSelect.value = '';
                    filterDepartments();
                    search();
                });

                filterDepartments();
            }

            input.setSelectionRange(input.value.length, input.value.length);
        })();
    </script>
</x-app-layout>