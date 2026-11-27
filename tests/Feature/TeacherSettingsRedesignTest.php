<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherSettingsRedesignTest extends TestCase
{
    use RefreshDatabase;

    private function createSyntheticUser(string $role, array $overrides = []): User
    {
        static $seq = 0;
        $seq++;

        return User::create(array_merge([
            'name' => ucfirst($role) . ' Settings Synthetic ' . $seq,
            'student_number' => $role === 'student' ? '2026-TS-' . $seq : null,
            'employee_id' => $role === 'teacher' ? 'FAC-TS-' . $seq : null,
            'course' => $role === 'student' ? 'BSCS' : 'FACULTY',
            'year_level' => '1',
            'semester' => '1',
            'department' => $role === 'teacher' ? 'Computer Science' : null,
            'position' => $role === 'teacher' ? 'Associate Professor' : null,
            'email' => $role . '.settings.' . $seq . '.' . uniqid() . '@example.test',
            'password' => Hash::make('StrongPass#12345'),
            'role' => $role,
            'is_active' => true,
        ], $overrides));
    }

    public function test_teacher_settings_replaces_regular_sidebar_with_teacher_settings_sidebar_and_restores_on_exit(): void
    {
        $teacher = $this->createSyntheticUser('teacher', [
            'name' => 'Prof. Synthetic Faculty',
            'employee_id' => 'FAC-9901',
            'department' => 'Information Systems',
            'position' => 'Senior Instructor',
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.profile'));
        $response->assertOk();
        $html = $response->getContent();

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new \DOMXPath($dom);

        // Exactly one primary sidebar rendered cleanly with Settings chrome
        $sidebars = $xpath->query('//aside');
        $this->assertSame(1, $sidebars->length);
        $sidebarNode = $xpath->query('//aside[@id="sidebar"]')->item(0);
        $this->assertNotNull($sidebarNode);

        $sidebarText = preg_replace('/\s+/', ' ', $sidebarNode->textContent ?? '');
        $sidebarHtml = $dom->saveHTML($sidebarNode);

        $this->assertStringContainsString('settings-sidebar-brand', $sidebarHtml);
        $this->assertStringContainsString('settings-sidebar-nav', $sidebarHtml);

        // Back to Dashboard returns to teacher dashboard
        $backLinks = $xpath->query('.//a[contains(@class, "settings-sidebar-back-btn")]', $sidebarNode);
        $this->assertSame(1, $backLinks->length);
        $this->assertSame(route('teacher.dashboard'), $backLinks->item(0)->getAttribute('href'));
        $this->assertStringContainsString('Back to Dashboard', $backLinks->item(0)->textContent ?? '');

        // Teacher-specific Settings items present
        foreach ([
            'Account',
            'Profile & faculty details',
            'Security',
            'Password & email',
            'Device passkeys',
            'Recovery codes',
            'Preferences',
            'Notification preferences',
        ] as $expectedItem) {
            $this->assertStringContainsString($expectedItem, $sidebarText);
        }

        // Regular teacher app navigation & student-only settings omitted from Teacher Settings sidebar
        $this->assertStringNotContainsString('My Classes', $sidebarText);
        $this->assertStringNotContainsString('Excuses & Corrections', $sidebarText);
        $this->assertStringNotContainsString('Attendance Reports', $sidebarText);
        $this->assertStringNotContainsString('Guardians', $sidebarText);
        $this->assertStringNotContainsString('Attendance device', $sidebarText);
        $this->assertStringNotContainsString('Biometrics', $sidebarText);

        // Exiting back to Teacher Dashboard or My Classes restores regular Teacher sidebar
        foreach ([route('teacher.dashboard'), route('teacher.classroom.index')] as $teacherAppUrl) {
            $dashHtml = $this->actingAs($teacher)->get($teacherAppUrl)->assertOk()->getContent();
            $dashDom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dashDom->loadHTML($dashHtml);
            libxml_clear_errors();
            $dashXpath = new \DOMXPath($dashDom);
            $dashSidebar = $dashXpath->query('//aside[@id="sidebar"]')->item(0);
            $this->assertNotNull($dashSidebar);
            $dashSidebarHtml = $dashDom->saveHTML($dashSidebar);
            $dashSidebarText = preg_replace('/\s+/', ' ', $dashSidebar->textContent ?? '');

            $this->assertStringContainsString('teacher-sidebar-nav', $dashSidebarHtml);
            $this->assertStringNotContainsString('settings-sidebar-nav', $dashSidebarHtml);
            $this->assertStringNotContainsString('Back to Dashboard', $dashSidebarText);
            $this->assertStringContainsString('My Classes', $dashSidebarText);
        }
    }

    public function test_teacher_settings_preserves_all_features_without_legacy_clutter_and_keeps_student_intact(): void
    {
        $teacher = $this->createSyntheticUser('teacher', [
            'name' => 'Dr. Elena Synthetic',
            'employee_id' => 'FAC-7742',
            'department' => 'Engineering',
            'position' => 'Department Chair',
        ]);
        Subject::create([
            'code' => 'ENG201',
            'name' => 'Circuits II',
            'year_level' => 2,
            'semester' => 1,
            'instructor_id' => $teacher->id,
            'section' => 'A',
        ]);

        $response = $this->actingAs($teacher)->get(route('teacher.profile'));
        $response->assertOk();
        $html = $response->getContent();

        // Modern Student-matching Settings structure & faculty fields preserved
        $response->assertSee('data-teacher-settings="' . $teacher->id . '"', false)
            ->assertSee('id="teacherSettingsContent"', false)
            ->assertSee('Dr. Elena Synthetic')
            ->assertSee('FAC-7742')
            ->assertSee('Engineering')
            ->assertSee('Department Chair')
            ->assertSee('1 class')
            ->assertSee('id="teacherAvatarDisplay"', false)
            ->assertSee(route('otp.email.change'), false)
            ->assertSee(route('otp.change'), false)
            ->assertSee('id="registerFpBtn"', false)
            ->assertSee('id="deviceList"', false)
            ->assertSee('id="webauthnUnsupported"', false)
            ->assertSee('id="generateCodesBtn"', false)
            ->assertSee('id="codesContainer"', false)
            ->assertSee(route('teacher.profile.update'), false)
            ->assertSee('name="notif_email"', false)
            ->assertSee('name="notif_push"', false)
            ->assertSee('teacher-settings.js', false);

        // Legacy brown styles and broken inline handlers removed
        $this->assertStringNotContainsString('#7f432e', $html);
        $this->assertStringNotContainsString('tchRequestOtp', $html);
        $this->assertStringNotContainsString('Guardians', $html);
        $this->assertStringNotContainsString('Attendance device', $html);

        // Student settings remains untouched with Student-specific options
        $this->flushSession();
        $student = $this->createSyntheticUser('student');
        $studentSettingsHtml = $this->actingAs($student)->get(route('settings'))->assertOk()->getContent();
        $this->assertStringContainsString('Guardians', $studentSettingsHtml);
        $this->assertStringContainsString('Attendance device', $studentSettingsHtml);
        $this->assertStringNotContainsString('Profile &amp; faculty details', $studentSettingsHtml);

        // Access control: students cannot access teacher settings route
        $this->actingAs($student)->get(route('teacher.profile'))->assertRedirect();
    }

    public function test_teacher_notification_preferences_persist_across_updates(): void
    {
        $teacher = $this->createSyntheticUser('teacher', [
            'notification_preferences' => ['email' => true, 'push' => true],
        ]);

        // Disable push, keep email enabled
        $this->actingAs($teacher)
            ->post(route('teacher.profile.update'), ['notif_email' => '1'])
            ->assertRedirect(route('teacher.profile'));

        $teacher->refresh();
        $this->assertTrue((bool) ($teacher->notification_preferences['email'] ?? false));
        $this->assertFalse((bool) ($teacher->notification_preferences['push'] ?? true));

        // Disable both (submit empty POST from notification form)
        $this->actingAs($teacher)
            ->post(route('teacher.profile.update'), [])
            ->assertRedirect(route('teacher.profile'));

        $teacher->refresh();
        $this->assertFalse((bool) ($teacher->notification_preferences['email'] ?? true));
        $this->assertFalse((bool) ($teacher->notification_preferences['push'] ?? true));

        // Re-enable both
        $this->actingAs($teacher)
            ->post(route('teacher.profile.update'), ['notif_email' => '1', 'notif_push' => '1'])
            ->assertRedirect(route('teacher.profile'));

        $teacher->refresh();
        $this->assertTrue((bool) ($teacher->notification_preferences['email'] ?? false));
        $this->assertTrue((bool) ($teacher->notification_preferences['push'] ?? false));
    }
}
