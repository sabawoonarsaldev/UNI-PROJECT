<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ $project->title }}</h2>
            <x-project-status :status="$project->status" />
        </div>
    </x-slot>

    @php
        $me = auth()->user();
        $isStudent = $project->student_id === $me->id;
        $lastRevision = $project->comments->where('type', 'status')->where('status', 'revision')->last();
        $roleLabels = ['admin' => 'Admin', 'teacher' => 'Supervisor', 'student' => 'Student'];
        $input = 'mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="rounded-md bg-green-100 p-4 text-sm text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-100 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            {{-- What the student has to do now --}}
            @if ($isStudent)
                @if ($project->status === 'revision')
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-900">
                        <div class="font-semibold">Changes were requested</div>
                        <p class="mt-1 whitespace-pre-line">{{ $lastRevision?->body }}</p>
                        <p class="mt-2 text-red-700">Update your project, upload a new version and submit it again.</p>
                    </div>
                @elseif ($project->status === 'draft')
                    <div class="rounded-xl border border-gray-200 bg-white p-5 text-sm text-gray-700">
                        Upload your document, then click <strong>Submit to supervisor</strong>.
                    </div>
                @elseif ($project->status === 'submitted')
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
                        Your project is waiting for the review of your supervisor.
                    </div>
                @elseif ($project->status === 'supervisor_approved')
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 text-sm text-blue-900">
                        Your supervisor approved the project. It is waiting for the final approval of the administration.
                    </div>
                @elseif ($project->status === 'approved')
                    <div class="rounded-xl border border-green-200 bg-green-50 p-5 text-sm text-green-900">
                        Your project is approved and published in the repository.
                        <a href="{{ route('repository.show', $project) }}" class="font-medium underline">Open the repository page</a>
                    </div>
                @endif
            @endif

            {{-- Overview --}}
            <x-panel title="Project information">
                <dl class="grid gap-4 p-6 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-gray-500">Student</dt>
                        <dd class="font-medium text-gray-900">{{ $project->student->name }}
                            <span class="text-gray-500">({{ $project->student->roll_number ?? '-' }})</span></dd></div>
                    <div><dt class="text-gray-500">Supervisor</dt>
                        <dd class="font-medium text-gray-900">{{ $project->supervisor?->name ?? 'Not assigned' }}</dd></div>
                    <div><dt class="text-gray-500">Department</dt>
                        <dd class="font-medium text-gray-900">{{ $project->department?->name ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Type / Year</dt>
                        <dd class="font-medium text-gray-900">{{ \App\Models\Project::TYPES[$project->type] ?? $project->type }} / {{ $project->year }}</dd></div>
                    <div><dt class="text-gray-500">Submitted</dt>
                        <dd class="font-medium text-gray-900">{{ $project->submitted_at?->format('Y-m-d H:i') ?? '-' }}</dd></div>
                    <div><dt class="text-gray-500">Approved</dt>
                        <dd class="font-medium text-gray-900">{{ $project->approved_at?->format('Y-m-d H:i') ?? '-' }}</dd></div>
                </dl>
            </x-panel>

            {{-- Actions of the student --}}
            @can('submit', $project)
                <form method="POST" action="{{ route('projects.submit', $project) }}"
                      onsubmit="return confirm('Submit the project to your supervisor? You cannot change it until the review is finished.');">
                    @csrf
                    <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                        Submit to supervisor
                    </button>
                </form>
            @endcan

            {{-- Review of the supervisor --}}
            @can('supervisorReview', $project)
                <x-panel title="Your review">
                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        <form method="POST" action="{{ route('projects.supervisor-approve', $project) }}" class="space-y-3">
                            @csrf
                            <x-input-label for="approve_note" value="Approve (the note is optional)" />
                            <textarea id="approve_note" name="note" rows="3" class="{{ $input }}"></textarea>
                            <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                Approve and send to admin
                            </button>
                        </form>

                        <form method="POST" action="{{ route('projects.request-revision', $project) }}" class="space-y-3">
                            @csrf
                            <x-input-label for="revision_note" value="Request changes (the note is required)" />
                            <textarea id="revision_note" name="note" rows="3" required class="{{ $input }}"></textarea>
                            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                Request revision
                            </button>
                        </form>
                    </div>
                </x-panel>
            @endcan

            {{-- Final review of the admin --}}
            @canany(['finalApprove', 'returnToStudent'], $project)
                <x-panel title="Administration review">
                    <div class="grid gap-6 p-6 md:grid-cols-2">
                        @can('finalApprove', $project)
                            <form method="POST" action="{{ route('projects.final-approve', $project) }}" class="space-y-3">
                                @csrf
                                <x-input-label for="final_note" value="Final approval (the note is optional)" />
                                <textarea id="final_note" name="note" rows="3" class="{{ $input }}"></textarea>
                                <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700">
                                    Approve and publish
                                </button>
                            </form>
                        @endcan

                        @can('returnToStudent', $project)
                            <form method="POST" action="{{ route('projects.return', $project) }}" class="space-y-3">
                                @csrf
                                <x-input-label for="return_note" value="Return to the student (the note is required)" />
                                <textarea id="return_note" name="note" rows="3" required class="{{ $input }}"></textarea>
                                <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                                    Return for revision
                                </button>
                            </form>
                        @endcan
                    </div>
                </x-panel>
            @endcanany

            {{-- Abstract and keywords --}}
            <x-panel title="Abstract">
                <div class="space-y-4 p-6">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-700">{{ $project->abstract }}</p>

                    <div class="flex flex-wrap gap-2">
                        @forelse ($project->keywords as $keyword)
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-700">{{ $keyword->name }}</span>
                        @empty
                            <span class="text-xs text-gray-400">No keywords.</span>
                        @endforelse
                    </div>
                </div>

                @can('update', $project)
                    <details class="border-t border-gray-100">
                        <summary class="cursor-pointer px-6 py-3 text-sm font-medium text-indigo-600">Edit project details</summary>
                        <form method="POST" action="{{ route('projects.update', $project) }}" class="space-y-6 p-6">
                            @csrf
                            @method('PUT')

                            @include('projects._fields', ['project' => $project])

                            <x-primary-button>Save changes</x-primary-button>
                        </form>
                    </details>
                @endcan
            </x-panel>

            {{-- Files --}}
            <x-panel title="Files">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">File</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Uploaded by</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($project->files as $file)
                                <tr>
                                    <td class="px-6 py-3 text-sm">
                                        <div class="font-medium text-gray-900">{{ $file->original_name }}</div>
                                        <div class="text-xs text-gray-500">
                                            {{ $file->humanSize() }}@if ($file->note) | {{ $file->note }}@endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-sm">
                                        @if ($file->kind === 'document')
                                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">Document v{{ $file->version }}</span>
                                        @else
                                            <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">Feedback v{{ $file->version }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-600">{{ $file->uploader?->name ?? '-' }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-500">{{ $file->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-3 text-right text-sm">
                                        <a href="{{ route('projects.files.download', $file) }}"
                                           class="inline-flex items-center gap-1 text-indigo-600 hover:underline">
                                            <x-icon name="arrow-down-tray" class="h-4 w-4" /> Download
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No files uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @canany(['uploadDocument', 'uploadFeedback'], $project)
                    <form method="POST" action="{{ route('projects.files.store', $project) }}" enctype="multipart/form-data"
                          class="space-y-3 border-t border-gray-100 p-6">
                        @csrf

                        <div class="text-sm font-medium text-gray-800">
                            {{ $isStudent ? 'Upload a new version of your document' : 'Upload a feedback file for the student' }}
                        </div>

                        <input type="file" name="file" required
                               class="block w-full text-sm text-gray-600 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-indigo-700 hover:file:bg-indigo-100">
                        <x-input-error :messages="$errors->get('file')" />

                        <input type="text" name="note" maxlength="500" placeholder="Note (optional)" class="{{ $input }}">

                        <x-primary-button>Upload</x-primary-button>
                        <p class="text-xs text-gray-500">PDF, Word, PowerPoint or ZIP. Maximum 20 MB.</p>
                    </form>
                @endcanany
            </x-panel>

            {{-- Discussion and history --}}
            <div id="discussion">
                <x-panel title="Discussion and history">
                    <div class="space-y-3 p-6">
                        @foreach ($project->comments as $comment)
                            @if ($comment->type === 'status')
                                <div class="rounded-md bg-gray-50 px-4 py-2 text-center text-xs text-gray-600">
                                    <x-project-status :status="$comment->status" />
                                    @if ($comment->body)
                                        <span class="ms-2 whitespace-pre-line">{{ $comment->body }}</span>
                                    @endif
                                    <span class="ms-2 text-gray-400">
                                        {{ $comment->user?->name }} | {{ $comment->created_at->format('Y-m-d H:i') }}
                                    </span>
                                </div>
                            @else
                                @php($mine = $comment->user_id === $me->id)
                                <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                                    <div class="max-w-xl rounded-lg px-4 py-2 text-sm {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800' }}">
                                        <div class="mb-1 text-xs font-semibold {{ $mine ? 'text-indigo-200' : 'text-gray-500' }}">
                                            {{ $comment->user?->name ?? 'Deleted user' }}
                                            ({{ $roleLabels[$comment->user?->role] ?? '-' }})
                                        </div>
                                        <p class="whitespace-pre-line">{{ $comment->body }}</p>
                                        <div class="mt-1 text-xs {{ $mine ? 'text-indigo-200' : 'text-gray-500' }}">
                                            {{ $comment->created_at->format('Y-m-d H:i') }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @can('comment', $project)
                        <form method="POST" action="{{ route('projects.comments.store', $project) }}"
                              class="space-y-3 border-t border-gray-100 p-6">
                            @csrf
                            <textarea name="body" rows="3" required placeholder="Write a message..."
                                      class="{{ $input }}">{{ old('body') }}</textarea>
                            <x-primary-button>Send</x-primary-button>
                        </form>
                    @else
                        <div class="border-t border-gray-100 px-6 py-4 text-sm text-gray-500">
                            The discussion is closed because the project is approved.
                        </div>
                    @endcan
                </x-panel>
            </div>

            {{-- Download records (admin) --}}
            @if ($me->role === 'admin')
                <x-panel :title="'Download records (' . $downloadsTotal . ')'">
                    <ul class="divide-y divide-gray-100">
                        @forelse ($downloads as $download)
                            <li class="flex justify-between px-6 py-3 text-sm">
                                <span class="text-gray-900">
                                    {{ $download->user?->name ?? 'Deleted user' }}
                                    <span class="text-gray-500">downloaded {{ $download->file?->original_name }}</span>
                                </span>
                                <span class="text-gray-500">{{ $download->created_at->format('Y-m-d H:i') }}</span>
                            </li>
                        @empty
                            <li class="px-6 py-6 text-center text-sm text-gray-500">No downloads recorded yet.</li>
                        @endforelse
                    </ul>
                </x-panel>
            @endif
        </div>
    </div>
</x-app-layout>