<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;

class SystemSettingsController extends Controller
{
    /** Public — the frontend uses this to decide whether to show payment UI at all (e.g. ActivateView). */
    public function status()
    {
        return response()->json([
            'payments_enabled' => SystemSetting::current()->payments_enabled,
        ]);
    }
}
