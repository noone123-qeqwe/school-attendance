<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_audit_logs_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin'
        ]);

        // Create sample activity log
        activity()
            ->causedBy($admin)
            ->withProperties(['ip' => '192.168.1.100', 'attributes' => ['status' => 'present'], 'old' => ['status' => 'absent']])
            ->log('updated');

        $response = $this->actingAs($admin)->get(route('admin.activity.log'));

        $response->assertStatus(200);
        $response->assertSee('Enterprise Audit Logs', false);
        $response->assertSee('Total Audit Events', false);
        $response->assertSee('Authentication & Access', false);
        $response->assertSee('Inspect Diff', false);
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin'
        ]);

        activity()->causedBy($admin)->log('login');
        activity()->causedBy($admin)->log('created');

        $response = $this->actingAs($admin)->get(route('admin.activity.log', ['action' => 'login']));

        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_data_entry_admin_is_forbidden_from_audit_logs(): void
    {
        $dataEntryAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'data_entry'
        ]);

        $response = $this->actingAs($dataEntryAdmin)->get(route('admin.activity.log'));

        $response->assertStatus(403);
    }
}
