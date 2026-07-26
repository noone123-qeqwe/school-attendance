<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DashboardRenderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_teacher_dashboard_renders()
    {
        $teacher = User::where('role', 'teacher')->first();
        $response = $this->actingAs($teacher)
                         ->withSession(['user_role' => 'teacher'])
                         ->get('/teacher/dashboard');
        
        file_put_contents(base_path('test_response.html'), $response->getContent());
        echo "Status: " . $response->getStatusCode() . "\n";
    }
}
