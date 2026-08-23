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
}
