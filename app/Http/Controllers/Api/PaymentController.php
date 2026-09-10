<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\DepositConfirmed;
use App\Models\Appointment;
use App\Models\Payment;
use App\Models\ProviderSubscriptionPayment;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private MpesaService $mpesa)
    {
    }

    /**
     * Booking deposits are currently disabled — clients book without paying up
     * front, and providers confirm manually instead. The endpoint and its
     * underlying model/table are left in place (not deleted) in case this is
     * re-enabled later; it just refuses to start a new charge for now.
     */
    public function initiateDeposit(Request $request, Appointment $appointment)
    {
        return response()->json([
            'message' => 'Booking deposits are currently disabled — no payment is needed to book.',
        ], 422);
    }

    /**
     * Public webhook — Safaricom calls this asynchronously with the result.
     * No auth middleware; verify by matching CheckoutRequestID instead.
     * Handles both appointment-deposit payments and provider subscription
     * payments, since both flows share this one callback URL.
     */
    public function callback(Request $request)
    {
        $payload = $request->input('Body.stkCallback');
        Log::info('M-Pesa callback received', ['payload' => $payload]);

        if (! $payload || ! isset($payload['CheckoutRequestID'])) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored — malformed payload']);
        }

        $checkoutRequestId = $payload['CheckoutRequestID'];
        $resultCode = $payload['ResultCode'];
        $success = $resultCode === 0;

        $receipt = null;
        if ($success && isset($payload['CallbackMetadata']['Item'])) {
            foreach ($payload['CallbackMetadata']['Item'] as $item) {
                if ($item['Name'] === 'MpesaReceiptNumber') {
                    $receipt = $item['Value'];
                }
            }
        }

        $payment = Payment::where('checkout_request_id', $checkoutRequestId)->first();

        if ($payment) {
            $payment->update([
                'status' => $success ? 'successful' : 'failed',
                'result_code' => $resultCode,
                'result_desc' => $payload['ResultDesc'] ?? null,
                'mpesa_receipt_number' => $receipt,
                'paid_at' => $success ? now() : null,
                'raw_callback' => $payload,
            ]);

            if ($success && $payment->type === 'deposit') {
                $payment->appointment->update([
                    'is_deposit_paid' => true,
                    'status' => 'confirmed',
                ]);

                broadcast(new DepositConfirmed($payment));
            }

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        $subscriptionPayment = ProviderSubscriptionPayment::where('checkout_request_id', $checkoutRequestId)->first();

        if ($subscriptionPayment) {
            if ($success) {
                $subscriptionPayment->update([
                    'result_code' => $resultCode,
                    'result_desc' => $payload['ResultDesc'] ?? null,
                    'mpesa_receipt_number' => $receipt,
                    'raw_callback' => $payload,
                ]);
                ProviderSubscriptionController::markSuccessful($subscriptionPayment);
            } else {
                $subscriptionPayment->update([
                    'status' => 'failed',
                    'result_code' => $resultCode,
                    'result_desc' => $payload['ResultDesc'] ?? null,
                    'raw_callback' => $payload,
                ]);
            }

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        Log::warning('M-Pesa callback for unknown payment', ['checkout_request_id' => $checkoutRequestId]);

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored — unknown transaction']);
    }

    /** Client-side polling fallback while waiting on the async callback (appointment deposits only — currently unused since deposits are disabled). */
    public function status(Request $request, Payment $payment)
    {
        abort_unless($payment->client_id === $request->user()->id, 403);

        if ($payment->status === 'pending' && $payment->checkout_request_id) {
            $query = $this->mpesa->stkQuery($payment->checkout_request_id);

            if (isset($query['ResultCode']) && $query['ResultCode'] === '0') {
                $payment->update(['status' => 'successful', 'paid_at' => now()]);
                $payment->appointment->update(['is_deposit_paid' => true, 'status' => 'confirmed']);
            } elseif (isset($query['ResultCode']) && $query['ResultCode'] !== null && $query['ResultCode'] !== '1032') {
                // 1032 = request still being processed / cancelled by user check separately
                $payment->update(['status' => 'failed', 'result_desc' => $query['ResultDesc'] ?? null]);
            }
        }

        return response()->json($payment->fresh());
    }
}
