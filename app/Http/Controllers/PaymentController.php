<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\DepositConfirmed;
use App\Models\Appointment;
use App\Models\Payment;
use App\Services\MpesaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PaymentController extends Controller
{
    public function __construct(private MpesaService $mpesa)
    {
    }

    /**
     * Feature: client pays a deposit to secure a booking.
     * Triggers an STK push — client approves on their phone, result arrives via callback().
     */
    public function initiateDeposit(Request $request, Appointment $appointment)
    {
        abort_unless($appointment->client_id === $request->user()->id, 403);
        abort_if($appointment->is_deposit_paid, 422, 'Deposit already paid for this appointment');

        $validated = $request->validate([
            'phone' => 'required|string|min:9',
        ]);

        $depositAmount = $appointment->deposit_amount > 0
            ? $appointment->deposit_amount
            : round($appointment->price_at_booking * config('mpesa.default_deposit_rate'), 2);

        $payment = Payment::create([
            'appointment_id' => $appointment->id,
            'client_id' => $appointment->client_id,
            'type' => 'deposit',
            'amount' => $depositAmount,
            'phone' => $this->mpesa->normalizePhone($validated['phone']),
            'status' => 'pending',
        ]);

        try {
            $result = $this->mpesa->stkPush(
                phone: $validated['phone'],
                amount: $depositAmount,
                accountReference: 'APPT-' . $appointment->id,
                description: 'Booking deposit'
            );
        } catch (RuntimeException $e) {
            $payment->update(['status' => 'failed', 'result_desc' => $e->getMessage()]);

            return response()->json(['message' => $e->getMessage()], 422);
        }

        $payment->update([
            'merchant_request_id' => $result['merchant_request_id'],
            'checkout_request_id' => $result['checkout_request_id'],
        ]);

        // Also stamp the deposit amount onto the appointment if it wasn't set yet
        if ($appointment->deposit_amount == 0) {
            $appointment->update(['deposit_amount' => $depositAmount]);
        }

        return response()->json([
            'message' => 'Check your phone to approve the M-Pesa prompt',
            'payment_id' => $payment->id,
            'checkout_request_id' => $result['checkout_request_id'],
        ]);
    }

    /**
     * Public webhook — Safaricom calls this asynchronously with the result.
     * No auth middleware; verify by matching CheckoutRequestID instead.
     */
    public function callback(Request $request)
    {
        $payload = $request->input('Body.stkCallback');
        Log::info('M-Pesa callback received', ['payload' => $payload]);

        if (! $payload || ! isset($payload['CheckoutRequestID'])) {
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored — malformed payload']);
        }

        $payment = Payment::where('checkout_request_id', $payload['CheckoutRequestID'])->first();

        if (! $payment) {
            Log::warning('M-Pesa callback for unknown payment', ['checkout_request_id' => $payload['CheckoutRequestID']]);

            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Ignored — unknown transaction']);
        }

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

        // Safaricom expects this exact shape to stop retrying the callback
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /** Client-side polling fallback while waiting on the async callback. */
    public function status(Request $request, Payment $payment)
    {
        abort_unless($payment->client_id === $request->user()->id, 403);

        // If still pending after a few seconds, actively query Safaricom rather than
        // leaving the user staring at a spinner if the callback got delayed.
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
