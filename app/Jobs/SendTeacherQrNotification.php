<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTeacherQrNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $teacherId,
        public int $generatorId,
        public string $subjectCode,
        public int $tokenId,
        public string $changeType,
        public string $generatorName,
    ) {}

    public function handle(): void
    {
        Notification::firstOrCreate([
            'user_id' => $this->teacherId,
            'type' => 'attendance_qr_' . $this->changeType,
            'subject_code' => $this->subjectCode,
            'message' => "Attendance QR #{$this->tokenId} {$this->changeType} by {$this->generatorName}.",
        ], [
            'sent_by' => $this->generatorId,
            'is_read' => false,
        ]);
    }
}
