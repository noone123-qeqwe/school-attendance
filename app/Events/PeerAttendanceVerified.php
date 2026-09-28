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
        public ?string $previousStatus = null,
        public ?int $instructorId = null,
    ) {}

    public function broadcastOn(): array
    {
        $ids = array_unique(array_filter([$this->teacherId, $this->instructorId]));
        return array_map(fn ($id) => new PrivateChannel('teacher-dashboard.'.$id), $ids);
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
            'previous_status' => $this->previousStatus,
            'verification_channel' => 'peer_biometric',
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
