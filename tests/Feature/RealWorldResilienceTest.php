<?php

namespace Tests\Feature;

use App\Models\BackupLog;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RealWorldResilienceTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test System Health Check Command (Human output & JSON mode)
     */
    public function test_system_health_check_command_runs_successfully()
    {
        $exitCode = Artisan::call('app:health-check');
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('SYSTEM OPERATIONAL HEALTH DIAGNOSTICS', $output);
        $this->assertStringContainsString('Database Subsystem', $output);
        $this->assertStringContainsString('Storage & Permissions', $output);

        // JSON mode
        $jsonExitCode = Artisan::call('app:health-check', ['--json' => true]);
        $this->assertEquals(0, $jsonExitCode);

        $jsonOutput = json_decode(Artisan::output(), true);
        $this->assertIsArray($jsonOutput);
        $this->assertArrayHasKey('status', $jsonOutput);
        $this->assertArrayHasKey('checks', $jsonOutput);
        $this->assertEquals('healthy', $jsonOutput['checks']['database']['status']);
        $this->assertEquals('healthy', $jsonOutput['checks']['cache']['status']);
    }

    /**
     * Test Automated Database Backup Command and Retention Pruning
     */
    public function test_automated_database_backup_creates_snapshot_and_prunes_old()
    {
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // Create a dummy old backup log & file older than 14 days
        $oldFile = 'backup_auto_old_test.sql';
        File::put($backupDir . '/' . $oldFile, '-- Old backup test data');
        $oldLog = new BackupLog([
            'filename' => $oldFile,
            'path'     => 'backups/' . $oldFile,
            'size'     => 100,
        ]);
        $oldLog->timestamps = false;
        $oldLog->created_at = now()->subDays(20);
        $oldLog->updated_at = now()->subDays(20);
        $oldLog->save();

        $exitCode = Artisan::call('app:backup-database', ['--days' => 14]);
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('Backup created successfully', $output);
        $this->assertStringContainsString('Pruned 1 old backup(s)', $output);

        // Verify the old log and file were pruned
        $this->assertDatabaseMissing('backup_logs', ['id' => $oldLog->id]);
        $this->assertFileDoesNotExist($backupDir . '/' . $oldFile);

        // Verify new backup log was recorded
        $latest = BackupLog::latest()->first();
        $this->assertNotNull($latest);
        $this->assertFileExists(storage_path('app/' . $latest->path));

        // Clean up created file
        File::delete(storage_path('app/' . $latest->path));
    }

    /**
     * Test SQLite WAL Checkpoint Command
     */
    public function test_wal_checkpoint_command_executes_successfully()
    {
        $exitCode = Artisan::call('app:wal-checkpoint');
        $this->assertEquals(0, $exitCode);

        $output = Artisan::output();
        $this->assertStringContainsString('WAL checkpoint completed', $output);
    }
}
