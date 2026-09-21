<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $superAdmin;
    protected User $student;
    protected User $teacher;
    protected Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withSession(['admin_2fa_verified' => true]);

        $this->superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
            'email' => 'super_smoke@school.edu',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2699991',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $this->subject = Subject::factory()->create([
            'instructor_id' => $this->teacher->id,
        ]);
    }

    public function test_admin_pages_render_successfully(): void
    {
        $routes = [
            'admin.dashboard' => [],
            'admin.students' => [],
            'admin.teachers' => [],
            'admin.subjects' => [],
            'admin.sections.index' => [],
            'admin.attendance' => [],
            'admin.reports' => [],
            'admin.calendar' => [],
            'admin.settings' => [],
            'admin.system-health.index' => [],
            'admin.system-update.index' => [],
            'admin.notifications' => [],
            'admin.backups.index' => [],
            'admin.roles.index' => [],
            'admin.student' => ['student' => $this->student->id],
            'admin.student.edit' => ['student' => $this->student->id],
            'admin.teacher.edit' => ['teacher' => $this->teacher->id],
            'admin.subjects.edit' => ['subject' => $this->subject->id],
        ];

        foreach ($routes as $name => $params) {
            $url = route($name, $params);
            $response = $this->actingAs($this->superAdmin)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "Route {$name} ({$url}) failed with status {$response->status()}"
            );
        }
    }

    public function test_export_attendance_pdf(): void
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->superAdmin)->get(route('admin.attendance.pdf'));
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_all_admin_get_routes_render_or_redirect_cleanly(): void
    {
        $router = app('router');
        $routes = $router->getRoutes();

        $tested = 0;
        foreach ($routes as $route) {
            if (!in_array('GET', $route->methods())) {
                continue;
            }

            $uri = $route->uri();
            if (!str_starts_with($uri, 'admin')) {
                continue;
            }

            // Replace parameters with sample IDs
            $path = preg_replace('/\{student(\?|:.*?)?\}/', (string)$this->student->id, $uri);
            $path = preg_replace('/\{teacher(\?|:.*?)?\}/', (string)$this->teacher->id, $path);
            $path = preg_replace('/\{subject(\?|:.*?)?\}/', (string)$this->subject->id, $path);
            $path = preg_replace('/\{role(\?|:.*?)?\}/', 'student', $path);
            $path = preg_replace('/\{section(\?|:.*?)?\}/', '1', $path);
            $path = preg_replace('/\{backup(\?|:.*?)?\}/', 'dummy.zip', $path);
            $path = preg_replace('/\{id(\?|:.*?)?\}/', '1', $path);
            $path = preg_replace('/\{attendance(\?|:.*?)?\}/', '1', $path);
            $path = preg_replace('/\{user(\?|:.*?)?\}/', (string)$this->student->id, $path);
            $path = preg_replace('/\{[a-zA-Z0-9_]+\}/', '1', $path);

            $response = $this->actingAs($this->superAdmin)->get('/' . $path);
            $statusCode = ($response instanceof \Illuminate\Testing\TestResponse)
                ? $response->getStatusCode()
                : (method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 200);
            $this->assertLessThan(
                500,
                $statusCode,
                "Route GET /{$uri} (resolved: /{$path}) threw server error {$statusCode}"
            );
            $tested++;
        }

        $this->assertGreaterThan(20, $tested);
    }
}
