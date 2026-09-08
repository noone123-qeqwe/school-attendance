<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherLiveQrAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student1;
    private User $student2;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'teacher']);
        
        $this->student1 = User::factory()->create([
            'role' => 'student',
            'name' => 'Alice Student',
            'student_number' => '2024-0001',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '2A',
        ]);

        $this->student2 = User::factory()->create([
            'role' => 'student',
            'name' => 'Bob Student',
            'student_number' => '2024-0002',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '2A',
        ]);

        $this->subject = Subject::factory()->create([
            'instructor_id' => $this->teacher->id,
            'code' => 'CS202',
            'name' => 'Data Structures & Algorithms',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => '2A',
        ]);

        $this->student1->enrolledSubjects()->attach($this->subject->id);
        $this->student2->enrolledSubjects()->attach($this->subject->id);
    }

    public function test_teacher_qr_page_renders_with_live_monitor_controls_and_projector_modal(): void
    {
        $response = $this->actingAs($this->teacher)->get(route('teacher.qr', ['subjectCode' => $this->subject->code]));

        $response->assertOk();
        $response->assertSee('Data Structures & Algorithms');
        $response->assertSee('CS202');

        // Main controls & buttons
        $response->assertSee('id="startBtn"', false);
        $response->assertSee('id="refreshBtn"', false);
        $response->assertSee('id="stopBtn"', false);
        $response->assertSee('id="soundToggleBtn"', false);
        $response->assertSee('id="projectorBtn"', false);
        $response->assertSee('id="copyLinkBtn"', false);

        // Live stats grid & roster filter pills
        $response->assertSee('id="totalStudents"', false);
        $response->assertSee('id="clockedIn"', false);
        $response->assertSee('id="lateCount"', false);
        $response->assertSee('id="absentCount"', false);
        $response->assertSee('id="progressPercent"', false);
        $response->assertSee('id="rosterSearch"', false);
        $response->assertSee('countPillPresent', false);
        $response->assertSee('countPillLate', false);
        $response->assertSee('countPillMissing', false);

        // Projector mode modal & ticker
        $response->assertSee('id="projectorModal"', false);
        $response->assertSee('id="projectorQrCode"', false);
        $response->assertSee('id="projectorSessionCode"', false);
        $response->assertSee('id="projectorTickerWrapper"', false);
        $response->assertSee('id="projectorRecentChips"', false);
    }

    public function test_teacher_can_start_session_and_retrieve_clockins_with_enhanced_stats(): void
    {
        $startResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/start', [
            'subject_code' => $this->subject->code,
            'classroom_lat' => 14.5000,
            'classroom_lng' => 121.0000,
        ]);

        $startResponse->assertOk();
        $this->assertTrue($startResponse->json('success'));
        $sessionId = $startResponse->json('session_id');
        $this->assertNotEmpty($sessionId);

        // Student 1 clocks in
        Attendance::create([
            'user_id' => $this->student1->id,
            'subject_code' => $this->subject->code,
            'date' => today()->toDateString(),
            'status' => 'Present',
            'time_in' => '09:05:00',
            'method' => 'qr',
        ]);

        // Teacher requests live clock-ins feed
        $clockinsResponse = $this->actingAs($this->teacher)->getJson("/teacher/qr/clockins?session_id={$sessionId}");

        $clockinsResponse->assertOk();
        $data = $clockinsResponse->json();

        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('clockins', $data);

        // Verify stats breakdown
        $this->assertEquals(2, $data['stats']['total_students']);
        $this->assertEquals(1, $data['stats']['clocked_in']);
        $this->assertEquals(1, $data['stats']['present']);
        $this->assertEquals(0, $data['stats']['late']);
        $this->assertEquals(1, $data['stats']['absent']);
        $this->assertEquals(50, $data['stats']['progress']);

        // Verify roster list includes both students with their statuses
        $names = array_column($data['clockins'], 'name');
        $this->assertContains('Alice Student', $names);
        $this->assertContains('Bob Student', $names);

        $alice = collect($data['clockins'])->firstWhere('name', 'Alice Student');
        $this->assertEquals('Present', $alice['status']);

        $bob = collect($data['clockins'])->firstWhere('name', 'Bob Student');
        $this->assertEquals('Missing', $bob['status']);
    }

    public function test_teacher_can_override_student_attendance_status(): void
    {
        $session = AttendanceSession::factory()->create([
            'subject_code' => $this->subject->code,
            'created_by' => $this->teacher->id,
            'active' => true,
        ]);

        // Override Bob Student to 'Present'
        $overrideResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/override', [
            'session_id' => $session->id,
            'student_id' => $this->student2->id,
            'status' => 'Present',
        ]);

        $overrideResponse->assertOk();
        $this->assertTrue($overrideResponse->json('success'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student2->id,
            'subject_code' => $this->subject->code,
            'status' => 'Present',
        ]);

        // Change Bob to 'Late'
        $lateResponse = $this->actingAs($this->teacher)->postJson('/teacher/qr/override', [
            'session_id' => $session->id,
            'student_id' => $this->student2->id,
            'status' => 'Late',
        ]);

        $lateResponse->assertOk();
        $this->assertTrue($lateResponse->json('success'));

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student2->id,
            'subject_code' => $this->subject->code,
            'status' => 'Late',
        ]);
    }
}
