<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\ProductListingPaymentConfirmed;
use App\Models\Product;
use App\Models\ProductListingPayment;
use App\Models\SystemSetting;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class ProductListingController extends Controller
{
    public function __construct(private MpesaService $mpesa)
    {
    }

    /** Current listing status for the seller's "My Products" page. */
    public function status(Request $request, Product $product)
    {
        abort_unless($product->user_id === $request->user()->id, 403);

        return response()->json([
            'is_active' => $product->is_listing_active,
            'expires_at' => $product->expires_at,
            'days_left' => $product->listing_days_left,
            'daily_rate' => config('mpesa.marketplace_daily_rate'),
            'payments_enabled' => SystemSetting::current()->marketplace_payments_enabled,
        ]);
    }

    /** Pay to activate or extend a listing — `days` is however long the seller wants it visible for. */
    public function initiate(Request $request, Product $product)
    {
        abort_unless($product->user_id === $request->user()->id, 403, 'You do not own this listing');

        abort_unless(
            SystemSetting::current()->marketplace_payments_enabled,
            422,
            'Marketplace payments are currently disabled by the administrator — no payment is needed right now.'
        );

        $validated = $request->validate([
            'phone' => 'required|string|min:9',
            'days' => 'required|integer|min:1|max:90',
        ]);

        $dailyRate = config('mpesa.marketplace_daily_rate');
        $amount = $dailyRate * $validated['days'];

        $payment = ProductListingPayment::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'days' => $validated['days'],
            'amount' => $amount,
            'phone' => $this->mpesa->normalizePhone($validated['phone']),
            'status' => 'pending',
        ]);

        try {
            $result = $this->mpesa->stkPush(
                phone: $validated['phone'],
                amount: $amount,
                accountReference: 'LISTING-' . $product->id,
                description: "Marketplace listing — {$validated['days']} day" . ($validated['days'] === 1 ? '' : 's')
            );
        } catch (RuntimeException $e) {
            $payment->update(['status' => 'failed', 'result_desc' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payment->update([
            'merchant_request_id' => $result['merchant_request_id'],
            'checkout_request_id' => $result['checkout_request_id'],
        ]);

        return response()->json([
            'message' => 'Check your phone to approve the M-Pesa prompt',
            'payment_id' => $payment->id,
            'checkout_request_id' => $result['checkout_request_id'],
        ]);
    }

    /** Polling fallback, same pattern as the provider subscription flow. */
    public function poll(Request $request, ProductListingPayment $payment)
    {
        abort_unless($payment->user_id === $request->user()->id, 403);

        if ($payment->status === 'pending' && $payment->checkout_request_id) {
            $query = $this->mpesa->stkQuery($payment->checkout_request_id);

            if (isset($query['ResultCode']) && $query['ResultCode'] === '0') {
                self::markSuccessful($payment);
            } elseif (isset($query['ResultCode']) && $query['ResultCode'] !== null && $query['ResultCode'] !== '1032') {
                $payment->update([
                    'status' => 'failed',
                    'result_desc' => MpesaService::friendlyResultMessage((int) $query['ResultCode'], $query['ResultDesc'] ?? null),
                ]);
            }
        }

        return response()->json($payment->fresh()->load('product'));
    }

    /**
     * Activates or extends the listing — renewing early stacks the new days on
     * top of whatever's left, same reasoning as the provider subscription flow.
     */
    public static function markSuccessful(ProductListingPayment $payment): void
    {
        $product = $payment->product;

        $periodStart = $product->is_listing_active && $product->expires_at ? $product->expires_at : now();
        $periodEnd = $periodStart->copy()->addDays($payment->days);

        $payment->update([
            'status' => 'successful',
            'paid_at' => now(),
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
        ]);

        $product->update(['expires_at' => $periodEnd]);
        Cache::tags(['products'])->flush();

        broadcast(new ProductListingPaymentConfirmed($payment));
    }
}
