<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:client,provider',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        // Providers get an empty profile shell immediately so they can start
        // filling in business_name, location, etc. via PATCH /provider/profile
        if ($user->role === 'provider') {
            $user->providerProfile()->create([
                'business_name' => $request->name,
                'provider_type' => $request->input('provider_type', 'barber'),
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('providerProfile'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('providerProfile'));
    }

    /**
     * "Continue with Google" — the frontend uses Google Identity Services to get
     * a signed ID token, which we verify here rather than running a full OAuth
     * redirect dance (unnecessary for a token the browser already obtained).
     *
     * New sign-ins are always created as clients — Google is a consumer-facing
     * shortcut, not a provider-onboarding path (providers still go through
     * /register + the subscription fee). An email that already has an account
     * (regardless of how it was created) just logs into that existing account.
     */
    public function google(Request $request)
    {
        $request->validate(['credential' => 'required|string']);

        $verification = Http::asForm()->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $request->credential,
        ]);

        if (! $verification->successful()) {
            return response()->json(['message' => 'Could not verify this Google sign-in. Please try again.'], 422);
        }

        $payload = $verification->json();

        $expectedClientId = config('services.google.client_id');
        if ($expectedClientId && ($payload['aud'] ?? null) !== $expectedClientId) {
            Log::warning('Google sign-in token audience mismatch', ['aud' => $payload['aud'] ?? null]);

            return response()->json(['message' => 'Could not verify this Google sign-in. Please try again.'], 422);
        }

        if (($payload['email_verified'] ?? 'false') !== 'true' || empty($payload['email'])) {
            return response()->json(['message' => 'Your Google account email is not verified.'], 422);
        }

        $email = $payload['email'];

        // Check trashed too — a suspended account shouldn't be reachable via a
        // different login method, and firstOrCreate() would otherwise throw a
        // duplicate-email error trying to insert a second row for it.
        $existing = User::withTrashed()->where('email', $email)->first();

        if ($existing?->trashed()) {
            return response()->json(['message' => 'This account has been suspended.'], 403);
        }

        $user = $existing ?? User::create([
            'name' => $payload['name'] ?? explode('@', $email)[0],
            'email' => $email,
            'password' => Hash::make(Str::random(40)), // unusable password — this account only signs in via Google
            'role' => 'client',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => $user->load('providerProfile'),
            'token' => $token,
        ], $existing ? 200 : 201);
    }
}
