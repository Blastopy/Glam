<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /** Public: catalogue for a given provider (feature #3). */
    public function index(int $providerProfileId)
    {
        $services = Service::where('provider_profile_id', $providerProfileId)
            ->where('is_active', true)
            ->get();

        return response()->json($services);
    }

    /** Provider: add a style/service to their own catalogue. */
    public function store(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403, 'Only providers can add services');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5|max:600',
        ]);

        $service = $profile->services()->create($validated);

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service)
    {
        $this->authorizeOwnership($request, $service);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'duration_minutes' => 'sometimes|integer|min:5|max:600',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        $this->authorizeOwnership($request, $service);
        $service->delete();

        return response()->json(['message' => 'Service removed']);
    }

    private function authorizeOwnership(Request $request, Service $service): void
    {
        abort_unless(
            $service->provider_profile_id === $request->user()->providerProfile?->id,
            403,
            'You do not own this service'
        );
    }
}
