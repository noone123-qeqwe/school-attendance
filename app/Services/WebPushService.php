<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?WebPush $webPush = null;

    /**
     * Get or initialize WebPush client.
     */
    protected function getWebPush(): ?WebPush
    {
        if ($this->webPush !== null) {
            return $this->webPush;
        }

        $subject = config('webpush.vapid.subject');
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');

        if (empty($publicKey) || empty($privateKey)) {
            Log::warning('WebPushService: VAPID keys are missing in configuration. Push dispatch skipped.');
            return null;
        }

        // Auto-detect OpenSSL config on Windows
        $candidatePaths = [
            getenv('OPENSSL_CONF'),
            'C:/xampp/php/extras/ssl/openssl.cnf',
            'C:/xampp/apache/bin/openssl.cnf',
            'C:/php/extras/ssl/openssl.cnf',
        ];
        foreach ($candidatePaths as $p) {
            if ($p && file_exists($p)) {
                putenv("OPENSSL_CONF={$p}");
                break;
            }
        }

        $auth = [
            'VAPID' => [
                'subject' => $subject ?: config('app.url', 'mailto:admin@school-attendance.edu'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ];

        $defaultOptions = [
            'TTL' => config('webpush.defaults.ttl', 86400),
            'urgency' => config('webpush.defaults.urgency', 'high'),
        ];

        try {
            $this->webPush = new WebPush($auth, $defaultOptions);
            $this->webPush->setReuseVAPIDHeaders(true);
            return $this->webPush;
        } catch (\Throwable $e) {
            Log::error('WebPushService initialization failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Send Web Push notification to a single PushSubscription.
     */
    public function sendToSubscription(PushSubscription $subscription, string $title, string $body, array $options = []): bool
    {
        $webPush = $this->getWebPush();
        if (!$webPush) {
            return false;
        }

        try {
            $subObj = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
                'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
            ]);

            $payload = json_encode([
                'title' => $title,
                'body' => $body,
                'icon' => $options['icon'] ?? config('webpush.defaults.icon'),
                'badge' => $options['badge'] ?? config('webpush.defaults.badge'),
                'url' => $options['url'] ?? $options['action_url'] ?? '/home',
                'tag' => $options['tag'] ?? 'school-alert-' . time(),
                'data' => array_merge([
                    'url' => $options['url'] ?? $options['action_url'] ?? '/home',
                    'timestamp' => now()->timestamp,
                ], $options['data'] ?? []),
                'actions' => $options['actions'] ?? [
                    ['action' => 'open', 'title' => 'Open Portal'],
                ],
            ]);

            $report = $webPush->sendOneNotification($subObj, $payload);

            if (!$report->isSuccess()) {
                Log::warning('WebPush send failed: ' . $report->getReason(), [
                    'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
                    'statusCode' => $report->getResponse()?->getStatusCode(),
                ]);

                // Delete subscription if expired or unregistered (404 Not Found / 410 Gone)
                if ($report->isSubscriptionExpired()) {
                    Log::info('Deleting expired WebPush subscription ID ' . $subscription->id);
                    $subscription->delete();
                }
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WebPushService error sending to subscription ' . $subscription->id . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send Web Push notification to a user across all their registered devices.
     */
    public function sendToUser($user, string $title, string $body, array $options = []): int
    {
        $userId = $user instanceof User ? $user->id : $user;
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $sent = 0;
        foreach ($subscriptions as $subscription) {
            if ($this->sendToSubscription($subscription, $title, $body, $options)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send notification to all linked parents/guardians of a student.
     */
    public function sendToParentsOfStudent($student, string $title, string $body, array $options = []): int
    {
        $studentUser = $student instanceof User ? $student : User::find($student);
        if (!$studentUser) {
            return 0;
        }

        $parents = $studentUser->parents;
        if (!$parents || $parents->isEmpty()) {
            return 0;
        }

        $sentTotal = 0;
        foreach ($parents as $parent) {
            $sentTotal += $this->sendToUser($parent, $title, $body, $options);
        }

        return $sentTotal;
    }

    /**
     * Send notification to all users matching a specific role.
     */
    public function sendToRole(string $role, string $title, string $body, array $options = []): int
    {
        $subscriptions = PushSubscription::whereHas('user', function ($q) use ($role) {
            $q->where('role', $role);
        })->get();

        $sent = 0;
        foreach ($subscriptions as $subscription) {
            if ($this->sendToSubscription($subscription, $title, $body, $options)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Broadcast an alert or maintenance announcement to all registered device subscriptions.
     */
    public function broadcastAnnouncement(string $title, string $body, array $options = [], ?string $role = null): int
    {
        $query = PushSubscription::query();
        if ($role) {
            $query->whereHas('user', function ($q) use ($role) {
                $q->where('role', $role);
            });
        }

        $subscriptions = $query->get();
        $sent = 0;

        foreach ($subscriptions as $subscription) {
            if ($this->sendToSubscription($subscription, $title, $body, $options)) {
                $sent++;
            }
        }

        return $sent;
    }
}
