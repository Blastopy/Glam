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
        'business_hours', 'is_verified',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_open' => 'boolean',
            'is_verified' => 'boolean',
            'business_hours' => 'array',
            'average_rating' => 'decimal:2',
        ];
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
