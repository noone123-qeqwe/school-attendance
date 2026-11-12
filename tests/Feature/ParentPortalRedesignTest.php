<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use App\Support\ParentNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ParentPortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $parent;
    private User $firstChild;
    private User $secondChild;
    private Attendance $absence;
    private Attendance $lateness;
    private ExcuseSubmission $excuse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parent = User::factory()->create(['role' => 'parent']);
        $this->firstChild = User::factory()->create(['role' => 'student', 'student_number' => '5558871', 'name' => 'A long student name for the family attendance overview', 'course' => 'BSCS', 'section' => 'A', 'year_level' => 2, 'semester' => 1]);
        $this->secondChild = User::factory()->create(['role' => 'student', 'student_number' => '5558872', 'name' => 'Another Family Student', 'section' => 'A']);
        $this->parent->children()->attach([$this->firstChild->id, $this->secondChild->id]);
        $subject = Subject::factory()->create(['code' => 'RDS101', 'name' => 'Research & Design: O\'Brien <Advanced>', 'year_level' => '2', 'semester' => '1', 'section' => 'A']);
        $this->firstChild->enrolledSubjects()->attach($subject);
        $subject->schedules()->create(['day' => 'Sunday', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'room' => 'Sunday laboratory']);
        // UI fixtures do not dispatch mail/SMS or create unrelated attendance alerts.
        $this->absence = Attendance::withoutEvents(fn () => Attendance::create(['user_id' => $this->firstChild->id, 'subject_id' => $subject->id, 'subject_code' => $subject->code, 'status' => 'Absent', 'date' => today()->toDateString()]));
        $this->lateness = Attendance::withoutEvents(fn () => Attendance::create(['user_id' => $this->secondChild->id, 'subject_id' => $subject->id, 'subject_code' => $subject->code, 'status' => 'Late', 'date' => today()->toDateString()]));
        $this->excuse = ExcuseSubmission::create(['user_id' => $this->firstChild->id, 'attendance_id' => $this->absence->id, 'reason' => 'Medical appointment & follow-up', 'description' => 'A medical appointment requiring attendance follow-up.', 'status' => 'pending']);
        $this->actingAs($this->parent);
    }

    public function test_parent_pages_render_with_consistent_assets_navigation_and_unique_ids(): void
    {
        $this->withoutExceptionHandling();
        $pages = [
            'parent.dashboard' => [], 'parent.link.form' => [], 'parent.schedule' => [],
            'parent.calendar' => [], 'parent.notifications' => [],
            'parent.excuses' => [], 'parent.excuses.create_general' => [], 'parent.profile' => [],
            'parent.child.warnings' => ['child' => $this->firstChild->id],
            'parent.child.excuse' => ['child' => $this->firstChild->id, 'attendance' => $this->absence->id],
            'parent.excuse.show' => ['excuseSubmission' => $this->excuse->id],
        ];
        foreach ($pages as $name => $parameters) {
            $response = $this->get(route($name, $parameters));
            $this->assertSame(200, $response->status(), "{$name} must render.");
            $html = $response->getContent();
            $this->assertStringContainsString('parent-portal-redesign.css', $html, $name);
            $this->assertStringContainsString('parent-workspace.js', $html, $name);
            $xpath = $this->xpath($html);
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $node) $ids[] = $node->getAttribute('id');
            $this->assertSame([], array_values(array_diff_assoc($ids, array_unique($ids))), "Duplicate IDs on {$name}");
            $this->assertSame(1, $xpath->query('//aside//nav[@aria-label="Parent navigation"]//a[@href="'.route('parent.calendar').'"]')->length);
            $this->capture($name, $html);
        }
        foreach (ParentNavigation::items() as [$label, $route]) $this->assertTrue(Route::has($route), $label);
    }

    public function test_empty_parent_pages_offer_a_linking_path_and_render_without_student_data(): void
    {
        $emptyParent = User::factory()->create(['role' => 'parent']);
        $this->actingAs($emptyParent);
        foreach (['parent.dashboard', 'parent.link.form', 'parent.schedule', 'parent.calendar', 'parent.excuses', 'parent.excuses.create_general', 'parent.notifications'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertStringNotContainsString($this->firstChild->student_number, $html);
            $this->assertStringContainsString(route('parent.link.form'), $html);
            $this->capture('empty-'.$route, $html);
        }
    }

    public function test_child_tabs_and_mobile_picker_keep_attendance_actions_with_the_correct_child(): void
    {
        $html = $this->get(route('parent.dashboard'))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(2, $xpath->query('//*[@role="tab" and @data-parent-child-tab]')->length);
        $this->assertSame(2, $xpath->query('//select[@data-parent-child-picker]/option')->length);
        $this->assertSame(1, $xpath->query('//*[@data-parent-child-panel and @hidden]')->length);
        foreach ([$this->firstChild, $this->secondChild] as $child) {
            $panel = $xpath->query('//*[@data-parent-child-panel="'.$child->id.'"]')->item(0);
            $this->assertNotNull($panel);
            $links = $xpath->query('.//a/@href', $panel);
            $destinations = [];
            foreach ($links as $link) $destinations[] = $link->nodeValue;
            $this->assertContains(route('parent.calendar', ['child_id' => $child->id]), $destinations);
            $this->assertContains(route('parent.child.report', $child), $destinations);
            $this->assertContains(route('parent.excuses.create_general', ['child_id' => $child->id]), $destinations);
        }
    }

    public function test_account_navigation_targets_the_parent_settings_page(): void
    {
        $html = $this->get(route('parent.dashboard'))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Parent navigation"]//a[@href="'.route('parent.profile').'"]')->length);
        $this->assertSame(0, $xpath->query('//nav[@aria-label="Parent navigation"]//a[@href="'.route('settings').'"]')->length);
        $this->assertStringContainsString('Account Settings', $html);
    }

    public function test_sunday_classes_and_the_chosen_child_appear_in_the_weekly_schedule(): void
    {
        $response = $this->get(route('parent.schedule', ['child_id' => $this->firstChild->id]))->assertOk();
        $response->assertSee('Sunday laboratory');
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//*[@id="day-card-sunday"]')->length);
        $this->assertSame(1, $xpath->query('//label[@for="parentContextChild"]')->length);
        $response->assertViewHas('selectedChild', fn ($child) => $child->id === $this->firstChild->id);
        $this->capture('schedule-sunday', $response->getContent());
    }

    public function test_selected_child_stays_consistent_across_parent_attendance_and_schedule_pages(): void
    {
        $key = ParentNavigation::selectionKey($this->parent);
        $this->withSession([$key => $this->secondChild->id]);

        $dashboard = $this->get(route('parent.dashboard'))->assertOk();
        $dashboard->assertViewHas('selectedChild', fn ($child) => $child->id === $this->secondChild->id);
        $dashboardXpath = $this->xpath($dashboard->getContent());
        $this->assertSame('true', $dashboardXpath->query('//*[@data-parent-child-tab="'.$this->secondChild->id.'"]')->item(0)?->getAttribute('aria-selected'));

        $this->get(route('parent.attendance.calendar'))
            ->assertRedirect(route('parent.calendar', ['child_id' => $this->secondChild->id]));
        foreach (['parent.schedule', 'parent.calendar'] as $route) {
            $response = $this->get(route($route))->assertOk();
            $response->assertViewHas('selectedChild', fn ($child) => $child->id === $this->secondChild->id);
        }
    }

    public function test_parent_navigation_is_grouped_without_removing_any_parent_destination(): void
    {
        $html = $this->get(route('parent.dashboard'))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(3, $xpath->query('//nav[@aria-label="Parent navigation"]//*[contains(concat(" ", normalize-space(@class), " "), " parent-nav-group ")]')->length);
        $this->assertSame(1, $xpath->query('//aside//nav[@aria-label="Parent navigation"]//a[@href="'.route('parent.calendar').'"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Parent navigation"]//a[@href="'.route('parent.profile').'"]')->length);
        $this->assertStringContainsString('Parent pages', $html);
    }

    public function test_calendar_and_filtered_records_use_the_same_selected_child(): void
    {
        $response = $this->get(route('parent.calendar', ['child_id' => $this->secondChild->id, 'status' => 'Late']))->assertOk();
        $response->assertSee('id="scalGrid"', false)->assertSee('id="attendance-records"', false);
        $this->assertSame([$this->lateness->id], $response->viewData('records')->pluck('id')->all());
        $this->assertSame($this->secondChild->id, $response->viewData('selectedChild')->id);

        $this->getJson(route('parent.calendar.data', ['child_id' => $this->secondChild->id]))
            ->assertOk()->assertJsonFragment(['id' => 'att_'.$this->lateness->id])
            ->assertJsonMissing(['id' => 'att_'.$this->absence->id]);

        $unlinked = User::factory()->create(['role' => 'student']);
        $this->getJson(route('parent.calendar.data', ['child_id' => $unlinked->id]))->assertForbidden();
    }

    public function test_child_detail_context_switcher_has_a_safe_server_side_fallback(): void
    {
        $this->get(route('parent.child', ['child' => $this->firstChild, 'child_id' => $this->secondChild->id]))
            ->assertRedirect(route('parent.calendar', ['child_id' => $this->secondChild->id]).'#attendance-records');

        $unlinked = User::factory()->create(['role' => 'student']);
        $this->get(route('parent.child', ['child' => $this->firstChild, 'child_id' => $unlinked->id]))
            ->assertRedirect(route('parent.calendar', ['child_id' => $this->firstChild->id]).'#attendance-records');
    }

    public function test_alert_badges_use_each_guardians_read_state_and_exclude_archived_alerts(): void
    {
        $otherParent = User::factory()->create(['role' => 'parent']);
        $otherParent->children()->attach($this->firstChild->id);
        $childAlert = Notification::create(['user_id' => $this->firstChild->id, 'type' => 'absence', 'message' => 'Linked student alert', 'is_read' => false]);
        Notification::create(['user_id' => $this->parent->id, 'type' => 'custom', 'message' => 'Parent private alert', 'is_read' => false]);
        $archived = Notification::create(['user_id' => $this->firstChild->id, 'type' => 'warning', 'message' => 'Archived alert', 'is_read' => false]);
        $archived->archive();
        $this->assertSame(2, ParentNavigation::unreadCount($this->parent));
        $this->post(route('parent.notifications.read'))->assertOk();
        $this->assertSame(0, ParentNavigation::unreadCount($this->parent));
        $this->assertSame(1, ParentNavigation::unreadCount($otherParent));
        $this->assertFalse($childAlert->fresh()->is_read);
        $html = $this->get(route('parent.dashboard'))->assertOk()->getContent();
        $this->assertSame(0, $this->xpath($html)->query('//*[@data-parent-unread-count and not(@hidden)]')->length);
    }

    public function test_calendar_and_attendance_links_never_select_an_unlinked_student(): void
    {
        $unlinked = User::factory()->create(['role' => 'student']);
        $response = $this->get(route('parent.calendar', ['child_id' => $unlinked->id]))->assertOk();
        $response->assertViewHas('selectedChild', fn ($child) => $child->id === $this->firstChild->id);
        preg_match('/const scalChildId\s*=\s*(\d+);/', $response->getContent(), $matches);
        $this->assertSame((string) $this->firstChild->id, $matches[1] ?? null);
        $this->assertSame([$this->absence->id], $response->viewData('records')->pluck('id')->all());
        $this->get(route('parent.child', $unlinked))->assertForbidden();
        $this->get(route('parent.child.warnings', $unlinked))->assertForbidden();
    }

    public function test_excuse_form_retains_selected_child_and_validation_values(): void
    {
        $response = $this->withSession(['_old_input' => ['child_id' => $this->secondChild->id, 'attendance_id' => $this->lateness->id, 'reason_type' => 'illness', 'reason' => 'Original explanation']])
            ->get(route('parent.excuses.create_general'))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame((string) $this->secondChild->id, $xpath->query('//select[@id="childSelect"]/option[@selected]')->item(0)?->getAttribute('value'));
        $this->assertSame((string) $this->lateness->id, $xpath->query('//*[@id="attendanceSelect"]')->item(0)?->getAttribute('data-selected'));
        $this->assertSame('illness', $xpath->query('//select[@id="reasonType"]/option[@selected]')->item(0)?->getAttribute('value'));
        $this->assertSame('Original explanation', $xpath->query('//*[@id="descInput"]')->item(0)?->textContent);
        foreach (['childSelect', 'attendanceSelect', 'reasonType', 'descInput'] as $field) $this->assertSame(1, $xpath->query('//label[@for="'.$field.'"]')->length);
        $this->capture('excuse-validation', $response->getContent());
    }

    public function test_other_roles_and_exported_reports_do_not_load_parent_screen_assets(): void
    {
        foreach (['student' => '/home', 'teacher' => '/teacher/dashboard', 'admin' => '/admin/dashboard'] as $role => $url) {
            $user = User::factory()->create(['role' => $role, 'admin_sub_role' => $role === 'admin' ? 'super_admin' : null]);
            $response = $this->actingAs($user)->get($url)->assertOk();
            $response->assertDontSee('parent-portal-redesign.css', false)->assertDontSee('parent-workspace.js', false);
        }
        $html = view('parent.report-pdf', ['child' => $this->firstChild, 'attendances' => collect(), 'warnings' => collect(), 'total' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'rate' => 0])->render();
        $this->assertStringNotContainsString('parent-portal-redesign.css', $html);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($document);
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('PARENT_UI_AUDIT_OUTPUT') !== '1') return;
        $directory = storage_path('framework/testing/parent-ui-audit');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        file_put_contents($directory.'/'.str_replace('.', '-', $name).'.html', $html);
    }
}
