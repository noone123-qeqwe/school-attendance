<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SystemHealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:health-check {--json : Output health check results as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform deep system resilience and operational health diagnostics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $checks = [];
        $overallHealthy = true;

        // 1. Database Connectivity & Performance
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $dbLatencyMs = round((microtime(true) - $start) * 1000, 2);

            $driver = DB::connection()->getDriverName();
            $meta = ['driver' => $driver, 'latency_ms' => $dbLatencyMs];

            if ($driver === 'sqlite') {
                $journal = DB::select('PRAGMA journal_mode');
                $sync = DB::select('PRAGMA synchronous');
                $busy = DB::select('PRAGMA busy_timeout');

                $meta['journal_mode'] = $journal[0]->journal_mode ?? 'unknown';
                $meta['synchronous']  = $sync[0]->synchronous ?? 'unknown';
                $meta['busy_timeout'] = $busy[0]->timeout ?? 'unknown';
            }

            $meta['total_users'] = User::count();
            $meta['total_attendance_records'] = Attendance::count();

            $checks['database'] = [
                'status'  => 'healthy',
                'latency' => "{$dbLatencyMs}ms",
                'details' => $meta,
            ];
        } catch (\Throwable $e) {
            $overallHealthy = false;
            $checks['database'] = [
                'status'  => 'unhealthy',
                'error'   => $e->getMessage(),
            ];
        }

        // 2. Storage Directory Permissions
        $storagePaths = [
            'app'        => storage_path('app'),
            'backups'    => storage_path('app/backups'),
            'cache'      => storage_path('framework/cache'),
            'sessions'   => storage_path('framework/sessions'),
            'views'      => storage_path('framework/views'),
            'logs'       => storage_path('logs'),
        ];

        $storageDetails = [];
        $storageHealthy = true;

        foreach ($storagePaths as $name => $path) {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true, true);
            }
            $isWritable = File::isWritable($path);
            $storageDetails[$name] = [
                'path'     => $path,
                'writable' => $isWritable,
            ];
            if (!$isWritable) {
                $storageHealthy = false;
                $overallHealthy = false;
            }
        }

        $checks['storage'] = [
            'status'  => $storageHealthy ? 'healthy' : 'unhealthy',
            'details' => $storageDetails,
        ];

        // 3. Cache Subsystem
        try {
            $testKey = 'health_check_' . uniqid();
            $start = microtime(true);
            Cache::put($testKey, 'ok', 10);
            $cachedVal = Cache::get($testKey);
            Cache::forget($testKey);
            $cacheLatencyMs = round((microtime(true) - $start) * 1000, 2);

            $checks['cache'] = [
                'status'     => ($cachedVal === 'ok') ? 'healthy' : 'unhealthy',
                'latency'    => "{$cacheLatencyMs}ms",
                'driver'     => config('cache.default'),
            ];
        } catch (\Throwable $e) {
            $overallHealthy = false;
            $checks['cache'] = [
                'status' => 'unhealthy',
                'error'  => $e->getMessage(),
            ];
        }

        // 4. Server Disk Space
        try {
            $freeBytes = disk_free_space(base_path());
            $totalBytes = disk_total_space(base_path());
            $freeGb = round($freeBytes / 1024 / 1024 / 1024, 2);
            $totalGb = round($totalBytes / 1024 / 1024 / 1024, 2);
            $usedPercent = round((($totalBytes - $freeBytes) / $totalBytes) * 100, 1);

            $checks['disk_space'] = [
                'status'        => ($freeGb > 1.0) ? 'healthy' : 'warning',
                'free_gb'       => $freeGb,
                'total_gb'      => $totalGb,
                'used_percent'  => "{$usedPercent}%",
            ];
        } catch (\Throwable $e) {
            $checks['disk_space'] = [
                'status' => 'unknown',
                'error'  => $e->getMessage(),
            ];
        }

        if ($this->option('json')) {
            $this->line(json_encode([
                'status'    => $overallHealthy ? 'healthy' : 'degraded',
                'timestamp' => now()->toIso8601String(),
                'checks'    => $checks,
            ], JSON_PRETTY_PRINT));
            return $overallHealthy ? self::SUCCESS : self::FAILURE;
        }

        $this->info("================================================================");
        $this->info("              SYSTEM OPERATIONAL HEALTH DIAGNOSTICS             ");
        $this->info("================================================================");

        // Database Table
        $this->newLine();
        $this->comment("▶ Database Subsystem:");
        $this->table(
            ['Property', 'Value'],
            [
                ['Status', $checks['database']['status']],
                ['Latency', $checks['database']['latency'] ?? 'N/A'],
                ['Driver', $checks['database']['details']['driver'] ?? 'N/A'],
                ['Journal Mode', $checks['database']['details']['journal_mode'] ?? 'N/A'],
                ['Synchronous', $checks['database']['details']['synchronous'] ?? 'N/A'],
                ['Busy Timeout', ($checks['database']['details']['busy_timeout'] ?? 'N/A') . 'ms'],
                ['Total Users', $checks['database']['details']['total_users'] ?? 'N/A'],
                ['Total Attendance Records', $checks['database']['details']['total_attendance_records'] ?? 'N/A'],
            ]
        );

        // Storage Table
        $this->newLine();
        $this->comment("▶ Storage & Permissions:");
        $storageRows = [];
        foreach ($checks['storage']['details'] as $dir => $info) {
            $storageRows[] = [$dir, $info['path'], $info['writable'] ? 'Writable (OK)' : 'READ ONLY (FAIL)'];
        }
        $this->table(['Directory', 'Path', 'Status'], $storageRows);

        // Cache & Disk
        $this->newLine();
        $this->comment("▶ Performance & Resources:");
        $this->table(
            ['Component', 'Status', 'Details'],
            [
                ['Cache Engine', $checks['cache']['status'], "Driver: " . ($checks['cache']['driver'] ?? 'N/A') . " (Latency: " . ($checks['cache']['latency'] ?? 'N/A') . ")"],
                ['Disk Free Space', $checks['disk_space']['status'], "Free: " . ($checks['disk_space']['free_gb'] ?? 'N/A') . " GB / Total: " . ($checks['disk_space']['total_gb'] ?? 'N/A') . " GB (" . ($checks['disk_space']['used_percent'] ?? 'N/A') . " used)"],
            ]
        );

        $this->newLine();
        if ($overallHealthy) {
            $this->info("✓ ALL SYSTEM SUBSYSTEMS ARE HEALTHY AND RESILIENT");
        } else {
            $this->warn("⚠ SOME SUBSYSTEMS REQUIRE ATTENTION (DEGRADED)");
        }
        $this->info("================================================================");

        return $overallHealthy ? self::SUCCESS : self::FAILURE;
    }
}
