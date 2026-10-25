<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['payments_enabled', 'marketplace_payments_enabled'];

    protected function casts(): array
    {
        return [
            'payments_enabled' => 'boolean',
            'marketplace_payments_enabled' => 'boolean',
        ];
    }

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
