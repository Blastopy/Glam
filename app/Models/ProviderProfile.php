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
        'business_hours', 'is_verified', 'subscription_expires_at',
    ];

    // Ships alongside every JSON response so the frontend can gate provider
    // features (dashboard access, going live) without a separate lookup.
    protected $appends = ['is_subscription_active'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_open' => 'boolean',
            'is_verified' => 'boolean',
            'business_hours' => 'array',
            'average_rating' => 'decimal:2',
            'subscription_expires_at' => 'datetime',
        ];
    }

    /**
     * A provider is "live" — bookable and visible to clients — only while
     * this holds. KES 70/month, enforced in ProviderProfileController@index
     * (discovery) and AppointmentController@store (booking).
     */
    public function getIsSubscriptionActiveAttribute(): bool
    {
        return $this->subscription_expires_at !== null && $this->subscription_expires_at->isFuture();
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

    /** Only providers with a currently-paid-up subscription — gates public discovery. */
    public function scopeSubscriptionActive($query)
    {
        return $query->whereNotNull('subscription_expires_at')->where('subscription_expires_at', '>', now());
    }

    /**
     * Haversine distance filter — same pattern used on Nyumba for property search.
     * Distance returned in kilometers as `distance_km`.
     */
    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 10)
    {
        $haversine = "(6371 * acos(cos(radians($lat))
            * cos(radians(latitude))
            * cos(radians(longitude) - radians($lng))
            + sin(radians($lat))
            * sin(radians(latitude))))";

        return $query
            ->selectRaw("provider_profiles.*, {$haversine} AS distance_km")
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance_km', '<=', $radiusKm)
            ->orderBy('distance_km');
    }
}
