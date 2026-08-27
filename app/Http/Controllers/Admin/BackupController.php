<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\BackupLog;

class BackupController extends Controller
{
    public function index()
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        return redirect()->route('admin.system-update.index', ['tab' => 'backups']);
    }

    public function create()
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            $filename = 'backup_' . date('Y_m_d_His') . '.sql';
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
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n\n");
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
                fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            }

            DB::commit();
            fclose($handle);

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
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create backup: ' . $e->getMessage()
                ], 500);
            }

            return back()->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    public function restore(BackupLog $backup)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $safeFilename = basename($backup->filename);
        $filepath = storage_path('app/backups/' . $safeFilename);

        if (!file_exists($filepath)) {
            return back()->with('error', 'Backup file not found on disk.');
        }

        try {
            $this->executeSqlFile($filepath);
            return back()->with('success', "Database successfully restored from {$safeFilename}!");
        } catch (\Exception $e) {
            return back()->with('error', 'Database restore failed: ' . $e->getMessage());
        }
    }

    public function uploadRestore(Request $request)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $request->validate([
            'backup_file' => 'required|file|max:10240'
        ]);

        $file = $request->file('backup_file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['sql', 'txt'])) {
            return back()->with('error', 'Please upload a valid .sql backup file.');
        }

        $filename = 'uploaded_restore_' . date('Y_m_d_His') . '_' . $file->getClientOriginalName();
        $path = storage_path('app/backups');
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $targetPath = $path . '/' . $filename;
        $file->move($path, $filename);

        try {
            $this->executeSqlFile($targetPath);

            BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($targetPath)
            ]);

            return back()->with('success', 'Database successfully restored from uploaded file!');
        } catch (\Exception $e) {
            return back()->with('error', 'Restore failed: ' . $e->getMessage());
        }
    }

    private function executeSqlFile(string $filepath): void
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        } elseif ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
        }

        $sql = file_get_contents($filepath);
        if (empty($sql)) {
            throw new \Exception('Backup file is empty.');
        }

        if ($driver === 'sqlite') {
            if (DB::transactionLevel() > 0) {
                $sql = preg_replace('/^\s*BEGIN\s+TRANSACTION\s*;/mi', '', $sql);
                $sql = preg_replace('/^\s*COMMIT\s*;/mi', '', $sql);
            } else {
                if (stripos($sql, 'BEGIN TRANSACTION') === false) {
                    $sql = "BEGIN TRANSACTION;\n" . $sql . "\nCOMMIT;";
                }
            }
        }

        try {
            DB::unprepared($sql);
        } finally {
            if ($driver === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } elseif ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        }
    }

    public function download(BackupLog $backup)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);

        $safeFilename = basename($backup->filename);
        $filepath = storage_path('app/backups/' . $safeFilename);

        if (file_exists($filepath)) {
            return response()->download($filepath, $safeFilename);
        }
        return back()->with('error', 'Backup file not found.');
    }

    public function destroy(BackupLog $backup)
    {
        abort_if(Auth::user()->admin_sub_role !== 'super_admin', 403);
        $filepath = storage_path('app/' . $backup->path);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $backup->delete();
        
        return back()->with('success', 'Backup deleted successfully!');
    }
}
