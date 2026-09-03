<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositConfirmed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('client.' . $this->payment->client_id),
            new PrivateChannel('provider.' . $this->payment->appointment->provider_profile_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'deposit.confirmed';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->payment->appointment_id,
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'mpesa_receipt_number' => $this->payment->mpesa_receipt_number,
        ];
    }
}
