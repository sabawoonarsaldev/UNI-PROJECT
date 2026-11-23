<?php

namespace App\Http\Controllers;

use App\Services\ProfileData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MyProfileController extends Controller
{
    public function show()
    {
        return view('profile.my', ProfileData::for(auth()->user()));
    }

    // A user changes only his photo and contact details. Everything else is managed by the administration.
    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $changes = ['phone' => $data['phone'] ?? null, 'address' => $data['address'] ?? null];

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::delete($user->photo_path);
            }

            $changes['photo_path'] = $request->file('photo')->store('photos/users');
        } elseif ($request->boolean('remove_photo') && $user->photo_path) {
            Storage::delete($user->photo_path);
            $changes['photo_path'] = null;
        }

        $user->update($changes);

        return redirect()->route('my-profile')->with('success', 'Your profile was updated.');
    }
}