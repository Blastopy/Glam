<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id', 'name', 'description', 'price',
        'duration_minutes', 'image', 'image_public_id', 'is_active',
    ];

    // Ships alongside every JSON response, matching what the frontend has
    // always expected as `image_url` — the storage backend behind it has
    // changed twice now (local disk → Cloudinary → R2) without the frontend
    // ever needing to know, since it only ever reads this one computed field.
    protected $appends = ['image_url'];

    // `image_public_id` is a leftover from the Cloudinary era — no longer
    // written to or read from (R2 deletes are keyed by the `image` path
    // itself, no separate asset ID needed), kept only so existing rows with
    // an old value don't need a migration to drop the column. Hidden since
    // it's meaningless now.
    protected $hidden = ['image_public_id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * `image` stores the R2 **object key** (e.g. "services/abc123.jpg"), not
     * a full URL — building the URL here, rather than storing it directly,
     * means switching the public domain (custom domain vs. the r2.dev dev
     * URL) later only requires an .env change, not rewriting every row.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('r2')->url($this->image) : null;
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
