<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Auto-close expired QR sessions and mark absent students (every minute)
Schedule::command('attendance:auto-close')->everyMinute();

// Mark absent students every 30 minutes for reliable coverage
Schedule::command('attendance:mark-absent')->everyThirtyMinutes();

// Check for excessive absences and issue warnings
Schedule::command('attendance:check-warnings')->dailyAt('19:00'); // Evening check

// Process unexcused absence count warnings
Schedule::command('attendance:warnings')->dailyAt('19:15');

// Check students with low attendance rate
Schedule::command('attendance:check-rate')->dailyAt('20:00'); // Evening rate check

// Send daily attendance digest to parents
Schedule::command('app:send-parent-digests')->dailyAt('18:00'); // Evening digest

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
