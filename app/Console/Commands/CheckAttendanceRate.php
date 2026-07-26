<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\Notification;
use App\Models\Setting;
use Carbon\Carbon;

class CheckAttendanceRate extends Command
{
    protected $signature = 'attendance:check-rate';

    protected $description = 'Warn students whose overall attendance rate falls below the configured threshold';

    public function handle()
    {
        $this->info('Checking attendance rates...');

        $threshold = (int) Setting::get('attendance_rate_threshold', 75);
        $students  = User::where('role', 'student')->get();
        $warned    = 0;

        foreach ($students as $student) {
            $total = Attendance::where('user_id', $student->id)->count();
            if ($total === 0) {
                continue;
            }

            $present = Attendance::where('user_id', $student->id)
                ->whereIn('status', ['Present', 'Late'])
                ->count();

            $rate = (int) round(($present / $total) * 100);

            if ($rate < $threshold) {
                // Avoid spamming: skip if a rate warning was sent in the last 7 days
                $recent = Warning::where('user_id', $student->id)
                    ->where('type', 'Low Attendance Rate')
                    ->where('created_at', '>=', Carbon::now()->subDays(7))
                    ->exists();

                if ($recent) {
                    continue;
                }

                Warning::create([
                    'user_id'      => $student->id,
                    'subject_code' => 'General',
                    'type'         => 'Low Attendance Rate',
                    'message'      => "Your overall attendance rate is {$rate}%, which is below the required {$threshold}%. Please attend your classes regularly.",
                    'sent_by'      => 'System',
                ]);

                Notification::create([
                    'user_id'  => $student->id,
                    'title'    => 'Low Attendance Rate Warning',
                    'message'  => "Your attendance rate ({$rate}%) is below the required threshold of {$threshold}%. Please attend your classes.",
                    'type'     => 'warning',
                    'icon'     => 'bi-exclamation-triangle',
                    'is_read'  => false,
                ]);

                activity()
                    ->performedOn($student)
                    ->causedByAnonymous()
                    ->log("System generated a low attendance rate warning ({$rate}% < {$threshold}%).");

                $warned++;
            }
        }

        $this->info("Done. Warned {$warned} student(s) with attendance rate below {$threshold}%.");
    }
}
