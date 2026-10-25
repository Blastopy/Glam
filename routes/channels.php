<?php

use App\Models\ProviderProfile;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
| Each channel here needs an explicit authorization rule — Echo will call
| /broadcasting/auth (protected by Sanctum) before subscribing a private channel.
*/

// A provider's dashboard listens here for new bookings, check-ins, status changes.
// Only that provider (the owning user) may subscribe.
Broadcast::channel('provider.{providerProfileId}', function ($user, $providerProfileId) {
    return $user->providerProfile?->id === (int) $providerProfileId;
});

// Direct chat between two specific users — sorted so both sides compute the same
// channel name regardless of who initiated it.
Broadcast::channel('conversation.{userA}.{userB}', function ($user, $userA, $userB) {
    return in_array($user->id, [(int) $userA, (int) $userB], true);
});

// A client listens here for their own appointment status updates + deposit confirmation.
Broadcast::channel('client.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});