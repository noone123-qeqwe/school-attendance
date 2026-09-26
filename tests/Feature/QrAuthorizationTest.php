<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Models\AttendanceSession;
use App\Jobs\SendTeacherSessionNotification;
use App\Jobs\SendTeacherAttendanceNotification;
use App\Events\TeacherAttendanceUpdated;
use App\Models\AttendanceQrToken;
use App\Services\AttendanceQrTokenService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class QrAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_cannot_start_session_for_unassigned_subject()
    {
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher1->id,
            'code' => 'TEST101'
        ]);

        // Teacher 2 tries to start session for Teacher 1's subject
        $response = $this->actingAs($teacher2)->postJson('/teacher/qr/start', [
            'subject_code' => $subject->code
        ]);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_stop_unowned_session()
    {
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);

        $subject = Subject::factory()->create([
            'instructor_id' => $teacher1->id,
            'code' => 'TEST101'
        ]);

        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $teacher1->id,
            'active' => true
        ]);

        // Teacher 2 tries to stop session
        $response = $this->actingAs($teacher2)->postJson('/teacher/qr/stop', [
            'session_id' => $session->id
        ]);

        $response->assertStatus(403);
    }

    public function test_matching_teacher_name_does_not_grant_class_control(): void
    {
        $owner = User::factory()->create(['role' => 'teacher', 'name' => 'Same Name']);
        $other = User::factory()->create(['role' => 'teacher', 'name' => 'Same Name']);
        $subject = Subject::factory()->create([
            'instructor_id' => $owner->id,
            'instructor' => 'Same Name',
        ]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $owner->id,
            'active' => true,
        ]);

        $this->actingAs($other)->postJson(route('teacher.qr.stop'), ['session_id' => $session->id])
            ->assertForbidden();
        $this->actingAs($other)->postJson(route('teacher.qr.start'), [
            'subject_code' => $subject->code,
        ])->assertForbidden();
    }

    public function test_teacher_cannot_read_another_teachers_live_clockins()
    {
        $owner = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $owner->id]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $owner->id,
        ]);

        $this->actingAs($other)->getJson(route('teacher.qr.clockins', [
            'session_id' => $session->id,
        ]))->assertForbidden();
    }

    public function test_teacher_qr_page_has_distinct_navigation_and_live_roster_ids(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $html = $this->actingAs($teacher)->get(route('teacher.qr', $subject->code))
            ->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'id="sidebar"'));
        $this->assertSame(1, substr_count($html, 'id="qrLiveSidebar"'));
    }

    public function test_teacher_can_extend_an_active_session_and_change_is_audited(): void
    {
        Queue::fake();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $teacher->id,
            'active' => true,
            'session_ends_at' => now()->addMinutes(20),
        ]);
        $originalEnd = $session->session_ends_at->timestamp;

        $this->actingAs($teacher)->postJson(route('teacher.qr.extend', $session), [
            'minutes' => 10,
        ])->assertOk()->assertJsonPath('session_end', $originalEnd + 600);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => AttendanceSession::class,
            'subject_id' => $session->id,
            'description' => 'attendance_session_extended',
        ]);
        Queue::assertPushed(SendTeacherSessionNotification::class, fn ($job) =>
            $job->sessionId === $session->id && $job->changeType === 'extended'
        );
    }

    public function test_only_class_teacher_can_extend_and_closed_session_cannot_be_reopened(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $teacher->id,
            'active' => true,
            'session_ends_at' => now()->addMinutes(20),
        ]);

        $this->actingAs($other)->postJson(route('teacher.qr.extend', $session), ['minutes' => 10])
            ->assertForbidden();
        $this->actingAs($teacher)->postJson(route('teacher.qr.extend', $session), ['minutes' => 0])
            ->assertUnprocessable();
        $session->update(['active' => false]);
        $this->actingAs($teacher)->postJson(route('teacher.qr.extend', $session), ['minutes' => 10])
            ->assertUnprocessable();
    }

    public function test_closing_session_invalidates_signed_qr_and_records_audit(): void
    {
        Queue::fake();
        $teacher = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code,
            'created_by' => $teacher->id,
            'active' => true,
            'session_ends_at' => now()->addMinutes(20),
        ]);
        $token = $this->actingAs($teacher)->postJson(route('teacher.qr.emergency', $session))
            ->assertOk()->json('token');

        $this->actingAs($teacher)->postJson(route('teacher.qr.stop'), [
            'session_id' => $session->id,
        ])->assertOk();

        $this->assertFalse($session->fresh()->active);
        $this->assertSame(0, AttendanceQrToken::where('active_session_id', $session->id)->count());
        $this->assertSame('closed', app(AttendanceQrTokenService::class)->inspect($token)['status']);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => AttendanceSession::class,
            'subject_id' => $session->id,
            'description' => 'attendance_session_closed',
        ]);
    }

    public function test_only_owner_can_read_ordered_session_activity(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $other = User::factory()->create(['role' => 'teacher']);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id]);
        $session = AttendanceSession::factory()->create([
            'subject_code' => $subject->code, 'created_by' => $teacher->id,
        ]);
        activity('attendance-session')->causedBy($teacher)->performedOn($session)
            ->log('attendance_session_started');
        activity('attendance-qr')->causedBy($teacher)->performedOn($session)
            ->log('qr_generated');

        $this->actingAs($other)->getJson(route('teacher.qr.timeline', $session))->assertForbidden();
        $this->actingAs($teacher)->getJson(route('teacher.qr.timeline', $session))
            ->assertOk()->assertJsonCount(2, 'events')
            ->assertJsonPath('events.0.action', 'qr_generated')
            ->assertJsonPath('events.1.action', 'attendance_session_started');
    }

    public function test_attendance_check_in_queues_teacher_notification(): void
    {
        Queue::fake();
        $teacher = User::factory()->create(['role' => 'teacher']);

        TeacherAttendanceUpdated::dispatch(
            $teacher->id, 'Student Name', 'CS101', 'Present', 'clock_in'
        );

        Queue::assertPushed(SendTeacherAttendanceNotification::class, fn ($job) =>
            $job->teacherId === $teacher->id && $job->studentName === 'Student Name'
        );
    }

    public function test_unauthorized_user_gets_404_for_nonexistent_session()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Try to stop non-existent session
        $response = $this->actingAs($teacher)->postJson('/teacher/qr/stop', [
            'session_id' => 9999
        ]);

        $response->assertStatus(404);
    }

    public function test_explicitly_enrolled_student_bypasses_mismatch_checks()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        $subject = Subject::factory()->create([
            'code' => 'CS201',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS'
        ]);

        // Explicitly enroll the student
        $student->enrolledSubjects()->attach($subject->id);

        $this->assertTrue($student->getAllSubjects()->contains('id', $subject->id));

        $controller = new \App\Http\Controllers\QrAttendanceController(new \App\Services\QrSessionService());
        $reflector = new \ReflectionClass($controller);
        $method = $reflector->getMethod('scheduleMismatchReason');
        $method->setAccessible(true);

        $reason = $method->invokeArgs($controller, [$subject, $student]);
        $this->assertNull($reason);
    }
}
