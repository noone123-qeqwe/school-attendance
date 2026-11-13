<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

/** Record limited face verification metadata without persisting biometric material. */
class BiometricVerificationAuditService
{
    public function record(User $user, Request $request, int $sessionId, bool $success, string $failureCategory): void
    {
        $agent = new Agent();
        $agent->setUserAgent((string) $request->userAgent());
        $deviceType = $agent->isTablet() ? 'tablet' : ($agent->isMobile() ? 'mobile' : 'desktop');
        $ip = (string) $request->ip();
        $key = (string) config('app.key') ?: 'attendance-biometric-audit';

        $properties = [
            'user_id' => $user->getKey(),
            'attendance_session_id' => $sessionId,
            'device_type' => $deviceType,
            'ip_hash' => $ip !== '' ? hash_hmac('sha256', $ip, $key) : null,
            'attempted_at' => now()->toIso8601String(),
            'result' => $success ? 'success' : 'failure',
            'failure_category' => $success ? null : $failureCategory,
        ];

        try {
            activity('face-attendance')->causedBy($user)->withProperties($properties)->log('Face verification attempt');
        } catch (\Throwable) {
            Log::error('Face attendance audit write failed', $properties);
        }
    }
}
