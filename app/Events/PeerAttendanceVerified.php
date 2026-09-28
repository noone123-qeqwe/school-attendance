<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class PeerAttendanceVerified implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $teacherId,
        public int $sessionId,
        public string $subjectCode,
        public string $studentName,
        public string $voucherName,
        public int $attendanceId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('teacher-dashboard.'.$this->teacherId)];
    }

    public function broadcastAs(): string
    {
        return 'attendance.peer.verified';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'subject_code' => $this->subjectCode,
            'student_name' => $this->studentName,
            'voucher_student_name' => $this->voucherName,
            'attendance_id' => $this->attendanceId,
            'status' => 'Present',
            'verification_channel' => 'peer_biometric',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
