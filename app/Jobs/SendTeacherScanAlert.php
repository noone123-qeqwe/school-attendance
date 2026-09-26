<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTeacherScanAlert implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $teacherId,
        public int $sessionId,
        public string $subjectCode,
        public string $reason,
        public ?int $studentId,
    ) {}

    public function handle(): void
    {
        Notification::firstOrCreate([
            'user_id' => $this->teacherId,
            'type' => 'attendance_scan_alert',
            'subject_code' => $this->subjectCode,
            'message' => "Suspicious scan in session #{$this->sessionId}: {$this->reason}"
                . ($this->studentId ? " (student #{$this->studentId})." : '.'),
        ], ['sent_by' => $this->studentId ?: $this->teacherId, 'is_read' => false]);
    }
}
