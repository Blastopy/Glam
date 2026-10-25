<?php

namespace App\Events;

use App\Models\ProductListingPayment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductListingPaymentConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ProductListingPayment $payment)
    {
    }

    /**
     * Broadcasts to a generic `user.{id}` channel rather than the
     * `provider.{id}` channel used elsewhere — a product seller isn't
     * necessarily a provider (any role can list a product), so this needed
     * its own channel not tied to having a provider profile. See
     * routes/channels.php for the new authorization rule this requires.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->payment->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'listing.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'product_id' => $this->payment->product_id,
            'payment_id' => $this->payment->id,
            'expires_at' => $this->payment->period_end,
        ];
    }
}
