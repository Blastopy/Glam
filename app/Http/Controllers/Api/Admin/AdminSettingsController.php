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

    /** The provider-subscription payments kill switch (KES 70/month to be listed/bookable). */
    public function updatePayments(Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $settings = SystemSetting::current();
        $settings->update(['payments_enabled' => $validated['enabled']]);

        SystemSetting::forgetCache();
        Cache::tags(['providers'])->flush();

        return response()->json($settings->fresh());
    }

    /**
     * The marketplace listing payments kill switch (KES 100/day for a product
     * to stay visible) — deliberately independent from the provider-subscription
     * toggle above, so an admin can run one free while charging for the other.
     */
    public function updateMarketplacePayments(Request $request)
    {
        $validated = $request->validate(['enabled' => 'required|boolean']);

        $settings = SystemSetting::current();
        $settings->update(['marketplace_payments_enabled' => $validated['enabled']]);

        SystemSetting::forgetCache();
        Cache::tags(['products'])->flush();

        return response()->json($settings->fresh());
    }
}
