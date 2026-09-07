<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Warning;
use App\Models\Notification;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ComprehensiveBugAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2026-00001',
        ]);
    }

    public function test_admin_can_access_academic_years_and_store_term()
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->post(route('admin.academic-years.store'), [
                'name' => '2026-2027',
                'semester' => 1,
                'start_date' => '2026-08-01',
                'end_date' => '2026-12-31',
                'is_current' => 1,
            ]);

        $response->assertRedirect(route('admin.academic-years.index'));
        $this->assertDatabaseHas('academic_years', [
            'name' => '2026-2027',
            'semester' => 1,
            'is_current' => 1,
        ]);
    }

    public function test_admin_admins_page_renders_cleanly_without_undefined_variable()
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.admins'));

        $response->assertStatus(200);
        $response->assertSee('Admins');
        $response->assertSee($this->superAdmin->name);
    }

    public function test_admin_can_export_early_warnings()
    {
        Warning::create([
            'user_id' => $this->student->id,
            'type' => 'chronic_absenteeism',
            'subject_code' => 'CS101',
            'message' => 'Student has 3 consecutive absences',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.early-warnings.export'));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition'), 'early-warnings'));
    }

    public function test_admin_can_export_subjects_pdf()
    {
        Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to Programming',
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.subjects.pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }

    public function test_admin_can_mark_single_notification_as_read()
    {
        $notification = Notification::create([
            'user_id' => $this->superAdmin->id,
            'type' => 'system',
            'message' => 'Test system alert',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->post(route('admin.notifications.markRead', $notification));

        $response->assertRedirect();
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_admin_can_view_and_update_gps_configuration()
    {
        $viewResponse = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->get(route('admin.gps'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('GPS Configuration');

        $updateResponse = $this->actingAs($this->superAdmin)
            ->withSession(['admin_2fa_verified' => true])
            ->post(route('admin.gps.update'), [
                'latitude' => 14.5500,
                'longitude' => 121.0500,
                'radius' => 60,
            ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('14.55', (string)\App\Models\Setting::get('gps_lat'));
        $this->assertEquals('121.05', (string)\App\Models\Setting::get('gps_lng'));
        $this->assertEquals(60, (int)\App\Models\Setting::get('gps_radius'));
    }

    public function test_teacher_can_access_corrections_and_update_status()
    {
        $subject = Subject::create([
            'code' => 'IT101',
            'name' => 'Web Systems',
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSIT',
            'instructor_id' => $this->teacher->id,
        ]);

        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => 'IT101',
            'date' => now()->toDateString(),
            'time_in' => '08:00:00',
            'status' => 'Absent',
        ]);

        $correction = AttendanceCorrection::create([
            'attendance_id' => $attendance->id,
            'student_id' => $this->student->id,
            'reason' => 'Was present but scanner timed out',
            'status' => 'pending',
        ]);

        // Access index
        $indexResponse = $this->actingAs($this->teacher)
            ->get(route('teacher.corrections'));
        $indexResponse->assertStatus(200);

        // Update status
        $updateResponse = $this->actingAs($this->teacher)
            ->post(route('teacher.corrections.update', $correction), [
                'action' => 'approve',
            ]);

        $updateResponse->assertRedirect();
        $this->assertEquals('approved', $correction->fresh()->status);
    }

    public function test_mobile_navigation_routes_redirect_gracefully()
    {
        $response = $this->actingAs($this->student)
            ->get(route('mobile.attendance'));
        $response->assertRedirect(route('student.attendance.calendar'));

        $scanResponse = $this->actingAs($this->student)
            ->get(route('mobile.scan'));
        $scanResponse->assertRedirect(route('home', ['action' => 'scan']));

        $historyResponse = $this->actingAs($this->student)
            ->get(route('mobile.history'));
        $historyResponse->assertRedirect(route('home'));
    }
}
