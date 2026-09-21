<?php

namespace App\Events;

use App\Models\ProviderSubscriptionPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionPaymentConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProviderSubscriptionPayment $payment)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('provider.' . $this->payment->provider_profile_id)];
    }

    public function broadcastAs(): string
    {
        return 'subscription.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'expires_at' => $this->payment->period_end,
        ];
    }
}
