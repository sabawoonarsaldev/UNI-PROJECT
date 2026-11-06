<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $project->title }}</h2>
            <a href="{{ route('repository.index') }}" class="text-sm text-gray-600 hover:underline">&larr; Back to repository</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-panel title="Project information">
                <dl class="grid gap-4 p-6 text-sm sm:grid-cols-2">
                    <div><dt class="text-gray-500">Author</dt><dd class="font-medium text-gray-900">{{ $project->student->name }}</dd></div>
                    <div><dt class="text-gray-500">Supervisor</dt><dd class="font-medium text-gray-900">{{ $project->supervisor?->name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt><dd class="font-medium text-gray-900">{{ $project->department?->name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Type / Year</dt>
                        <dd class="font-medium text-gray-900">{{ \App\Models\Project::TYPES[$project->type] ?? $project->type }} / {{ $project->year }}</dd></div>
                    <div><dt class="text-gray-500">Published</dt><dd class="font-medium text-gray-900">{{ $project->approved_at?->format('Y-m-d') }}</dd></div>
                    <div><dt class="text-gray-500">Downloads</dt><dd class="font-medium text-gray-900">{{ $downloadsTotal }}</dd></div>
                </dl>
            </x-panel>

            <x-panel title="Abstract">
                <div class="space-y-4 p-6">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $project->abstract }}</p>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($project->keywords as $keyword)
                            <a href="{{ route('repository.index', ['keyword' => $keyword->name]) }}"
                               class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100">{{ $keyword->name }}</a>
                        @endforeach
                    </div>
                </div>
            </x-panel>

            <x-panel title="Document">
                <div class="p-6">
                    @if ($document)
                        <a href="{{ route('projects.files.download', $document) }}"
                           class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                            <x-icon name="arrow-down-tray" class="h-5 w-5" />
                            Download {{ $document->original_name }} ({{ $document->humanSize() }})
                        </a>
                    @else
                        <p class="text-sm text-gray-500">No document is available.</p>
                    @endif
                </div>
            </x-panel>
        </div>
    </div>
</x-app-layout>