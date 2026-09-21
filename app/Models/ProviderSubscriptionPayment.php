<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderSubscriptionPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_profile_id', 'amount', 'phone',
        'merchant_request_id', 'checkout_request_id', 'mpesa_receipt_number',
        'status', 'result_code', 'result_desc', 'paid_at',
        'period_start', 'period_end', 'raw_callback',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'period_start' => 'datetime',
            'period_end' => 'datetime',
            'raw_callback' => 'array',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
