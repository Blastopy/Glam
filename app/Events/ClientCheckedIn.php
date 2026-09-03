<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientCheckedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Appointment $appointment)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('provider.' . $this->appointment->provider_profile_id)];
    }

    public function broadcastAs(): string
    {
        return 'appointment.checked-in';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'client_name' => $this->appointment->client->name,
            'checked_in_at' => $this->appointment->checked_in_at,
        ];
    }
}
