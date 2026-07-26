<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AttendanceAutoCloseService;

class AutoCloseAttendanceSessions extends Command
{
    protected $signature = 'attendance:auto-close';
    protected $description = 'Automatically close expired attendance sessions and mark absent students';

    public function handle()
    {
        $this->info('Checking for expired attendance sessions...');
        
        $service = app(AttendanceAutoCloseService::class);
        $closedSessions = $service->closeExpiredSessions();
        
        if ($closedSessions > 0) {
            $this->info("✅ Auto-closed {$closedSessions} expired session(s) and marked absent students");
        } else {
            $this->info('No expired sessions found');
        }
        
        return Command::SUCCESS;
    }
}