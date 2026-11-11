<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Support\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSimpleNavigationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $this->actingAs($this->admin);
    }

    public function test_navigation_and_search_share_destinations_and_permissions_for_all_admin_levels(): void
    {
        foreach (['super_admin', 'auditor', 'data_entry', null] as $level) {
            $this->admin->admin_sub_role = $level;
            $groups = AdminNavigation::groups($this->admin);
            $this->assertSame(['Home', 'People', 'Classes', 'Attendance', 'School & system'], array_column($groups, 'label'));
            $destinations = [];
            foreach ($groups as $group) foreach ($group['items'] as $item) $destinations[] = route($item[1]);
            $search = AdminNavigation::searchItems($this->admin);
            $this->assertSame($destinations, array_column($search, 'url'));
            $this->assertSame(count($destinations), count(array_unique($destinations)));
            $this->assertSame($level === 'super_admin', in_array(route('admin.settings'), $destinations, true));
            $this->assertSame($level !== 'data_entry', in_array(route('admin.activity.log'), $destinations, true));
            foreach ($search as $item) $this->assertNotEmpty($item['hint']);
        }
    }

    public function test_nested_forms_rosters_and_reports_keep_the_right_navigation_area(): void
    {
        foreach ([
            'admin.student.create' => 'People', 'admin.teacher.edit' => 'People',
            'admin.enrollments.index' => 'Classes', 'admin.subjects.edit' => 'Classes',
            'admin.class-schedules.index' => 'Classes', 'admin.reports.teacher-performance' => 'Attendance',
            'admin.attendance.preview' => 'Attendance', 'admin.system-update.index' => 'School & system',
        ] as $route => $group) {
            $this->assertSame($group, AdminNavigation::currentGroup($this->admin, $route)['label']);
        }
    }

    public function test_mobile_navigation_uses_five_task_areas_and_related_pages_are_reachable(): void
    {
        $response = $this->get(route('admin.class-schedules.index'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(5, $xpath->query('//*[@id="mobileBottomNav"]/*')->length);
        $this->assertSame(1, $xpath->query('//*[@id="mobileBottomNav"]/a[contains(@class,"active") and contains(.,"Classes")]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="mobileBottomNav"]//*[contains(@class,"mbn-featured-btn")]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-admin-section-switcher]//a[@href="'.route('admin.subjects').'"]')->length);
        $this->assertSame(0, $xpath->query('//*[contains(@class,"sch-nav-tabs")]')->length);
    }

    public function test_home_prioritizes_four_tasks_without_technical_tools(): void
    {
        $xpath = $this->xpath($this->get(route('admin.dashboard'))->assertOk()->getContent());
        $tasks = $xpath->query('//*[contains(@class,"admin-task-grid")]/a');
        $this->assertSame(4, $tasks->length);
        $hrefs = [];
        foreach ($tasks as $task) $hrefs[] = $task->getAttribute('href');
        $this->assertContains(route('admin.class-schedules.index'), $hrefs);
        $this->assertNotContains(route('admin.system-update.index'), $hrefs);
        $this->assertSame(1, $xpath->query('//*[@id="at-risk-section"]')->length);
    }

    public function test_settings_categories_keep_every_field_inside_the_original_save_form(): void
    {
        $xpath = $this->xpath($this->get(route('admin.settings'))->assertOk()->getContent());
        $this->assertSame(5, $xpath->query('//form[@id="settingsForm"]/details[contains(@class,"admin-settings-section")]')->length);
        $this->assertSame(1, $xpath->query('//form[@id="settingsForm"]/details[@open]')->length);
        foreach (['school_name', 'academic_year', 'qr_expiry', 'gps_lat', 'email_notifications', 'admin_ip_whitelist'] as $name) {
            $this->assertGreaterThan(0, $xpath->query('//form[@id="settingsForm"]//*[@name="'.$name.'"]')->length, $name);
        }
        $this->assertSame(0, $xpath->query('//form[@id="settingsForm"]//form')->length);
        $this->assertSame(route('admin.settings.update'), $xpath->query('//form[@id="settingsForm"]')->item(0)->getAttribute('action'));
    }

    public function test_settings_validation_opens_categories_and_displays_the_error(): void
    {
        $response = $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag())->put('default', new \Illuminate\Support\MessageBag(['qr_expiry' => 'Choose a valid QR expiry.']))])
            ->get(route('admin.settings'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(5, $xpath->query('//form[@id="settingsForm"]/details[@open]')->length);
        $this->assertSame(1, $xpath->query('//*[@role="alert" and contains(.,"Choose a valid QR expiry.")]')->length);
    }

    public function test_subject_actions_use_working_forms_and_auditors_only_see_read_actions(): void
    {
        $subject = Subject::factory()->create(['instructor_id' => User::factory()->teacher()->create()->id]);
        $xpath = $this->xpath($this->get(route('admin.subjects'))->assertOk()->getContent());
        $this->assertSame(1, $xpath->query('//main//a[@href="'.route('admin.subjects.create').'"]')->length);
        $this->assertSame(1, $xpath->query('//main//a[@href="'.route('admin.subjects.edit', $subject).'"]')->length);
        $this->get(route('admin.subjects.create'))->assertOk();
        $this->get(route('admin.subjects.edit', $subject))->assertOk();
        $this->admin->update(['admin_sub_role' => 'auditor']);
        $xpath = $this->xpath($this->get(route('admin.subjects'))->assertOk()->getContent());
        $this->assertSame(0, $xpath->query('//main//a[@href="'.route('admin.subjects.create').'"]')->length);
        $this->assertSame(0, $xpath->query('//main//a[@href="'.route('admin.subjects.edit', $subject).'"]')->length);
        $this->assertSame(0, $xpath->query('//main//form[@method="POST"]')->length);
        $this->assertSame(1, $xpath->query('//main//a[@href="'.route('admin.enrollments.index', $subject).'"]')->length);
        $xpath = $this->xpath($this->get(route('admin.class-schedules.index'))->assertOk()->getContent());
        $this->assertSame(0, $xpath->query('//button[@onclick="openModal(\'addScheduleModal\')"]')->length);
        $this->assertSame(0, $xpath->query('//form[@action="'.route('admin.class-schedules.publish').'"]')->length);
    }

    public function test_report_picker_preserves_filters_and_supports_every_existing_report(): void
    {
        $response = $this->get(route('admin.reports', ['type' => 'monthly', 'month' => '2026-11', 'course' => 'BSCS']))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(9, $xpath->query('//select[@id="adminReportType"]/option')->length);
        $this->assertSame('monthly', $xpath->query('//select[@id="adminReportType"]/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('2026-11', $xpath->query('//form[contains(@class,"admin-report-picker")]/input[@name="month"]')->item(0)->getAttribute('value'));
        $this->assertSame(0, $xpath->query('//form//form')->length);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($document);
    }
}
