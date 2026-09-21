<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Otp;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentParentBindingTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $parent;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Student
        $this->student = User::create([
            'name'           => 'Juan Dela Cruz',
            'email'          => 'juan.delacruz@school.edu',
            'student_number' => '20260099',
            'role'           => 'student',
            'course'         => 'BSCS',
            'year_level'     => 2,
            'semester'       => 1,
            'password'       => Hash::make('password123'),
            'is_active'      => true,
        ]);

        // Parent
        $this->parent = User::create([
            'name'      => 'Maria Dela Cruz',
            'email'     => 'maria.delacruz@gmail.com',
            'phone'     => '09171234567',
            'role'      => 'parent',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        // Admin
        $this->admin = User::create([
            'name'           => 'Admin User',
            'email'          => 'admin@school.edu',
            'employee_id'    => 'A-001',
            'role'           => 'admin',
            'admin_sub_role' => 'super_admin',
            'password'       => Hash::make('password123'),
            'is_active'      => true,
        ]);
    }

    public function test_student_can_generate_parent_link_code(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson(route('student.parent_link.generate_code'));

        $response->assertStatus(200)
            ->assertJson([
                'success'        => true,
                'student_name'   => 'Juan Dela Cruz',
                'student_number' => '20260099',
            ])
            ->assertJsonStructure([
                'code',
                'expires_at',
                'expires_in_seconds',
            ]);

        $code = $response->json('code');
        $this->assertDatabaseHas('otps', [
            'user_id' => $this->student->id,
            'code'    => $code,
            'purpose' => 'student_parent_invite',
            'used'    => false,
        ]);
    }

    public function test_parent_can_link_using_valid_student_code(): void
    {
        // 1. Generate code for student
        $codeResponse = $this->actingAs($this->student)
            ->postJson(route('student.parent_link.generate_code'));
        $code = $codeResponse->json('code');

        // 2. Parent links using the code
        $linkResponse = $this->actingAs($this->parent)
            ->postJson(route('parent.link.code'), ['code' => $code]);

        $linkResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'student' => [
                    'id'             => $this->student->id,
                    'name'           => 'Juan Dela Cruz',
                    'student_number' => '20260099',
                ],
            ]);

        // Assert pivot table record
        $this->assertDatabaseHas('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);

        // Assert student's guardian_email is synchronized
        $this->assertEquals($this->parent->email, $this->student->fresh()->guardian_email);

        // Assert OTP is marked as used
        $this->assertDatabaseHas('otps', [
            'code'    => $code,
            'purpose' => 'student_parent_invite',
            'used'    => true,
        ]);

        // Assert notification created for student
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->student->id,
            'type'    => 'parent_linked',
        ]);
    }

    public function test_parent_cannot_link_with_invalid_or_expired_code(): void
    {
        // Test non-existent code
        $response = $this->actingAs($this->parent)
            ->postJson(route('parent.link.code'), ['code' => '999999']);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Test expired code
        $expiredOtp = Otp::create([
            'user_id'    => $this->student->id,
            'email'      => $this->student->email,
            'code'       => '112233',
            'purpose'    => 'student_parent_invite',
            'used'       => false,
            'expires_at' => now()->subMinutes(1),
        ]);

        $expiredResponse = $this->actingAs($this->parent)
            ->postJson(route('parent.link.code'), ['code' => '112233']);

        $expiredResponse->assertStatus(422);

        $this->assertDatabaseMissing('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_cannot_link_same_student_twice(): void
    {
        // Bind parent and student
        DB::table('parent_student')->insert([
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Try linking with code
        $codeResponse = $this->actingAs($this->student)
            ->postJson(route('student.parent_link.generate_code'));
        $code = $codeResponse->json('code');

        $linkResponse = $this->actingAs($this->parent)
            ->postJson(route('parent.link.code'), ['code' => $code]);

        $linkResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        // Try linking with OTP initiate
        $otpResponse = $this->actingAs($this->parent)
            ->postJson(route('parent.link.send-otp'), ['student_number' => $this->student->student_number]);

        $otpResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_parent_can_unlink_child(): void
    {
        // Bind parent and student
        DB::table('parent_student')->insert([
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->student->update(['guardian_email' => $this->parent->email]);

        $response = $this->actingAs($this->parent)
            ->postJson(route('parent.link.unlink'), ['student_id' => $this->student->id]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);

        // Student guardian email cleared when sole parent unlinks
        $this->assertNull($this->student->fresh()->guardian_email);

        // Student notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->student->id,
            'type'    => 'parent_unlinked',
        ]);
    }

    public function test_student_can_unlink_parent(): void
    {
        // Bind parent and student
        DB::table('parent_student')->insert([
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->postJson(route('student.parent_link.unlink'), ['parent_id' => $this->parent->id]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);

        // Parent notified
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->parent->id,
            'type'    => 'student_unlinked',
        ]);
    }

    public function test_admin_can_link_and_unlink_parent_directly(): void
    {
        // 1. Admin links parent
        $linkResponse = $this->actingAs($this->admin)
            ->post(route('admin.student.link_parent', $this->student), [
                'parent_id' => $this->parent->id,
            ]);

        $linkResponse->assertSessionHas('success');

        $this->assertDatabaseHas('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);

        $this->assertEquals($this->parent->email, $this->student->fresh()->guardian_email);

        // 2. Admin unlinks parent
        $unlinkResponse = $this->actingAs($this->admin)
            ->delete(route('admin.student.unlink_parent', [$this->student, $this->parent]));

        $unlinkResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('parent_student', [
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
        ]);
    }

    public function test_parent_link_child_page_renders_with_linked_children(): void
    {
        DB::table('parent_student')->insert([
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->parent)
            ->get(route('parent.link.form'));

        $response->assertStatus(200)
            ->assertSee('Juan Dela Cruz')
            ->assertSee('20260099')
            ->assertSee('Instant Link Code')
            ->assertSee('Student ID &amp; Email OTP', false);
    }

    public function test_student_settings_page_renders_family_tab(): void
    {
        DB::table('parent_student')->insert([
            'parent_id'  => $this->parent->id,
            'student_id' => $this->student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->student)
            ->get(route('settings'));

        $response->assertStatus(200)
            ->assertSee('Family / Guardian')
            ->assertSee('Instant Link Code')
            ->assertSee('Maria Dela Cruz');
    }
}
