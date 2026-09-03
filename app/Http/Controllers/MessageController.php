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

    /** List distinct conversations with last message + unread count. */
    public function index(Request $request)
    {
        $me = $request->user();

        $conversations = Message::selectRaw(
                'CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as other_user_id, MAX(created_at) as last_message_at',
                [$me->id]
            )
            ->where('sender_id', $me->id)->orWhere('receiver_id', $me->id)
            ->groupBy('other_user_id')
            ->orderByDesc('last_message_at')
            ->with(['sender', 'receiver'])
            ->get();

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
