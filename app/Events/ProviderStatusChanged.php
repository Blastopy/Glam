<?php

namespace App\Events;

use App\Models\ProviderProfile;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProviderStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProviderProfile $providerProfile)
    {
    }

    /**
     * Public channel — any client browsing the discover page can see this
     * provider's live open/closed state without authenticating a private channel.
     */
    public function broadcastOn(): array
    {
        return [new Channel('providers.' . $this->providerProfile->id)];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'is_open' => $this->providerProfile->is_open,
            'closed_reason' => $this->providerProfile->closed_reason,
        ];
    }
}
