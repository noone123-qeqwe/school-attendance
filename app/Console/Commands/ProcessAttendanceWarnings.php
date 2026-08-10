<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Setting;
use App\Models\Warning;
use App\Models\Notification;
use App\Models\Attendance;
use Carbon\Carbon;

class ProcessAttendanceWarnings extends Command
{
    protected $signature = 'attendance:warnings';
    protected $description = 'Process automated disciplinary workflows for students with excessive absences.';

    public function handle()
    {
        $threshold = Setting::where('key', 'warning_threshold')->value('value') ?? 3;
        
        $students = User::where('role', 'student')->with('parents')->get();
        
        foreach ($students as $student) {
            // Count unexcused absences
            $absences = Attendance::where('user_id', $student->id)
                ->where('status', 'Absent')
                ->where('excused', false)
                ->count();
                
            if ($absences >= $threshold) {
                // Check if warning already sent recently (within 30 days to avoid spamming)
                $recentWarning = Warning::where('user_id', $student->id)
                    ->where('type', 'system_absence_threshold')
                    ->where('created_at', '>=', Carbon::now()->subDays(30))
                    ->exists();
                    
                if (!$recentWarning) {
                    // Create Warning
                    Warning::create([
                        'user_id' => $student->id,
                        'type' => 'system_absence_threshold',
                        'message' => "Automated System Warning: You have reached {$absences} unexcused absences. Please contact the counselor.",
                        'sent_by' => null // System
                    ]);
                    
                    // Notify student
                    Notification::create([
                        'user_id' => $student->id,
                        'title' => 'Critical Absence Warning',
                        'message' => "You have reached {$absences} unexcused absences.",
                        'type' => 'warning_3',
                        'link' => route('profile')
                    ]);
                    
                    // Notify parents
                    foreach ($student->parents as $parent) {
                        Notification::create([
                            'user_id' => $parent->id,
                            'title' => 'Critical Absence Warning - ' . $student->name,
                            'message' => "Your child {$student->name} has reached {$absences} unexcused absences.",
                            'type' => 'warning_3',
                            'link' => route('parent.child.warnings', $student->id)
                        ]);
                    }
                }
            }
        }
        
        $this->info("Attendance warnings processed successfully.");
    }
}
