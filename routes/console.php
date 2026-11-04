<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Bound verification requests become unusable at expiry even if the browser closes.
Schedule::call(function () {
    \App\Models\PeerVouchRequest::whereIn('status', ['pending', 'processing'])
        ->where('expires_at', '<=', now())
        ->update(['status' => 'expired', 'failure_reason' => 'expired', 'consumed_at' => now()]);
})->everyMinute()->name('peer-snap-expire')->onOneServer()->withoutOverlapping();

// Auto-close expired QR sessions and mark absent students (every minute)
Schedule::command('attendance:auto-close')->everyMinute()->onOneServer()->withoutOverlapping();

// Mark absent students every 30 minutes for reliable coverage
Schedule::command('attendance:mark-absent')->everyThirtyMinutes()->onOneServer()->withoutOverlapping();

// Flag chronic absence patterns; the separate check-rate command handles low rates.
Schedule::command('attendance:check-warnings')->dailyAt('19:00')->onOneServer()->withoutOverlapping();

// Process unexcused absence count warnings
Schedule::command('attendance:warnings')->dailyAt('19:15')->onOneServer()->withoutOverlapping();

// Check students with low attendance rate
Schedule::command('attendance:check-rate')->dailyAt('20:00')->onOneServer()->withoutOverlapping(); // Evening rate check

// Send daily attendance digest to parents
Schedule::command('app:send-parent-digests')->dailyAt('18:00')->onOneServer()->withoutOverlapping(); // Evening digest

// Automated Database Backup (Daily at 02:00 with 14-day retention pruning)
Schedule::command('app:backup-database --days=14')->dailyAt('02:00')->onOneServer()->withoutOverlapping();

// Hourly SQLite WAL Checkpoint & Query Plan Optimization
Schedule::command('app:wal-checkpoint')->hourly()->onOneServer()->withoutOverlapping();

// Automated Pruning of Failed Queue Jobs & Expired Password Tokens
Schedule::command('queue:prune-failed --hours=48')->daily()->onOneServer()->withoutOverlapping();
Schedule::command('auth:clear-resets')->everyFifteenMinutes()->onOneServer()->withoutOverlapping();

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
