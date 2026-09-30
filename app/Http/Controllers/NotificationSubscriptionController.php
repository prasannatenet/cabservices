<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Stores the browser subscriptions used to deliver desktop notifications.
 *
 * A subscription belongs to the browser that created it, so the endpoints sit
 * behind auth: a dispatcher has to be logged in on each device he wants to be
 * notified on.
 */
class NotificationSubscriptionController extends Controller
{
    /**
     * The public key the browser needs to open a push subscription.
     */
    public function key(): JsonResponse
    {
        return response()->json([
            'public_key' => config('webpush.vapid.public_key'),
        ]);
    }

    /**
     * Remember this browser so pushes for the user reach this device.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        $request->user()->updatePushSubscription(
            $validated['endpoint'],
            $validated['keys']['p256dh'],
            $validated['keys']['auth'],
        );

        return response()->json(['subscribed' => true]);
    }

    /**
     * Stop pushing to this browser, e.g. when the user turns notifications off.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $request->user()->deletePushSubscription($validated['endpoint']);

        return response()->json(['subscribed' => false]);
    }
}
