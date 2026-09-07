<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordToggleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_password_eye_toggle_button()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('toggleEye', false);
        $response->assertSee('loginPassword', false);
        $response->assertSee('eye-toggle', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('type="button"', false);
        $response->assertDontSee('onpointerdown="event.preventDefault();"', false);
        $response->assertDontSee('tabindex="-1"', false);
    }

    public function test_register_page_renders_password_eye_toggle_buttons()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('btn-toggle-password', false);
        $response->assertSee('btn-toggle-password-conf', false);
        $response->assertSee('togglePassword', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_qr_login_page_renders_password_eye_toggle_button()
    {
        $view = $this->view('qr.login', ['token' => 'sample_token', 'errors' => new \Illuminate\Support\ViewErrorBag()]);

        $view->assertSee('togglePassword', false);
        $view->assertSee('eye-toggle', false);
        $view->assertSee('aria-label="Show password"', false);
    }

    public function test_admin_profile_renders_password_eye_toggle_buttons()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.profile'));

        $response->assertStatus(200);
        $response->assertSee('apw1', false);
        $response->assertSee('apw2', false);
        $response->assertSee('togglePw', false);
        $response->assertSee('eye-btn', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_teacher_profile_renders_password_eye_toggle_buttons()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $response = $this->actingAs($teacher)->get(route('teacher.profile'));

        $response->assertStatus(200);
        $response->assertSee('tpw1', false);
        $response->assertSee('tpw2', false);
        $response->assertSee('togglePw', false);
        $response->assertSee('eye-btn', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_parent_profile_renders_password_eye_toggle_buttons()
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->get(route('parent.profile'));

        $response->assertStatus(200);
        $response->assertSee('spw1', false);
        $response->assertSee('spw2', false);
        $response->assertSee('togglePw', false);
        $response->assertSee('eye-btn', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_settings_renders_password_eye_toggle_buttons()
    {
        $user = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertStatus(200);
        $response->assertSee('spw1', false);
        $response->assertSee('spw2', false);
        $response->assertSee('togglePw', false);
        $response->assertSee('eye-btn', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_force_change_password_page_renders_eye_toggle_buttons()
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('password.change.form'));

        $response->assertStatus(200);
        $response->assertSee('togglePassword', false);
        $response->assertSee('eye-toggle', false);
        $response->assertSee('aria-label="Show password"', false);
        $response->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_admin_create_teacher_renders_eye_toggle_buttons()
    {
        $view = $this->view('admin.teachers.create', ['errors' => new \Illuminate\Support\ViewErrorBag()]);

        $view->assertSee('t_pw1', false);
        $view->assertSee('t_pw2', false);
        $view->assertSee('aria-label="Show password"', false);
        $view->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_admin_create_student_renders_eye_toggle_button()
    {
        $view = $this->view('admin.students.create', [
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'sections' => collect(),
            'courses' => collect(),
        ]);

        $view->assertSee('s_pw1', false);
        $view->assertSee('data-toggle-password="s_pw1"', false);
        $view->assertSee('aria-label="Show password"', false);
    }

    public function test_admin_create_admin_renders_eye_toggle_buttons()
    {
        $view = $this->view('admin.admins.create', ['errors' => new \Illuminate\Support\ViewErrorBag()]);

        $view->assertSee('a_pw1', false);
        $view->assertSee('a_pw2', false);
        $view->assertSee('data-toggle-password="a_pw1"', false);
        $view->assertSee('data-toggle-password="a_pw2"', false);
        $view->assertSee('aria-label="Show password"', false);
        $view->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_admin_edit_teacher_renders_eye_toggle_buttons()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $view = $this->view('admin.teachers.edit', [
            'teacher' => $teacher,
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'sections' => collect(),
            'assignedSectionIds' => [],
        ]);

        $view->assertSee('te_pw1', false);
        $view->assertSee('te_pw2', false);
        $view->assertSee('data-toggle-password="te_pw1"', false);
        $view->assertSee('data-toggle-password="te_pw2"', false);
        $view->assertSee('aria-label="Show password"', false);
        $view->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_admin_edit_student_renders_eye_toggle_buttons()
    {
        $student = User::factory()->create(['role' => 'student']);
        $view = $this->view('admin.students.edit', [
            'student' => $student,
            'errors' => new \Illuminate\Support\ViewErrorBag(),
            'sections' => collect(),
            'courses' => collect(),
        ]);

        $view->assertSee('se_pw1', false);
        $view->assertSee('se_pw2', false);
        $view->assertSee('data-toggle-password="se_pw1"', false);
        $view->assertSee('data-toggle-password="se_pw2"', false);
        $view->assertSee('aria-label="Show password"', false);
        $view->assertSee('aria-label="Show password confirmation"', false);
    }

    public function test_reset_password_page_renders_eye_toggle_buttons()
    {
        $view = $this->view('auth.reset-password', [
            'token' => 'sample_token',
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ]);

        $view->assertSee('pw1', false);
        $view->assertSee('pw2', false);
        $view->assertSee('data-toggle-password="pw1"', false);
        $view->assertSee('data-toggle-password="pw2"', false);
        $view->assertSee('aria-label="Show password"', false);
        $view->assertSee('aria-label="Show password confirmation"', false);
    }
}
