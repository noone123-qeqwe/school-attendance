<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\BackupLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemUpdateAndBackupTest extends TestCase
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
            'email' => 'superadmin@school.test',
            'must_change_password' => false,
        ]);

        $this->regularAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'department_admin',
            'email' => 'deptadmin@school.test',
            'must_change_password' => false,
        ]);
    }

    public function test_super_admin_can_access_system_update_center(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.system-update.index'));
        $response->assertStatus(200);
        $response->assertSee('System Update');
        $response->assertSee('Run 1-Click System Update');
    }

    public function test_regular_admin_cannot_access_system_update_center(): void
    {
        $response = $this->actingAs($this->regularAdmin)->get(route('admin.system-update.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_execute_full_system_update(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.run'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'message',
            'results' => [
                '*' => ['step', 'status', 'message']
            ]
        ]);
    }

    public function test_super_admin_can_clear_caches(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.cache-clear'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
    }

    public function test_super_admin_can_bump_pwa_version(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.pwa-bump'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'version',
            'pushed_count',
            'message'
        ]);
    }

    public function test_super_admin_can_check_migration_status(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.migrate-status'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'pending_count',
            'pending_migrations',
            'applied_count',
            'is_up_to_date'
        ]);
    }

    public function test_super_admin_can_run_health_check(): void
    {
        $response = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.health-check'));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);
        $response->assertJsonStructure([
            'success',
            'score',
            'checks' => [
                '*' => ['name', 'status', 'message']
            ],
            'timestamp'
        ]);
    }

    public function test_super_admin_can_create_and_restore_backup(): void
    {
        $createRes = $this->actingAs($this->superAdmin)->post(route('admin.backups.create'));
        $createRes->assertRedirect();

        $backup = BackupLog::latest()->first();
        $this->assertNotNull($backup);

        $restoreRes = $this->actingAs($this->superAdmin)->post(route('admin.backups.restore', $backup));
        $restoreRes->assertRedirect();
        if (session('error')) {
            $this->fail('Restore failed with session error: ' . session('error'));
        }
        $restoreRes->assertSessionHas('success');
    }

    public function test_system_update_creates_in_app_notification_and_poll_returns_it(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'email' => 'student_test@school.test',
        ]);

        // Super admin runs bump PWA version / system update
        $bumpRes = $this->actingAs($this->superAdmin)->postJson(route('admin.system-update.pwa-bump'));
        $bumpRes->assertStatus(200);

        // Verify notification was created in DB for student
        $notif = \App\Models\Notification::where('user_id', $student->id)->where('type', 'system_update')->latest()->first();
        $this->assertNotNull($notif);
        $this->assertFalse($notif->is_read);

        // Verify poll endpoint returns the notification for student
        $pollRes = $this->actingAs($student)->getJson(route('notifications.poll'));
        $pollRes->assertStatus(200);
        $pollRes->assertJsonStructure([
            'unread_count',
            'notifications' => [
                '*' => ['id', 'type', 'message', 'is_read', 'created_at_human', 'is_today']
            ]
        ]);
        $this->assertGreaterThanOrEqual(1, $pollRes->json('unread_count'));
        $this->assertEquals('system_update', $pollRes->json('notifications.0.type'));
    }
}
