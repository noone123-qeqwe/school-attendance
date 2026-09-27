<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditRolePageSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_parameterless_role_pages_render_without_server_errors(): void
    {
        $roles = [
            'student' => User::factory()->create(['role' => 'student']),
            'teacher' => User::factory()->teacher()->create(),
            'parent' => User::factory()->create(['role' => 'parent']),
            'admin' => User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']),
            'data_entry' => User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'data_entry']),
            'auditor' => User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'auditor']),
        ];
        $prefixes = [
            'student' => ['mobile/home', 'mobile/profile', 'mobile/attendance', 'mobile/scan', 'mobile/history'],
            'teacher' => ['teacher/', 'mobile/classes', 'mobile/students', 'mobile/home'],
            'parent' => ['parent/', 'mobile/children', 'mobile/reports', 'mobile/home'],
            'admin' => ['admin/', 'mobile/dashboard', 'mobile/settings', 'mobile/home'],
            'data_entry' => ['admin/', 'mobile/dashboard', 'mobile/settings', 'mobile/home'],
            'auditor' => ['admin/', 'mobile/dashboard', 'mobile/settings', 'mobile/home'],
        ];

        $failures = [];
        $counts = [];
        foreach ($roles as $role => $user) {
            $this->actingAs($user)->withSession(['admin_2fa_verified' => true]);
            $counts[$role] = 0;
            foreach (Route::getRoutes() as $route) {
                $uri = $route->uri();
                if (!in_array('GET', $route->methods(), true) || str_contains($uri, '{')) {
                    continue;
                }
                $isRolePage = collect($prefixes[$role])->contains(fn ($prefix) => str_starts_with($uri, $prefix));
                if ($role === 'student' && in_array('student', $route->gatherMiddleware(), true)) {
                    $isRolePage = true;
                }
                if (!$isRolePage) {
                    continue;
                }
                if (str_contains($uri, '/2fa') || preg_match('~/(export|pdf|csv|download|template)(/|$)~', $uri)) {
                    continue;
                }

                $counts[$role]++;
                $response = $this->get('/'.$uri);
                $status = $response->getStatusCode();
                $expectedDenial = in_array($role, ['data_entry', 'auditor'], true) && $status === 403;
                if ($status >= 500 || $status === 404 || ($status === 403 && !$expectedDenial)) {
                    $failures[] = "{$role} GET /{$uri}: {$status}";
                }
            }
        }

        fwrite(STDERR, 'Role page counts: '.json_encode($counts).PHP_EOL);
        $this->assertSame([], $failures, implode(PHP_EOL, $failures));
    }
}
