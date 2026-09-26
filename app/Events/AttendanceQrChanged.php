<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceQrChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $teacherId,
        public string $subjectCode,
        public int $tokenId,
        public string $generatorName,
        public int $generatorId,
        public string $changeType,
        public int $expiresAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('teacher-dashboard.' . $this->teacherId),
            new PrivateChannel('assistant-session.' . $this->sessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.qr.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'subject_code' => $this->subjectCode,
            'token_id' => $this->tokenId,
            'generated_by' => $this->generatorName,
            'change_type' => $this->changeType,
            'expires_at' => $this->expiresAt,
        ];
    }
}
