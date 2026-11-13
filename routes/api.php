<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientBookController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProviderSubscriptionController;
use App\Http\Controllers\Api\ProviderProfileController;
use App\Http\Controllers\Api\ProviderAnalyticsController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\AppointmentQuickActionController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SystemSettingsController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\Admin\AdminAppointmentController;
use App\Http\Controllers\Api\Admin\AdminProviderController;
use App\Http\Controllers\Api\Admin\AdminSettingsController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductListingController;
use App\Http\Controllers\Api\Admin\AdminProductController;

/*
|--------------------------------------------------------------------------
| API Routes (v1) — Sanctum token auth
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public ──────────────────────────────────────────────
    // Throttled: brute-force / registration-spam protection on the two
    // unauthenticated endpoints that accept credentials.
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/auth/google', [AuthController::class, 'google'])->middleware('throttle:10,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:5,1');

    // Feature #5: quick Accept/Decline buttons in the push notification
    Route::middleware('signed')->group(function () {
    Route::get('/appointments/{appointment}/quick-accept', [AppointmentQuickActionController::class, 'accept'])
        ->name('appointments.quick-accept');
    Route::get('/appointments/{appointment}/quick-decline', [AppointmentQuickActionController::class, 'decline'])
        ->name('appointments.quick-decline');
    });

    // Feature #2 & #6: browse/discover providers + their catalogue, no auth needed
    Route::get('/providers', [ProviderProfileController::class, 'index']);
    Route::get('/providers/{providerProfile}', [ProviderProfileController::class, 'show']);
    Route::get('/providers/{providerProfileId}/services', [ServiceController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{product}', [ProductController::class, 'show']);


    // Lets the frontend know whether the payments system is currently switched on
    Route::get('/system-settings', [SystemSettingsController::class, 'status']);

    // M-Pesa callback — Safaricom hits this server-to-server, must stay outside auth
    // and outside CSRF/VerifyCsrfToken (api group already excludes CSRF by default).
    Route::post('/payments/mpesa/callback', [PaymentController::class, 'callback']);

    // ── Authenticated ───────────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api-role'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Feature #3: provider profile self-management
        Route::patch('/provider/profile', [ProviderProfileController::class, 'update']);
        Route::patch('/provider/status', [ProviderProfileController::class, 'toggleOpen']); // feature #5

        // Feature #3: catalogue management (provider-only, enforced in controller)
        Route::post('/services', [ServiceController::class, 'store']);
        Route::patch('/services/{service}', [ServiceController::class, 'update']);
        Route::delete('/services/{service}', [ServiceController::class, 'destroy']);

        Route::post('/products', [ProductController::class, 'store']);
        Route::get('/my-products', [ProductController::class, 'mine']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        Route::get('/products/{product}/listing', [ProductListingController::class, 'status']);
        Route::post('/products/{product}/listing/pay', [ProductListingController::class, 'initiate']);
        Route::get('/products/listing-payments/{payment}/poll', [ProductListingController::class, 'poll']);

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


        // Feature #5: quick Accept/Decline buttons in the push notification
        Route::post('/push/subscribe', [PushSubscriptionController::class, 'store']);
        Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy']);

        // Feature #7: provider's client book
        Route::get('/provider/clients', [ClientBookController::class, 'index']);
        Route::post('/provider/clients', [ClientBookController::class, 'store']);
        Route::patch('/provider/clients/{client}', [ClientBookController::class, 'updateNotes']);
        Route::delete('/provider/clients/{client}', [ClientBookController::class, 'destroy']);

        // Reviews
        Route::post('/appointments/{appointment}/review', [ReviewController::class, 'store']);

        // M-Pesa deposit — currently disabled, see PaymentController::initiateDeposit
        Route::post('/appointments/{appointment}/deposit', [PaymentController::class, 'initiateDeposit']);
        Route::get('/payments/{payment}/status', [PaymentController::class, 'status']);

        // Provider monthly subscription fee (KES 70) — required to be listed and bookable,
        // unless the admin has switched off payments system-wide (see AdminSettingsController)
        Route::get('/provider/subscription', [ProviderSubscriptionController::class, 'status']);
        Route::post('/provider/subscription/pay', [ProviderSubscriptionController::class, 'initiate']);
        Route::get('/provider/subscription/{payment}/poll', [ProviderSubscriptionController::class, 'poll']);
        Route::get('/provider/analytics/clients', [ProviderAnalyticsController::class, 'clients']);
        

        // ── Admin panel ───────────────────────────────────
        Route::middleware('admin')->prefix('admin')->group(function () {
            Route::get('/analytics/overview', [AdminAnalyticsController::class, 'overview']);
            Route::get('/analytics/revenue', [AdminAnalyticsController::class, 'revenueSeries']);
            Route::get('/analytics/subscription-payments', [AdminAnalyticsController::class, 'subscriptionPaymentsSeries']);
            Route::get('/analytics/bookings', [AdminAnalyticsController::class, 'bookingsSeries']);
            Route::get('/analytics/user-growth', [AdminAnalyticsController::class, 'userGrowthSeries']);
            Route::get('/analytics/top-providers', [AdminAnalyticsController::class, 'topProviders']);

            Route::get('/marketplace', [AdminProductController::class, 'index']);
Route::patch('/marketplace/{product}/deactivate', [AdminProductController::class, 'deactivate']);
Route::patch('/marketplace/{product}/reactivate', [AdminProductController::class, 'reactivate']);
            

            Route::get('/users', [AdminUserController::class, 'index']);
            Route::get('/users/{id}', [AdminUserController::class, 'show']);
            Route::patch('/users/{id}/suspend', [AdminUserController::class, 'suspend']);
            Route::patch('/users/{id}/reactivate', [AdminUserController::class, 'reactivate']);

            Route::get('/providers', [AdminProviderController::class, 'index']);
            Route::get('/providers/{providerProfile}', [AdminProviderController::class, 'show']);
            Route::patch('/providers/{providerProfile}/verify', [AdminProviderController::class, 'verify']);
            Route::patch('/providers/{providerProfile}/unverify', [AdminProviderController::class, 'unverify']);
            Route::patch('/providers/{providerProfile}/feature', [AdminProviderController::class, 'feature']);
            Route::patch('/providers/{providerProfile}/unfeature', [AdminProviderController::class, 'unfeature']);

            Route::get('/appointments', [AdminAppointmentController::class, 'index']);
            Route::patch('/appointments/{appointment}/cancel', [AdminAppointmentController::class, 'cancel']);

            // System-wide payments kill switch
            Route::get('/settings', [AdminSettingsController::class, 'show']);
            Route::patch('/settings/payments', [AdminSettingsController::class, 'updatePayments']);

            Route::patch('/settings/marketplace-payments', [AdminSettingsController::class, 'updateMarketplacePayments']);
        });
    });
});
