<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use App\Support\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class AdminPortalRedesignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin', 'admin_sub_role' => 'super_admin']);
        $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true]);
    }

    public function test_admin_workspaces_render_with_consistent_navigation_and_unique_ids(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $department = Department::create(['code' => 'ENG', 'name' => 'Engineering']);
        $course = Course::create(['department_id' => $department->id, 'code' => 'BSCS', 'name' => 'Computer Science']);
        Section::create(['course_id' => $course->id, 'name' => 'BSCS-1A', 'year_level' => 1]);
        $pages = [
            'admin.dashboard' => [], 'admin.students' => [], 'admin.teachers' => [],
            'admin.parents.index' => [], 'admin.departments.index' => [], 'admin.courses.index' => [],
            'admin.sections.index' => [], 'admin.academic-years.index' => [], 'admin.subjects' => [],
            'admin.class-schedules.index' => [], 'admin.attendance' => [], 'admin.reports' => [],
            'admin.calendar' => [], 'admin.announcements.index' => [], 'admin.notifications' => [],
            'admin.qr' => [], 'admin.excuses' => [], 'admin.corrections' => [], 'admin.early-warnings' => [],
            'admin.profile' => [], 'admin.settings' => [], 'admin.activity.log' => [],
            'admin.admins' => [], 'admin.roles.index' => [], 'admin.backups.index' => [],
            'admin.system-health.index' => [], 'admin.system-update.index' => [], 'admin.policies.edit' => [],
            'admin.student' => ['student' => $student->id],
            'admin.student.create' => [], 'admin.student.edit' => ['student' => $student->id],
            'admin.teacher.create' => [], 'admin.teacher.edit' => ['teacher' => $teacher->id],
            'admin.subjects.create' => [], 'admin.subjects.edit' => ['subject' => $subject->id],
            'admin.enrollments.index' => ['subject' => $subject->id],
            'admin.students.preview' => [], 'admin.attendance.preview' => [],
            'admin.admin.create' => [], 'admin.gps' => [],
        ];
        foreach ($pages as $name => $parameters) {
            $response = $this->get(route($name, $parameters));
            $redirectTabs = ['admin.backups.index' => 'backups', 'admin.system-health.index' => 'health'];
            if (isset($redirectTabs[$name])) {
                $response->assertRedirect(route('admin.system-update.index', ['tab' => $redirectTabs[$name]]));
                $response = $this->get(route('admin.system-update.index', ['tab' => $redirectTabs[$name]]));
            }
            $this->assertSame(200, $response->status(), "Page {$name} must render.");
            $html = $response->getContent();
            $this->assertStringContainsString('admin-portal-redesign.css', $html, $name);
            $this->assertStringContainsString('admin-workspace.js', $html, $name);
            $xpath = $this->xpath($html);
            $this->assertSame(2, $xpath->query('//*[@data-admin-navigation]')->length, $name);
            $this->assertSame(2, $xpath->query('//*[@data-admin-navigation]//a[@aria-current="page"]')->length, $name);
            $ids = [];
            foreach ($xpath->query('//*[@id]') as $element) $ids[] = $element->getAttribute('id');
            $this->assertSame([], array_values(array_diff_assoc($ids, array_unique($ids))), "Duplicate IDs on {$name}");
            $this->capture($name, $html);
        }
    }

    public function test_navigation_only_advertises_available_pages_for_each_admin_level(): void
    {
        foreach (['super_admin', 'data_entry', 'auditor', null] as $level) {
            $this->admin->admin_sub_role = $level;
            $destinations = [];
            foreach (AdminNavigation::groups($this->admin) as $group) {
                foreach ($group['items'] as [$label, $route]) {
                    $this->assertTrue(Route::has($route), "Missing destination: {$label}");
                    $destinations[] = $route;
                }
            }
            $this->assertSame($level === 'super_admin', in_array('admin.settings', $destinations, true));
            $this->assertSame($level !== 'data_entry', in_array('admin.activity.log', $destinations, true));
        }
    }

    public function test_read_only_admins_keep_browse_tools_without_write_controls(): void
    {
        $department = Department::create(['code' => 'SCI', 'name' => 'Science']);
        Course::create(['department_id' => $department->id, 'code' => 'BSCI', 'name' => 'Science course']);
        $this->admin->update(['admin_sub_role' => 'auditor']);
        foreach (['admin.students', 'admin.courses.index', 'admin.departments.index', 'admin.sections.index'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $xpath = $this->xpath($html);
            $this->assertSame(0, $xpath->query('//*[@data-admin-edit-record]')->length);
            $this->assertSame(0, $xpath->query('//*[@data-admin-navigation]//a[contains(@href,"/admin/settings")]')->length);
            $this->capture('auditor-'.$route, $html);
        }
    }

    public function test_academic_filters_search_the_dataset_and_retain_query_on_pagination(): void
    {
        $science = Department::create(['code' => 'SCI', 'name' => 'Science']);
        $arts = Department::create(['code' => 'ART', 'name' => 'Arts']);
        $scienceCourse = Course::create(['department_id' => $science->id, 'code' => 'BSCS', 'name' => 'Computer Science']);
        Course::create(['department_id' => $arts->id, 'code' => 'BA', 'name' => 'Art History']);
        Section::create(['course_id' => $scienceCourse->id, 'name' => 'BSCS-1A', 'year_level' => 1]);
        foreach (range(1, 11) as $i) Section::create(['course_id' => $scienceCourse->id, 'name' => 'SCI-'.$i, 'year_level' => 2]);
        $this->get(route('admin.departments.index', ['search' => 'Science']))->assertOk()
            ->assertViewHas('departments', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $science->id);
        $this->get(route('admin.courses.index', ['search' => 'Computer', 'department_id' => $science->id]))->assertOk()
            ->assertViewHas('courses', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $scienceCourse->id);
        $response = $this->get(route('admin.sections.index', ['search' => 'SCI-', 'course_id' => $scienceCourse->id]))->assertOk();
        $response->assertViewHas('sections', fn ($rows) => $rows->total() === 11 && str_contains($rows->nextPageUrl(), 'search=SCI-'));
        $response->assertSee('Scheduled Classes');
        $this->capture('sections-filtered', $response->getContent());
    }

    public function test_academic_editor_preserves_special_characters_and_existing_update_permissions(): void
    {
        $department = Department::create(['code' => 'RD', 'name' => 'Research & Design', 'description' => 'O\'Brien <Advanced>']);
        $course = Course::create(['department_id' => $department->id, 'code' => 'RDA', 'name' => 'Research Arts']);
        foreach (['departments' => $department, 'courses' => $course] as $type => $record) {
            $html = $this->get(route('admin.'.$type.'.index'))->assertOk()->getContent();
            $button = $this->xpath($html)->query('//*[@data-admin-edit-record]')->item(0);
            $values = json_decode($button->getAttribute('data-admin-edit-record'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame($record->name, $values['name']);
            $this->assertSame(route('admin.'.$type.'.update', $record), $button->getAttribute('data-edit-url'));
            $this->capture($type.'-editor', $html);
        }
        $this->put(route('admin.departments.update', $department), ['code' => 'RD', 'name' => 'Applied Research', 'description' => 'Updated'])->assertRedirect();
        $this->put(route('admin.courses.update', $course), ['department_id' => $department->id, 'code' => 'RDA', 'name' => 'Applied Arts'])->assertRedirect();
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Applied Research']);
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Applied Arts']);
        $this->admin->update(['admin_sub_role' => 'auditor']);
        $this->putJson(route('admin.courses.update', $course), ['name' => 'Forbidden'])->assertForbidden();
        $this->assertDatabaseMissing('courses', ['name' => 'Forbidden']);
    }

    public function test_qr_search_and_single_print_do_not_submit_other_selected_students(): void
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Unique QR Student', 'student_number' => '5558881', 'course' => 'BSCS', 'year_level' => 2]);
        User::factory()->create(['role' => 'student', 'name' => 'Another Student', 'student_number' => '5558882', 'course' => 'BSIT', 'year_level' => 1]);
        $response = $this->get(route('admin.qr', ['search' => '5558881', 'course' => 'BSCS', 'year' => 2]))->assertOk();
        $response->assertViewHas('students', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $student->id);
        $html = $response->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query('//button[@form="singleQRPrint" and @name="student_ids[]"]')->length);
        $this->assertSame(0, $xpath->query('//form[@id="bulkPrintForm"]//form')->length);
        $this->assertStringNotContainsString('QR regenerated successfully', $html);
        $this->capture('qr-filtered', $html);
    }

    public function test_directory_downloads_keep_the_current_filters_in_the_action_menu(): void
    {
        $response = $this->get(route('admin.students', ['search' => 'Example']))->assertOk();
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//details[contains(@class,"admin-action-menu")]')->length);
        foreach (['admin.students.csv', 'admin.students.preview'] as $destination) {
            $href = route($destination, ['search' => 'Example']);
            $this->assertSame(1, $xpath->query('//details[contains(@class,"admin-action-menu")]//a[@href="'.$href.'"]')->length);
        }
    }

    public function test_qr_print_uses_cached_images_without_writing_to_student_accounts(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jLXkAAAAASUVORK5CYII=');
        Http::fake(['api.qrserver.com/*' => Http::response($png, 200)]);
        $student = User::factory()->create(['role' => 'student', 'student_number' => '5558883']);
        $beforePrint = $student->fresh()->getRawOriginal();
        Pdf::shouldReceive('loadView')->times(3)->with('admin.qr.pdf', \Mockery::on(function ($data) use ($student) {
            return $data['students']->count() === 1 && str_starts_with($data['qrImages'][$student->id], 'data:image/png;base64,');
        }))->andReturnSelf();
        Pdf::shouldReceive('stream')->times(3)->with('bulk-qr-codes.pdf')->andReturn(response('PDF fixture'));

        foreach (range(1, 2) as $attempt) {
            $this->post(route('admin.qr.bulk-print'), ['student_ids' => [$student->id]])->assertOk();
        }
        Http::assertSentCount(1);
        $this->assertSame($beforePrint, $student->fresh()->getRawOriginal());
        $student->update(['student_number' => '5558884']);
        $this->post(route('admin.qr.bulk-print'), ['student_ids' => [$student->id]])->assertOk();
        Http::assertSentCount(2);
        $this->assertCount(2, Storage::disk('public')->files('qrcodes'));
    }

    public function test_qr_provider_failure_is_reported_without_a_blank_pdf_or_invalid_cache(): void
    {
        Storage::fake('public');
        Http::fake(['api.qrserver.com/*' => Http::response('Unavailable', 503)]);
        Pdf::shouldReceive('loadView')->never();
        $student = User::factory()->create(['role' => 'student']);
        $this->from(route('admin.qr'))->post(route('admin.qr.bulk-print'), ['student_ids' => [$student->id]])
            ->assertRedirect(route('admin.qr'))->assertSessionHas('error');
        $this->assertSame([], Storage::disk('public')->files('qrcodes'));
        $teacher = User::factory()->teacher()->create();
        $this->postJson(route('admin.qr.bulk-print'), ['student_ids' => [$teacher->id]])->assertUnprocessable();
    }

    public function test_qr_print_produces_a_pdf_with_embedded_images(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jLXkAAAAASUVORK5CYII=');
        Http::fake(['api.qrserver.com/*' => Http::response($png, 200)]);
        $student = User::factory()->create(['role' => 'student']);
        $response = $this->post(route('admin.qr.bulk-print'), ['student_ids' => [$student->id]])->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertCount(1, Storage::disk('public')->files('qrcodes'));
    }

    public function test_other_roles_do_not_load_admin_redesign_assets(): void
    {
        foreach (['student' => '/home', 'teacher' => '/teacher/dashboard', 'parent' => '/parent/dashboard'] as $role => $url) {
            $user = User::factory()->create(['role' => $role]);
            $response = $this->actingAs($user)->get($url)->assertOk();
            $response->assertDontSee('admin-portal-redesign.css', false)->assertDontSee('admin-workspace.js', false);
        }
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="utf-8" ?>'.$html);
        return new \DOMXPath($document);
    }

    private function capture(string $name, string $html): void
    {
        if (getenv('ADMIN_UI_AUDIT_OUTPUT') !== '1') return;
        $directory = storage_path('framework/testing/admin-ui-audit');
        if (!is_dir($directory)) mkdir($directory, 0775, true);
        file_put_contents($directory.'/'.str_replace('.', '-', $name).'.html', $html);
    }
}
