<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);
    }

    public function test_student_desktop_pages_load_the_shared_student_design_layer(): void
    {
        $studentPages = [
            '/home',
            '/profile',
            '/my-classes',
            '/attendance/records',
            '/student/attendance-calendar',
            '/student/calendar',
            '/excuses',
            '/excuses/general/new',
            '/notifications',
        ];

        foreach ($studentPages as $url) {
            $response = $this->actingAs($this->student)->get($url);

            $response->assertOk();
            $response->assertSee('student-portal-redesign.css', false);
            $response->assertSee('student-portal-simple.css', false);
            $response->assertSee('class="student-portal"', false);
            $this->assertSame(1, substr_count($response->getContent(), 'id="cmdPaletteOverlay"'), "Search must have one instance on {$url}.");
            $this->captureUiPage($url, $response->getContent());
        }
    }

    public function test_student_mobile_pages_load_the_shared_student_design_layer(): void
    {
        foreach (['/mobile/home', '/mobile/scan', '/mobile/history', '/mobile/profile'] as $url) {
            $response = $this->actingAs($this->student)->get($url);

            $response->assertOk();
            $response->assertSee('student-portal-redesign.css', false);
            $response->assertSee('student-portal-simple.css', false);
            $response->assertSee('student-mobile-portal', false);
            $this->captureUiPage($url, $response->getContent());
        }
    }

    public function test_student_settings_keeps_its_existing_dedicated_design(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertOk();
        $response->assertDontSee('student-portal-redesign.css', false);
        $response->assertDontSee('student-portal-simple.css', false);
        $this->captureUiPage('/settings', $response->getContent());
    }

    public function test_record_status_and_special_character_subject_names_are_preserved(): void
    {
        $subject = Subject::factory()->create(['name' => 'Research & Design: O\'Brien <Advanced>']);
        Attendance::create([
            'user_id' => $this->student->id, 'subject_id' => $subject->id,
            'subject_code' => $subject->code, 'date' => today()->toDateString(), 'status' => 'Escaped',
        ]);
        $response = $this->actingAs($this->student)->get('/attendance/records')->assertOk();
        $this->captureUiPage('/attendance/records-populated', $response->getContent());
        $document = new \DOMDocument();
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $status = $xpath->query('//*[@data-status="Escaped"]//*[contains(@class,"status-badge")]')->item(0);
        $this->assertSame('Escaped', trim($status?->textContent ?? ''));
        $this->assertSame($subject->name, trim($xpath->query('//*[@data-status="Escaped"]//h4')->item(0)?->textContent ?? ''));
    }

    public function test_excuse_subject_selection_is_labelled_and_retained_after_validation(): void
    {
        $subject = Subject::factory()->create(['name' => 'A long subject title with practical laboratory work and field research']);
        $this->student->enrolledSubjects()->attach($subject);
        $attendance = Attendance::create([
            'user_id' => $this->student->id, 'subject_id' => $subject->id,
            'subject_code' => $subject->code, 'date' => today()->toDateString(), 'status' => 'Absent',
        ]);
        foreach (['/excuses/general/new', '/excuses/create/'.$attendance->id] as $url) {
            $response = $this->actingAs($this->student)->withSession(['_old_input' => ['subject_codes' => [$subject->code]]])->get($url)->assertOk();
            $this->captureUiPage($url, $response->getContent());
            $document = new \DOMDocument();
            @$document->loadHTML($response->getContent());
            $xpath = new \DOMXPath($document);
            $checkbox = $xpath->query('//label[contains(@class,"excuse-subject-card")]/input[@type="checkbox"]')->item(0);
            $this->assertNotNull($checkbox);
            $this->assertTrue($checkbox->hasAttribute('checked'));
            $this->assertSame($subject->code, $checkbox->getAttribute('value'));
            $this->assertSame(1, $xpath->query('//label[@for="excuseReason"]')->length);
            $this->assertSame(1, $xpath->query('//label[@for="descInput"]')->length);
        }
        $legacyClasses = $this->get('/classes')->assertOk();
        $this->assertSame('student.classes', $legacyClasses->original->name());
    }

    private function captureUiPage(string $url, string $html): void
    {
        if (getenv('STUDENT_UI_AUDIT_OUTPUT') !== '1') return;
        $directory = storage_path('framework/testing/student-ui-audit');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        file_put_contents($directory.'/'.preg_replace('/[^a-z0-9-]/i', '-', trim($url, '/')).'.html', $html);
    }

    public function test_student_settings_uses_roomy_desktop_and_narrow_phone_layouts(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertOk();
        $response->assertSee('max-width: 1480px', false);
        $response->assertSee('grid-template-columns: 1fr; gap: 8px;', false);
        $response->assertSee('padding: 22px 14px;', false);
    }

    public function test_student_design_layer_includes_desktop_mobile_and_reduced_motion_rules(): void
    {
        $stylesheet = file_get_contents(public_path('css/student-portal-redesign.css'));

        $this->assertNotFalse($stylesheet);
        $this->assertStringContainsString('body.student-portal .student-hero-banner', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-header', $stylesheet);
        $this->assertStringContainsString('padding: clamp(28px, 3vw, 46px)', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-home .quick-action-btn', $stylesheet);
        $this->assertStringContainsString('@media (max-width: 480px)', $stylesheet);
        $this->assertStringContainsString('body.student-mobile-portal .mobile-history .stats-pills-grid', $stylesheet);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $stylesheet);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $stylesheet);
    }
}
