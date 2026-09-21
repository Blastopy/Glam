<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id', 'name', 'description', 'price',
        'duration_minutes', 'image', 'image_public_id', 'is_active',
    ];

    // Ships alongside every JSON response — kept as `image_url` (rather than
    // renaming the frontend's expected key) even though `image` now already
    // holds the full Cloudinary URL directly, no local-storage path building needed.
    protected $appends = ['image_url'];

    // Cloudinary's asset ID is an internal implementation detail for
    // uploads/deletes — no reason to expose it to the frontend.
    protected $hidden = ['image_public_id'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image;
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
