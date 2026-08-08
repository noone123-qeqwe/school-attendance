<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Warning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckEarlyWarnings extends Command
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
    protected $description = 'Check for chronic absenteeism (3+ unexcused absences/lates in 30 days) and flag students.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting early warning checks...');
        
        $thirtyDaysAgo = now()->subDays(30)->toDateString();

        // Get counts of unexcused absences and lates per student per subject
        $flaggedRecords = Attendance::selectRaw('user_id, subject_code, count(*) as infractions')
            ->whereIn('status', ['Absent', 'Late'])
            ->where('excused', false)
            ->whereDate('date', '>=', $thirtyDaysAgo)
            ->groupBy('user_id', 'subject_code')
            ->having('infractions', '>=', 3)
            ->get();

        $count = 0;

        foreach ($flaggedRecords as $record) {
            // Check if a warning already exists for this user/subject in the last 30 days
            $recentWarning = Warning::where('user_id', $record->user_id)
                ->where('subject_code', $record->subject_code)
                ->where('created_at', '>=', $thirtyDaysAgo)
                ->first();

            if (!$recentWarning) {
                $user = User::find($record->user_id);
                
                $warning = Warning::create([
                    'user_id' => $record->user_id,
                    'subject_code' => $record->subject_code,
                    'type' => 'chronic_absenteeism',
                    'message' => "Chronic absenteeism detected: {$record->infractions} unexcused absences/lates in the last 30 days.",
                ]);

                // Log the activity using Spatie Activitylog
                if ($user) {
                    activity()
                        ->performedOn($user)
                        ->withProperties([
                            'subject_code' => $record->subject_code, 
                            'infractions' => $record->infractions,
                            'type' => 'early_warning_flag'
                        ])
                        ->log("Flagged for chronic absenteeism in {$record->subject_code}");
                }

                $count++;
            }
        }

        $this->info("Completed. Generated {$count} new early warnings.");
    }
}
