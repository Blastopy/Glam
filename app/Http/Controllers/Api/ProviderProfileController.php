<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ProviderStatusChanged;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;

class ProviderProfileController extends Controller
{
    /**
     * Feature #2 & #6: browse providers, optionally filtered by location/type,
     * with their services eager-loaded for "estimated charges".
     */
    public function index(Request $request)
    {
        $query = ProviderProfile::query()
            ->subscriptionActive() // only providers who've paid the monthly fee are discoverable
            ->featuredFirst() // admin-promoted providers are advertised ahead of others
            ->with(['services' => fn ($q) => $q->where('is_active', true), 'user:id,name,avatar'])
            ->withCount('appointments');

        if ($request->boolean('open_only')) {
            $query->open();
        }

        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->boolean('home_service')) {
            $query->offersHomeService();
        }

        if ($request->filled('lat') && $request->filled('lng')) {
            $query->nearby(
                (float) $request->lat,
                (float) $request->lng,
                (float) $request->input('radius_km', 10)
            );
        } else {
            $query->latest();
        }

        return response()->json($query->paginate(15));
    }

    public function show(ProviderProfile $providerProfile)
    {
        $providerProfile->load([
            'services' => fn ($q) => $q->where('is_active', true),
            'user:id,name,avatar,phone',
            'reviews.client:id,name,avatar',
        ]);

        return response()->json($providerProfile);
    }

    /** Feature #3: provider edits their own profile. */
    public function update(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 404, 'No provider profile found');

        $validated = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'provider_type' => 'sometimes|in:barber,salon,manicurist,other',
            'bio' => 'nullable|string',
            'address' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'business_hours' => 'nullable|array',
            'offers_home_service' => 'sometimes|boolean',
            'home_service_fee' => 'nullable|numeric|min:0',
        ]);

        // A fee only makes sense alongside the capability itself — clear it if
        // home service is being turned off, rather than leaving a stale value.
        if (($validated['offers_home_service'] ?? $profile->offers_home_service) === false) {
            $validated['home_service_fee'] = null;
        }

        $profile->update($validated);

        return response()->json($profile);
    }

    /** Feature #5: provider toggles open/closed, optionally with a reason. */
    public function toggleOpen(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 404, 'No provider profile found');

        $validated = $request->validate([
            'is_open' => 'required|boolean',
            'closed_reason' => 'nullable|string|max:255',
        ]);

        $profile->update($validated);

        broadcast(new ProviderStatusChanged($profile));

        return response()->json($profile);
    }
}
