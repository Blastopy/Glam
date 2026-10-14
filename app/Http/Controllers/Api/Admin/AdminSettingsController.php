<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

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
     * again regardless of their subscription state — useful before Daraja is
     * configured, or if payments need to be paused for any reason.
     */
    public function updatePayments(Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $settings = SystemSetting::current();
        $settings->update(['payments_enabled' => $validated['enabled']]);

        return response()->json($settings);
    }
}
