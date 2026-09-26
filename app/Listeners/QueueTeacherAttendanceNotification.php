<?php

namespace App\Listeners;

use App\Events\TeacherAttendanceUpdated;
use App\Jobs\SendTeacherAttendanceNotification;

class QueueTeacherAttendanceNotification
{
    public function handle(TeacherAttendanceUpdated $event): void
    {
        if ($event->type !== 'clock_in') return;

        SendTeacherAttendanceNotification::dispatch(
            $event->teacherId,
            $event->subjectCode,
            $event->studentName,
            $event->status,
            now()->toIso8601String(),
        );
    }
}
