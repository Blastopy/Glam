<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['payments_enabled'];

    protected function casts(): array
    {
        return ['payments_enabled' => 'boolean'];
    }

    /**
     * There's only ever one row. Reads always go through this rather than a
     * raw query, so every caller agrees on "the" settings row without having
     * to know it's id=1 — and it self-heals if that row is ever missing.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
