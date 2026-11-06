<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">University Research Project Repository</h2>
    </x-slot>

    @php($select = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500')

    <div class="py-8">
        <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('repository.index') }}"
                  class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex gap-2">
                    <input type="text" name="q" value="{{ request('q') }}"
                           placeholder="Search by title, abstract, keyword, student or supervisor..."
                           class="flex-1 {{ $select }}">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        <x-icon name="magnifying-glass" class="h-4 w-4" /> Search
                    </button>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <select name="department_id" class="{{ $select }}">
                        <option value="">All departments</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(request('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>

                    <select name="type" class="{{ $select }}">
                        <option value="">All types</option>
                        @foreach (\App\Models\Project::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="year" class="{{ $select }}">
                        <option value="">All years</option>
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected(request('year') == $year)>{{ $year }}</option>
                        @endforeach
                    </select>

                    <select name="supervisor_id" class="{{ $select }}">
                        <option value="">All supervisors</option>
                        @foreach ($supervisors as $supervisor)
                            <option value="{{ $supervisor->id }}" @selected(request('supervisor_id') == $supervisor->id)>{{ $supervisor->name }}</option>
                        @endforeach
                    </select>

                    <select name="sort" class="{{ $select }}">
                        <option value="">Newest first</option>
                        <option value="title" @selected(request('sort') === 'title')>Title (A-Z)</option>
                        <option value="downloads" @selected(request('sort') === 'downloads')>Most downloaded</option>
                    </select>
                </div>

                @if (request('keyword'))
                    <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                    <div class="text-sm text-gray-600">
                        Keyword: <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">{{ request('keyword') }}</span>
                        <a href="{{ route('repository.index', request()->except('keyword', 'page')) }}" class="ms-2 text-indigo-600 hover:underline">clear</a>
                    </div>
                @endif

                @if (request()->hasAny(['q', 'department_id', 'type', 'year', 'supervisor_id', 'sort', 'keyword']))
                    <a href="{{ route('repository.index') }}" class="text-sm text-gray-600 hover:underline">Reset all filters</a>
                @endif
            </form>

            @if ($popularKeywords->isNotEmpty())
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-gray-500">Popular keywords:</span>
                    @foreach ($popularKeywords as $keyword)
                        <a href="{{ route('repository.index', ['keyword' => $keyword->name]) }}"
                           class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                            {{ $keyword->name }} ({{ $keyword->projects_count }})
                        </a>
                    @endforeach
                </div>
            @endif

            <div class="text-sm text-gray-500">{{ $projects->total() }} project(s) found</div>

            @forelse ($projects as $project)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <a href="{{ route('repository.show', $project) }}" class="text-lg font-semibold text-indigo-700 hover:underline">
                        {{ $project->title }}
                    </a>

                    <div class="mt-1 text-xs text-gray-500">
                        {{ $project->student->name }}
                        | Supervisor: {{ $project->supervisor?->name ?? '-' }}
                        | {{ $project->department?->name ?? '-' }}
                        | {{ \App\Models\Project::TYPES[$project->type] ?? $project->type }}
                        | {{ $project->year }}
                    </div>

                    <p class="mt-3 text-sm text-gray-700">{{ \Illuminate\Support\Str::limit($project->abstract, 240) }}</p>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($project->keywords as $keyword)
                                <a href="{{ route('repository.index', ['keyword' => $keyword->name]) }}"
                                   class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600 hover:bg-gray-200">{{ $keyword->name }}</a>
                            @endforeach
                        </div>
                        <span class="text-xs text-gray-500">{{ $project->downloads_count }} download(s)</span>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-gray-200 bg-white p-10 text-center text-sm text-gray-500 shadow-sm">
                    No approved projects match your search.
                </div>
            @endforelse

            {{ $projects->links() }}
        </div>
    </div>
</x-app-layout>