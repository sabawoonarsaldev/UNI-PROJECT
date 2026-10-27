<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    // List of conversations, the most recent first
    public function index()
    {
        $me = auth()->id();

        $messages = Message::with(['sender', 'receiver'])
            ->where('sender_id', $me)
            ->orWhere('receiver_id', $me)
            ->latest()
            ->get();

        $conversations = $messages
            ->groupBy(fn ($message) => $message->sender_id == $me ? $message->receiver_id : $message->sender_id)
            ->map(function ($group) use ($me) {
                $latest = $group->first();

                return [
                    'user' => $latest->sender_id == $me ? $latest->receiver : $latest->sender,
                    'latest' => $latest,
                    'unread' => $group->where('receiver_id', $me)->where('is_read', false)->count(),
                ];
            });

        return view('messages.index', compact('conversations'));
    }

    // Choose who to write to
    public function create()
    {
        $me = auth()->user();

        $recipients = $me->role === 'admin'
            ? User::whereIn('role', ['teacher', 'student'])->orderBy('role')->orderBy('name')->get()
            : User::where('role', 'admin')->orderBy('name')->get();

        return view('messages.create', compact('recipients'));
    }

    public function start(Request $request)
    {
        $request->validate(['recipient_id' => 'required|exists:users,id']);

        $recipient = User::findOrFail($request->recipient_id);
        abort_unless($this->canMessage(auth()->user(), $recipient), 403);

        return redirect()->route('messages.show', $recipient);
    }

    // One conversation
    public function show(User $user)
    {
        $me = auth()->user();
        abort_unless($this->canMessage($me, $user), 403);

        // Opening the conversation marks the received messages as read
        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $messages = Message::where(function ($query) use ($me, $user) {
                $query->where('sender_id', $me->id)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($query) use ($me, $user) {
                $query->where('sender_id', $user->id)->where('receiver_id', $me->id);
            })
            ->orderBy('created_at')
            ->get();

        return view('messages.show', compact('user', 'messages'));
    }

    public function store(Request $request, User $user)
    {
        $me = auth()->user();
        abort_unless($this->canMessage($me, $user), 403);

        $validated = $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        Message::create([
            'sender_id' => $me->id,
            'receiver_id' => $user->id,
            'body' => $validated['body'],
        ]);

        return redirect()->route('messages.show', $user);
    }

    // Admins can write to teachers and students; teachers and students can only write to admins
    private function canMessage(User $me, User $other): bool
    {
        if ($me->id === $other->id) {
            return false;
        }

        if ($me->role === 'admin') {
            return in_array($other->role, ['teacher', 'student']);
        }

        return $other->role === 'admin';
    }
}