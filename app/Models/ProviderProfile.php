<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'business_name', 'provider_type', 'bio', 'address',
        'latitude', 'longitude', 'cover_image', 'is_open', 'closed_reason',
        'business_hours', 'is_verified', 'subscription_expires_at', 'is_featured',
        'offers_home_service', 'home_service_fee',
    ];

    // Ships alongside every JSON response so the frontend can gate provider
    // features (dashboard access, going live) and show status without a
    // separate lookup.
    protected $appends = ['is_subscription_active', 'subscription_days_left'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_open' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
            'offers_home_service' => 'boolean',
            'home_service_fee' => 'decimal:2',
            'business_hours' => 'array',
            'average_rating' => 'decimal:2',
            'subscription_expires_at' => 'datetime',
        ];
    }

    /**
     * A provider is "live" — bookable and visible to clients — only while
     * this holds. KES 70/month, enforced in ProviderProfileController@index
     * (discovery) and AppointmentController@store (booking).
     *
     * This is the single place the admin's system-wide payments toggle is
     * checked: while payments are disabled, every provider is treated as
     * active regardless of subscription_expires_at — everything downstream
     * (discovery, booking, the frontend's router guard, the admin panel's
     * days-left display) reads this one attribute, so nothing else needs to
     * know the toggle exists.
     */
    public function getIsSubscriptionActiveAttribute(): bool
    {
        if (! SystemSetting::current()->payments_enabled) {
            return true;
        }

        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isFuture();
    }

    /** Whole days remaining on the current subscription — 0 once expired, never activated, or payments are disabled system-wide. */
    public function getSubscriptionDaysLeftAttribute(): int
    {
        if ($this->subscription_expires_at === null || ! $this->is_subscription_active) {
            return 0;
        }

        $secondsLeft = $this->subscription_expires_at->getTimestamp() - now()->getTimestamp();

        return max(0, (int) ceil($secondsLeft / 86400));
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(ProviderSubscriptionPayment::class);
    }

    // ── Relationships ─────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** Clients in this provider's book (feature #7: manage my clients). */
    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_provider', 'provider_profile_id', 'client_id')
            ->withPivot('notes', 'source')
            ->withTimestamps();
    }

    // ── Query scopes ──────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('is_open', true);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('provider_type', $type);
    }

    /**
     * Only providers with a currently-paid-up subscription — gates public
     * discovery. A no-op (shows everyone) while the admin's system-wide
     * payments toggle is off, matching getIsSubscriptionActiveAttribute().
     */
    public function scopeSubscriptionActive($query)
    {
        if (! SystemSetting::current()->payments_enabled) {
            return $query;
        }

        return $query->whereNotNull('subscription_expires_at')->where('subscription_expires_at', '>', now());
    }

    /** Featured providers first — set by an admin. */
    public function scopeFeaturedFirst($query)
    {
        return $query->orderByDesc('is_featured');
    }

    public function scopeOffersHomeService($query)
    {
        return $query->where('offers_home_service', true);
    }

    /**
     * Haversine distance filter — same pattern used on Nyumba for property search.
     * Distance returned in kilometers as `distance_km`.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        // $haversine = "(6371 * acos(cos(radians($lat))
        //     * cos(radians(latitude))
        //     * cos(radians(longitude) - radians($lng))
        //     + sin(radians($lat))
        //     * sin(radians(latitude))))";

        // return $query
        //     ->selectRaw("provider_profiles.*, {$haversine} AS distance_km")
        //     ->whereNotNull('latitude')
        //     ->whereNotNull('longitude')
        //     ->having('distance_km', '<=', $radiusKm)
        //     ->orderBy('distance_km');

        $latRad = deg2rad($lat);
    $lngRad = deg2rad($lng);

    $distanceSql = <<<SQL
        6371 * acos(
            LEAST(
                1.0,
                GREATEST(
                    -1.0,
                    cos(?) *
                    cos(radians(latitude)) *
                    cos(radians(longitude) - ?) +
                    sin(?) *
                    sin(radians(latitude))
                )
            )
        )
    SQL;

    return $query
        ->selectRaw(
            "provider_profiles.*, {$distanceSql} AS distance_km",
            [$latRad, $lngRad, $latRad]
        )
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->orderByRaw(
            "6371 * acos(
                LEAST(
                    1.0,
                    GREATEST(
                        -1.0,
                        cos(?) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - ?) +
                        sin(?) *
                        sin(radians(latitude))
                    )
                )
            )",
            [$latRad, $lngRad, $latRad]
        )
        ->whereRaw(
            "6371 * acos(
                LEAST(
                    1.0,
                    GREATEST(
                        -1.0,
                        cos(?) *
                        cos(radians(latitude)) *
                        cos(radians(longitude) - ?) +
                        sin(?) *
                        sin(radians(latitude))
                    )
                )
            ) <= ?",
            [$latRad, $lngRad, $latRad, $radiusKm]
        );
    }
}
