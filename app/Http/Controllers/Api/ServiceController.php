<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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

    /** Provider: add a style/service to their own catalogue, with an optional style photo. */
    public function store(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403, 'Only providers can add services');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'required|integer|min:5|max:600',
            'image' => 'nullable|image|max:4096', // 4MB — the style photo clients see on the catalogue
        ]);

        $validated['image'] = $request->hasFile('image')
            ? $request->file('image')->store('services', 'r2')
            : null;

        $service = $profile->services()->create($validated);
        Cache::tags(['providers'])->flush(); // the new style/photo shows up on provider cards in discovery

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
            'image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            // Replace: remove the old object so R2 storage doesn't accumulate orphans
            if ($service->image) {
                Storage::disk('r2')->delete($service->image);
            }
            $validated['image'] = $request->file('image')->store('services', 'r2');
        } else {
            // No new file uploaded on this request — don't overwrite the existing image with null
            unset($validated['image']);
        }

        $service->update($validated);
        Cache::tags(['providers'])->flush();

        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        $this->authorizeOwnership($request, $service);

        if ($service->image) {
            Storage::disk('r2')->delete($service->image);
        }

        $service->delete();
        Cache::tags(['providers'])->flush();

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
