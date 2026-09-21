<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use RuntimeException;

class ServiceController extends Controller
{
    public function __construct(private CloudinaryService $cloudinary)
    {
    }

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

        if ($request->hasFile('image')) {
            try {
                $upload = $this->cloudinary->upload($request->file('image'));
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            $validated['image'] = $upload['url'];
            $validated['image_public_id'] = $upload['public_id'];
        } else {
            $validated['image'] = null;
        }

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
            'image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('image')) {
            try {
                $upload = $this->cloudinary->upload($request->file('image'));
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            // Replace: remove the old asset so Cloudinary storage doesn't accumulate orphans
            if ($service->image_public_id) {
                $this->cloudinary->destroy($service->image_public_id);
            }

            $validated['image'] = $upload['url'];
            $validated['image_public_id'] = $upload['public_id'];
        } else {
            // No new file uploaded on this request — don't overwrite the existing image with null
            unset($validated['image']);
        }

        $service->update($validated);

        return response()->json($service);
    }

    public function destroy(Request $request, Service $service)
    {
        $this->authorizeOwnership($request, $service);

        if ($service->image_public_id) {
            $this->cloudinary->destroy($service->image_public_id);
        }

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
