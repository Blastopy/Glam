<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        // This is an API-only backend with a separate Vue SPA — the password
        // reset email needs to link to the frontend's own reset-password page
        // (which collects the new password and calls POST /api/v1/reset-password),
        // not Laravel's default web route (there isn't one; this app has no web UI).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'https://glamfrontend.onrender.com/'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api-role', function (Request $request) {
            $user = $request->user();
            $role = $user?->role ?? 'guest';

            $limits = [
                'admin' => 300,
                'provider' => 120,
                'client' => 60,
                'guest' => 30,
            ];

            $maxAttempts = $limits[$role] ?? 60;

            return Limit::perMinute($maxAttempts)->by($user?->id ?? $request->ip());
        });
    }
}
