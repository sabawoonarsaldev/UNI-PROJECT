<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Concerns\HasFacultyFilter;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    use HasFacultyFilter;

    // List of conversations, the most recent first. The admin can search and filter by faculty and department.
    public function index(Request $request)
    {
        $me = auth()->user();

        $messages = Message::with(['sender.department', 'receiver.department'])
            ->where('sender_id', $me->id)
            ->orWhere('receiver_id', $me->id)
            ->latest()
            ->get();

        $conversations = $messages
            ->groupBy(fn ($message) => $message->sender_id == $me->id ? $message->receiver_id : $message->sender_id)
            ->map(function ($group) use ($me) {
                $latest = $group->first();

                return [
                    'user' => $latest->sender_id == $me->id ? $latest->receiver : $latest->sender,
                    'latest' => $latest,
                    'group' => $group,
                    'unread' => $group->where('receiver_id', $me->id)->where('is_read', false)->count(),
                ];
            });

        $term = mb_strtolower(trim((string) $request->query('q')));
        $facultyId = $request->query('faculty_id');
        $departmentId = $request->query('department_id');

        $conversations = $conversations->filter(function ($conversation) use ($term, $facultyId, $departmentId) {
            $user = $conversation['user'];

            // The name of the person, or any message of the conversation
            if ($term !== '' && ! str_contains(mb_strtolower($user->name), $term)
                && ! $conversation['group']->contains(fn ($message) => str_contains(mb_strtolower($message->body), $term))) {
                return false;
            }

            if ($departmentId && $user->department_id != $departmentId) {
                return false;
            }

            if ($facultyId && $user->department?->faculty_id != $facultyId) {
                return false;
            }

            return true;
        });

        return view('messages.index', [
            'conversations' => $conversations,
            'isAdmin' => $me->role === 'admin',
        ] + $this->facultyOptions($request));
    }

    // Choose who to write to. The admin finds the person by search, faculty, department and role.
    public function create(Request $request)
    {
        $me = auth()->user();
        $isAdmin = $me->role === 'admin';

        if ($isAdmin) {
            $query = User::withAccount()
                ->with('department.faculty')
                ->whereIn('role', ['teacher', 'student'])
                ->when($request->query('role') && in_array($request->query('role'), ['teacher', 'student']),
                    fn ($q) => $q->where('role', $request->query('role')));

            $query = $this->limitUsersToFaculty($query, $request)->search($request->query('q'));
        } else {
            // Teachers and students can write only to the admins
            $query = User::where('role', 'admin');
        }

        $recipients = $query->orderBy('role')->orderBy('name')->paginate(20)->withQueryString();

        return view('messages.create', [
            'recipients' => $recipients,
            'isAdmin' => $isAdmin,
        ] + $this->facultyOptions($request));
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

    // Admins can write to teachers and students who have an account; teachers and students only to admins
    private function canMessage(User $me, User $other): bool
    {
        if ($me->id === $other->id) {
            return false;
        }

        if ($me->role === 'admin') {
            return in_array($other->role, ['teacher', 'student']) && $other->hasAccount();
        }

        return $other->role === 'admin';
    }
}