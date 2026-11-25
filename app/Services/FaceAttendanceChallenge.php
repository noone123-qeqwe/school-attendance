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
        $challenge = $this->buildChallenge($remaining);
        Cache::put($this->key($request->user(), $session), [
            'challenge' => $challenge,
            'binding' => $this->scanKey($request, $request->input('token')),
            'issued_at' => now()->getTimestampMs(),
            'expires_at' => now()->addSeconds($remaining)->getTimestampMs(),
        ], $remaining);

        return $challenge;
    }

    public function issueForLogin(Request $request, User $user): array
    {
        $challenge = $this->buildChallenge(self::TTL_SECONDS);
        $request->session()->put('webauthn.face_login', [
            'user_id' => (int) $user->id,
            'challenge_hash' => hash('sha256', $challenge['id']),
        ]);
        Cache::put($this->loginKey($user), [
            'challenge' => $challenge,
            'binding' => $this->loginBinding($request),
            'user_id' => (int) $user->id,
            'issued_at' => now()->getTimestampMs(),
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->getTimestampMs(),
        ], self::TTL_SECONDS);

        return $challenge;
    }

    /** Caller holds the per-student/session verification lock. */
    public function consume(Request $request, AttendanceSession $session): ?array
    {
        return $this->consumeTicket(
            $this->key($request->user(), $session),
            $this->scanKey($request, $request->input('token')),
            $request->input('face_challenge')
        );
    }

    /** Caller holds the per-account login verification lock. */
    public function consumeForLogin(Request $request, User $user): ?array
    {
        $sessionState = $request->session()->pull('webauthn.face_login');
        if (!is_array($sessionState) || (int) ($sessionState['user_id'] ?? 0) !== (int) $user->id) {
            return null;
        }

        return $this->consumeTicket(
            $this->loginKey($user),
            $this->loginBinding($request),
            $request->input('face_challenge'),
            (int) $user->id
        );
    }

    public function lockKey(User $user, AttendanceSession $session): string
    {
        return $this->key($user, $session).':verify';
    }

    public function loginLockKey(User $user): string
    {
        return $this->loginKey($user).':verify';
    }

    private function buildChallenge(int $ttlSeconds): array
    {
        $poses = ['left', 'right', 'up', 'down'];
        for ($i = count($poses) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$poses[$i], $poses[$j]] = [$poses[$j], $poses[$i]];
        }

        return [
            'id' => bin2hex(random_bytes(32)),
            'poses' => ['front', ...$poses],
            'expires_in' => $ttlSeconds,
        ];
    }

    private function consumeTicket(string $key, string $expectedBinding, mixed $submittedId, ?int $expectedUserId = null): ?array
    {
        $ticket = Cache::get($key);
        if (!is_string($submittedId) || !is_array($ticket)
            || !hash_equals((string) ($ticket['binding'] ?? ''), $expectedBinding)
            || !hash_equals((string) ($ticket['challenge']['id'] ?? ''), $submittedId)
            || ($expectedUserId !== null && (int) ($ticket['user_id'] ?? 0) !== $expectedUserId)) {
            return null;
        }

        Cache::forget($key);
        $now = now()->getTimestampMs();
        if ($now - $ticket['issued_at'] < 3500 || $now >= $ticket['expires_at']) {
            return null;
        }

        return $ticket['challenge']['poses'];
    }

    private function key(User $user, AttendanceSession $session): string
    {
        return 'qr_face_challenge:'.$user->id.':'.$session->id;
    }

    private function loginKey(User $user): string
    {
        return 'login_face_challenge:'.$user->id;
    }

    private function loginBinding(Request $request): string
    {
        $session = $request->session();
        $token = $session->get('webauthn.face_binding');
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            $session->put('webauthn.face_binding', $token);
        }

        return 'login_face:'.hash('sha256', $token);
    }

    private function scanKey(Request $request, string $token): string
    {
        return 'qr_face_scan:'.hash('sha256', $request->user()->id.'|'.$request->session()->getId().'|'.$token);
    }
}
