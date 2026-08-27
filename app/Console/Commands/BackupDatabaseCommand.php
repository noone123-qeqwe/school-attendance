<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database {--days=14 : Days of backup history to retain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an automated database snapshot and prune old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting automated database backup snapshot...');

        try {
            $filename = 'backup_auto_' . date('Y_m_d_His') . '.sql';
            $path = storage_path('app/backups');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            $filepath = $path . '/' . $filename;
            $handle = fopen($filepath, 'w');

            if (!$handle) {
                $this->error("Cannot open file for writing: {$filepath}");
                return self::FAILURE;
            }

            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }
            DB::beginTransaction();

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
                    fwrite($handle, "-- Table structure for `{$tableName}`\n\nDROP TABLE IF EXISTS `{$tableName}`;\n" . array_values((array)$createTable)[1] . ";\n\n");

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

            DB::commit();
            fclose($handle);

            $size = filesize($filepath);
            $backup = BackupLog::create([
                'filename' => $filename,
                'path'     => 'backups/' . $filename,
                'size'     => $size,
            ]);

            $this->info("Backup created successfully: {$filename} (" . round($size / 1024 / 1024, 2) . " MB)");

            // Prune old backups older than specified retention days
            $retentionDays = (int) $this->option('days');
            $cutoffDate = now()->subDays($retentionDays);

            $oldBackups = BackupLog::where('created_at', '<', $cutoffDate)->get();
            $prunedCount = 0;

            foreach ($oldBackups as $old) {
                $oldPath = storage_path('app/' . $old->path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
                $old->delete();
                $prunedCount++;
            }

            if ($prunedCount > 0) {
                $this->info("Pruned {$prunedCount} old backup(s) older than {$retentionDays} days.");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Failed to create backup: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
