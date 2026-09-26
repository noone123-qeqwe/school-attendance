<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceSessionChanged implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $sessionId,
        public int $teacherId,
        public string $subjectCode,
        public string $changeType,
        public int $sessionEndsAt,
        public ?int $actorId = null,
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
        return 'attendance.session.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->sessionId,
            'subject_code' => $this->subjectCode,
            'change_type' => $this->changeType,
            'session_ends_at' => $this->sessionEndsAt,
        ];
    }
}
