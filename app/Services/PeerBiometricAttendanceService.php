<?php

namespace App\Services;

use App\Events\PeerAttendanceVerified;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\PeerFaceEnrollment;
use App\Models\PeerVouchRequest;
use App\Models\User;
use App\Policies\PeerVouchPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PeerBiometricAttendanceService
{
    public function __construct(private PeerVouchPolicy $policy, private PeerFaceVerifier $verifier) {}

    public function eligibleSessions(User $host): array
    {
        if (!$this->isAvailable()) return [];
        return AttendanceSession::where('active', true)
            ->where('session_ends_at', '>', now())
            ->get()->filter(fn ($session) => $this->policy->create($host, $session)
                && !$this->locked($host->id, null)
                && $this->successfulVouches($session->id, $host->id) < config('peer_snap.max_vouches'))
            ->map(fn ($session) => [
                'id' => $session->id,
                'subject_code' => $session->subject_code,
                'subject_name' => $session->subject?->name,
                'remaining_vouches' => max(0, config('peer_snap.max_vouches') - $this->successfulVouches($session->id, $host->id)),
            ])->values()->all();
    }

    public function start(User $host, int $sessionId, string $studentNumber): array
    {
        if (!$this->isAvailable()) {
            abort(503, 'Peer verification is not available yet.');
        }

        $outcome = DB::transaction(function () use ($host, $sessionId, $studentNumber) {
            $session = AttendanceSession::whereKey($sessionId)->lockForUpdate()->first();
            if (!$session || !$this->policy->create($host, $session)) {
                Log::warning('peer_snap.start_denied', ['session_id' => $sessionId, 'voucher_student_id' => $host->id, 'reason' => 'host_ineligible']);
                abort(403, 'Host is not eligible for this session.');
            }
            if ($this->locked($host->id, null)) {
                Log::warning('peer_snap.start_denied', ['session_id' => $sessionId, 'voucher_student_id' => $host->id, 'reason' => 'rate_limited']);
                abort(429, 'Too many failed attempts. Please try later.');
            }
            if ($this->successfulVouches($session->id, $host->id) >= config('peer_snap.max_vouches')) {
                Log::warning('peer_snap.start_denied', ['session_id' => $sessionId, 'voucher_student_id' => $host->id, 'reason' => 'quota']);
                abort(403, 'Peer check-in limit reached for this session.');
            }

            $subject = User::where('student_number', $studentNumber)->where('role', 'student')->first();
            if ($subject && $this->locked($host->id, $subject->id)) {
                Log::warning('peer_snap.start_denied', ['session_id' => $sessionId, 'voucher_student_id' => $host->id, 'reason' => 'subject_rate_limited']);
                abort(429, 'Too many failed attempts. Please try later.');
            }
            $enrollment = $subject ? PeerFaceEnrollment::where('user_id', $subject->id)
                ->whereNull('revoked_at')->where('model_version', config('peer_snap.model_version'))->first() : null;
            $existing = $subject ? Attendance::withTrashed()->where('user_id', $subject->id)
                ->where('subject_code', $session->subject_code)->whereDate('date', now('Asia/Manila')->toDateString())
                ->first() : null;
            $eligible = $subject && $subject->isActive() && $subject->id !== $host->id
                && $this->policy->isClassMember($subject, $session->subject)
                && $enrollment && (!$existing || ($existing->status === 'Absent' && !$existing->excused));

            $nonce = bin2hex(random_bytes(32));
            $attempt = PeerVouchRequest::create([
                'verification_id' => (string) Str::uuid(),
                'session_id' => $session->id,
                'subject_student_id' => $subject?->id,
                'voucher_student_id' => $host->id,
                'nonce_hash' => hash('sha256', $nonce),
                'challenge' => collect(['blink_twice', 'turn_left', 'turn_right'])->random(),
                'status' => $eligible ? 'pending' : 'rejected',
                'failure_reason' => $eligible ? null : 'student_ineligible',
                'attempt_number' => min(255, 1 + PeerVouchRequest::where('voucher_student_id', $host->id)
                    ->where('created_at', '>=', now()->subMinutes(config('peer_snap.lockout_minutes')))->count()),
                'expires_at' => now()->addSeconds(config('peer_snap.session_ttl_seconds')),
            ]);

            Log::info('peer_snap.attempt_created', $this->audit($attempt));
            if (!$eligible) return ['error' => true];

            return [
                'verification_id' => $attempt->verification_id,
                'nonce' => $nonce,
                'challenge' => $attempt->challenge,
                'expires_at' => $attempt->expires_at->toIso8601String(),
            ];
        });

        if (isset($outcome['error'])) abort(422, 'Student cannot be checked in through this method.');
        return $outcome;
    }

    public function confirm(User $host, string $verificationId, string $nonce, array $frames): array
    {
        $claim = DB::transaction(function () use ($host, $verificationId, $nonce) {
            $attempt = PeerVouchRequest::where('verification_id', $verificationId)->lockForUpdate()->first();
            if (!$attempt || $attempt->voucher_student_id !== $host->id) {
                Log::warning('peer_snap.confirm_denied', ['verification_id' => $verificationId, 'voucher_student_id' => $host->id, 'reason' => 'not_found']);
                abort(404, 'Verification request not found.');
            }
            if ($attempt->status !== 'pending') {
                Log::warning('peer_snap.confirm_denied', $this->audit($attempt) + ['reason' => 'replay']);
                abort(409, 'Verification request already used.');
            }
            if ($attempt->expires_at->isPast()) {
                $attempt->update(['status' => 'expired', 'failure_reason' => 'expired', 'consumed_at' => now()]);
                return ['error' => 410, 'message' => 'Verification session expired.'];
            }
            if (!hash_equals($attempt->nonce_hash, hash('sha256', $nonce))) {
                $attempt->update(['status' => 'rejected', 'failure_reason' => 'invalid_nonce', 'consumed_at' => now()]);
                Log::warning('peer_snap.invalid_nonce', $this->audit($attempt));
                return ['error' => 422, 'message' => 'Verification failed.'];
            }
            if ($this->locked($host->id, $attempt->subject_student_id)) {
                $attempt->update(['status' => 'locked', 'failure_reason' => 'rate_limited', 'consumed_at' => now()]);
                return ['error' => 429, 'message' => 'Too many failed attempts. Please try later.'];
            }
            $attempt->update(['status' => 'processing']);
            return ['attempt' => $attempt];
        });
        if (isset($claim['error'])) abort($claim['error'], $claim['message']);
        /** @var PeerVouchRequest $attempt */
        $attempt = $claim['attempt'];
        $enrollment = PeerFaceEnrollment::where('user_id', $attempt->subject_student_id)
            ->whereNull('revoked_at')->where('model_version', config('peer_snap.model_version'))->first();

        try {
            if (!$enrollment) throw new \RuntimeException('Enrollment unavailable.');
            $decision = $this->verifier->verify($attempt, $nonce, $enrollment->verifier_subject_ref, $frames);
        } catch (Throwable $e) {
            $this->reject($attempt, 'verifier_unavailable');
            Log::warning('peer_snap.verifier_unavailable', $this->audit($attempt) + ['error_class' => get_class($e)]);
            abort(503, 'Verification service is unavailable. Please ask your teacher for help.');
        }

        if (!$decision['accepted']) {
            $this->reject($attempt, $decision['reason']);
            abort(422, match ($decision['reason']) {
                'face_count' => 'Exactly one face must be visible.',
                'image_quality' => 'Improve lighting and keep your face inside the guide.',
                'liveness' => 'Liveness check failed. Please try again.',
                default => 'The live face could not be verified.',
            });
        }

        $result = DB::transaction(function () use ($attempt, $host, $enrollment) {
            $session = AttendanceSession::whereKey($attempt->session_id)->lockForUpdate()->first();
            $lockedAttempt = PeerVouchRequest::whereKey($attempt->id)->lockForUpdate()->first();
            $subject = User::whereKey($attempt->subject_student_id)->lockForUpdate()->first();
            if (!$lockedAttempt || $lockedAttempt->status !== 'processing') abort(409, 'Verification request already used.');
            if ($lockedAttempt->expires_at->isPast() || !$session || !$this->policy->create($host, $session)
                || !$subject || !$subject->isActive()
                || !$session->subject || !$this->policy->isClassMember($subject, $session->subject)
                || $this->locked($host->id, $subject->id)
                || !PeerFaceEnrollment::where('user_id', $subject->id)
                    ->where('verifier_subject_ref', $enrollment->verifier_subject_ref)
                    ->where('model_version', config('peer_snap.model_version'))->whereNull('revoked_at')->exists()
                || $this->successfulVouches($session->id, $host->id) >= config('peer_snap.max_vouches')) {
                $lockedAttempt->update(['status' => 'rejected', 'failure_reason' => 'eligibility_changed', 'consumed_at' => now()]);
                return ['error' => 409, 'message' => 'Session eligibility changed. Please try again.'];
            }
            $today = now('Asia/Manila')->toDateString();
            $attendance = Attendance::withTrashed()->where('user_id', $subject->id)
                ->where('subject_code', $session->subject_code)->whereDate('date', $today)
                ->lockForUpdate()->first();
            if ($attendance && ($attendance->status !== 'Absent' || $attendance->excused)) {
                $lockedAttempt->update(['status' => 'rejected', 'failure_reason' => 'duplicate_attendance', 'consumed_at' => now()]);
                return ['error' => 409, 'message' => 'Attendance has already been recorded.'];
            }
            $previousStatus = $attendance?->status;
            $data = [
                'user_id' => $subject->id, 'subject_id' => $session->subject->id,
                'subject_code' => $session->subject_code, 'subject_name' => $session->subject->name,
                'class' => $session->subject->section ?? $subject->section ?? 'Regular',
                'session_id' => $session->id, 'date' => $today, 'status' => 'Present',
                'time_in' => now('Asia/Manila')->format('H:i:s'), 'checked_in_at' => now(),
                'method' => 'peer_biometric', 'verification_channel' => 'peer_biometric',
                'is_provisional' => false, 'monitoring_status' => 'peer_verified',
            ];
            if ($attendance) {
                if ($attendance->trashed()) $attendance->restore();
                $attendance->fill($data)->save();
            } else {
                $attendance = Attendance::create($data);
            }
            $verifiedAt = now();
            $lockedAttempt->update([
                'status' => 'verified', 'consumed_at' => $verifiedAt,
                'failure_reason' => null, 'attendance_id' => $attendance->id,
                'decision_mac' => PeerVouchRequest::decisionMac(
                    $lockedAttempt->verification_id, $session->id, $subject->id,
                    $host->id, $attendance->id, $verifiedAt->toIso8601String(),
                ),
            ]);
            Log::info('peer_snap.verified', $this->audit($lockedAttempt) + ['attendance_id' => $attendance->id]);
            return ['attendance' => $attendance, 'session' => $session,
                'subject' => $subject, 'previous_status' => $previousStatus];
        });
        if (isset($result['error'])) abort($result['error'], $result['message']);

        try {
            event(new PeerAttendanceVerified(
                (int) $result['session']->created_by, (int) $result['session']->id,
                $result['session']->subject_code, $result['subject']->name,
                $host->name, (int) $result['attendance']->id,
                $result['previous_status'], $result['session']->subject?->instructor_id,
            ));
        } catch (Throwable $e) {
            Log::warning('peer_snap.broadcast_failed', ['verification_id' => $verificationId, 'error_class' => get_class($e)]);
        }
        return ['attendance_id' => $result['attendance']->id, 'status' => 'Present',
            'student_name' => $result['subject']->name, 'verification_channel' => 'peer_biometric',
            'is_provisional' => false];
    }

    private function reject(PeerVouchRequest $attempt, string $reason): void
    {
        PeerVouchRequest::whereKey($attempt->id)->where('status', 'processing')->update([
            'status' => 'rejected', 'failure_reason' => $reason, 'consumed_at' => now(), 'updated_at' => now(),
        ]);
        Log::warning('peer_snap.rejected', $this->audit($attempt) + ['failure_reason' => $reason]);
    }

    private function locked(int $hostId, ?int $subjectId): bool
    {
        $since = now()->subMinutes(config('peer_snap.lockout_minutes'));
        $failures = fn () => PeerVouchRequest::where('status', 'rejected')
            ->where('created_at', '>=', $since)->whereIn('failure_reason', [
                'student_ineligible', 'invalid_nonce', 'face_count', 'image_quality', 'liveness', 'face_mismatch',
            ]);
        if ($failures()->where('voucher_student_id', $hostId)->count() >= config('peer_snap.max_failed_attempts')) return true;
        return $subjectId !== null && $failures()->where('subject_student_id', $subjectId)->count() >= config('peer_snap.max_failed_attempts');
    }

    private function successfulVouches(int $sessionId, int $hostId): int
    {
        return PeerVouchRequest::where('session_id', $sessionId)->where('voucher_student_id', $hostId)
            ->where('status', 'verified')->count();
    }

    public function isAvailable(): bool
    {
        return (bool) config('peer_snap.enabled')
            && str_starts_with((string) config('peer_snap.verifier_url'), 'https://')
            && (bool) config('peer_snap.verifier_token') && (bool) config('peer_snap.model_version')
            && config('peer_snap.match_threshold') > 0 && config('peer_snap.match_threshold') <= 1
            && config('peer_snap.pad_threshold') > 0 && config('peer_snap.pad_threshold') <= 1;
    }

    private function audit(PeerVouchRequest $attempt): array
    {
        return ['verification_id' => $attempt->verification_id, 'session_id' => $attempt->session_id,
            'subject_student_id' => $attempt->subject_student_id, 'voucher_student_id' => $attempt->voucher_student_id,
            'status' => $attempt->status, 'failure_reason' => $attempt->failure_reason];
    }
}
