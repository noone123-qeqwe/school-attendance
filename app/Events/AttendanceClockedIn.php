<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceClockedIn implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $subjectName,
        public string $status,
        public string $time
    ) {}

    /**
     * Broadcast on the private admin-dashboard channel.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'attendance.clocked-in';
    }

    public function broadcastWith(): array
    {
        return [
            'student_name'  => $this->studentName,
            'subject_name'  => $this->subjectName,
            'status'        => $this->status,
            'time'          => $this->time,
        ];
    }
}
