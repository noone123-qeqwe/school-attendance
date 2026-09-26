<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTeacherAttendanceNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $teacherId,
        public string $subjectCode,
        public string $studentName,
        public string $status,
        public string $occurredAt,
    ) {}

    public function handle(): void
    {
        Notification::firstOrCreate([
            'user_id' => $this->teacherId,
            'type' => 'attendance_recorded',
            'subject_code' => $this->subjectCode,
            'message' => "{$this->studentName} marked {$this->status} in {$this->subjectCode} at {$this->occurredAt}.",
        ], ['sent_by' => $this->teacherId, 'is_read' => false]);
    }
}
