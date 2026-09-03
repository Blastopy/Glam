<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id', 'provider_profile_id', 'service_id', 'scheduled_at',
        'checked_in_at', 'price_at_booking', 'deposit_amount', 'is_deposit_paid',
        'status', 'client_notes', 'provider_notes',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'price_at_booking' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'is_deposit_paid' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** Client marks "on my way / arrived" — feature #1 (name + arrival time shown to provider). */
    public function checkIn(): void
    {
        $this->update(['checked_in_at' => now()]);
    }
}
