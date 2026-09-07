<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoTopProgressBarTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_does_not_contain_native_app_progress_bar()
    {
        $teacher = User::factory()->create(['role' => 'teacher', 'is_active' => true]);
        
        $response = $this->actingAs($teacher)
                         ->withSession(['user_role' => 'teacher'])
                         ->get('/teacher/dashboard');

        $response->assertStatus(200);

        // Assert top progress bar element is completely removed from DOM
        $response->assertDontSee('id="native-app-progress"', false);
        $response->assertDontSee('native-app-progress', false);

        // Assert no event listener refers to native-app-progress
        $content = $response->getContent();
        $this->assertStringNotContainsString('native-app-progress', $content);
        $this->assertStringNotContainsString("bar.style.width = '40%'", $content);
        $this->assertStringNotContainsString("bar.style.width = '85%'", $content);
        $this->assertStringNotContainsString("bar.style.width = '100%'", $content);
    }

    public function test_student_dashboard_does_not_contain_native_app_progress_bar()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'is_active' => true,
        ]);

        $response = $this->actingAs($student)
                         ->withSession(['user_role' => 'student'])
                         ->get('/home');

        $response->assertStatus(200);
        $response->assertDontSee('id="native-app-progress"', false);
        $response->assertDontSee('native-app-progress', false);
    }

    public function test_admin_dashboard_does_not_contain_native_app_progress_bar()
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)
                         ->withSession(['user_role' => 'admin'])
                         ->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('id="native-app-progress"', false);
        $response->assertDontSee('native-app-progress', false);
    }
}
