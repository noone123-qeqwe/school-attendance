<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspInlineScriptTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_pages_render_inline_scripts_with_the_response_csp_nonce(): void
    {
        $admin = User::factory()->admin()->create(['admin_sub_role' => 'super_admin']);
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $parent = User::factory()->create(['role' => 'parent']);

        $pages = [
            [$admin, route('admin.dashboard'), true],
            [$teacher, route('teacher.attendance'), false],
            [$student, route('student.schedule'), false],
            [$parent, route('parent.notifications'), false],
        ];

        foreach ($pages as [$user, $url, $adminVerified]) {
            $response = $this->actingAs($user)
                ->withSession(['admin_2fa_verified' => $adminVerified])
                ->get($url);
            $response->assertOk();

            $policy = (string) $response->headers->get('Content-Security-Policy');
            $this->assertSame(1, preg_match("/'nonce-([^']+)'/", $policy, $nonceMatch));

            preg_match_all('/<script\b(?![^>]*\bsrc=)[^>]*>/i', $response->getContent(), $inlineScripts);
            $this->assertNotEmpty($inlineScripts[0], $url.' should exercise an inline script.');
            foreach ($inlineScripts[0] as $openingTag) {
                $this->assertStringContainsString('nonce="'.$nonceMatch[1].'"', $openingTag, $url.' has an inline script blocked by CSP.');
            }
        }
    }
}
