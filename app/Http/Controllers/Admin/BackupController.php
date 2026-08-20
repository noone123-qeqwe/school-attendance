<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        $backups = \App\Models\BackupLog::latest()->paginate(10);
        return view('admin.backups.index', compact('backups'));
    }

    public function create()
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        try {
            $filename = 'backup_' . date('Y_m_d_His') . '.sql';
            $path = storage_path('app/backups');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $filepath = $path . '/' . $filename;
            
            $handle = fopen($filepath, 'w');
            if (!$handle) throw new \Exception('Cannot open file for writing');

            \Illuminate\Support\Facades\DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            \Illuminate\Support\Facades\DB::beginTransaction();

            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $tables = \Illuminate\Support\Facades\DB::select('SHOW TABLES');
            
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                
                $createTable = \Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE `$tableName`")[0];
                fwrite($handle, "-- Table structure for `$tableName`\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `$tableName`;\n");
                fwrite($handle, array_values((array)$createTable)[1] . ";\n\n");
                
                $cursor = \Illuminate\Support\Facades\DB::table($tableName)->cursor();
                $hasRows = false;
                
                foreach ($cursor as $row) {
                    if (!$hasRows) {
                        fwrite($handle, "-- Dumping data for table `$tableName`\n\n");
                        $hasRows = true;
                    }
                    $rowArr = (array)$row;
                    $keys = array_map(function($key) { return "`$key`"; }, array_keys($rowArr));
                    $values = array_map(function($val) use ($pdo) {
                        if (is_null($val)) return "NULL";
                        return $pdo->quote($val);
                    }, array_values($rowArr));
                    fwrite($handle, "INSERT INTO `$tableName` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n");
                }
                if ($hasRows) {
                    fwrite($handle, "\n");
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            fclose($handle);

            \App\Models\BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($filepath)
            ]);

            return back()->with('success', 'Backup created successfully!');
        } catch (\Exception $e) {
            if (\Illuminate\Support\Facades\DB::transactionLevel() > 0) {
                \Illuminate\Support\Facades\DB::rollBack();
            }
            if (isset($handle) && is_resource($handle)) {
                fclose($handle);
            }
            return back()->with('error', 'Failed to create backup: ' . $e->getMessage());
        }
    }

    public function download(\App\Models\BackupLog $backup)
    {
        $filepath = storage_path('app/' . $backup->path);
        if (file_exists($filepath)) {
            return response()->download($filepath);
        }
        return back()->with('error', 'Backup file not found.');
    }

    public function destroy(\App\Models\BackupLog $backup)
    {
        abort_if(\Illuminate\Support\Facades\Auth::user()->admin_sub_role !== 'super_admin', 403);
        $filepath = storage_path('app/' . $backup->path);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $backup->delete();
        
        return back()->with('success', 'Backup deleted successfully!');
    }
}
