<?php

namespace App\Listeners;

use App\Events\AttendanceSessionChanged;
use App\Jobs\SendTeacherSessionNotification;
use App\Models\AttendanceSession;
use App\Models\User;

class AuditAttendanceSessionChange
{
    public function handle(AttendanceSessionChanged $event): void
    {
        $session = AttendanceSession::find($event->sessionId);
        if (!$session) return;

        $activity = activity('attendance-session')->performedOn($session)
            ->withProperties([
                'attendance_session_id' => $session->id,
                'subject_code' => $event->subjectCode,
                'session_ends_at' => $event->sessionEndsAt,
            ]);
        if ($actor = User::find($event->actorId ?? $event->teacherId)) $activity->causedBy($actor);
        $activity->log('attendance_session_' . $event->changeType);

        if (in_array($event->changeType, ['extended', 'closed'], true)) {
            SendTeacherSessionNotification::dispatch(
                $event->teacherId, $event->sessionId, $event->subjectCode, $event->changeType
            );
        }
    }
}
