<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────
    // LOGIN PAGE
    // ─────────────────────────────────────────

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Welcome back');
        $response->assertSee('SIGN IN');
    }

    public function test_register_page_renders(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Create Account');
    }

    // ─────────────────────────────────────────
    // STUDENT LOGIN (via student number)
    // ─────────────────────────────────────────

    public function test_student_can_login_with_student_number(): void
    {
        $student = User::factory()->create([
            'student_number' => '1234567',
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '1234567',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/home');
    }

    public function test_student_cannot_login_with_wrong_password(): void
    {
        User::factory()->create([
            'student_number' => '1234567',
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '1234567',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_student_cannot_login_with_nonexistent_number(): void
    {
        $response = $this->post('/login', [
            'identifier' => '9999999',
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    // ─────────────────────────────────────────
    // TEACHER/ADMIN LOGIN (via email)
    // ─────────────────────────────────────────

    public function test_teacher_can_login_with_email(): void
    {
        $teacher = User::factory()->teacher()->create();

        $response = $this->post('/login', [
            'identifier' => $teacher->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('teacher.dashboard'));
    }

    public function test_admin_can_login_with_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/login', [
            'identifier' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_student_can_login_with_email(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => $student->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/home');
        $this->assertAuthenticatedAs($student);
    }

    // ─────────────────────────────────────────
    // VALIDATION
    // ─────────────────────────────────────────

    public function test_login_requires_identifier(): void
    {
        $response = $this->post('/login', [
            'identifier' => '',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('identifier');
    }

    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'identifier' => '1234567',
            'password' => '',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ─────────────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    // ─────────────────────────────────────────
    // GUEST REDIRECTS
    // ─────────────────────────────────────────

    public function test_guest_is_redirected_from_home(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_is_redirected_from_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $response->assertRedirect('/home');
    }

    // ─────────────────────────────────────────
    // STUDENT 0703250 SCENARIO TESTS
    // ─────────────────────────────────────────

    public function test_student_0703250_can_login_with_student123(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'email' => 'ibnkervijamatos44@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '0703250',
            'password' => 'student123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
        $this->assertEquals('student', session('user_role'));
    }

    public function test_student_0703250_can_login_with_student_id_field_name(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'email' => 'ibnkervijamatos44@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'student_id' => '0703250',
            'password' => 'student123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
    }

    public function test_student_0703250_can_login_without_leading_zero(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '703250',
            'password' => 'student123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
    }

    public function test_student_0703250_can_login_with_formatted_dash(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '070-3250',
            'password' => 'student123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
    }

    public function test_student_0703250_can_login_with_email(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'email' => 'ibnkervijamatos44@gmail.com',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'ibnkervijamatos44@gmail.com',
            'password' => 'student123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
    }

    public function test_student_0703250_cannot_login_with_incorrect_password(): void
    {
        User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '0703250',
            'password' => 'wrongpassword',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_student_0703250_cannot_login_with_incorrect_id(): void
    {
        User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '0703999',
            'password' => 'student123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
    }

    public function test_api_login_with_0703250_and_student123(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/login', [
            'student_id' => '0703250',
            'password' => 'student123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'success' => true,
            'role' => 'student',
        ]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_api_login_with_incorrect_password_is_rejected(): void
    {
        User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/login', [
            'identifier' => '0703250',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'success' => false,
            'message' => 'Incorrect ID/email or password.',
        ]);
    }

    public function test_api_login_with_incorrect_id_is_rejected(): void
    {
        $response = $this->postJson('/api/login', [
            'identifier' => '0703999',
            'password' => 'student123',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'success' => false,
            'message' => 'Incorrect ID/email or password.',
        ]);
    }

    public function test_login_with_accidental_spaces_in_credentials(): void
    {
        $student = User::factory()->create([
            'student_number' => '0703250',
            'password' => \Illuminate\Support\Facades\Hash::make('student123'),
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => '   0703250   ',
            'password' => '  student123  ',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/home');
    }

    public function test_parent_can_login_with_email(): void
    {
        $parent = User::factory()->create([
            'email' => 'parent.test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('parent123'),
            'role' => 'parent',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'parent.test@example.com',
            'password' => 'parent123',
        ]);

        $this->assertAuthenticatedAs($parent);
        $response->assertRedirect(route('parent.dashboard'));
    }

    public function test_teacher_can_login_with_employee_id(): void
    {
        $teacher = User::factory()->create([
            'employee_id' => 'T-2024-001',
            'password' => \Illuminate\Support\Facades\Hash::make('teacher123'),
            'role' => 'teacher',
        ]);

        $response = $this->post('/login', [
            'identifier' => 'T-2024-001',
            'password' => 'teacher123',
        ]);

        $this->assertAuthenticatedAs($teacher);
        $response->assertRedirect(route('teacher.dashboard'));
    }
}
