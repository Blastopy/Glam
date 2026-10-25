<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'description', 'price', 'category', 'image', 'is_active', 'expires_at',
    ];

    protected $appends = ['image_url', 'is_listing_active', 'listing_days_left'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('r2')->url($this->image) : null;
    }

    /**
     * Publicly visible only while this holds — paid window still running AND
     * the seller hasn't paused it. Respects its own independent admin toggle
     * (marketplace_payments_enabled, separate from the provider-subscription one).
     */
    public function getIsListingActiveAttribute(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! SystemSetting::current()->marketplace_payments_enabled) {
            return true;
        }

        return $this->expires_at !== null && $this->expires_at->isFuture();
    }

    public function getListingDaysLeftAttribute(): int
    {
        if ($this->expires_at === null || ! $this->is_listing_active) {
            return 0;
        }

        $secondsLeft = $this->expires_at->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($secondsLeft / 86400));
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function listingPayments(): HasMany
    {
        return $this->hasMany(ProductListingPayment::class);
    }

    public function scopeListingActive($query)
    {
        if (! SystemSetting::current()->marketplace_payments_enabled) {
            return $query->where('is_active', true);
        }

        return $query->where('is_active', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', now());
    }
}
