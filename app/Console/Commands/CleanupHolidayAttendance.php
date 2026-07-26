<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Attendance;
use App\Models\Holiday;
use Carbon\Carbon;

class CleanupHolidayAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:cleanup-holiday {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove attendance records for holidays';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::today();
        
        $this->info("Checking date: " . $date->toDateString());
        
        // Check if the date is a holiday
        $holiday = Holiday::getHoliday($date);
        
        if (!$holiday) {
            $this->info("Date is not a holiday. No cleanup needed.");
            return;
        }
        
        $this->info("Date is a holiday: {$holiday->name} ({$holiday->type_label})");
        
        // Get attendance records for this date
        $attendanceRecords = Attendance::whereDate('date', $date)->get();
        
        if ($attendanceRecords->isEmpty()) {
            $this->info("No attendance records found for this date.");
            return;
        }
        
        $this->info("Found {$attendanceRecords->count()} attendance records for this holiday.");
        
        // Show what will be deleted
        $this->table(
            ['Student ID', 'Student Name', 'Subject', 'Status'],
            $attendanceRecords->map(function($record) {
                return [
                    $record->user_id,
                    $record->user->name ?? 'Unknown',
                    $record->subject_code,
                    $record->status
                ];
            })
        );
        
        if ($this->confirm('Do you want to remove these attendance records since this is a holiday?', true)) {
            $count = Attendance::whereDate('date', $date)->delete();
            $this->info("Removed {$count} attendance records for the holiday.");
        } else {
            $this->info("Cleanup cancelled.");
        }
    }
}
