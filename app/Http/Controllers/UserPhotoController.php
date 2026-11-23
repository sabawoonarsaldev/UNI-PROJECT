<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

class UserPhotoController extends Controller
{
    // Photos are private: only the admin and the owner can open them
    public function show(User $user)
    {
        $viewer = auth()->user();

        abort_unless($viewer->role === 'admin' || $viewer->id === $user->id, 403);
        abort_unless($user->photo_path && Storage::exists($user->photo_path), 404);

        return Storage::response($user->photo_path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}