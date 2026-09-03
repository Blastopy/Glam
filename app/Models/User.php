<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ── Role helpers ──────────────────────────────────────
    public function isProvider(): bool
    {
        return $this->role === 'provider';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    // ── Relationships ─────────────────────────────────────

    /** If this user is a provider, their business profile. */
    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    /** Appointments this user booked as a client. */
    public function appointmentsAsClient(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /** Providers this client has booked with / been added by (feature #7). */
    public function providers(): BelongsToMany
    {
        return $this->belongsToMany(ProviderProfile::class, 'client_provider', 'client_id', 'provider_profile_id')
            ->withPivot('notes', 'source')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'client_id');
    }
}
