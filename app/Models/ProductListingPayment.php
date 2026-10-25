<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductListingPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'user_id', 'days', 'amount', 'phone',
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

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
