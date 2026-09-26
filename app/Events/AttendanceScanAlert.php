<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class AttendanceScanAlert implements ShouldBroadcastNow
{
    use Dispatchable;

    public function __construct(
        public int $teacherId,
        public int $sessionId,
        public string $reason,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('teacher-dashboard.' . $this->teacherId)];
    }

    public function broadcastAs(): string
    {
        return 'attendance.scan.alert';
    }

    public function broadcastWith(): array
    {
        return ['session_id' => $this->sessionId, 'reason' => $this->reason];
    }
}
