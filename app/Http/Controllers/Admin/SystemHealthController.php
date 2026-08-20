<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Mail;

class SystemHealthController extends Controller
{
    public function index()
    {
        // 1. Database Connection Check
        $dbStatus = 'Online';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Offline';
        }

        // 2. Queue & Jobs Status
        $failedJobs = DB::table('failed_jobs')->count();
        $queueSize = Queue::size('default');

        // 3. Simple mail config check
        $mailConfigured = !empty(config('mail.mailers.smtp.host')) && config('mail.mailers.smtp.host') !== '127.0.0.1';

        // 4. Backups count
        // For now just simulate or read a backups directory
        $backupCount = 0;
        if (\Illuminate\Support\Facades\Storage::disk('local')->exists('backups')) {
            $backupCount = count(\Illuminate\Support\Facades\Storage::disk('local')->files('backups'));
        }

        return view('admin.system_health.index', compact('dbStatus', 'failedJobs', 'queueSize', 'mailConfigured', 'backupCount'));
    }
}
