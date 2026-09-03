<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MpesaService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('mpesa.base_url');
    }

    /**
     * Get (and cache) an OAuth access token. Daraja tokens are valid ~1hr,
     * cached for 55 min to stay safely inside that window.
     */
    public function getAccessToken(): string
    {
        return Cache::remember('mpesa_access_token', now()->addMinutes(55), function () {
            $response = Http::withBasicAuth(
                config('mpesa.consumer_key'),
                config('mpesa.consumer_secret')
            )->get("{$this->baseUrl}/oauth/v1/generate", ['grant_type' => 'client_credentials']);

            if (! $response->successful()) {
                Log::error('M-Pesa token request failed', ['response' => $response->body()]);
                throw new RuntimeException('Could not authenticate with M-Pesa');
            }

            return $response->json('access_token');
        });
    }

    /**
     * Normalizes a Kenyan phone number to the 2547XXXXXXXX format Daraja expects.
     * Accepts 07XXXXXXXX, 7XXXXXXXX, 2547XXXXXXXX, +2547XXXXXXXX.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (str_starts_with($digits, '0')) {
            return '254' . substr($digits, 1);
        }

        if (str_starts_with($digits, '254')) {
            return $digits;
        }

        return '254' . $digits;
    }

    /**
     * Initiates an STK push (Lipa Na M-Pesa Online) prompt to the client's phone.
     *
     * @return array{merchant_request_id: string, checkout_request_id: string, response_description: string}
     */
    public function stkPush(string $phone, float $amount, string $accountReference, string $description): array
    {
        $shortcode = config('mpesa.shortcode');
        $passkey = config('mpesa.passkey');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);
        $phone = $this->normalizePhone($phone);

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => (int) round($amount), // Daraja expects a whole-number amount
                'PartyA' => $phone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => config('mpesa.callback_url'),
                'AccountReference' => $accountReference,
                'TransactionDesc' => $description,
            ]);

        $body = $response->json();

        if (! $response->successful() || ($body['ResponseCode'] ?? null) !== '0') {
            Log::error('M-Pesa STK push failed', ['response' => $body]);
            throw new RuntimeException($body['errorMessage'] ?? 'Failed to initiate M-Pesa payment');
        }

        return [
            'merchant_request_id' => $body['MerchantRequestID'],
            'checkout_request_id' => $body['CheckoutRequestID'],
            'response_description' => $body['ResponseDescription'],
        ];
    }

    /** Actively query the status of a previously-initiated STK push (fallback if callback is delayed/lost). */
    public function stkQuery(string $checkoutRequestId): array
    {
        $shortcode = config('mpesa.shortcode');
        $passkey = config('mpesa.passkey');
        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode . $passkey . $timestamp);

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/mpesa/stkpushquery/v1/query", [
                'BusinessShortCode' => $shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID' => $checkoutRequestId,
            ]);

        return $response->json();
    }
}
