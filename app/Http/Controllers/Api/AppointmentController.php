<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ClientCheckedIn;
use App\Events\NewAppointmentBooked;
use App\Models\Appointment;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    /** List appointments for the logged-in user (as client or as provider). */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isProvider()) {
            $query = $user->providerProfile->appointments()
                ->with(['client:id,name,phone,avatar', 'service:id,name,price,duration_minutes']);
        } else {
            $query = $user->appointmentsAsClient()
                // `review` is eager-loaded so the frontend can tell, per appointment,
                // whether the client has already rated it — without a second request.
                ->with(['providerProfile:id,business_name,cover_image', 'service:id,name,price', 'review']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('scheduled_at')->paginate(20));
    }

    /** Feature #4: client books an appointment — optionally as a home visit. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date|after:now',
            'client_notes' => 'nullable|string|max:1000',
            'is_home_service' => 'sometimes|boolean',
            'home_address' => 'required_if:is_home_service,true|nullable|string|max:255',
            // Optional — the client's browser may decline geolocation permission,
            // in which case the provider only sees the typed address instead.
            'home_latitude' => 'nullable|numeric|between:-90,90',
            'home_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $service = Service::with('providerProfile')->findOrFail($validated['service_id']);
        $wantsHomeService = $validated['is_home_service'] ?? false;

        abort_if(! $service->providerProfile->is_open, 422, 'This provider is currently closed');
        abort_if(! $service->providerProfile->is_subscription_active, 422, 'This provider is not currently accepting bookings.');
        abort_if($wantsHomeService && ! $service->providerProfile->offers_home_service, 422, 'This provider does not offer home visits.');

        // Wrapped in a transaction with a row lock on the provider's same-day appointments
        // so two clients booking the same slot at the same instant can't both succeed —
        // the second request blocks briefly, then sees the first one's booking and is rejected.
        $appointment = DB::transaction(function () use ($service, $validated, $wantsHomeService, $request) {
            $start = Carbon::parse($validated['scheduled_at']);
            $end = $start->copy()->addMinutes($service->duration_minutes);

            $sameDayAppointments = Appointment::where('provider_profile_id', $service->provider_profile_id)
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->whereDate('scheduled_at', $start->toDateString())
                ->with('service:id,duration_minutes')
                ->lockForUpdate()
                ->get();

            $hasConflict = $sameDayAppointments->contains(function ($existing) use ($start, $end) {
                $existingStart = Carbon::parse($existing->scheduled_at);
                $existingEnd = $existingStart->copy()->addMinutes($existing->service->duration_minutes ?? 30);

                return $start->lt($existingEnd) && $end->gt($existingStart);
            });

            abort_if($hasConflict, 422, 'This time slot is no longer available with this provider — please pick another time.');

            // The callout fee (if any) is folded into price_at_booking at creation
            // time, same reasoning as the service price snapshot: a later change
            // to the provider's fee shouldn't retroactively alter past bookings.
            $price = $service->price;
            if ($wantsHomeService && $service->providerProfile->home_service_fee) {
                $price += $service->providerProfile->home_service_fee;
            }

            return Appointment::create([
                'client_id' => $request->user()->id,
                'provider_profile_id' => $service->provider_profile_id,
                'service_id' => $service->id,
                'scheduled_at' => $validated['scheduled_at'],
                'price_at_booking' => $price,
                'client_notes' => $validated['client_notes'] ?? null,
                'is_home_service' => $wantsHomeService,
                'home_address' => $wantsHomeService ? $validated['home_address'] : null,
                'home_latitude' => $wantsHomeService ? ($validated['home_latitude'] ?? null) : null,
                'home_longitude' => $wantsHomeService ? ($validated['home_longitude'] ?? null) : null,
                'status' => 'pending',
            ]);
        });

        broadcast(new NewAppointmentBooked($appointment));

        return response()->json($appointment->load('service', 'providerProfile'), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeParty($request, $appointment);

        return response()->json($appointment->load('client:id,name,phone,avatar', 'service', 'providerProfile', 'review'));
    }

    /** Provider confirms/starts/completes/no-shows; client may only cancel their own booking. */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $this->authorizeParty($request, $appointment);

        $isProvider = $appointment->provider_profile_id === $request->user()->providerProfile?->id;

        $allowedStatuses = $isProvider
            ? ['confirmed', 'in_progress', 'completed', 'cancelled', 'no_show']
            : ['cancelled']; // a client can cancel, but only a provider can confirm/start/complete/no-show

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'provider_notes' => 'nullable|string|max:1000',
        ]);

        if (! $isProvider) {
            abort_if(
                ! in_array($appointment->status, ['pending', 'confirmed'], true),
                422,
                'This appointment can no longer be cancelled.'
            );
            unset($validated['provider_notes']); // clients don't write provider notes
        }

        $appointment->update($validated);

        if ($validated['status'] === 'completed') {
            // Auto-add client to provider's book (feature #7)
            $appointment->providerProfile->clients()->syncWithoutDetaching([
                $appointment->client_id => ['source' => 'auto_after_booking'],
            ]);

            $appointment->providerProfile->increment('completed_appointments_count');
        }

        return response()->json($appointment);
    }

    /** Feature #1: client checks in — shows name + arrival time to the provider. */
    public function checkIn(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->client_id === $request->user()->id, 403);

        $appointment->checkIn();

        broadcast(new ClientCheckedIn($appointment));

        return response()->json($appointment);
    }

    private function authorizeParty(Request $request, Appointment $appointment): void
    {
        $user = $request->user();
        $isClient = $appointment->client_id === $user->id;
        $isProvider = $appointment->provider_profile_id === $user->providerProfile?->id;

        abort_unless($isClient || $isProvider, 403);
    }
}
