<?php

namespace App\Http\Controllers;

use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // The admin manages announcements from the admin panel
        if ($user->role === 'admin') {
            return redirect()->route('admin.announcements.index');
        }

        $announcements = Announcement::visibleTo($user)
            ->with(['department', 'creator'])
            ->latest()
            ->paginate(10);

        return view('announcements.index', compact('announcements'));
    }
}