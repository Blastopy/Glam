<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\MessageSent as MessageSentEvent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    /** Conversation thread between the logged-in user and one other user. */
    public function thread(Request $request, User $user)
    {
        $me = $request->user();

        $messages = Message::where(function ($q) use ($me, $user) {
                $q->where('sender_id', $me->id)->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($me, $user) {
                $q->where('sender_id', $user->id)->where('receiver_id', $me->id);
            })
            ->orderBy('created_at')
            ->paginate(50);

        Message::where('sender_id', $user->id)
            ->where('receiver_id', $me->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json($messages);
    }

    /** List distinct conversations, with the other person's info, last message, and unread count. */
    public function index(Request $request)
    {
        $me = $request->user();

        $otherUserIds = Message::where('sender_id', $me->id)->pluck('receiver_id')
            ->merge(Message::where('receiver_id', $me->id)->pluck('sender_id'))
            ->unique();

        $conversations = $otherUserIds
            ->map(function ($otherId) use ($me) {
                $otherUser = User::select('id', 'name', 'avatar')->find($otherId);

                if (! $otherUser) {
                    return null; // the other party's account no longer exists
                }

                $lastMessage = Message::where(function ($q) use ($me, $otherId) {
                        $q->where('sender_id', $me->id)->where('receiver_id', $otherId);
                    })
                    ->orWhere(function ($q) use ($me, $otherId) {
                        $q->where('sender_id', $otherId)->where('receiver_id', $me->id);
                    })
                    ->latest('created_at')
                    ->first();

                return [
                    'user' => $otherUser,
                    'last_message' => $lastMessage?->body,
                    'last_message_at' => $lastMessage?->created_at,
                    'unread_count' => Message::where('sender_id', $otherId)
                        ->where('receiver_id', $me->id)
                        ->whereNull('read_at')
                        ->count(),
                ];
            })
            ->filter()
            ->sortByDesc('last_message_at')
            ->values();

        return response()->json($conversations);
    }

    /** Feature #4: send a message, optionally tied to an appointment. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id|different:sender_id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'sender_id' => $request->user()->id,
            'receiver_id' => $validated['receiver_id'],
            'appointment_id' => $validated['appointment_id'] ?? null,
            'body' => $validated['body'],
        ]);

        broadcast(new MessageSentEvent($message))->toOthers();

        return response()->json($message->load('sender:id,name,avatar'), 201);
    }
}
