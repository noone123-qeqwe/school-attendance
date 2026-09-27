<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\ExcuseSubmission;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RoleHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_web_routes_require_a_verified_code(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);

        $this->actingAs($admin)->withSession(['admin_2fa_verified' => false])
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.2fa.form'));
        $this->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_api_login_issues_token_only_after_email_code(): void
    {
        Mail::fake();
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'password' => Hash::make('AdminPass123!'),
        ]);

        $credentials = ['identifier' => $admin->email, 'password' => 'AdminPass123!'];
        $this->postJson('/api/login', $credentials)
            ->assertStatus(202)->assertJsonMissingPath('token');

        $code = Otp::where('user_id', $admin->id)->where('purpose', 'admin_api_login')->firstOrFail()->code;
        $this->postJson('/api/login', $credentials + ['otp' => $code])
            ->assertOk()->assertJsonStructure(['token']);
        $this->postJson('/api/login', $credentials + ['otp' => $code])
            ->assertStatus(422)->assertJsonMissingPath('token');
    }

    public function test_null_admin_subrole_has_no_super_admin_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => null]);

        $this->assertFalse($admin->isSuperAdmin());
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.roles.index'))->assertForbidden();
    }

    public function test_legacy_admin_migration_keeps_one_active_super_admin(): void
    {
        $inactive = User::factory()->create([
            'role' => 'admin', 'admin_sub_role' => null, 'is_active' => false,
        ]);
        $active = User::factory()->create([
            'role' => 'admin', 'admin_sub_role' => null, 'is_active' => true,
        ]);

        $migration = require database_path('migrations/2026_09_27_000001_make_legacy_admin_sub_roles_explicit.php');
        $migration->up();

        $this->assertSame('data_entry', $inactive->fresh()->admin_sub_role);
        $this->assertSame('super_admin', $active->fresh()->admin_sub_role);
    }

    public function test_legacy_admin_token_cannot_mutate_versions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $legacyToken = $admin->createToken('legacy')->plainTextToken;

        $this->withToken($legacyToken)->postJson('/api/version/update', [])
            ->assertForbidden();

        $verifiedToken = $admin->createToken('verified', ['admin-2fa-verified'])->plainTextToken;
        app('auth')->forgetGuards();
        $this->withToken($verifiedToken)->postJson('/api/version/update', [])
            ->assertStatus(200);
    }

    public function test_new_parent_has_no_predictable_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => true])
            ->post(route('admin.parents.store'), [
                'name' => 'Parent Example',
                'email' => 'parent.example@gmail.com',
            ])->assertRedirect(route('admin.parents.index'))
            ->assertSessionHas('success', fn ($message) => !str_contains($message, 'Parent@'));

        $parent = User::where('email', 'parent.example@gmail.com')->firstOrFail();
        $this->assertNull($parent->email_verified_at);
        $this->assertFalse(Hash::check('Parent@'.date('Y'), $parent->password));
    }

    public function test_teacher_cannot_review_orphaned_attendance_or_excuse(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $attendance = Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'UNKNOWN',
            'status' => 'Absent',
            'date' => now()->toDateString(),
        ]);
        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'student_id' => $student->id,
            'reason' => 'Please review',
            'status' => 'pending',
        ]);
        $excuse = ExcuseSubmission::create([
            'user_id' => $student->id,
            'attendance_id' => $attendance->id,
            'reason' => 'Sick',
            'description' => 'Absent due to illness',
            'status' => 'pending',
        ]);

        $this->actingAs($teacher)->post(route('teacher.corrections.update', $correction), [
            'action' => 'approve',
        ])->assertForbidden();
        $attendance->delete();
        $this->actingAs($teacher)->post(route('teacher.excuse.approve', $excuse))
            ->assertForbidden();
    }

    public function test_mobile_home_uses_the_signed_in_role(): void
    {
        $teacher = User::factory()->teacher()->create();
        $parent = User::factory()->create(['role' => 'parent']);
        $admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'data_entry']);

        $this->actingAs($teacher)->get(route('mobile.home'))
            ->assertRedirect(route('teacher.dashboard'));
        $this->actingAs($parent)->get(route('mobile.home'))
            ->assertRedirect(route('parent.dashboard'));
        $this->actingAs($admin)->get(route('mobile.home'))
            ->assertRedirect(route('admin.dashboard'));
        $this->get(route('mobile.settings'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
