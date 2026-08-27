<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\User;
use App\Models\BackupLog;
use Tests\TestCase;

class BackupRestoreSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $regularAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'must_change_password' => false,
        ]);

        $this->regularAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'department_admin',
            'must_change_password' => false,
        ]);
    }

    public function test_upload_restore_size_limit()
    {
        // Create a 15MB file, which exceeds 10MB limit
        $largeFile = UploadedFile::fake()->create('large.sql', 15360);

        $response = $this->actingAs($this->superAdmin)->post('/admin/backups/upload-restore', [
            'backup_file' => $largeFile
        ]);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_upload_restore_allows_small_files()
    {
        $smallFile = UploadedFile::fake()->create('small.sql', 1024);

        $response = $this->actingAs($this->superAdmin)->post('/admin/backups/upload-restore', [
            'backup_file' => $smallFile
        ]);

        $response->assertSessionDoesntHaveErrors('backup_file');
        $response->assertSessionHas('error'); 
    }

    public function test_backup_operations_require_super_admin_authorization()
    {
        $backup = BackupLog::create([
            'filename' => 'test_backup.sql',
            'path' => 'backups/test_backup.sql',
            'size' => 1024,
        ]);

        // Regular admin should be forbidden (403) from all backup endpoints
        $this->actingAs($this->regularAdmin)->get('/admin/backups')->assertStatus(403);
        $this->actingAs($this->regularAdmin)->post('/admin/backups/create')->assertStatus(403);
        $this->actingAs($this->regularAdmin)->post("/admin/backups/{$backup->id}/restore")->assertStatus(403);
        $this->actingAs($this->regularAdmin)->post('/admin/backups/upload-restore')->assertStatus(403);
        $this->actingAs($this->regularAdmin)->get("/admin/backups/{$backup->id}/download")->assertStatus(403);
        $this->actingAs($this->regularAdmin)->delete("/admin/backups/{$backup->id}")->assertStatus(403);

        // Guests should be redirected to login
        auth()->logout();
        $this->get('/admin/backups')->assertRedirect('/login');
        $this->post('/admin/backups/create')->assertRedirect('/login');
    }

    public function test_sqlite_dump_structure_is_valid_and_does_not_contain_mysql_syntax()
    {
        $exitCode = Artisan::call('app:backup-database');
        $this->assertSame(0, $exitCode);

        $latestBackup = BackupLog::latest()->first();
        $this->assertNotNull($latestBackup);

        $filepath = storage_path('app/' . $latestBackup->path);
        $this->assertFileExists($filepath);

        $content = file_get_contents($filepath);

        if (DB::connection()->getDriverName() === 'sqlite') {
            // Must contain SQLite pragmas and transactions
            $this->assertStringContainsString('PRAGMA foreign_keys = OFF;', $content);
            $this->assertStringContainsString('PRAGMA foreign_keys = ON;', $content);
            $this->assertStringContainsString('BEGIN TRANSACTION;', $content);
            $this->assertStringContainsString('COMMIT;', $content);

            // Must NOT contain MySQL statements
            $this->assertStringNotContainsString('SET FOREIGN_KEY_CHECKS', $content);
            $this->assertStringNotContainsString('SHOW TABLES', $content);
            $this->assertStringNotContainsString('SHOW CREATE TABLE', $content);
        }

        // Clean up generated backup file
        if (File::exists($filepath)) {
            File::delete($filepath);
        }
        $latestBackup->delete();
    }

    public function test_mysql_dump_structure_contains_foreign_key_checks()
    {
        // Verify that in MySQL context, FOREIGN_KEY_CHECKS statements are formatted correctly
        $commandReflection = new \ReflectionClass(\App\Console\Commands\BackupDatabaseCommand::class);
        $commandSource = file_get_contents($commandReflection->getFileName());

        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $commandSource);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=1;', $commandSource);
        $this->assertStringContainsString('@OLD_FOREIGN_KEY_CHECKS', $commandSource);

        $controllerReflection = new \ReflectionClass(\App\Http\Controllers\Admin\BackupController::class);
        $controllerSource = file_get_contents($controllerReflection->getFileName());

        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=0;', $controllerSource);
        $this->assertStringContainsString('SET FOREIGN_KEY_CHECKS=1;', $controllerSource);
        $this->assertStringContainsString('@OLD_FOREIGN_KEY_CHECKS', $controllerSource);
    }

    public function test_restore_failure_cleanup_removes_uploaded_file()
    {
        $corruptedSql = "INVALID SQL SYNTAX THAT WILL DEFINITELY FAIL;";
        $file = UploadedFile::fake()->createWithContent('corrupted.sql', $corruptedSql);

        $response = $this->actingAs($this->superAdmin)->post('/admin/backups/upload-restore', [
            'backup_file' => $file
        ]);

        $response->assertSessionHas('error');

        // Verify that no orphaned file remains in storage/app/backups/
        $files = File::glob(storage_path('app/backups/uploaded_restore_*_corrupted.sql'));
        $this->assertEmpty($files, 'Corrupted upload backup file should be removed on restore failure.');
    }

    public function test_super_admin_can_create_backup_via_controller()
    {
        $response = $this->actingAs($this->superAdmin)->post('/admin/backups/create');
        $response->assertSessionHas('success');

        $latestBackup = BackupLog::latest()->first();
        $this->assertNotNull($latestBackup);

        $filepath = storage_path('app/' . $latestBackup->path);
        if (File::exists($filepath)) {
            File::delete($filepath);
        }
        $latestBackup->delete();
    }
}
