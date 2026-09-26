<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTeacherSessionNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $teacherId,
        public int $sessionId,
        public string $subjectCode,
        public string $changeType,
    ) {}

    public function handle(): void
    {
        Notification::firstOrCreate([
            'user_id' => $this->teacherId,
            'type' => 'attendance_session_' . $this->changeType,
            'subject_code' => $this->subjectCode,
            'message' => "Attendance session #{$this->sessionId} {$this->changeType}.",
        ], ['sent_by' => $this->teacherId, 'is_read' => false]);
    }
}
