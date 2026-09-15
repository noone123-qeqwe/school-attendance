<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
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

    // ─────────────────────────────────────────
    // DASHBOARD ACCESS
    // ─────────────────────────────────────────

    public function test_student_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    public function test_dashboard_shows_student_name(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);
        $response->assertSee($this->student->name);
    }

    // ─────────────────────────────────────────
    // ATTENDANCE STATS
    // ─────────────────────────────────────────

    public function test_dashboard_shows_attendance_counts(): void
    {
        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        // Create some attendance records
        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => 'CS101',
            'status' => 'Present',
            'date' => today(),
            'time_in' => now()->format('H:i:s'),
        ]);

        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => 'CS101',
            'status' => 'Late',
            'date' => today()->subDay(),
            'time_in' => now()->format('H:i:s'),
        ]);

        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => 'CS101',
            'status' => 'Absent',
            'date' => today()->subDays(2),
        ]);

        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);
        // The dashboard should render without errors even with attendance data
        $response->assertSee('Present');
    }

    public function test_dashboard_renders_with_no_attendance_records(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        // Should still render successfully with zero records
        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // MY CLASSES
    // ─────────────────────────────────────────

    public function test_student_can_view_my_classes(): void
    {
        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
    }

    public function test_my_classes_shows_matching_subjects(): void
    {
        $subject = Subject::create([
            'code' => 'CS201',
            'name' => 'Data Structures',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertSee('Data Structures');
    }

    public function test_my_classes_does_not_show_other_year_subjects(): void
    {
        Subject::create([
            'code' => 'CS401',
            'name' => 'Thesis Writing',
            'year_level' => 4,
            'semester' => 2,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertDontSee('Thesis Writing');
    }

    public function test_my_classes_does_not_show_other_course_subjects(): void
    {
        Subject::create([
            'code' => 'IT201',
            'name' => 'Information Tech Basic',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSIT',
        ]);

        $response = $this->actingAs($this->student)->get('/my-classes');

        $response->assertStatus(200);
        $response->assertDontSee('Information Tech Basic');
    }

    // ─────────────────────────────────────────
    // SETTINGS
    // ─────────────────────────────────────────

    public function test_student_can_view_settings(): void
    {
        $response = $this->actingAs($this->student)->get('/settings');

        $response->assertStatus(200);
    }

    // ─────────────────────────────────────────
    // EXCUSE SUBMISSION WITH SUBJECT SELECTION
    // ─────────────────────────────────────────

    public function test_student_can_view_excuse_submission_form_with_enrolled_subjects(): void
    {
        $subject = Subject::create([
            'code' => 'CS201',
            'name' => 'Data Structures',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->student)->get('/excuses/general/new');

        $response->assertStatus(200);
        $response->assertSee('Data Structures');
        $response->assertSee('CS201');
    }

    public function test_student_can_submit_excuse_for_selected_subjects(): void
    {
        $sub1 = Subject::create([
            'code' => 'CS201',
            'name' => 'Data Structures',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $sub2 = Subject::create([
            'code' => 'CS202',
            'name' => 'Algorithms',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $response = $this->actingAs($this->student)->post('/excuses/general/store', [
            'subject_codes' => ['CS201', 'CS202'],
            'date' => '2026-08-24',
            'reason' => 'Medical/Health Issues',
            'description' => 'Had a severe fever and medical checkup.',
        ]);

        $response->assertRedirect('/excuses');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => 'CS201',
            'date' => '2026-08-24',
        ]);

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => 'CS202',
            'date' => '2026-08-24',
        ]);

        $this->assertEquals(2, \App\Models\ExcuseSubmission::where('user_id', $this->student->id)->count());
    }

    public function test_dashboard_renders_responsive_attendance_warning_card(): void
    {
        \App\Models\Warning::create([
            'user_id' => $this->student->id,
            'subject_code' => 'SDM',
            'type' => 'warning_consecutive_3',
            'message' => '🚨 URGENT: You have been absent for 3 consecutive sessions in Software Deployment.',
            'sent_by' => $this->student->id,
        ]);

        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);
        $response->assertSee('mobile-warning-section');
        $response->assertSee('Action Required: Attendance Warning');
        $response->assertSee('You have 1 active warning(s)');
        $response->assertSee('SDM');
        $response->assertSee('🚨 URGENT: You have been absent for 3 consecutive sessions in Software Deployment.');
        $response->assertSee('Submit Excuse');
        $response->assertSee('View Records');

        // Check CSS rules for mobile responsiveness
        $content = $response->getContent();
        $this->assertStringContainsString('flex-direction: column !important;', $content);
        $this->assertStringContainsString('overflow-wrap: break-word !important;', $content);
        $this->assertStringContainsString('word-break: normal !important;', $content);
    }

    // ─────────────────────────────────────────
    // ATTENDANCE CALENDAR & EVENTS INTEGRATION
    // ─────────────────────────────────────────

    public function test_upcoming_events_section_is_removed_and_schedule_is_full_width(): void
    {
        $response = $this->actingAs($this->student)->get('/home');

        $response->assertStatus(200);
        // Verify separate Upcoming Events section is completely removed
        $response->assertDontSee('Upcoming Events');
        $response->assertDontSee('Full Calendar');
        $response->assertDontSee('No upcoming events or holidays');

        // Verify Today's Schedule takes full width (col-12)
        $content = $response->getContent();
        $this->assertStringContainsString('<div class="col-12">', $content);
        $this->assertStringContainsString("Today's Schedule", $content);
    }

    public function test_attendance_calendar_integrates_events_exams_and_holidays(): void
    {
        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Mathematics',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        // 1. Attendance record on Sept 14, 2026
        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => 'CS101',
            'status' => 'Present',
            'date' => '2026-09-14',
            'time_in' => '07:52:00',
        ]);

        // 2. School Event on Sept 18, 2026
        \App\Models\Event::create([
            'name' => 'Science Fair',
            'description' => 'Annual Science Exhibition',
            'date' => '2026-09-18',
            'start_time' => '09:00:00',
            'end_time' => '16:00:00',
            'location' => 'School Gym',
            'type' => 'school_event',
            'status' => 'scheduled',
            'created_by' => $this->student->id,
        ]);

        // 3. Exam on Sept 19, 2026 (linked to subject)
        \App\Models\Event::create([
            'name' => 'Midterm Examination',
            'description' => 'Mathematics Midterm Test',
            'date' => '2026-09-19',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'class_id' => $subject->id,
            'type' => 'exam',
            'status' => 'scheduled',
            'created_by' => $this->student->id,
        ]);

        // 4. Holiday on Sept 20, 2026
        \App\Models\Holiday::create([
            'name' => 'National Holiday',
            'description' => 'No classes',
            'date' => '2026-09-20',
            'type' => 'national',
            'is_active' => true,
            'created_by' => $this->student->id,
        ]);

        $response = $this->actingAs($this->student)->get('/home?cal_year=2026&cal_month=9');
        $response->assertStatus(200);

        $content = $response->getContent();

        // Check Attendance Calendar title & View Records button
        $this->assertStringContainsString('Attendance Calendar', $content);
        $this->assertStringContainsString('View Records', $content);

        // Check calendar legend indicators
        $this->assertStringContainsString('Present', $content);
        $this->assertStringContainsString('Late', $content);
        $this->assertStringContainsString('Absent', $content);
        $this->assertStringContainsString('Exam', $content);
        $this->assertStringContainsString('Event', $content);
        $this->assertStringContainsString('Holiday', $content);

        // Check dot indicators in calendar
        $this->assertStringContainsString('dot-present', $content);
        $this->assertStringContainsString('dot-event', $content);
        $this->assertStringContainsString('dot-exam', $content);
        $this->assertStringContainsString('dot-holiday', $content);

        // Check day cell status classes
        $this->assertStringContainsString('status-event', $content);
        $this->assertStringContainsString('status-exam', $content);
        $this->assertStringContainsString('status-holiday', $content);

        // Check events map JSON payload passed to JavaScript
        $this->assertStringContainsString('Science Fair', $content);
        $this->assertStringContainsString('School Gym', $content);
        $this->assertStringContainsString('Midterm Examination', $content);
        $this->assertStringContainsString('National Holiday', $content);

        // Check Day Summary Inspector modal structure
        $this->assertStringContainsString('daySummaryModal', $content);
        $this->assertStringContainsString('No records or events for this date.', $content);
        $this->assertStringContainsString('EVENTS / IMPORTANT DATES', $content);
        $this->assertStringContainsString('ATTENDANCE', $content);
    }

    public function test_student_attendance_calendar_dedicated_page_renders_events(): void
    {
        \App\Models\Holiday::create([
            'name' => 'Special Non-Working Holiday',
            'description' => 'No classes today',
            'date' => '2026-09-21',
            'type' => 'national',
            'is_active' => true,
            'created_by' => $this->student->id,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.attendance.calendar', ['cal_year' => 2026, 'cal_month' => 9]));
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringContainsString('Attendance Calendar', $content);
        $this->assertStringContainsString('Special Non-Working Holiday', $content);
        $this->assertStringContainsString('att-cal-dot holiday', $content);
        $this->assertStringContainsString('No records or events for this date.', $content);
    }
}

