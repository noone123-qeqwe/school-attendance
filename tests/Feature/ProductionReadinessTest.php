<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ProductionReadinessTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $teacher;
    protected $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'employee_id' => 'T-2026-001',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2026-0001',
            'course' => 'BSCS',
            'year_level' => 1,
            'is_active' => true,
        ]);

        AcademicYear::create([
            'name' => '2026-2027',
            'semester' => 1,
            'start_date' => '2026-08-01',
            'end_date' => '2026-12-20',
            'is_current' => true,
        ]);
    }

    /** @test */
    public function active_users_can_authenticate_and_access_protected_routes()
    {
        $response = $this->actingAs($this->student)->get(route('student.classes'));
        $response->assertStatus(200);
    }

    /** @test */
    public function deactivated_users_are_immediately_logged_out_by_middleware()
    {
        $this->student->update(['is_active' => false]);

        $response = $this->actingAs($this->student)->get(route('student.classes'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /** @test */
    public function deactivated_users_cannot_login()
    {
        $this->student->update([
            'password' => Hash::make('secret123'),
            'is_active' => false,
        ]);

        $response = $this->post(route('login.submit'), [
            'identifier' => $this->student->email,
            'password' => 'secret123',
        ]);

        $response->assertSessionHasErrors(['identifier']);
        $this->assertGuest();
    }

    /** @test */
    public function admin_can_deactivate_and_reactivate_student_and_teacher()
    {
        // Deactivate student
        $resDeact = $this->actingAs($this->admin)->patch(route('admin.student.deactivate', $this->student->id));
        $resDeact->assertSessionHas('success');
        $this->assertFalse($this->student->fresh()->isActive());

        // Reactivate student
        $resReact = $this->actingAs($this->admin)->patch(route('admin.student.reactivate', $this->student->id));
        $resReact->assertSessionHas('success');
        $this->assertTrue($this->student->fresh()->isActive());

        // Deactivate teacher
        $resTeachDeact = $this->actingAs($this->admin)->patch(route('admin.teacher.deactivate', $this->teacher->id));
        $resTeachDeact->assertSessionHas('success');
        $this->assertFalse($this->teacher->fresh()->isActive());

        // Reactivate teacher
        $resTeachReact = $this->actingAs($this->admin)->patch(route('admin.teacher.reactivate', $this->teacher->id));
        $resTeachReact->assertSessionHas('success');
        $this->assertTrue($this->teacher->fresh()->isActive());
    }

    /** @test */
    public function admin_can_manage_academic_years_and_switch_active_term()
    {
        $term2 = AcademicYear::create([
            'name' => '2026-2027',
            'semester' => 2,
            'start_date' => '2027-01-10',
            'end_date' => '2027-05-30',
            'is_current' => false,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.academic-years.set-current', $term2->id));
        $response->assertSessionHas('success');

        $this->assertTrue($term2->fresh()->is_current);
        $this->assertEquals('2026-2027', Setting::get('academic_year'));
        $this->assertEquals('2nd Semester', Setting::get('current_semester'));
    }

    /** @test */
    public function admin_can_view_individual_attendance_records_tab()
    {
        $subject = Subject::factory()->create(['code' => 'CS101', 'instructor_id' => $this->teacher->id]);
        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '08:05:00',
            'method' => 'qr',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.attendance', ['tab' => 'records']));
        $response->assertStatus(200);
        $response->assertSee($this->student->name);
        $response->assertSee('CS101');
    }

    /** @test */
    public function admin_can_override_attendance_record_with_audit_trail()
    {
        $subject = Subject::factory()->create(['code' => 'CS102', 'instructor_id' => $this->teacher->id]);
        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Absent',
            'method' => 'qr',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.attendance.override', $attendance->id), [
            'status' => 'Excused',
            'reason' => 'Student presented medical certificate from university clinic.',
        ]);

        $response->assertSessionHas('success');
        $fresh = $attendance->fresh();
        $this->assertTrue((bool)$fresh->excused);
        $this->assertEquals('Student presented medical certificate from university clinic.', $fresh->excuse_note);

        // Verify Spatie audit trail
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Attendance::class,
            'subject_id' => $attendance->id,
            'causer_id' => $this->admin->id,
        ]);
    }

    /** @test */
    public function reports_and_csv_streaming_works_for_admin_and_teacher()
    {
        $subject = Subject::factory()->create(['code' => 'CS103', 'instructor_id' => $this->teacher->id]);
        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => $subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '08:00:00',
        ]);

        // Admin Reports & CSV
        $adminReport = $this->actingAs($this->admin)->get(route('admin.reports', ['type' => 'percentage']));
        $adminReport->assertStatus(200);

        $adminCsv = $this->actingAs($this->admin)->get(route('admin.reports.csv', ['type' => 'percentage']));
        $adminCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $adminCsv->headers->get('content-type'));

        // Teacher Reports & CSV
        $teacherReport = $this->actingAs($this->teacher)->get(route('teacher.reports', ['type' => 'daily']));
        $teacherReport->assertStatus(200);

        $teacherCsv = $this->actingAs($this->teacher)->get(route('teacher.reports.csv', ['type' => 'daily']));
        $teacherCsv->assertStatus(200);
        $this->assertStringContainsString('text/csv', $teacherCsv->headers->get('content-type'));
    }
}
