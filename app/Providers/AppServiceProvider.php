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
        // This is an API-only backend with a separate Vue SPA — the password
        // reset email needs to link to the frontend's own reset-password page
        // (which collects the new password and calls POST /api/v1/reset-password),
        // not Laravel's default web route (there isn't one; this app has no web UI).
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'https://glamfrontend.onrender.com'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=" . urlencode($notifiable->getEmailForPasswordReset());
        });

        $this->registerRateLimiters();
    }

    /**
     * Role-based API rate limiting. Guests (unauthenticated — public discovery
     * browsing) are limited by IP since there's no user to key on; everyone
     * else is limited by user ID, with higher ceilings for roles that
     * legitimately make more requests per minute during normal use — a
     * provider's dashboard polls messages/bookings badges and often has
     * multiple tabs/devices open, an admin's analytics dashboard fires several
     * chart requests per page load. This is deliberately separate from the
     * tighter, IP-based throttles already on /login, /register, etc. — those
     * are about brute-force protection before any role is even known, not
     * normal-usage capacity.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (! $user) {
                return Limit::perMinute(30)->by($request->ip());
            }

            return match ($user->role) {
                'admin' => Limit::perMinute(300)->by($user->id),
                'provider' => Limit::perMinute(120)->by($user->id),
                default => Limit::perMinute(60)->by($user->id), // client
            };
        });
    }
}
