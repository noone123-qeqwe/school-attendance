<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeacherAttendanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $teacherId,
        public string $studentName,
        public string $subjectCode,
        public string $status,
        public string $type = 'clock_in',
        public ?array $stats = null
    ) {}

    /**
     * Broadcast on the teacher's private dashboard channel.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('teacher-dashboard.' . $this->teacherId)];
    }

    public function broadcastAs(): string
    {
        return 'attendance.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'student_name'    => $this->studentName,
            'subject_code'    => $this->subjectCode,
            'status'          => $this->status,
            'type'            => $this->type,
            'time'            => now()->format('H:i:s'),
            'stats'           => $this->stats,
            'total_present'   => $this->stats['total_present'] ?? null,
            'total_late'      => $this->stats['total_late'] ?? null,
            'total_absent'    => $this->stats['total_absent'] ?? null,
            'total_students'  => $this->stats['total_students'] ?? null,
        ];
    }
}