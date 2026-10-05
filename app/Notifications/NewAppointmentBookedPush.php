<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewAppointmentBookedPush extends Notification
{
    public function __construct(public Appointment $appointment)
    {
    }

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * The Accept/Decline buttons are the whole point of this notification —
     * they're signed, temporary URLs (not requiring the bearer token the SPA
     * normally uses) so the service worker can fire them directly in the
     * background with a plain fetch(), without needing API auth state that a
     * service worker doesn't have easy access to. See
     * AppointmentQuickActionController for what they actually do.
     */
    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        $acceptUrl = URL::temporarySignedRoute(
            'appointments.quick-accept',
            now()->addHours(24),
            ['appointment' => $this->appointment->id]
        );

        $declineUrl = URL::temporarySignedRoute(
            'appointments.quick-decline',
            now()->addHours(24),
            ['appointment' => $this->appointment->id]
        );

        return (new WebPushMessage)
            ->title('New booking request')
            ->icon('/logo.jpg')
            ->body("{$this->appointment->client->name} wants to book {$this->appointment->service->name} — " . $this->appointment->scheduled_at->format('D, M j \a\t g:ia'))
            ->action('Accept', 'accept')
            ->action('Decline', 'decline')
            ->data([
                'appointment_id' => $this->appointment->id,
                'accept_url' => $acceptUrl,
                'decline_url' => $declineUrl,
                // Where a plain (non-action-button) tap on the notification body opens
                'open_url' => '/dashboard',
            ])
            ->options(['TTL' => 86400]); // drop it after 24h if the device was offline the whole time
    }
}
