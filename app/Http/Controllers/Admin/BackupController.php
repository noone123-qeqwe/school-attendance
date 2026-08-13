<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index()
    {
        $backups = \App\Models\BackupLog::latest()->paginate(10);
        return view('admin.backups.index', compact('backups'));
    }

    public function create()
    {
        try {
            $filename = 'backup_' . date('Y_m_d_His') . '.sql';
            $path = storage_path('app/backups');
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
            }
            $filepath = $path . '/' . $filename;

            $tables = \DB::select('SHOW TABLES');
            $sql = "";
            foreach ($tables as $table) {
                $tableName = array_values((array)$table)[0];
                
                $createTable = \DB::select("SHOW CREATE TABLE `$tableName`")[0];
                $sql .= "-- Table structure for `$tableName`\n\n";
                $sql .= "DROP TABLE IF EXISTS `$tableName`;\n";
                $sql .= array_values((array)$createTable)[1] . ";\n\n";
                
                $rows = \DB::table($tableName)->get();
                if ($rows->count() > 0) {
                    $sql .= "-- Dumping data for table `$tableName`\n\n";
                    foreach ($rows as $row) {
                        $rowArr = (array)$row;
                        $keys = array_map(function($key) { return "`$key`"; }, array_keys($rowArr));
                        $values = array_map(function($val) {
                            if (is_null($val)) return "NULL";
                            return "'" . addslashes($val) . "'";
                        }, array_values($rowArr));
                        $sql .= "INSERT INTO `$tableName` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }

            file_put_contents($filepath, $sql);

            \App\Models\BackupLog::create([
                'filename' => $filename,
                'path' => 'backups/' . $filename,
                'size' => filesize($filepath)
            ]);

            return back()->with('success', 'Backup created successfully!');
        } catch (\Exception $e) {
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
        $filepath = storage_path('app/' . $backup->path);
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $backup->delete();
        
        return back()->with('success', 'Backup deleted successfully!');
    }
}
