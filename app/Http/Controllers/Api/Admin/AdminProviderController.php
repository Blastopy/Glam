<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use Illuminate\Http\Request;

class AdminProviderController extends Controller
{
    public function index(Request $request)
    {
        $query = ProviderProfile::query()->with('user:id,name,email,phone');

        if ($request->filled('type')) {
            $query->where('provider_type', $request->type);
        }

        if ($request->boolean('unverified_only')) {
            $query->where('is_verified', false);
        }

        if ($request->filled('search')) {
            $query->where('business_name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->paginate(25));
    }

    public function show(ProviderProfile $providerProfile)
    {
        $providerProfile->load('user:id,name,email,phone', 'services', 'reviews');
        $providerProfile->loadCount([
            'appointments',
            'appointments as completed_count' => fn ($q) => $q->where('status', 'completed'),
            'appointments as cancelled_count' => fn ($q) => $q->whereIn('status', ['cancelled', 'no_show']),
        ]);

        return response()->json($providerProfile);
    }

    public function verify(ProviderProfile $providerProfile)
    {
        $providerProfile->update(['is_verified' => true]);

        return response()->json($providerProfile);
    }

    public function unverify(ProviderProfile $providerProfile)
    {
        $providerProfile->update(['is_verified' => false]);

        return response()->json($providerProfile);
    }

    /** Featured providers are advertised ahead of others in public discovery. */
    public function feature(ProviderProfile $providerProfile)
    {
        $providerProfile->update(['is_featured' => true]);

        return response()->json($providerProfile);
    }

    public function unfeature(ProviderProfile $providerProfile)
    {
        $providerProfile->update(['is_featured' => false]);

        return response()->json($providerProfile);
    }
}
