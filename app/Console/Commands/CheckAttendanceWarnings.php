<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\Notification;
use App\Notifications\AttendanceWarningNotification;
use Carbon\Carbon;
use Spatie\Activitylog\Facades\Activity;

class CheckAttendanceWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-warnings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for students with excessive absences and generate warnings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting attendance warning check...');

        // Get all students
        $students = User::where('role', 'student')->get();
        $threshold = (int) \App\Models\Setting::get('warning_threshold', 3);
        $warningsCreated = 0;

        foreach ($students as $student) {
            // Get the last N attendances for this student ordered by date desc
            $recentAttendances = Attendance::where('user_id', $student->id)
                ->orderBy('date', 'desc')
                ->take($threshold)
                ->get();

            if ($recentAttendances->count() >= $threshold) {
                $consecutiveAbsences = true;
                foreach ($recentAttendances as $attendance) {
                    // Fix: use capital 'Absent' to match the stored status value
                    if ($attendance->status !== 'Absent' || $attendance->excused) {
                        $consecutiveAbsences = false;
                        break;
                    }
                }

                if ($consecutiveAbsences) {
                    // Check if a warning was already created recently to prevent spamming
                    $recentWarning = Warning::where('user_id', $student->id)
                        ->where('type', 'Consecutive Absences')
                        ->where('created_at', '>=', Carbon::now()->subDays(3))
                        ->exists();

                    if (!$recentWarning) {
                        // Create Warning
                        $warning = Warning::create([
                            'user_id' => $student->id,
                            'subject_code' => $recentAttendances->first()->subject_code ?? 'General',
                            'type' => 'Consecutive Absences',
                            'message' => 'You have accumulated ' . $threshold . ' consecutive unexcused absences. Please submit an excuse or contact your instructor.',
                            'sent_by' => 'System'
                        ]);

                        // Create Notification for the student
                        Notification::create([
                            'user_id' => $student->id,
                            'title' => 'Attendance Warning',
                            'message' => 'You have accumulated ' . $threshold . ' consecutive unexcused absences. Please check your dashboard.',
                            'type' => 'warning',
                            'icon' => 'bi-exclamation-triangle',
                            'is_read' => false
                        ]);

                        // Log this action
                        activity()
                            ->performedOn($student)
                            ->causedByAnonymous()
                            ->log("System generated an attendance warning for {$threshold} consecutive absences.");

                        // Send Email Notification
                        $student->notify(new AttendanceWarningNotification($warning));

                        $warningsCreated++;
                    }
                }
            }
        }

        $this->info("Completed. Generated {$warningsCreated} warnings.");
    }
}
