<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_academic_workspace_reuses_each_module_route_with_the_same_navigation(): void
    {
        $admin = $this->user('admin', 'super_admin');
        $student = $this->user('student');
        $teacher = $this->user('teacher');
        $subject = Subject::create([
            'code' => 'ENG101', 'name' => 'Writing', 'course' => 'BSCS',
            'year_level' => 1, 'semester' => 1, 'instructor_id' => $teacher->id,
        ]);
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => true]);

        $academicPages = [
            [route('admin.academic.overview'), 'Overview'],
            [route('admin.students'), 'Students'],
            [route('admin.student.create'), 'Students'],
            [route('admin.student', $student), 'Students'],
            [route('admin.student.edit', $student), 'Students'],
            [route('admin.teachers'), 'Teachers'],
            [route('admin.teacher.edit', $teacher), 'Teachers'],
            [route('admin.class-schedules.index'), 'Classes & Sections'],
            [route('admin.sections.index'), 'Classes & Sections'],
            [route('admin.subjects'), 'Classes & Sections'],
            [route('admin.subjects.edit', $subject), 'Classes & Sections'],
            [route('admin.enrollments.index', $subject), 'Classes & Sections'],
            [route('admin.subjects.assistants.index', $subject), 'Classes & Sections'],
            [route('admin.attendance'), 'Attendance'],
            [route('admin.attendance', ['tab' => 'records', 'search' => 'workspace']), 'Attendance'],
            [route('admin.excuses'), 'Attendance'],
            [route('admin.corrections'), 'Attendance'],
        ];

        foreach ($academicPages as [$url, $label]) {
            $response = $this->get($url)->assertOk();
            $dom = new \DOMDocument();
            $dom->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new \DOMXPath($dom);
            $tabs = $xpath->query('//nav[@aria-label="Academic Management tabs"]/a');
            $this->assertSame(['Overview', 'Students', 'Teachers', 'Classes & Sections', 'Attendance'], array_map(
                static fn (\DOMNode $tab) => trim($tab->textContent),
                iterator_to_array($tabs)
            ));
            $this->assertSame($label, trim($xpath->evaluate('string(//nav[@aria-label="Academic Management tabs"]/a[@aria-current])')), $url);
            $sidebar = $xpath->query('//aside[@id="sidebar"]//a[contains(@data-title, "Academic Management")][@aria-current]');
            $this->assertSame(1, $sidebar->length);
            $this->assertSame(route('admin.academic.overview'), $sidebar->item(0)->getAttribute('href'));
            $this->assertSame(0, $xpath->query('//aside[@id="sidebar"]//a[@data-title="Students" or @data-title="Teachers" or @data-title="Attendance records"]')->length);
        }
    }

    public function test_dashboard_and_academic_overview_summarize_recorded_attendance(): void
    {
        $admin = $this->user('admin', 'super_admin');
        $student = $this->user('student');
        $subject = Subject::create([
            'code' => 'ART101',
            'name' => 'Illustration',
            'course' => 'BSCS',
            'year_level' => 1,
            'semester' => 1,
        ]);
        foreach ([0 => 'Present', 1 => 'Late', 2 => 'Absent'] as $offset => $status) {
            Attendance::create([
                'user_id' => $student->id,
                'subject_id' => $subject->id,
                'subject_code' => $subject->code,
                'date' => today()->subDays($offset)->toDateString(),
                'status' => $status,
                'time_in' => '08:00:00',
            ]);
        }

        $this->actingAs($admin)->withSession(['admin_2fa_verified' => true]);
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Attendance trend')
            ->assertSee('100%')
            ->assertSee('Live QR sessions')
            ->assertDontSee('class="admin-metrics"', false)
            ->assertDontSee('Items awaiting review')
            ->assertDontSee('Students with low attendance')
            ->assertDontSee('id="adminRecentTitle"', false);
        $this->get(route('admin.academic.overview'))->assertOk()
            ->assertSee('67%')
            ->assertSee('Past 30 days')
            ->assertSee('Recent schedule changes');
    }

    public function test_permissions_and_notification_filters_are_preserved_across_the_workspace(): void
    {
        $this->get(route('admin.academic.overview'))->assertRedirect(route('login'));
        $this->actingAs($this->user('student'))->get(route('admin.academic.overview'))->assertRedirect(route('home'));
        $this->flushSession();
        $admin = $this->user('admin', 'auditor');
        $this->actingAs($admin)->withSession(['admin_2fa_verified' => false])
            ->get(route('admin.academic.overview'))->assertRedirect(route('admin.2fa.form'));

        Notification::create(['user_id' => $admin->id, 'type' => 'system', 'message' => 'Test timetable ready', 'is_read' => false]);
        Notification::create(['user_id' => $admin->id, 'type' => 'system', 'message' => 'Test audit completed', 'is_read' => false]);
        Notification::create(['user_id' => $admin->id, 'type' => 'system', 'message' => 'Test timetable archived', 'is_read' => true, 'archived_at' => now()]);

        $this->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.notifications', ['q' => 'timetable', 'type' => 'system']))
            ->assertOk()->assertSee('Test timetable ready')->assertDontSee('Test audit completed')->assertDontSee('Test timetable archived')
            ->assertDontSee('Mark all as read');
        $this->get(route('admin.notifications', ['status' => 'archived']))
            ->assertOk()->assertSee('Test timetable archived')->assertDontSee('Test timetable ready');
        $this->postJson(route('admin.notifications.markAllRead'))->assertForbidden();

        $this->flushSession();
        $this->actingAs($this->user('admin', 'data_entry'))
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.academic.overview'))->assertOk()
            ->assertDontSee('data-title="System settings"', false)
            ->assertDontSee('data-title="Audit logs"', false);
        $this->get(route('admin.settings'))->assertForbidden();
        $this->get(route('admin.activity.log'))->assertForbidden();
    }

    public function test_remaining_admin_destinations_render_with_the_shared_navigation(): void
    {
        $this->actingAs($this->user('admin', 'super_admin'))->withSession(['admin_2fa_verified' => true]);

        foreach ([
            'admin.academic-years.index', 'admin.admins', 'admin.admin.create', 'admin.activity.log',
            'admin.announcements.index', 'admin.attendance.preview', 'admin.calendar',
            'admin.courses.index', 'admin.departments.index', 'admin.early-warnings', 'admin.gps',
            'admin.notifications', 'admin.parents.index', 'admin.policies.edit', 'admin.profile', 'admin.qr',
            'admin.reports', 'admin.roles.index', 'admin.settings', 'admin.students.preview',
            'admin.subjects.create', 'admin.system-update.index', 'admin.teacher.create',
        ] as $route) {
            $response = $this->get(route($route));
            $this->assertSame(200, $response->getStatusCode(), $route.' redirected to '.$response->headers->get('Location'));
            $response->assertSee('id="adminSidebarLinks"', false);
        }

        foreach (['backups' => 'admin.backups.index', 'health' => 'admin.system-health.index'] as $tab => $route) {
            $this->get(route($route))->assertRedirect(route('admin.system-update.index', ['tab' => $tab]));
            $this->get(route('admin.system-update.index', ['tab' => $tab]))->assertOk();
        }
    }

    private function user(string $role, ?string $adminRole = null): User
    {
        return User::create([
            'name' => ucfirst($adminRole ?? $role).' Workspace Tester',
            'email' => ($adminRole ?? $role).'.workspace@example.test',
            'student_number' => $role === 'student' ? '2766119' : null,
            'employee_id' => $role !== 'student' ? 'UI-'.strtoupper($adminRole ?? $role) : null,
            'password' => bcrypt('WorkspaceTestOnly26!'),
            'role' => $role,
            'admin_sub_role' => $adminRole,
            'is_active' => true,
        ]);
    }
}
