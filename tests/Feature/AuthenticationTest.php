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

    public function test_student_cannot_login_with_email(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
        ]);

        $response = $this->post('/login', [
            'identifier' => $student->email,
            'password' => 'password',
        ]);

        // Student should be rejected when using email
        $this->assertGuest();
        $response->assertSessionHasErrors('identifier');
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
}
