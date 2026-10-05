<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /** Called right after the browser's PushManager.subscribe() resolves. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        $request->user()->updatePushSubscription(
            endpoint: $validated['endpoint'],
            publicKey: $validated['keys']['p256dh'],
            authToken: $validated['keys']['auth'],
            contentEncoding: $validated['contentEncoding'] ?? 'aesgcm',
        );

        return response()->json(['message' => 'Subscribed to push notifications']);
    }

    /** Called when the person turns notifications off, or the browser's subscription is revoked. */
    public function destroy(Request $request)
    {
        $endpoint = $request->input('endpoint');

        if ($endpoint) {
            $request->user()->deletePushSubscription($endpoint);
        }

        return response()->json(['message' => 'Unsubscribed from push notifications']);
    }
}
