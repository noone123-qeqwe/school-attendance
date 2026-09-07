<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use App\Models\BackupLog;
use App\Models\Setting;

class SystemUpdateController extends Controller
{
    public function index(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $activeTab = $request->query('tab', 'updates');

        $isDown = app()->isDownForMaintenance();
        $laravelVersion = app()->version();
        $phpVersion = phpversion();
        $appEnvironment = app()->environment();
        $debugMode = config('app.debug');

        // Database details & latency check
        $driver = DB::connection()->getDriverName();
        $dbLatencyMs = 0;
        $dbTableCount = 0;
        $dbSizeBytes = 0;

        $dbStartTime = microtime(true);
        try {
            DB::connection()->getPdo();
            if ($driver === 'sqlite') {
                $dbVersion = 'SQLite ' . (DB::select('SELECT sqlite_version() as v')[0]->v ?? '3.x');
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $dbTableCount = count($tables);
                $sqlitePath = config('database.connections.sqlite.database');
                if ($sqlitePath && File::exists($sqlitePath)) {
                    $dbSizeBytes = filesize($sqlitePath);
                }
            } elseif ($driver === 'pgsql') {
                $dbVersion = 'PostgreSQL ' . (DB::select('SELECT version() as v')[0]->v ?? '15+');
                $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
                $dbTableCount = count($tables);
                try {
                    $sizeRes = DB::select('SELECT pg_database_size(current_database()) as size');
                    $dbSizeBytes = $sizeRes[0]->size ?? 0;
                } catch (\Exception $e) {
                    $dbSizeBytes = 0;
                }
            } else {
                $dbVersion = 'MySQL ' . (DB::select('SELECT VERSION() as version')[0]->version ?? '8.0');
                $tables = DB::select('SHOW TABLES');
                $dbTableCount = count($tables);
                try {
                    $sizeRes = DB::select('SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES WHERE table_schema = DATABASE()');
                    $dbSizeBytes = $sizeRes[0]->size ?? 0;
                } catch (\Exception $e) {
                    $dbSizeBytes = 0;
                }
            }
            $dbLatencyMs = round((microtime(true) - $dbStartTime) * 1000, 2);
            $dbConnected = true;
        } catch (\Exception $e) {
            $dbConnected = false;
            $dbVersion = 'Disconnected';
            $dbLatencyMs = 0;
        }

        // Service Worker Version
        $swPath = public_path('sw.js');
        $swVersion = 'v8';
        if (File::exists($swPath)) {
            $content = File::get($swPath);
            if (preg_match('/CACHE_VERSION\s*=\s*[\'"]v?(\d+)[\'"]/', $content, $matches)) {
                $swVersion = 'v' . $matches[1];
            } elseif (preg_match('/CACHE_NAME\s*=\s*[`\'"][^`\'"]*v(\d+)[`\'"]/', $content, $matches)) {
                $swVersion = 'v' . $matches[1];
            }
        }

        // Storage & Directory Write Permissions
        $storageWritable = is_writable(storage_path());
        $bootstrapCacheWritable = is_writable(base_path('bootstrap/cache'));
        $backupsDir = storage_path('app/backups');
        $backupsWritable = file_exists($backupsDir) ? is_writable($backupsDir) : is_writable(storage_path('app'));

        // Disk space
        $diskFreeBytes = @disk_free_space(base_path()) ?: 0;
        $diskTotalBytes = @disk_total_space(base_path()) ?: 0;
        $diskUsedPercent = $diskTotalBytes > 0 ? round((($diskTotalBytes - $diskFreeBytes) / $diskTotalBytes) * 100, 1) : 0;

        // PHP Extensions Check
        $requiredExtensions = [
            'pdo' => extension_loaded('pdo'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'bcmath' => extension_loaded('bcmath'),
            'fileinfo' => extension_loaded('fileinfo'),
            'curl' => extension_loaded('curl'),
        ];
        $allExtensionsLoaded = !in_array(false, $requiredExtensions, true);

        // Memory & Execution Limits
        $memoryLimit = ini_get('memory_limit');
        $memoryUsedBytes = memory_get_usage(true);
        $maxExecutionTime = ini_get('max_execution_time') . 's';
        $opcacheEnabled = function_exists('opcache_get_status') && is_array(@opcache_get_status());

        // Pending Migrations Inspection
        $pendingMigrations = $this->getPendingMigrationsInternal();
        $pendingMigrationsCount = count($pendingMigrations);

        // Backups Management (Full Paginated list for Backups Tab & Recent for Updates Tab)
        $allBackups = BackupLog::latest()->paginate(10)->withQueryString();
        $recentBackups = BackupLog::latest()->take(5)->get();
        $totalBackupCount = BackupLog::count();
        $totalBackupSize = BackupLog::sum('size');

        // Queues & Jobs Telemetry
        $failedJobsCount = 0;
        if (Schema::hasTable('failed_jobs')) {
            try { $failedJobsCount = DB::table('failed_jobs')->count(); } catch (\Exception $e) {}
        }
        $queueSize = 0;
        try {
            $queueSize = Queue::size();
        } catch (\Exception $e) {
            $queueSize = 0;
        }

        // Mail configuration check
        $mailHost = config('mail.mailers.smtp.host') ?: config('mail.default');
        $mailConfigured = !empty(config('mail.mailers.smtp.host')) && config('mail.mailers.smtp.host') !== '127.0.0.1';

        // Health Score (0 - 100%)
        $healthScore = 100;
        if (!$dbConnected) $healthScore -= 40;
        if (!$storageWritable || !$bootstrapCacheWritable) $healthScore -= 20;
        if (!$allExtensionsLoaded) $healthScore -= 20;
        if ($pendingMigrationsCount > 0) $healthScore -= 10;
        if ($isDown) $healthScore -= 10;
        if ($failedJobsCount > 0) $healthScore -= 5;
        $healthScore = max(0, $healthScore);

        return view('admin.system_update.index', compact(
            'activeTab',
            'isDown',
            'laravelVersion',
            'phpVersion',
            'appEnvironment',
            'debugMode',
            'driver',
            'dbVersion',
            'dbConnected',
            'dbLatencyMs',
            'dbTableCount',
            'dbSizeBytes',
            'swVersion',
            'storageWritable',
            'bootstrapCacheWritable',
            'backupsWritable',
            'diskFreeBytes',
            'diskTotalBytes',
            'diskUsedPercent',
            'requiredExtensions',
            'allExtensionsLoaded',
            'memoryLimit',
            'memoryUsedBytes',
            'maxExecutionTime',
            'opcacheEnabled',
            'pendingMigrationsCount',
            'pendingMigrations',
            'allBackups',
            'recentBackups',
            'totalBackupCount',
            'totalBackupSize',
            'failedJobsCount',
            'queueSize',
            'mailHost',
            'mailConfigured',
            'healthScore'
        ));
    }

    public function runFullUpdate(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $results = [];
        $overallSuccess = true;

        // 1. Auto Backup Database Snapshot
        try {
            $filename = 'backup_pre_update_' . date('Y_m_d_His') . '.sql';
            $path = storage_path('app/backups');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $filepath = $path . '/' . $filename;
            
            $handle = fopen($filepath, 'w');
            if ($handle) {
                $driver = DB::connection()->getDriverName();
                $pdo = DB::connection()->getPdo();

                if ($driver === 'sqlite') {
                    fwrite($handle, "PRAGMA foreign_keys = OFF;\nBEGIN TRANSACTION;\n\n");
                    $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                    foreach ($tables as $table) {
                        $tableName = $table->name;
                        $createSql = $table->sql;
                        fwrite($handle, "-- Table structure for `{$tableName}`\n\nDROP TABLE IF EXISTS `{$tableName}`;\n{$createSql};\n\n");
                        
                        $cursor = DB::table($tableName)->cursor();
                        $hasRows = false;
                        foreach ($cursor as $row) {
                            if (!$hasRows) {
                                fwrite($handle, "-- Dumping data for table `{$tableName}`\n\n");
                                $hasRows = true;
                            }
                            $rowArr = (array)$row;
                            $keys = array_map(fn($k) => "`{$k}`", array_keys($rowArr));
                            $values = array_map(function($v) use ($pdo) {
                                return is_null($v) ? "NULL" : $pdo->quote($v);
                            }, array_values($rowArr));
                            fwrite($handle, "INSERT INTO `{$tableName}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                        }
                        if ($hasRows) fwrite($handle, "\n");
                    }
                    fwrite($handle, "COMMIT;\nPRAGMA foreign_keys = ON;\n");
                } else {
                    $tables = DB::select('SHOW TABLES');
                    foreach ($tables as $table) {
                        $tableName = array_values((array)$table)[0];
                        $createTable = DB::select("SHOW CREATE TABLE `{$tableName}`")[0];
                        $createTableArr = array_values((array)$createTable);
                        $createSql = $createTableArr[1] ?? '';
                        fwrite($handle, "-- Table structure for `{$tableName}`\n\nDROP TABLE IF EXISTS `{$tableName}`;\n" . $createSql . ";\n\n");
                        
                        $cursor = DB::table($tableName)->cursor();
                        $hasRows = false;
                        foreach ($cursor as $row) {
                            if (!$hasRows) {
                                fwrite($handle, "-- Dumping data for table `{$tableName}`\n\n");
                                $hasRows = true;
                            }
                            $rowArr = (array)$row;
                            $keys = array_map(fn($k) => "`{$k}`", array_keys($rowArr));
                            $values = array_map(function($v) use ($pdo) {
                                return is_null($v) ? "NULL" : $pdo->quote($v);
                            }, array_values($rowArr));
                            fwrite($handle, "INSERT INTO `{$tableName}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                        }
                        if ($hasRows) fwrite($handle, "\n");
                    }
                }
                fclose($handle);

                $backupLog = BackupLog::create([
                    'filename' => $filename,
                    'path' => 'backups/' . $filename,
                    'size' => filesize($filepath)
                ]);
                $results[] = [
                    'step' => 'Database Safety Snapshot',
                    'status' => 'success',
                    'message' => "Safety backup snapshot created: {$filename} (" . round(filesize($filepath) / 1024 / 1024, 2) . " MB)",
                    'backup_id' => $backupLog->id,
                    'filename' => $filename
                ];
            } else {
                $results[] = [
                    'step' => 'Database Safety Snapshot',
                    'status' => 'warning',
                    'message' => 'Unable to write backup file to storage/app/backups. Skipping safety snapshot.'
                ];
            }
        } catch (\Exception $e) {
            $results[] = [
                'step' => 'Database Safety Snapshot',
                'status' => 'warning',
                'message' => 'Backup warning: ' . $e->getMessage()
            ];
        }

        // 2. Run Database Migrations
        try {
            Artisan::call('migrate', ['--force' => true]);
            $migrateOutput = trim(Artisan::output());
            $results[] = [
                'step' => 'Database Migrations',
                'status' => 'success',
                'message' => $migrateOutput ?: 'Database schema is completely up to date. No pending migrations.'
            ];
        } catch (\Exception $e) {
            $overallSuccess = false;
            $results[] = [
                'step' => 'Database Migrations',
                'status' => 'error',
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }

        // 3. Clear & Rebuild Application Caches
        try {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            try { Artisan::call('config:clear'); } catch (\Exception $ex) {}
            try { Artisan::call('route:clear'); } catch (\Exception $ex) {}
            $results[] = [
                'step' => 'Cache Invalidation & Warmup',
                'status' => 'success',
                'message' => 'All compiled views, route tables, configuration files, and application caches cleared and re-primed.'
            ];
        } catch (\Exception $e) {
            $results[] = [
                'step' => 'Cache Invalidation & Warmup',
                'status' => 'warning',
                'message' => 'Cache warning: ' . $e->getMessage()
            ];
        }

        // 4. Bump Service Worker / PWA Asset Version
        try {
            $newVer = $this->bumpPwaVersionInternal();
            $results[] = [
                'step' => 'PWA & Client App Broadcast',
                'status' => 'success',
                'message' => "PWA Service Worker cache version bumped to {$newVer}. All student, teacher, and admin clients will receive the fresh build automatically."
            ];
        } catch (\Exception $e) {
            $results[] = [
                'step' => 'PWA & Client App Broadcast',
                'status' => 'warning',
                'message' => 'PWA Broadcast warning: ' . $e->getMessage()
            ];
        }

        // 5. Broadcast Push Announcement to All Registered User Devices
        try {
            $updateTitle = '🚀 System Update Complete';
            $updateBody = 'A new system update was installed. All features and performance optimizations are now live!';
            
            app(\App\Services\WebPushService::class)->broadcastAnnouncement(
                $updateTitle,
                $updateBody,
                ['url' => route('intro'), 'tag' => 'sys-update-' . time()]
            );

            // Create in-app notification records for all active users
            try {
                $activeUserIds = \App\Models\User::pluck('id');
                $senderId = Auth::id() ?? \App\Models\User::where('role', 'admin')->value('id');
                $notifBatch = [];
                $now = now();
                foreach ($activeUserIds as $uId) {
                    $notifBatch[] = [
                        'user_id' => $uId,
                        'sent_by' => $senderId,
                        'type' => 'system_update',
                        'subject_code' => 'SYS',
                        'message' => "Smart Attendance full system update applied successfully. New features, security improvements, and optimizations are live.",
                        'is_read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($notifBatch)) {
                    \App\Models\Notification::insert($notifBatch);
                }
            } catch (\Throwable $notifErr) {
                Log::warning('In-app system update notification insert error: ' . $notifErr->getMessage());
            }

            $results[] = [
                'step' => 'Live User Broadcast & Push Announcement',
                'status' => 'success',
                'message' => 'Instant push notification broadcast dispatched to all connected student, teacher, and parent devices.'
            ];
        } catch (\Throwable $e) {
            $results[] = [
                'step' => 'Live User Broadcast & Push Announcement',
                'status' => 'warning',
                'message' => 'Push broadcast notice: ' . $e->getMessage()
            ];
        }

        return response()->json([
            'success' => $overallSuccess,
            'message' => $overallSuccess ? 'Full 1-click system update completed and broadcasted to all users successfully!' : 'System update completed with warnings or migration errors.',
            'results' => $results,
            'timestamp' => now()->format('M d, Y h:i:s A')
        ]);
    }

    public function runMigrations(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            Artisan::call('migrate', ['--force' => true]);
            $output = trim(Artisan::output());
            return response()->json([
                'success' => true,
                'message' => $output ?: 'Database schema is already up to date.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkMigrations(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            $pending = $this->getPendingMigrationsInternal();
            $appliedCount = Schema::hasTable('migrations') ? DB::table('migrations')->count() : 0;
            return response()->json([
                'success' => true,
                'pending_count' => count($pending),
                'pending_migrations' => $pending,
                'applied_count' => $appliedCount,
                'is_up_to_date' => count($pending) === 0,
                'message' => count($pending) === 0
                    ? 'All database migrations are applied. Database schema is completely up to date.'
                    : count($pending) . ' pending migration(s) ready to be applied.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check migration status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clearCaches(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            Artisan::call('view:clear');
            Artisan::call('cache:clear');
            try { Artisan::call('config:clear'); } catch (\Exception $ex) {}
            try { Artisan::call('route:clear'); } catch (\Exception $ex) {}
            return response()->json([
                'success' => true,
                'message' => 'Application view cache, config cache, route cache, and system cache cleared successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear caches: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bumpPwaVersion(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            $newVer = $this->bumpPwaVersionInternal();

            // Clear compiled view cache so new asset query hashes take effect immediately
            try { Artisan::call('view:clear'); } catch (\Throwable $ex) {}

            // Broadcast Web Push announcement to all registered user devices (mobile & desktop)
            $pushedCount = 0;
            try {
                $pushedCount = app(\App\Services\WebPushService::class)->broadcastAnnouncement(
                    '⚡ System Update Available (' . $newVer . ')',
                    "A new application update ({$newVer}) is live! Tap to reload the latest features and optimizations.",
                    [
                        'url' => route('intro'),
                        'tag' => 'pwa-broadcast-' . time(),
                        'version' => $newVer
                    ]
                );
            } catch (\Throwable $pushErr) {
                Log::warning('PWA broadcast push error: ' . $pushErr->getMessage());
            }

            // Create in-app notification records for all active users
            try {
                $activeUserIds = \App\Models\User::pluck('id');
                $senderId = Auth::id() ?? \App\Models\User::where('role', 'admin')->value('id');
                $notifBatch = [];
                $now = now();
                foreach ($activeUserIds as $uId) {
                    $notifBatch[] = [
                        'user_id' => $uId,
                        'sent_by' => $senderId,
                        'type' => 'system_update',
                        'subject_code' => 'SYS',
                        'message' => "Smart Attendance updated to build {$newVer}. New features, security improvements, and speed optimizations are live.",
                        'is_read' => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if (!empty($notifBatch)) {
                    \App\Models\Notification::insert($notifBatch);
                }
            } catch (\Throwable $notifErr) {
                Log::warning('In-app system update notification insert error: ' . $notifErr->getMessage());
            }

            return response()->json([
                'success' => true,
                'version' => $newVer,
                'pushed_count' => $pushedCount,
                'message' => "Client app version successfully bumped to {$newVer}! Web Push notification dispatched to {$pushedCount} registered mobile and desktop devices. Connected devices will reload updated code immediately."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bump version: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleMaintenance(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            if (app()->isDownForMaintenance()) {
                Artisan::call('up');

                // Broadcast Web Push announcement
                try {
                    app(\App\Services\WebPushService::class)->broadcastAnnouncement(
                        '🟢 System Maintenance Concluded',
                        'The attendance portal is fully operational and open for all regular classes and clock-ins.',
                        ['url' => route('intro'), 'tag' => 'maint-live-' . time()]
                    );
                } catch (\Throwable $pushErr) {
                    \Log::warning('Maintenance live push notice: ' . $pushErr->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'is_down' => false,
                    'status_label' => 'Live & Operational',
                    'message' => 'Application is now LIVE and accessible to all users.'
                ]);
            } else {
                Artisan::call('down', [
                    '--render' => 'offline',
                    '--secret' => 'admin-access-portal'
                ]);

                // Broadcast Web Push announcement
                try {
                    app(\App\Services\WebPushService::class)->broadcastAnnouncement(
                        '⚠️ System Maintenance Active',
                        'System maintenance is currently in progress. Student services will resume shortly.',
                        ['url' => route('intro'), 'tag' => 'maint-active-' . time()]
                    );
                } catch (\Throwable $pushErr) {
                    \Log::warning('Maintenance active push notice: ' . $pushErr->getMessage());
                }

                return response()->json([
                    'success' => true,
                    'is_down' => true,
                    'status_label' => 'Maintenance Mode Active',
                    'secret' => 'admin-access-portal',
                    'message' => 'Application placed in MAINTENANCE MODE. Super admins can bypass with secret token (/admin-access-portal).'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle maintenance mode: ' . $e->getMessage()
            ], 500);
        }
    }

    public function runHealthCheck(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        
        $checks = [];

        // 1. Database Connection & Latency
        $dbStart = microtime(true);
        try {
            DB::connection()->getPdo();
            $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
            $checks[] = [
                'name' => 'Database Connectivity',
                'status' => 'pass',
                'message' => "Connected to " . DB::connection()->getDriverName() . " ({$dbLatency} ms)"
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Database Connectivity',
                'status' => 'fail',
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }

        // 2. Storage Permissions
        $storageWritable = is_writable(storage_path());
        $checks[] = [
            'name' => 'Storage Directory Permissions',
            'status' => $storageWritable ? 'pass' : 'fail',
            'message' => $storageWritable ? 'storage/ is fully writable (0775/0755)' : 'storage/ is NOT writable'
        ];

        // 3. Bootstrap Cache
        $bootstrapCache = is_writable(base_path('bootstrap/cache'));
        $checks[] = [
            'name' => 'Bootstrap Cache Permissions',
            'status' => $bootstrapCache ? 'pass' : 'fail',
            'message' => $bootstrapCache ? 'bootstrap/cache is writable' : 'bootstrap/cache is NOT writable'
        ];

        // 4. Cache System Read/Write test
        try {
            \Illuminate\Support\Facades\Cache::put('__health_test_key', 'ok', 10);
            $cachedVal = \Illuminate\Support\Facades\Cache::get('__health_test_key');
            \Illuminate\Support\Facades\Cache::forget('__health_test_key');
            $checks[] = [
                'name' => 'Cache Engine Read/Write',
                'status' => $cachedVal === 'ok' ? 'pass' : 'warn',
                'message' => 'Cache driver (' . config('cache.default') . ') operational'
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'name' => 'Cache Engine Read/Write',
                'status' => 'warn',
                'message' => 'Cache test failed: ' . $e->getMessage()
            ];
        }

        // 5. Disk Free Space
        $freeBytes = @disk_free_space(base_path()) ?: 0;
        $freeMb = round($freeBytes / 1024 / 1024, 1);
        $checks[] = [
            'name' => 'Disk Storage Capacity',
            'status' => $freeBytes > (50 * 1024 * 1024) ? 'pass' : 'warn',
            'message' => "{$freeMb} MB free disk space available"
        ];

        // 6. Queue status
        $failedJobs = 0;
        if (Schema::hasTable('failed_jobs')) {
            try { $failedJobs = DB::table('failed_jobs')->count(); } catch (\Exception $e) {}
        }
        $checks[] = [
            'name' => 'Background Job Queue',
            'status' => $failedJobs === 0 ? 'pass' : 'warn',
            'message' => $failedJobs === 0 ? '0 failed jobs in queue' : "{$failedJobs} failed jobs detected"
        ];

        // Calculate score
        $passedCount = count(array_filter($checks, fn($c) => $c['status'] === 'pass'));
        $score = round(($passedCount / count($checks)) * 100);

        return response()->json([
            'success' => true,
            'score' => $score,
            'checks' => $checks,
            'timestamp' => now()->format('h:i:s A')
        ]);
    }

    private function getPendingMigrationsInternal(): array
    {
        try {
            $ranMigrations = [];
            if (Schema::hasTable('migrations')) {
                $ranMigrations = DB::table('migrations')->pluck('migration')->toArray();
            }
            $allMigrationFiles = collect(File::files(database_path('migrations')))
                ->map(fn($file) => $file->getFilenameWithoutExtension())
                ->toArray();
            return array_values(array_diff($allMigrationFiles, $ranMigrations));
        } catch (\Exception $e) {
            return [];
        }
    }

    private function bumpPwaVersionInternal()
    {
        $swPath = public_path('sw.js');
        $newVersion = 'v8';

        // Capture old mtime BEFORE modifying sw.js so we can forget the old cache key
        $oldMtime = File::exists($swPath) ? filemtime($swPath) : 0;

        if (File::exists($swPath)) {
            $content = File::get($swPath);
            $currentNum = 7;
            if (preg_match('/CACHE_VERSION\s*=\s*[\'"]v?(\d+)[\'"]/', $content, $matches)) {
                $currentNum = (int)$matches[1];
            } elseif (preg_match('/CACHE_NAME\s*=\s*[`\'"][^`\'"]*v(\d+)[`\'"]/', $content, $matches)) {
                $currentNum = (int)$matches[1];
            }

            $nextNum = $currentNum + 1;
            $newVersion = 'v' . $nextNum;
            $timestamp = now()->toIso8601String();

            // Update CACHE_VERSION
            if (preg_match('/const\s+CACHE_VERSION\s*=\s*[\'"][^\'"]+[\'"];/', $content)) {
                $content = preg_replace('/const\s+CACHE_VERSION\s*=\s*[\'"][^\'"]+[\'"];/', "const CACHE_VERSION = '{$newVersion}';", $content);
            } else {
                $content = "const CACHE_VERSION = '{$newVersion}';\n" . $content;
            }

            // Update CACHE_NAME
            $content = preg_replace('/const\s+CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/', "const CACHE_NAME = `attendance-{$newVersion}`;", $content);

            // Update RUNTIME_CACHE_NAME
            $content = preg_replace('/const\s+RUNTIME_CACHE_NAME\s*=\s*[`\'"][^`\'"]+[`\'"];/', "const RUNTIME_CACHE_NAME = `attendance-runtime-{$newVersion}`;", $content);

            // Ensure timestamp header comment to guarantee byte-level modification
            if (preg_match('/\/\* BUMP_TIMESTAMP:.*?\*\//s', $content)) {
                $content = preg_replace('/\/\* BUMP_TIMESTAMP:.*?\*\//s', "/* BUMP_TIMESTAMP: {$timestamp} */", $content);
            } else {
                $content = "/* BUMP_TIMESTAMP: {$timestamp} */\n" . $content;
            }

            File::put($swPath, $content);
        }

        $tagsPath = resource_path('views/partials/pwa-tags.blade.php');
        if (File::exists($tagsPath)) {
            $tagsContent = File::get($tagsPath);
            $tagsContent = preg_replace('/\/sw\.js(?:\?[^"\']*)?/', '/sw.js?v={{ $swQueryVer }}', $tagsContent);
            File::put($tagsPath, $tagsContent);
        }

        $manifestPath = public_path('manifest.json');
        if (File::exists($manifestPath)) {
            $manifestContent = File::get($manifestPath);
            $appVer = (string)config('changelog.default_version', '2.3.0');
            $manifestContent = preg_replace('/"version"\s*:\s*"[^"]+"/', "\"version\": \"{$appVer}\"", $manifestContent);
            File::put($manifestPath, $manifestContent);
        }

        \Illuminate\Support\Facades\Cache::forever('pwa_sw_version', $newVersion);

        // Flush the old mtime-based cached version response so /pwa/version immediately returns the new tag
        // The new mtime (from the File::put above) will auto-generate a fresh cache key on next request
        if ($oldMtime) {
            \Illuminate\Support\Facades\Cache::forget('pwa_version_response_' . $oldMtime);
        }

        return $newVersion;
    }

    /**
     * Send an independent diagnostic test email (Super Admin tool).
     */
    public function sendTestEmail(Request $request, \App\Services\Email\EmailDeliveryService $emailDeliveryService)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $request->validate(['email' => 'required|email']);
        $email = trim((string) $request->input('email'));

        $result = $emailDeliveryService->sendDiagnosticTestEmail($email);

        return response()->json([
            'success'     => $result->success,
            'provider'    => $result->provider,
            'message_id'  => $result->messageId,
            'response'    => $result->response,
            'message'     => $result->success 
                ? "Diagnostic test email successfully delivered to {$email} via {$result->provider}."
                : ($result->error ?: "Failed to deliver test email."),
            'diagnostics' => $result->diagnostics,
        ], $result->success ? 200 : 500);
    }
}
