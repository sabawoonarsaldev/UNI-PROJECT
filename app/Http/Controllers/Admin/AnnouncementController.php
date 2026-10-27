<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with(['department', 'creator'])
            ->latest()
            ->paginate(10);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();

        return view('admin.announcements.create', compact('departments'));
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
        $departments = Department::orderBy('name')->get();

        return view('admin.announcements.edit', compact('announcement', 'departments'));
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

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'target_role' => ['required', Rule::in(['all', 'teacher', 'student'])],
            // Empty means "all departments"
            'department_id' => 'nullable|exists:departments,id',
        ]);
    }
}