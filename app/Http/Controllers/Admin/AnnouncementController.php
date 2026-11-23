<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use App\Models\Faculty;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['faculty', 'department', 'creator'])
            ->latest()
            ->paginate(10);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        return view('admin.announcements.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by'] = auth()->id();

        Announcement::create($validated);

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement published successfully.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.edit', $this->formData() + ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Announcement deleted successfully.');
    }

    private function formData(): array
    {
        return [
            'faculties' => Faculty::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name', 'faculty_id']),
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'target_role' => ['required', Rule::in(['all', 'teacher', 'student'])],
            'faculty_id' => 'nullable|exists:faculties,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // A department belongs to a faculty
        if (! empty($data['department_id'])) {
            $department = Department::findOrFail($data['department_id']);

            if (! empty($data['faculty_id']) && (int) $data['faculty_id'] !== $department->faculty_id) {
                throw ValidationException::withMessages([
                    'department_id' => 'This department does not belong to the selected faculty.',
                ]);
            }

            $data['faculty_id'] = $department->faculty_id;
        }

        // No faculty = every faculty, so there is no department either
        if (empty($data['faculty_id'])) {
            $data['department_id'] = null;
        }

        return $data;
    }
}