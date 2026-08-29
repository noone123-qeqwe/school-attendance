<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\Notification;
use App\Models\Holiday;
use App\Events\NotificationSent;
use Carbon\Carbon;

class MarkAbsentStudents extends Command
{
    protected $signature = 'attendance:mark-absent {date?}';
    protected $description = 'Mark students as absent if they have no attendance record for today';

    public function handle()
    {
        // For testing, allow date parameter
        $today = $this->argument('date') ? Carbon::parse($this->argument('date'))->timezone('Asia/Manila') : now('Asia/Manila');
        $dayName = $today->format('l'); // e.g. "Monday"
        $currentTime = $today->format('H:i:s');

        // Check if today is a holiday (if auto holiday is enabled)
        if (\App\Models\Setting::get('auto_holiday', 1) && Holiday::isHoliday($today->toDateString())) {
            $holiday = Holiday::getHoliday($today->toDateString());
            $this->info("Today is a holiday: {$holiday->name}");
            $this->info("No students will be marked absent due to auto holiday exemption.");
            return;
        }

        // Get subjects scheduled for today whose end_time has passed
        $schedules = \App\Models\Schedule::with('subject')
            ->where('day', $dayName)
            ->where('end_time', '<', $currentTime)
            ->get();

        $absentCount = 0;
        $osasWarningsCount = 0;
        $notificationsSentCount = 0;

        foreach ($schedules as $schedule) {
            $subject = $schedule->subject;
            if (!$subject) continue;

            // Only mark absent if class actually happened:
            // A QR session was started for this subject today, OR at least one student already has a record
            $classHappened = \App\Models\AttendanceSession::where('subject_code', $subject->code)
                ->whereDate('created_at', $today)
                ->exists()
                || Attendance::where('subject_code', $subject->code)
                    ->whereDate('date', $today)
                    ->exists();

            if (!$classHappened) continue;

            $students = $subject->getAllStudents();

            foreach ($students as $student) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($student, $subject, $today, &$absentCount, &$notificationsSentCount, &$osasWarningsCount) {
                    // Check if already has attendance record today for this subject
                    $exists = Attendance::where('user_id', $student->id)
                        ->where('subject_code', $subject->code)
                        ->whereDate('date', $today)
                        ->exists();

                    if (!$exists) {
                        // Mark as absent
                        Attendance::create([
                            'user_id'      => $student->id,
                            'subject_code' => $subject->code,
                            'date'         => $today->toDateString(),
                            'status'       => 'Absent',
                        ]);
                        
                        $absentCount++;

                        // Send individual absence notification
                        $this->sendAbsenceNotification($student, $subject, $today);
                        $notificationsSentCount++;

                        // Check for 3 consecutive absences and send OSAS warning
                        if ($this->checkConsecutiveAbsences($student->id, $subject->code)) {
                            $osasWarningsCount++;
                            $this->info("OSAS warning sent to {$student->name} for {$subject->code}");
                        }
                    }
                });
            }
        }

        $this->info("Absent marking completed for {$today->toDateString()}");
        $this->info("Total absences marked: {$absentCount}");
        $this->info("Absence notifications sent: {$notificationsSentCount}");
        $this->info("OSAS warnings sent: {$osasWarningsCount}");
    }

    private function sendAbsenceNotification($student, $subject, $date)
    {
        $adminUser = User::where('role', 'admin')->first();
        $dateFormatted = $date->format('M j, Y');
        
        $message = "📋 You were marked absent in {$subject->name} ({$subject->code}) on {$dateFormatted}. If you attended this class, please contact your instructor immediately.";

        // Create notification
        Notification::create([
            'user_id' => $student->id,
            'sent_by' => $adminUser ? $adminUser->id : null,
            'type' => 'absence',
            'subject_code' => $subject->code,
            'message' => $message
        ]);

        try {
            broadcast(new NotificationSent(
                userId: $student->id,
                message: $message,
                type: 'absence'
            ));
        } catch (\Exception $e) {
            // Broadcasting failed, continue silently
        }
    }

    private function checkConsecutiveAbsences($studentId, $subjectCode)
    {
        // Get the last 3 attendance records for this student in this subject
        $recentAttendance = Attendance::where('user_id', $studentId)
            ->where('subject_code', $subjectCode)
            ->orderBy('date', 'desc')
            ->take(3)
            ->get();

        // Check if all 3 are absent and consecutive
        if ($recentAttendance->count() >= 3 && 
            $recentAttendance->every(fn($record) => $record->status === 'Absent')) {
            
            // Check if dates are consecutive (allowing for weekends/holidays)
            $dates = $recentAttendance->pluck('date')->map(function($date) {
                return $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
            })->sort()->values();
            $isConsecutive = true;
            
            for ($i = 1; $i < $dates->count(); $i++) {
                $daysDiff = $dates[$i]->diffInDays($dates[$i-1]);
                // Allow up to 7 days difference (accounting for weekends)
                if ($daysDiff > 7) {
                    $isConsecutive = false;
                    break;
                }
            }

            if ($isConsecutive) {
                // Check if OSAS warning already sent
                $osasWarningExists = Warning::where('user_id', $studentId)
                    ->where('subject_code', $subjectCode)
                    ->where('type', 'warning_consecutive_3')
                    ->exists();

                if (!$osasWarningExists) {
                    $this->sendAutomaticOsasWarning($studentId, $subjectCode);
                    return true;
                }
            }
        }
        
        return false;
    }

    private function sendAutomaticOsasWarning($studentId, $subjectCode)
    {
        $student = User::find($studentId);
        $subject = Subject::where('code', $subjectCode)->first();
        $subjectName = $subject ? $subject->name : $subjectCode;

        $message = "🚨 URGENT: You have been absent for 3 CONSECUTIVE sessions in {$subjectName}. YOU HAVE TO GO TO THE OSAS TO GET THE READMISSION TO ENTER MY CLASS.";

        // Save warning record (sent by system - use admin ID or null)
        $adminUser = User::where('role', 'admin')->first();
        
        Warning::create([
            'user_id' => $studentId,
            'subject_code' => $subjectCode,
            'type' => 'warning_consecutive_3',
            'message' => $message,
            'sent_by' => $adminUser ? $adminUser->id : null,
        ]);

        // Create notification
        Notification::create([
            'user_id' => $studentId,
            'sent_by' => $adminUser ? $adminUser->id : null,
            'type' => 'warning_consecutive_3',
            'subject_code' => $subjectCode,
            'message' => $message
        ]);

        try {
            broadcast(new NotificationSent(
                userId: $studentId,
                message: $message,
                type: 'warning_consecutive_3'
            ));
        } catch (\Exception $e) {
            // Broadcasting failed, continue silently
        }
    }
}