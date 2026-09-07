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
    }

    public function test_register_page_renders_password_eye_toggle_buttons()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('btn-toggle-password', false);
        $response->assertSee('btn-toggle-password-conf', false);
        $response->assertSee('togglePassword', false);
    }

    public function test_qr_login_page_renders_password_eye_toggle_button()
    {
        $view = $this->view('qr.login', ['token' => 'sample_token', 'errors' => new \Illuminate\Support\ViewErrorBag()]);

        $view->assertSee('togglePassword', false);
        $view->assertSee('eye-toggle', false);
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
    }

    public function test_force_change_password_page_renders_eye_toggle_buttons()
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('password.change.form'));

        $response->assertStatus(200);
        $response->assertSee('togglePassword', false);
        $response->assertSee('eye-toggle', false);
    }
}
