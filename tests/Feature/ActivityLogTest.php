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

    public function test_audit_logs_can_be_filtered_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Super Admin Person', 'admin_sub_role' => 'super_admin']);
        $student = User::factory()->create(['role' => 'student', 'name' => 'John Student User']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Jane Teacher Prof']);
        $parent = User::factory()->create(['role' => 'parent', 'name' => 'Bob Parent Guardian']);

        activity()->causedBy($admin)->log('Admin performed system update');
        activity()->causedBy($student)->log('Student clocked in');
        activity()->causedBy($teacher)->log('Teacher started QR session');
        activity()->causedBy($parent)->log('Parent viewed child attendance');

        // Filter by student
        $resStudent = $this->actingAs($admin)->get(route('admin.activity.log', ['role' => 'student']));
        $resStudent->assertStatus(200);
        $resStudent->assertSee('Student clocked in');
        $resStudent->assertDontSee('Teacher started QR session');

        // Filter by teacher
        $resTeacher = $this->actingAs($admin)->get(route('admin.activity.log', ['role' => 'teacher']));
        $resTeacher->assertStatus(200);
        $resTeacher->assertSee('Teacher started QR session');
        $resTeacher->assertDontSee('Student clocked in');

        // Filter by parent
        $resParent = $this->actingAs($admin)->get(route('admin.activity.log', ['role' => 'parent']));
        $resParent->assertStatus(200);
        $resParent->assertSee('Parent viewed child attendance');
        $resParent->assertDontSee('Student clocked in');

        // Filter by admin
        $resAdmin = $this->actingAs($admin)->get(route('admin.activity.log', ['role' => 'admin']));
        $resAdmin->assertStatus(200);
        $resAdmin->assertSee('Admin performed system update');
        $resAdmin->assertDontSee('Parent viewed child attendance');
    }
}

