<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Subject;
use App\Models\Warning;
use App\Models\Notification;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;

class CheckAttendanceWarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-warnings {--threshold=85 : The attendance rate threshold to trigger a warning}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detects students dropping below a threshold and notifies parents/teachers.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold');
        $this->info("Checking attendance warnings (Threshold: {$threshold}%)...");

        $students = User::where('role', 'student')->with('enrollments.subject')->get();

        foreach ($students as $student) {
            foreach ($student->enrollments as $enrollment) {
                $subject = $enrollment->subject;
                if (!$subject) continue;

                $totalClasses = Attendance::where('user_id', $student->id)
                    ->where('subject_code', $subject->code)
                    ->count();

                if ($totalClasses < 5) {
                    continue; // Not enough data
                }

                $presentLateCount = Attendance::where('user_id', $student->id)
                    ->where('subject_code', $subject->code)
                    ->whereIn('status', ['Present', 'Late'])
                    ->count();

                $rate = round(($presentLateCount / $totalClasses) * 100);

                if ($rate < $threshold) {
                    // Check if a warning was already sent recently (e.g. within 7 days)
                    $recentWarning = Warning::where('student_id', $student->id)
                        ->where('subject_code', $subject->code)
                        ->where('created_at', '>=', now()->subDays(7))
                        ->exists();

                    if (!$recentWarning) {
                        $this->warn("Student {$student->name} is at {$rate}% in {$subject->code}");

                        // Create warning
                        $warning = Warning::create([
                            'student_id' => $student->id,
                            'subject_code' => $subject->code,
                            'type' => 'Attendance Drop',
                            'severity' => 'High',
                            'message' => "Attendance rate dropped to {$rate}% (Below {$threshold}%)",
                            'resolved' => false,
                        ]);

                        // Notify Teacher
                        if ($subject->instructor_id) {
                            Notification::create([
                                'user_id' => $subject->instructor_id,
                                'type' => 'attendance_warning',
                                'title' => 'Student Attendance Drop',
                                'message' => "{$student->name} dropped to {$rate}% attendance in {$subject->code}.",
                                'action_url' => '/students',
                            ]);
                        }

                        // Notify Parents
                        foreach ($student->parents as $parent) {
                            Notification::create([
                                'user_id' => $parent->id,
                                'type' => 'attendance_warning',
                                'title' => 'Attendance Alert for ' . $student->name,
                                'message' => "{$student->name}'s attendance rate in {$subject->code} dropped to {$rate}%.",
                                'action_url' => '/child/' . $student->id,
                            ]);
                        }
                    }
                }
            }
        }

        $this->info("Attendance warning check complete.");
    }
}
