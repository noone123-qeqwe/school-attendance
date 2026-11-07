<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Models\BackupLog;
use App\Services\PostgresBackupService;

class BackupController extends Controller
{
    public function index()
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);
        return redirect()->route('admin.system-update.index', ['tab' => 'backups']);
    }

    public function create()
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);

        if (DB::connection()->getDriverName() === 'pgsql') {
            return $this->createPostgresBackup();
        }

        $handle = null;
        $inTransaction = false;
        $filepath = null;

        try {
            $filename = 'backup_' . date('Y_m_d_His') . '_' . bin2hex(random_bytes(8)) . '.sql';
            $path = storage_path('app/backups');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $filepath = $path . '/' . $filename;
            
            $handle = fopen($filepath, 'w');
            if (!$handle) throw new \Exception('Cannot open file for writing');

            $driver = DB::connection()->getDriverName();
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
            } elseif ($driver === 'pgsql') {
                fwrite($handle, "-- PostgreSQL Database Dump\nSET CONSTRAINTS ALL DEFERRED;\n\n");
                $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
                foreach ($tables as $table) {
                    $tableName = $table->table_name;
                    fwrite($handle, "-- Dumping data for table \"{$tableName}\"\n\n");
                    $cursor = DB::table($tableName)->cursor();
                    $hasRows = false;
                    foreach ($cursor as $row) {
                        if (!$hasRows) {
                            $hasRows = true;
                        }
                        $rowArr = (array)$row;
                        $keys = array_map(fn($k) => "\"{$k}\"", array_keys($rowArr));
                        $values = array_map(function($v) use ($pdo) {
                            return is_null($v) ? "NULL" : $pdo->quote((string)$v);
                        }, array_values($rowArr));
                        fwrite($handle, "INSERT INTO \"{$tableName}\" (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                    }
                    if ($hasRows) fwrite($handle, "\n");
                }
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
            fclose($handle);
            $handle = null;

            $backup = BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($filepath)
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully!',
                    'backup' => $backup
                ]);
            }

            return back()->with('success', 'Backup created successfully!');
        } catch (\Throwable $e) {
            if ($inTransaction && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            Log::warning('Database backup failed.', [
                'admin_id' => Auth::id(),
                'exception' => get_class($e),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup creation failed.'
                ], 500);
            }
            return back()->with('error', 'Backup creation failed.');
        } finally {
            if ($handle && is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function createPostgresBackup()
    {
        $filepath = null;

        try {
            $filename = 'backup_' . date('Y_m_d_His') . '_' . bin2hex(random_bytes(8)) . '.sql';
            $path = storage_path('app/backups');
            File::ensureDirectoryExists($path, 0755, true);
            $filepath = $path . '/' . $filename;

            app(PostgresBackupService::class)->dumpTo($filepath);
            if (!is_file($filepath) || filesize($filepath) === 0) {
                throw new \RuntimeException('PostgreSQL backup did not produce a usable file.');
            }

            $backup = BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($filepath),
            ]);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully!',
                    'backup' => $backup,
                ]);
            }

            return back()->with('success', 'Backup created successfully!');
        } catch (\Throwable $exception) {
            if ($filepath && file_exists($filepath)) {
                @unlink($filepath);
            }

            Log::warning('PostgreSQL backup failed.', [
                'admin_id' => Auth::id(),
                'exception' => get_class($exception),
            ]);

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Backup creation failed.'], 500);
            }

            return back()->with('error', 'Backup creation failed.');
        }
    }

    public function restore(BackupLog $backup)
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);

        $safeFilename = basename($backup->filename);
        $filepath = storage_path('app/backups/' . $safeFilename);

        if (!file_exists($filepath)) {
            return back()->with('error', 'Backup file not found on disk.');
        }

        try {
            $this->executeSqlFile($filepath);
            return back()->with('success', "Database successfully restored from {$safeFilename}!");
        } catch (\Throwable $e) {
            Log::warning('Database restore failed.', [
                'admin_id' => Auth::id(),
                'backup_id' => $backup->id,
                'exception' => get_class($e),
            ]);
            return back()->with('error', 'Database restore failed.');
        }
    }

    public function uploadRestore(Request $request)
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);

        $request->validate([
            'backup_file' => 'required|file|max:10240'
        ]);

        $file = $request->file('backup_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['sql', 'txt'])) {
            return back()->with('error', 'Please upload a valid .sql backup file.');
        }

        // Never use a client supplied filename as part of a filesystem path.
        $filename = 'uploaded_restore_' . date('Y_m_d_His') . '_' . bin2hex(random_bytes(16)) . '.sql';
        $path = storage_path('app/backups');
        $targetPath = $path . '/' . $filename;

        try {
            File::ensureDirectoryExists($path, 0755, true);
            $file->move($path, $filename);
            $this->executeSqlFile($targetPath);

            BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($targetPath)
            ]);

            return back()->with('success', 'Database successfully restored from uploaded file!');
        } catch (\Throwable $e) {
            if (file_exists($targetPath)) {
                @unlink($targetPath);
            }
            Log::warning('Uploaded database restore failed.', [
                'admin_id' => Auth::id(),
                'exception' => get_class($e),
            ]);
            return back()->with('error', 'Restore failed. The uploaded backup could not be restored.');
        }
    }

    private function executeSqlFile(string $filepath): void
    {
        if (!file_exists($filepath) || filesize($filepath) === 0) {
            throw new \Exception('Backup file is empty or does not exist.');
        }

        $maxRestoreSizeMb = max(1, (int) config('services.backup.max_restore_size_mb', 512));
        if (filesize($filepath) > $maxRestoreSizeMb * 1024 * 1024) {
            throw new \RuntimeException('Backup file exceeds the configured restore size limit.');
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            app(PostgresBackupService::class)->restoreFrom($filepath);
            return;
        }

        $pdo = DB::connection()->getPdo();

        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        $handle = fopen($filepath, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open backup file for reading: {$filepath}");
        }

        $inTransaction = false;

        try {
            if ($driver === 'sqlite' && !$pdo->inTransaction()) {
                $pdo->beginTransaction();
                $inTransaction = true;
            }

            $query = '';
            while (($line = fgets($handle)) !== false) {
                $trimmed = trim($line);

                // Skip comments and empty lines (unless conditional MySQL comment)
                if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*')) {
                    if (!str_starts_with($trimmed, '/*!')) {
                        continue;
                    }
                }

                // If on SQLite, skip any MySQL-specific directives or duplicate transaction statements
                if ($driver === 'sqlite') {
                    if (stripos($trimmed, 'SET FOREIGN_KEY_CHECKS') !== false ||
                        stripos($trimmed, 'SET @OLD_') !== false ||
                        stripos($trimmed, 'BEGIN TRANSACTION') !== false ||
                        stripos($trimmed, 'COMMIT') !== false) {
                        continue;
                    }
                }

                $query .= $line;

                // Check if query is complete (ends with semicolon)
                if (preg_match('/;\s*$/', $trimmed)) {
                    $stmt = trim($query);
                    if ($stmt !== '') {
                        $pdo->exec($stmt);
                    }
                    $query = '';
                }
            }

            $stmt = trim($query);
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }

            if ($inTransaction && $pdo->inTransaction()) {
                $pdo->commit();
                $inTransaction = false;
            }
        } catch (\Throwable $e) {
            if ($inTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
            try {
                if ($driver === 'mysql') {
                    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                } elseif ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = ON;');
                }
            } catch (\Throwable $ignored) {}
        }
    }

    public function download(BackupLog $backup)
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);

        $safeFilename = basename($backup->filename);
        $filepath = storage_path('app/backups/' . $safeFilename);

        if (file_exists($filepath)) {
            return response()->download($filepath, $safeFilename);
        }
        return back()->with('error', 'Backup file not found.');
    }

    public function destroy(BackupLog $backup)
    {
        abort_if(!Auth::user()->isSuperAdmin(), 403);
        $filepath = storage_path('app/' . $backup->path);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $backup->delete();
        
        return back()->with('success', 'Backup deleted successfully!');
    }
}
