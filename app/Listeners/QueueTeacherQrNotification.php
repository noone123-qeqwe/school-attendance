<?php

namespace App\Listeners;

use App\Events\AttendanceQrChanged;
use App\Jobs\SendTeacherQrNotification;

class QueueTeacherQrNotification
{
    public function handle(AttendanceQrChanged $event): void
    {
        SendTeacherQrNotification::dispatch(
            $event->teacherId,
            $event->generatorId,
            $event->subjectCode,
            $event->tokenId,
            $event->changeType,
            $event->generatorName,
        );
    }
}
