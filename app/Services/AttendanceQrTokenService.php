<?php

namespace App\Services;

use App\Models\AttendanceQrToken;
use App\Models\AttendanceSession;
use App\Models\User;
use App\Events\AttendanceQrChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Closure;

class AttendanceQrTokenService
{
    public function recordWhileValid(?string $rawToken, AttendanceSession $session, Closure $record): mixed
    {
        if ($rawToken === null) {
            return $record();
        }

        return DB::transaction(function () use ($rawToken, $session, $record) {
            // Serialize the final scan write with QR replacement. A token that
            // was valid when scanning began may have been replaced in between.
            AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            $inspection = $this->inspect($rawToken);
            if ($inspection['status'] !== 'valid'
                || (int) $inspection['token']->attendance_session_id !== (int) $session->id) {
                throw ValidationException::withMessages(['qr' => 'QR expired or no longer active. Please scan the current QR.']);
            }

            return $record();
        });
    }

    public function issue(AttendanceSession $session, User $operator, string $mode = 'show'): AttendanceQrToken
    {
        if (!in_array($mode, ['show', 'auto', 'manual', 'emergency'], true)) {
            throw new \InvalidArgumentException('Invalid QR operation.');
        }

        $issued = DB::transaction(function () use ($session, $operator, $mode) {
            $session = AttendanceSession::whereKey($session->id)->lockForUpdate()->firstOrFail();
            if (!$session->isSessionActive()) {
                throw ValidationException::withMessages(['session' => 'Attendance session has ended.']);
            }
            if ($operator->isTeacher() || $operator->isDepartmentHead() || $operator->isAdmin()) {
                $elevated = $operator->isDepartmentHead() || $operator->isAdmin();
                abort_unless($operator->isActive() && ($elevated || (int) $session->subject?->instructor_id === (int) $operator->id), 403);
            } else {
                abort_unless($mode !== 'emergency' && $operator->can('generateAssistantQr', $session), 403);
            }

            $current = AttendanceQrToken::where('active_session_id', $session->id)
                ->lockForUpdate()->first();
            if ($current && $current->expires_at->isFuture() && in_array($mode, ['show', 'auto'], true)) {
                return $current;
            }

            if ($mode === 'manual') {
                $used = AttendanceQrToken::where('attendance_session_id', $session->id)
                    ->where('rotation_type', 'manual')->count();
                if ($used >= (int) config('student_assistants.max_manual_regenerations', 3)) {
                    throw ValidationException::withMessages(['qr' => 'Manual QR replacement limit reached for this session.']);
                }
            }

            if ($current) {
                activity('attendance-qr')->causedBy($operator)->performedOn($session)
                    ->withProperties([
                        'attendance_session_id' => $session->id,
                        'qr_token_id' => $current->id,
                        'replacement_requested_by' => $operator->id,
                    ])->log($current->expires_at->isPast() ? 'qr_expired' : 'qr_invalidated');
                $current->update(['active_session_id' => null, 'invalidated_at' => now()]);
            }

            // Retire the legacy QR/code when the signed QR takes over. The old
            // scanner may still know the old value but server validation rejects it.
            $session->update([
                'token' => AttendanceSession::generateToken($session->subject_code),
                'previous_token' => null,
                'previous_session_code' => null,
                'expires_at' => now()->subMinute(),
            ]);

            $now = now();
            $token = AttendanceQrToken::create([
                'attendance_session_id' => $session->id,
                'active_session_id' => $session->id,
                'nonce' => bin2hex(random_bytes(32)),
                'generated_by' => $operator->id,
                'generator_type' => $operator->isStudent() ? 'student_assistant' : 'teacher',
                'rotation_type' => $mode,
                'issued_at' => $now,
                'expires_at' => $now->copy()->addSeconds((int) config('student_assistants.qr_token_ttl', 60))
                    ->min($session->session_ends_at),
            ]);

            activity('attendance-qr')->causedBy($operator)->performedOn($session)
                ->withProperties([
                    'attendance_session_id' => $session->id,
                    'subject_code' => $session->subject_code,
                    'qr_token_id' => $token->id,
                    'generator_type' => $token->generator_type,
                    'rotation_type' => $mode,
                    'replaced_token_id' => $current?->id,
                ])->log($current ? 'qr_replaced' : 'qr_generated');

            return $token;
        });

        if ($issued->wasRecentlyCreated) {
            try {
                $ownerId = (int) ($issued->session?->subject?->instructor_id ?: $issued->session?->created_by);
                AttendanceQrChanged::dispatch(
                    $issued->attendance_session_id,
                    $ownerId,
                    $issued->session->subject_code,
                    $issued->id,
                    $operator->name,
                    $operator->id,
                    $issued->rotation_type === 'show' ? 'generated' : 'replaced',
                    $issued->expires_at->timestamp,
                );
            } catch (\Throwable $exception) {
                Log::warning('QR status broadcast or notification failed', [
                    'attendance_session_id' => $issued->attendance_session_id,
                    'qr_token_id' => $issued->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return $issued;
    }

    public function tokenFor(AttendanceQrToken $token): string
    {
        $payload = implode('.', [
            'aqr', $token->attendance_session_id, $token->id, $token->nonce,
            $token->issued_at->timestamp, $token->expires_at->timestamp,
        ]);

        return $payload . '.' . hash_hmac('sha256', $payload, $this->signingKey());
    }

    /** @return array{status: string, token: AttendanceQrToken|null} */
    public function inspect(string $raw): array
    {
        if (!preg_match('/^aqr\.(\d+)\.(\d+)\.([a-f0-9]{64})\.(\d+)\.(\d+)\.([a-f0-9]{64})$/', $raw, $parts)) {
            return ['status' => 'invalid', 'token' => null];
        }

        $token = AttendanceQrToken::with('session')->find((int) $parts[2]);
        if (!$token || (int) $parts[1] !== (int) $token->attendance_session_id
            || !hash_equals($token->nonce, $parts[3])
            || (int) $parts[4] !== $token->issued_at->timestamp
            || (int) $parts[5] !== $token->expires_at->timestamp
            || !hash_equals($this->tokenFor($token), $raw)) {
            return ['status' => 'invalid', 'token' => null];
        }

        if (!$token->session?->isSessionActive()) {
            return ['status' => 'closed', 'token' => $token];
        }
        if ($token->invalidated_at || (int) $token->active_session_id !== (int) $token->attendance_session_id) {
            return ['status' => 'replaced', 'token' => $token];
        }
        if ($token->expires_at->isPast()) {
            return ['status' => 'expired', 'token' => $token];
        }

        return ['status' => 'valid', 'token' => $token];
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: '';
        }
        if ($key === '') {
            throw new \RuntimeException('Application signing key is not configured.');
        }

        return hash_hmac('sha256', 'attendance-qr-v1', $key, true);
    }
}
