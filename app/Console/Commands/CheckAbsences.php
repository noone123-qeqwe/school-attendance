<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckAbsences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-absences';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for students hitting absence thresholds and sends automated warnings.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking absences for today...');
        $today = now()->toDateString();
        
        // Get all absences that occurred today
        $todaysAbsences = \App\Models\Attendance::where('status', 'Absent')
            ->whereDate('date', $today)
            ->with(['user', 'subject'])
            ->get();

        $emailsSent = 0;

        foreach ($todaysAbsences as $absence) {
            $student = $absence->user;
            $subject = $absence->subject;

            if (!$student || !$subject) continue;

            // Count total absences for this student in this subject
            $totalAbsences = \App\Models\Attendance::where('user_id', $student->id)
                ->where('subject_code', $subject->code)
                ->where('status', 'Absent')
                ->count();

            // Thresholds: Warning at 3, Critical at 5
            if ($totalAbsences === 3 || $totalAbsences === 5) {
                // Dispatch email
                \Illuminate\Support\Facades\Mail::to($student->email)
                    ->send(new \App\Mail\AbsenceWarning($student, $subject, $totalAbsences));
                
                $this->line("Sent warning to {$student->email} for {$subject->code} ({$totalAbsences} absences).");
                $emailsSent++;
            }
        }

        $this->info("Completed. Sent {$emailsSent} warnings.");
    }
}
