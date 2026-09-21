<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\SubscriptionPaymentConfirmed;
use App\Models\ProviderSubscriptionPayment;
use App\Models\SystemSetting;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use RuntimeException;

class ProviderSubscriptionController extends Controller
{
    public function __construct(private MpesaService $mpesa)
    {
    }

    /** Current subscription status for the logged-in provider's dashboard banner. */
    public function status(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403, 'Only providers have a subscription');

        return response()->json([
            'is_active' => $profile->is_subscription_active,
            'expires_at' => $profile->subscription_expires_at,
            'days_left' => $profile->subscription_days_left,
            'fee' => config('mpesa.provider_subscription_fee'),
            // Lets the frontend show "payments are currently disabled" instead
            // of a payment form when the admin has switched the system off.
            'payments_enabled' => SystemSetting::current()->payments_enabled,
        ]);
    }

    /** Triggers the STK push for the monthly fee. */
    public function initiate(Request $request)
    {
        $profile = $request->user()->providerProfile;
        abort_unless($profile, 403, 'Only providers can pay this fee');

        abort_unless(
            SystemSetting::current()->payments_enabled,
            422,
            'Payments are currently disabled by the administrator — no payment is needed right now.'
        );

        $validated = $request->validate([
            'phone' => 'required|string|min:9',
        ]);

        $fee = config('mpesa.provider_subscription_fee');

        $payment = ProviderSubscriptionPayment::create([
            'provider_profile_id' => $profile->id,
            'amount' => $fee,
            'phone' => $this->mpesa->normalizePhone($validated['phone']),
            'status' => 'pending',
        ]);

        try {
            $result = $this->mpesa->stkPush(
                phone: $validated['phone'],
                amount: $fee,
                accountReference: 'SUB-' . $profile->id,
                description: 'SalonBook monthly provider fee'
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

    /** Client-side polling fallback while waiting on the async callback — mostly a safety net now that the callback also broadcasts instantly (see markSuccessful). */
    public function poll(Request $request, ProviderSubscriptionPayment $payment)
    {
        abort_unless($payment->provider_profile_id === $request->user()->providerProfile?->id, 403);

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

        return response()->json($payment->fresh()->load('providerProfile'));
    }

    /**
     * Activates or extends the provider's subscription. Renewing early doesn't
     * waste the remaining days — the new period stacks on top of whatever's
     * left, rather than resetting from "now". Broadcasts immediately so a
     * connected frontend confirms instantly instead of waiting on its next
     * poll tick (up to 3s) — polling remains only as a fallback for when
     * Reverb isn't running.
     */
    public static function markSuccessful(ProviderSubscriptionPayment $payment): void
    {
        $profile = $payment->providerProfile;
        $days = config('mpesa.provider_subscription_days');

        $periodStart = $profile->is_subscription_active ? $profile->subscription_expires_at : now();
        $periodEnd = ($periodStart ?? now())->copy()->addDays($days);

        $payment->update([
            'status' => 'successful',
            'paid_at' => now(),
            'period_start' => $periodStart ?? now(),
            'period_end' => $periodEnd,
        ]);

        $profile->update(['subscription_expires_at' => $periodEnd]);

        broadcast(new SubscriptionPaymentConfirmed($payment));
    }
}
