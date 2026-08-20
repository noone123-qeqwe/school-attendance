<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_dashboard_renders()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $response = $this->actingAs($teacher)
                         ->withSession(['user_role' => 'teacher'])
                         ->get('/teacher/dashboard');
        
        $response->assertStatus(200);
        file_put_contents(base_path('test_response.html'), $response->getContent());
    }
}
