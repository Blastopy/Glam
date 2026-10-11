<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ProviderStatusChanged;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ProviderProfileController extends Controller
{
    /**
     * Feature #2 & #6: browse providers, optionally filtered by location/type,
     * with their services eager-loaded for "estimated charges".
     */
    public function index(Request $request)
    {
        $cacheKey = 'providers:index:' . md5($request->getQueryString() ?? '');

        // Discovery is the single most-hit public endpoint and changes slowly
        // (a provider toggling open/closed or a new booking doesn't need to be
        // reflected within the same second) — short TTL cache, tag-based so a
        // genuine change (profile edit, verify, feature, open/closed, catalogue
        // edit) can bust it immediately instead of waiting the full 60s out.
        return Cache::tags(['providers'])->remember($cacheKey, 60, function () use ($request) {
            $query = ProviderProfile::query()
                ->subscriptionActive() // only providers who've paid the monthly fee are discoverable
                ->featuredFirst() // admin-promoted providers are advertised ahead of others
                // Computed fresh via SQL aggregate rather than trusting the
                // average_rating/reviews_count columns on provider_profiles —
                // those are a cache that depends on every review-writing code
                // path remembering to update them, and historically drifted to
                // 0 despite real reviews existing. Aliased to avoid colliding
                // with the real columns of near-identical names.
                ->withAvg('reviews', 'rating')
                ->withCount(['reviews as fresh_reviews_count'])
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

            if ($request->filled('search')) {
                $term = '%' . $request->search . '%';
                $query->where(function ($q) use ($term) {
                    $q->where('business_name', 'like', $term)
                        ->orWhereHas('services', function ($sq) use ($term) {
                            $sq->where('name', 'like', $term)->where('is_active', true);
                        });
                });
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

            return $query->paginate(15);
        });
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

        if (($validated['offers_home_service'] ?? $profile->offers_home_service) === false) {
            $validated['home_service_fee'] = null;
        }

        $profile->update($validated);
        Cache::tags(['providers'])->flush();

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
        Cache::tags(['providers'])->flush();

        broadcast(new ProviderStatusChanged($profile));

        return response()->json($profile);
    }
}
