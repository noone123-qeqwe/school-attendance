<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    protected WebPushService $webPushService;

    public function __construct(WebPushService $webPushService)
    {
        $this->webPushService = $webPushService;
    }

    /**
     * Return public VAPID key to the client for subscription creation.
     */
    public function getPublicKey()
    {
        $publicKey = config('webpush.vapid.public_key');

        return response()->json([
            'success' => !empty($publicKey),
            'publicKey' => $publicKey,
        ]);
    }

    /**
     * Store or update a browser push subscription.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'content_encoding' => 'nullable|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $endpoint = $request->input('endpoint');
        $endpointHash = hash('sha256', $endpoint);

        try {
            $subscription = PushSubscription::updateOrCreate(
                [
                    'endpoint_hash' => $endpointHash,
                ],
                [
                    'user_id' => Auth::id(),
                    'endpoint' => $endpoint,
                    'public_key' => $request->input('keys.p256dh'),
                    'auth_token' => $request->input('keys.auth'),
                    'content_encoding' => $request->input('content_encoding', 'aes128gcm'),
                    'device_name' => $request->input('device_name', $request->header('User-Agent') ? substr($request->header('User-Agent'), 0, 80) : 'Browser Device'),
                    'user_agent' => $request->header('User-Agent'),
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Web Push notification device registered successfully!',
                'subscription_id' => $subscription->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed storing push subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed storing push subscription: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        $endpointHash = hash('sha256', $request->input('endpoint'));
        PushSubscription::where('endpoint_hash', $endpointHash)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device unsubscribed from Web Push notifications.',
        ]);
    }

    /**
     * Dispatch an instant test notification to caller's registered device(s).
     */
    public function sendTest(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $sentCount = $this->webPushService->sendToUser(
            $user,
            '🔔 Push Notification Active!',
            'Real-Time Web Push alerts are successfully configured and verified for your account.',
            [
                'url' => route('home'),
                'tag' => 'test-notification-' . time(),
            ]
        );

        if ($sentCount > 0) {
            return response()->json([
                'success' => true,
                'message' => "Test push delivered to {$sentCount} active device(s)!",
                'count' => $sentCount,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No active device subscriptions found for your account. Please enable push permissions first.',
        ], 404);
    }
}
