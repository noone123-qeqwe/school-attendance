<?php

namespace App\Listeners;

use App\Events\AttendanceMarked;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendParentAttendanceAlert
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AttendanceMarked $event): void
    {
        $attendance = $event->attendance;

        // Only alert for Absent or Late
        if (!in_array($attendance->status, ['Absent', 'Late'])) {
            return;
        }

        // We assume attendance has a related user
        $student = User::find($attendance->user_id);
        if (!$student) {
            return;
        }

        $teacherId = auth()->id() ?? 1; // Fallback if system job
        $subjectName = $attendance->subject ? $attendance->subject->name : $attendance->subject_code;

        $type = $attendance->status === 'Absent' ? 'absent_alert' : 'late_alert';
        $message = "Your child, {$student->name}, was marked {$attendance->status} in {$subjectName} on " . \Carbon\Carbon::parse($attendance->date)->format('M d, Y') . ".";

        // Create the notification for the student (parents view child notifications)
        Notification::create([
            'user_id' => $student->id,
            'sent_by' => $teacherId,
            'type' => $type,
            'subject_code' => $attendance->subject_code,
            'message' => $message,
            'is_read' => false,
        ]);
    }
}
