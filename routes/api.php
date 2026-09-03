<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientBookController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1) — Sanctum token auth
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Feature #2 & #6: browse/discover providers + their catalogue, no auth needed
    Route::get('/providers', [ProviderProfileController::class, 'index']);
    Route::get('/providers/{providerProfile}', [ProviderProfileController::class, 'show']);
    Route::get('/providers/{providerProfileId}/services', [ServiceController::class, 'index']);

    // M-Pesa callback — Safaricom hits this server-to-server, must stay outside auth
    // and outside CSRF/VerifyCsrfToken (api group already excludes CSRF by default).
    Route::post('/payments/mpesa/callback', [PaymentController::class, 'callback']);

    // ── Authenticated ───────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Feature #3: provider profile self-management
        Route::patch('/provider/profile', [ProviderProfileController::class, 'update']);
        Route::patch('/provider/status', [ProviderProfileController::class, 'toggleOpen']); // feature #5

        // Feature #3: catalogue management (provider-only, enforced in controller)
        Route::post('/services', [ServiceController::class, 'store']);
        Route::patch('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        // Feature #4: booking
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show']);
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::post('/appointments/{appointment}/check-in', [AppointmentController::class, 'checkIn']); // feature #1

        // Feature #4: chat
        Route::get('/messages', [MessageController::class, 'index']);
        Route::get('/messages/{user}', [MessageController::class, 'thread']);
        Route::post('/messages', [MessageController::class, 'store']);

        // Feature #7: provider's client book
        Route::get('/provider/clients', [ClientBookController::class, 'index']);
        Route::post('/provider/clients', [ClientBookController::class, 'store']);
        Route::patch('/provider/clients/{client}', [ClientBookController::class, 'updateNotes']);
        Route::delete('/provider/clients/{client}', [ClientBookController::class, 'destroy']);

        // Reviews
        Route::post('/appointments/{appointment}/review', [ReviewController::class, 'store']);

        // M-Pesa deposit
        Route::post('/appointments/{appointment}/deposit', [PaymentController::class, 'initiateDeposit']);
        Route::get('/payments/{payment}/status', [PaymentController::class, 'status']);
    });
});
