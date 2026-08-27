<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Tests\TestCase;

class BackupRestoreSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_restore_size_limit()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin'
        ]);

        Storage::fake('local');

        // Create a 15MB file, which exceeds 10MB limit
        $largeFile = UploadedFile::fake()->create('large.sql', 15360);

        $response = $this->actingAs($admin)->post('/admin/backups/upload-restore', [
            'backup_file' => $largeFile
        ]);

        $response->assertSessionHasErrors('backup_file');
    }

    public function test_upload_restore_allows_small_files()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin'
        ]);

        Storage::fake('local');

        $smallFile = UploadedFile::fake()->create('small.sql', 1024);

        $response = $this->actingAs($admin)->post('/admin/backups/upload-restore', [
            'backup_file' => $smallFile
        ]);

        $response->assertSessionDoesntHaveErrors('backup_file');
        // Since the SQL file is just dummy data, it will likely throw a SQL exception, 
        // which we catch and redirect with an error message
        $response->assertSessionHas('error'); 
    }
}
