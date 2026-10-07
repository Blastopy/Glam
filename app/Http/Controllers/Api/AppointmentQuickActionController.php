<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

/**
 * Endpoints a push notification's Accept/Decline buttons hit directly — secured
 * by Laravel's signed-URL middleware rather than a bearer token, since a service
 * worker reacting to a notification click doesn't have easy access to the SPA's
 * normal auth state. The signature itself (tied to this exact appointment ID,
 * expiring after 24h — see NewAppointmentBookedPush) is what proves the request
 * is legitimate, the same trust model Laravel's own signed URLs use for things
 * like email-verification links.
 */
class AppointmentQuickActionController extends Controller
{
    public function accept(Request $request, Appointment $appointment)
    {
        return $this->applyStatus($appointment, 'confirmed', 'Booking confirmed — the client has been notified.');
    }

    public function decline(Request $request, Appointment $appointment)
    {
        return $this->applyStatus($appointment, 'cancelled', 'Booking declined.');
    }

    private function applyStatus(Appointment $appointment, string $status, string $successMessage)
    {
        if (! in_array($appointment->status, ['pending', 'confirmed'], true)) {
            return $this->html('This booking has already been ' . $appointment->status . ' — no change made.', isError: true);
        }

        $appointment->update(['status' => $status]);

        return $this->html($successMessage);
    }

    /**
     * These links are opened via a background fetch() from the service worker
     * most of the time (no visible page), but can also be opened directly if
     * someone taps the notification body itself or the link is shared/reopened
     * — so a plain, readable HTML response is friendlier than raw JSON here.
     */
    private function html(string $message, bool $isError = false)
    {
        $color = $isError ? '#B54B2C' : '#7C9473';

        return response(
            "<!DOCTYPE html><html><head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
            <style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#FBF6EC;}
            p{color:{$color};font-size:1.1rem;text-align:center;padding:1rem;}</style></head>
            <body><p>" . htmlspecialchars($message) . '</p></body></html>',
            200,
            ['Content-Type' => 'text/html']
        );
    }
}
