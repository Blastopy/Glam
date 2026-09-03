<?php

namespace App\Events;

use App\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewAppointmentBooked implements ShouldBroadcast
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
        return 'appointment.booked';
    }

    public function broadcastWith(): array
    {
        // Keep the payload lean — the frontend just needs enough to show a toast
        // and refresh its list, not the full model graph.
        return [
            'appointment_id' => $this->appointment->id,
            'client_name' => $this->appointment->client->name,
            'service_name' => $this->appointment->service->name,
            'scheduled_at' => $this->appointment->scheduled_at,
        ];
    }
}
