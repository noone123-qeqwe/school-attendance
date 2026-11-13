<?php

namespace App\Services;

use App\Models\AttendanceSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FaceAttendanceChallenge
{
    public const TTL_SECONDS = 70;
    // Two front frames, then a centered transition and two frames per turn.
    public const FRAME_COUNT = 14;
    public const MAX_FRAME_LENGTH = 240000;

    public function admitScan(Request $request, AttendanceSession $session, string $token): void
    {
        Cache::add($this->scanKey($request, $token), (int) $session->id, 100);
    }

    public function admittedSession(Request $request, string $token): int
    {
        return (int) Cache::get($this->scanKey($request, $token), 0);
    }

    public function issue(Request $request, AttendanceSession $session): array
    {
        $remaining = max(1, min(self::TTL_SECONDS, $session->session_ends_at->timestamp - now()->timestamp));
        $poses = ['left', 'right', 'up', 'down'];
        for ($i = count($poses) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$poses[$i], $poses[$j]] = [$poses[$j], $poses[$i]];
        }
        $challenge = [
            'id' => bin2hex(random_bytes(32)),
            'poses' => ['front', ...$poses],
            'expires_in' => $remaining,
        ];
        Cache::put($this->key($request->user(), $session), [
            'challenge' => $challenge,
            'binding' => $this->scanKey($request, $request->input('token')),
            'issued_at' => now()->getTimestampMs(),
            'expires_at' => now()->addSeconds($remaining)->getTimestampMs(),
        ], $remaining);

        return $challenge;
    }

    /** Caller holds the per-student/session verification lock. */
    public function consume(Request $request, AttendanceSession $session): ?array
    {
        $key = $this->key($request->user(), $session);
        $ticket = Cache::get($key);
        $id = $request->input('face_challenge');
        if (!is_string($id) || !is_array($ticket)
            || !hash_equals($ticket['binding'], $this->scanKey($request, $request->input('token')))
            || !hash_equals($ticket['challenge']['id'], $id)) {
            return null;
        }

        Cache::forget($key);
        $now = now()->getTimestampMs();
        if ($now - $ticket['issued_at'] < 3500 || $now >= $ticket['expires_at']) {
            return null;
        }

        return $ticket['challenge']['poses'];
    }

    public function lockKey(User $user, AttendanceSession $session): string
    {
        return $this->key($user, $session).':verify';
    }

    private function key(User $user, AttendanceSession $session): string
    {
        return 'qr_face_challenge:'.$user->id.':'.$session->id;
    }

    private function scanKey(Request $request, string $token): string
    {
        return 'qr_face_scan:'.hash('sha256', $request->user()->id.'|'.$request->session()->getId().'|'.$token);
    }
}
