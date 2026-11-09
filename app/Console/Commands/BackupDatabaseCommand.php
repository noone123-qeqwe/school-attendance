<?php

namespace App\Console\Commands;

use App\Models\BackupLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\PostgresBackupService;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-database {--days=14 : Days of backup history to retain} {--prune : Delete backups older than the retention period} {--prefix=backup_auto : Safe filename prefix for this backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database snapshot and optionally prune old backups';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting automated database backup snapshot...');

        $handle = null;
        $inTransaction = false;
        $filepath = null;

        try {
            $prefix = trim((string) $this->option('prefix'));
            if ($prefix === '' || !preg_match('/\A[a-zA-Z0-9_]+\z/', $prefix)) {
                throw new \InvalidArgumentException('Backup filename prefix is invalid.');
            }

            $filename = $prefix . '_' . date('Y_m_d_His') . '_' . bin2hex(random_bytes(8)) . '.sql';
            $path = storage_path('app/backups');

            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }

            $filepath = $path . '/' . $filename;
            $handle = fopen($filepath, 'w');

            if (!$handle) {
                $this->error('Cannot open the database backup file for writing.');
                return self::FAILURE;
            }

            $driver = DB::connection()->getDriverName();
            if (!in_array($driver, ['pgsql', 'mysql', 'sqlite'], true)) {
                throw new \RuntimeException('Database backups are not supported for this driver.');
            }

            if ($driver === 'pgsql') {
                fclose($handle);
                $handle = null;
                app(PostgresBackupService::class)->dumpTo($filepath);
            } else {
                if ($driver === 'mysql') {
                    DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
                }
                DB::beginTransaction();
                $inTransaction = true;

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
                            $rowArr = (array) $row;
                            $keys = array_map(fn ($key) => "`{$key}`", array_keys($rowArr));
                            $values = array_map(
                                fn ($value) => is_null($value) ? 'NULL' : $pdo->quote($value),
                                array_values($rowArr)
                            );
                            fwrite($handle, "INSERT INTO `{$tableName}` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                        }
                        if ($hasRows) {
                            fwrite($handle, "\n");
                        }
                    }
                    fwrite($handle, "COMMIT;\nPRAGMA foreign_keys = ON;\n");
                } else {
                    fwrite($handle, "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
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
                    fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;\n");
                }

                DB::commit();
                $inTransaction = false;
            }

            if (is_resource($handle)) {
                fclose($handle);
                $handle = null;
            }

            $size = filesize($filepath);
            $backup = BackupLog::create([
                'filename' => $filename,
                'path'     => 'backups/' . $filename,
                'size'     => $size,
            ]);

            $this->info("Backup created successfully: {$filename} (" . round($size / 1024 / 1024, 2) . " MB)");

            if ($this->option('prune')) {
                $retentionDays = max(1, (int) $this->option('days'));
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
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if ($inTransaction && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }
            Log::warning('Automated database backup failed.', [
                'exception' => get_class($e),
                'connection' => config('database.default'),
            ]);
            $this->error('Failed to create database backup.');
            return self::FAILURE;
        } finally {
            if ($handle && is_resource($handle)) {
                fclose($handle);
            }
        }
    }
}
