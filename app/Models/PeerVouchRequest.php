<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeerVouchRequest extends Model
{
    protected $fillable = [
        'verification_id', 'session_id', 'subject_student_id', 'voucher_student_id',
        'attendance_id', 'decision_mac',
        'nonce_hash', 'challenge', 'status', 'failure_reason', 'attempt_number',
        'expires_at', 'consumed_at',
    ];

    protected $casts = ['expires_at' => 'datetime', 'consumed_at' => 'datetime'];

    public function session()
    {
        return $this->belongsTo(AttendanceSession::class, 'session_id');
    }

    public function subject()
    {
        return $this->belongsTo(User::class, 'subject_student_id');
    }

    public function voucher()
    {
        return $this->belongsTo(User::class, 'voucher_student_id');
    }

    public function hasValidDecisionMac(): bool
    {
        if ($this->status !== 'verified' || !$this->decision_mac || !$this->attendance_id || !$this->consumed_at) {
            return false;
        }
        $attendance = Attendance::withTrashed()->find($this->attendance_id);
        if (!$attendance || $attendance->user_id !== $this->subject_student_id
            || $attendance->session_id !== $this->session_id
            || $attendance->verification_channel !== 'peer_biometric') return false;

        return hash_equals($this->decision_mac, self::decisionMac(
            $this->verification_id, $this->session_id, $this->subject_student_id,
            $this->voucher_student_id, $attendance->id, $this->consumed_at->toIso8601String(),
        ));
    }

    public static function decisionMac(string $verificationId, int $sessionId, int $subjectId,
        int $voucherId, int $attendanceId, string $verifiedAt): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) $key = base64_decode(substr($key, 7), true) ?: '';
        if ($key === '') throw new \RuntimeException('APP_KEY is required for peer attendance audit integrity.');
        return hash_hmac('sha256', implode('|', [
            $verificationId, $sessionId, $subjectId, $voucherId, $attendanceId, $verifiedAt,
        ]), $key);
    }
}
