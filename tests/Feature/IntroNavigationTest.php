<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class IntroNavigationTest extends TestCase
{
    use RefreshDatabase;

    public static function destinations(): array
    {
        return [
            'guest sign-in' => [null, 'login'],
            'student dashboard' => ['student', 'home'],
            'teacher dashboard' => ['teacher', 'teacher.dashboard'],
            'parent dashboard' => ['parent', 'parent.dashboard'],
            'admin dashboard' => ['admin', 'admin.dashboard'],
        ];
    }

    #[DataProvider('destinations')]
    public function test_skip_uses_the_correct_destination_and_keeps_a_real_fallback_link(?string $role, string $route): void
    {
        if ($role !== null) {
            $this->actingAs(User::factory()->create(['role' => $role]));
        }

        $response = $this->get(route('intro'))->assertOk();
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $skip = $xpath->query('//*[@id="skipBtn"]')->item(0);
        $this->assertNotNull($skip);
        $this->assertSame('a', $skip->nodeName);
        $this->assertSame(route($route), $skip->getAttribute('href'));
        preg_match('/const destUrl\s*=\s*([^;]+);/', $response->getContent(), $destination);
        $this->assertSame(route($route), json_decode($destination[1], true));
        $this->assertSame(1, $xpath->query('//*[@id="introStatus" and @role="status"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="introRecovery"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="fadeOut"]')->length);
    }

    public function test_rendered_intro_scripts_have_valid_javascript_syntax(): void
    {
        $response = $this->get(route('intro'))->assertOk();
        preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script>/i', $response->getContent(), $scripts);
        $this->assertNotEmpty($scripts[1]);

        foreach ($scripts[1] as $index => $code) {
            if (trim($code) === '') {
                continue;
            }
            $process = new Process(['node', '--check']);
            $process->setInput($code);
            $process->run();
            $this->assertTrue($process->isSuccessful(), "Intro script {$index}: " . $process->getErrorOutput());
        }
    }
}
