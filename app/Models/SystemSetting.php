<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['payments_enabled'];

    protected function casts(): array
    {
        return ['payments_enabled' => 'boolean'];
    }

    /**
     * There's only ever one row, read on practically every request (it's
     * checked inside ProviderProfile::isSubscriptionActive, which runs for
     * every provider serialized anywhere in the app) — a prime caching
     * candidate. Cached for 10 minutes, but AdminSettingsController::updatePayments
     * explicitly busts this immediately on change rather than waiting out the
     * TTL, so toggling the switch takes effect right away.
     */
    public static function current(): self
    {
        return Cache::remember('system_settings', now()->addMinutes(10), function () {
            return static::firstOrCreate(['id' => 1]);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('system_settings');
    }
}
