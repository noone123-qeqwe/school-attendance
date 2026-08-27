<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WalCheckpointCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:wal-checkpoint';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform an SQLite WAL checkpoint and optimize database statistics';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver !== 'sqlite') {
            $this->info("WAL checkpoint is only applicable for SQLite databases (current driver: {$driver}). Skipping.");
            return self::SUCCESS;
        }

        $this->info('Starting SQLite WAL checkpoint and optimization...');

        try {
            $dbPath = config('database.connections.sqlite.database');

            // 1. Run WAL Checkpoint (TRUNCATE mode flushes pages & resets WAL size)
            $checkpointResult = DB::select('PRAGMA wal_checkpoint(TRUNCATE)');
            $this->info('WAL checkpoint completed.');

            // 2. Optimize table query statistics
            DB::statement('PRAGMA optimize');
            $this->info('SQLite query statistics optimized.');

            // 3. Report file statistics
            if ($dbPath && File::exists($dbPath)) {
                $mainSize = round(File::size($dbPath) / 1024 / 1024, 2);
                $walPath = $dbPath . '-wal';
                $walSize = File::exists($walPath) ? round(File::size($walPath) / 1024, 2) : 0;

                $this->info("Database Main File: {$mainSize} MB | WAL Journal: {$walSize} KB");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to perform WAL checkpoint: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
