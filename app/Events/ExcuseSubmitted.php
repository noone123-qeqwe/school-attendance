<?php

namespace App\Events;

use App\Models\ExcuseSubmission;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExcuseSubmitted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExcuseSubmission $excuseSubmission,
        public string $studentName,
        public string $subjectCode,
        public int $teacherId
    ) {}

    /**
     * Broadcast on the private teacher channel.
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('teacher.' . $this->teacherId)];
    }

    public function broadcastAs(): string
    {
        return 'excuse.submitted';
    }

    public function broadcastWith(): array
    {
        return [
            'excuse_id'     => $this->excuseSubmission->id,
            'student_name'  => $this->studentName,
            'subject_code'  => $this->subjectCode,
            'reason'        => $this->excuseSubmission->reason,
            'submitted_at'  => $this->excuseSubmission->created_at->format('Y-m-d H:i:s'),
        ];
    }
}