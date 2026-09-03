<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ClientCheckedIn;
use App\Events\NewAppointmentBooked;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;
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
                ->with(['providerProfile:id,business_name,cover_image', 'service:id,name,price']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->orderBy('scheduled_at')->paginate(20));
    }

    /** Feature #4: client books an appointment. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date|after:now',
            'client_notes' => 'nullable|string|max:1000',
        ]);

        $service = Service::with('providerProfile')->findOrFail($validated['service_id']);

        abort_if(! $service->providerProfile->is_open, 422, 'This provider is currently closed');

        $appointment = Appointment::create([
            'client_id' => $request->user()->id,
            'provider_profile_id' => $service->provider_profile_id,
            'service_id' => $service->id,
            'scheduled_at' => $validated['scheduled_at'],
            'price_at_booking' => $service->price,
            'client_notes' => $validated['client_notes'] ?? null,
            'status' => 'pending',
        ]);

        broadcast(new NewAppointmentBooked($appointment));

        return response()->json($appointment->load('service', 'providerProfile'), 201);
    }

    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeParty($request, $appointment);

        return response()->json($appointment->load('client:id,name,phone,avatar', 'service', 'providerProfile', 'review'));
    }

    /** Provider confirms/declines, or either party cancels/completes. */
    public function updateStatus(Request $request, Appointment $appointment)
    {
        $this->authorizeParty($request, $appointment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])],
            'provider_notes' => 'nullable|string|max:1000',
        ]);

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
