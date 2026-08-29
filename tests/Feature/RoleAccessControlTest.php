<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create(['role' => 'student']);
        $this->teacher = User::factory()->teacher()->create();
        $this->admin = User::factory()->admin()->create();
    }

    // ─────────────────────────────────────────
    // PUBLIC ROUTES (accessible to all)
    // ─────────────────────────────────────────

    public function test_intro_page_is_public(): void
    {
        $this->get('/')->assertStatus(200);
    }

    public function test_qr_scan_page_is_public(): void
    {
        // QR scan with a fake token — should not 500
        $response = $this->get('/qr/scan/fake-token');

        $this->assertTrue(in_array($response->status(), [200, 302, 403, 404]));
    }

    // ─────────────────────────────────────────
    // STUDENT-ONLY ROUTES
    // ─────────────────────────────────────────

    public function test_student_can_access_home(): void
    {
        $this->actingAs($this->student)->get('/home')->assertStatus(200);
    }

    public function test_student_can_access_settings(): void
    {
        $this->actingAs($this->student)->get('/settings')->assertStatus(200);
    }

    public function test_student_can_access_notifications(): void
    {
        $this->actingAs($this->student)->get('/notifications')->assertStatus(200);
    }

    public function test_student_can_access_my_classes(): void
    {
        $this->actingAs($this->student)->get('/my-classes')->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // TEACHER ROUTES — CROSS-ROLE CHECKS
    // ─────────────────────────────────────────

    public function test_teacher_can_access_teacher_routes(): void
    {
        $this->actingAs($this->teacher)->get('/teacher/dashboard')->assertStatus(200);
        $this->actingAs($this->teacher)->get('/teacher/subjects')->assertRedirect(route('teacher.classroom.index'));
        $this->actingAs($this->teacher)->get('/teacher/attendance')->assertStatus(200);
    }

    public function test_student_blocked_from_teacher_routes(): void
    {
        $routes = ['/teacher/dashboard', '/teacher/subjects', '/teacher/attendance'];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->student)->get($route);
            $this->assertTrue(
                in_array($response->status(), [302, 403]),
                "Student should not access {$route}, got status {$response->status()}"
            );
        }
    }

    public function test_admin_blocked_from_teacher_routes(): void
    {
        $routes = ['/teacher/dashboard', '/teacher/subjects', '/teacher/attendance'];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route);
            $this->assertTrue(
                in_array($response->status(), [302, 403]),
                "Admin should not access {$route}, got status {$response->status()}"
            );
        }
    }

    // ─────────────────────────────────────────
    // ADMIN ROUTES — CROSS-ROLE CHECKS
    // ─────────────────────────────────────────

    public function test_admin_can_access_admin_routes(): void
    {
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/students')->assertStatus(200);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/subjects')->assertStatus(200);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/attendance')->assertStatus(200);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/notifications')->assertStatus(200);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true])->get('/admin/calendar')->assertStatus(200);
    }

    public function test_student_blocked_from_admin_routes(): void
    {
        $routes = ['/admin/dashboard', '/admin/students', '/admin/subjects', '/admin/attendance'];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->student)->get($route);
            $this->assertTrue(
                in_array($response->status(), [302, 403]),
                "Student should not access {$route}, got status {$response->status()}"
            );
        }
    }

    public function test_teacher_blocked_from_admin_routes(): void
    {
        $routes = ['/admin/dashboard', '/admin/students', '/admin/subjects', '/admin/attendance'];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->teacher)->get($route);
            $this->assertTrue(
                in_array($response->status(), [302, 403]),
                "Teacher should not access {$route}, got status {$response->status()}"
            );
        }
    }

    // ─────────────────────────────────────────
    // GUEST BLOCKED FROM ALL PROTECTED ROUTES
    // ─────────────────────────────────────────

    public function test_guest_redirected_from_all_protected_routes(): void
    {
        $routes = ['/home', '/my-classes', '/settings', '/notifications',
                   '/teacher/dashboard', '/admin/dashboard'];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $this->assertTrue(
                in_array($response->status(), [302, 401]),
                "Guest should be redirected from {$route}, got status {$response->status()}"
            );
        }
    }
}
