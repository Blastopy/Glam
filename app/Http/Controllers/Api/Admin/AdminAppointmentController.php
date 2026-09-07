<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;

class AdminAppointmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Appointment::query()
            ->with(['client:id,name,phone', 'providerProfile:id,business_name', 'service:id,name,price']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from')) {
            $query->where('scheduled_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('scheduled_at', '<=', $request->to);
        }

        return response()->json($query->latest('scheduled_at')->paginate(25));
    }

    /** Platform-level override — for disputes, abuse, or erroneous bookings. */
    public function cancel(Request $request, Appointment $appointment)
    {
        abort_if(in_array($appointment->status, ['completed', 'cancelled']), 422, 'Appointment cannot be cancelled from its current state');

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $appointment->update([
            'status' => 'cancelled',
            'provider_notes' => trim(($appointment->provider_notes ?? '') . "\n[Cancelled by admin] " . ($validated['reason'] ?? '')),
        ]);

        return response()->json($appointment);
    }
}
