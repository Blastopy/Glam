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
        'duration_minutes', 'image', 'is_active',
    ];

    // Ships alongside every JSON response so the frontend never has to
    // build the storage URL itself.
    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
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
