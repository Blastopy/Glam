<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->client_id === $request->user()->id, 403);
        abort_unless($appointment->status === 'completed', 422, 'Only completed appointments can be reviewed');
        abort_if($appointment->review()->exists(), 422, 'Appointment already reviewed');

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review = Review::create([
            'appointment_id' => $appointment->id,
            'client_id' => $appointment->client_id,
            'provider_profile_id' => $appointment->provider_profile_id,
            ...$validated,
        ]);

        // Recalculate cached average rating
        $profile = $appointment->providerProfile;
        $profile->update([
            'average_rating' => $profile->reviews()->avg('rating') ?? 0,
            'reviews_count' => $profile->reviews()->count(),
        ]);

        return response()->json($review, 201);
    }
}
