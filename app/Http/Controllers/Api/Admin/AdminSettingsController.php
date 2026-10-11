<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminSettingsController extends Controller
{
    public function show()
    {
        return response()->json(SystemSetting::current());
    }

    /**
     * The payments kill switch. Turning this on starts enforcing the KES 70
     * monthly fee (ProviderProfile::isSubscriptionActive stops auto-passing
     * everyone); turning it off immediately makes every provider bookable
     * again regardless of their subscription state.
     */
    public function updatePayments(Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $settings = SystemSetting::current();
        $settings->update(['payments_enabled' => $validated['enabled']]);

        SystemSetting::forgetCache();
        // The discovery listing's subscriptionActive() scope reads this flag
        // indirectly through every provider it serializes — bust it too, or
        // the old "everyone active" (or "enforce subscriptions") view could
        // keep showing for up to a minute after the toggle.
        Cache::tags(['providers'])->flush();

        return response()->json($settings->fresh());
    }
}
