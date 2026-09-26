<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\AttendanceQrToken;
use App\Models\ClassStudentAssistant;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\AttendanceQrTokenService;
use App\Jobs\SendTeacherQrNotification;
use Illuminate\Support\Facades\Queue;

class StudentAssistantAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $assistant;
    private Subject $subject;
    private AttendanceSession $session;
    private ClassStudentAssistant $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->assistant = User::factory()->create([
            'role' => 'student', 'is_active' => true,
            'course' => 'BSCS', 'section' => '3A', 'year_level' => 3, 'semester' => 1,
        ]);
        $this->subject = Subject::factory()->create([
            'instructor_id' => $this->teacher->id, 'course' => 'BSCS',
            'section' => '3A', 'year_level' => 3, 'semester' => 1,
        ]);
        $this->subject->enrolledStudents()->attach($this->assistant->id);
        Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => now('Asia/Manila')->format('l'),
            'start_time' => now('Asia/Manila')->subMinutes(10)->format('H:i:s'),
            'end_time' => now('Asia/Manila')->addMinutes(50)->format('H:i:s'),
        ]);
        $this->session = AttendanceSession::factory()->create([
            'subject_code' => $this->subject->code,
            'created_by' => $this->teacher->id,
            'session_ends_at' => now()->addMinutes(50),
        ]);
        $this->assignment = ClassStudentAssistant::create([
            'subject_id' => $this->subject->id,
            'student_id' => $this->assistant->id,
            'assigned_by_teacher_id' => $this->teacher->id,
            'active_slot' => 1,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    private function sessionUrl(?AttendanceSession $session = null): string
    {
        return route('student-assistant.sessions.show', $session ?: $this->session);
    }

    public function test_assigned_student_can_view_only_basic_active_session_status(): void
    {
        $this->actingAs($this->assistant)->get(route('student-assistant.classes'))
            ->assertOk()->assertSee($this->subject->name);
        $this->actingAs($this->assistant)->get($this->sessionUrl())
            ->assertOk()->assertSee('Student Assistant')->assertDontSee('Attendance History');
    }

    public function test_unassigned_or_other_class_student_cannot_open_session(): void
    {
        $other = User::factory()->create(['role' => 'student']);
        $this->actingAs($other)->get($this->sessionUrl())->assertForbidden();

        $otherSubject = Subject::factory()->create(['instructor_id' => $this->teacher->id]);
        $otherSession = AttendanceSession::factory()->create([
            'subject_code' => $otherSubject->code, 'created_by' => $this->teacher->id,
        ]);
        $this->actingAs($this->assistant)->get($this->sessionUrl($otherSession))->assertForbidden();
    }

    public function test_revoked_expired_or_inactive_account_is_denied(): void
    {
        $this->assignment->update(['revoked_at' => now(), 'active_slot' => null]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();

        $this->assignment->update(['revoked_at' => null, 'active_slot' => 1, 'expires_at' => now()->subMinute()]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();

        $this->assignment->update(['expires_at' => now()->addMonth()]);
        $this->assistant->update(['is_active' => false]);
        $this->assertFalse($this->assistant->fresh()->can('viewAssistantQr', $this->session));
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertStatus(302);
    }

    public function test_closed_or_ended_session_is_denied(): void
    {
        $this->session->update(['active' => false]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();
        $this->actingAs($this->assistant)->getJson(route('student-assistant.qr.status', $this->session))
            ->assertStatus(410)->assertJsonPath('message', 'Attendance session has ended.');

        $this->session->update(['active' => true, 'session_ends_at' => now()->subSecond()]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();
        $this->actingAs($this->assistant)->getJson(route('student-assistant.qr.status', $this->session))
            ->assertStatus(410);
    }

    public function test_before_attendance_window_is_denied(): void
    {
        Schedule::where('subject_id', $this->subject->id)->update([
            'start_time' => now('Asia/Manila')->addHour()->format('H:i:s'),
            'end_time' => now('Asia/Manila')->addHours(2)->format('H:i:s'),
        ]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();
    }

    public function test_assistant_cannot_use_teacher_attendance_management_routes(): void
    {
        $this->actingAs($this->assistant)->postJson(route('teacher.qr.stop'), [
            'session_id' => $this->session->id,
        ])->assertStatus(302);
        $this->assertTrue($this->session->fresh()->active);
    }

    public function test_show_current_qr_is_idempotent_and_manual_replacement_invalidates_it(): void
    {
        $url = route('student-assistant.qr.generate', $this->session);
        $first = $this->actingAs($this->assistant)->postJson($url)->assertOk()->json();
        $second = $this->actingAs($this->assistant)->postJson($url)->assertOk()->json();
        $this->assertSame($first['token'], $second['token']);
        $this->assertSame('valid', app(AttendanceQrTokenService::class)->inspect($first['token'])['status']);

        $replacement = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.replace', $this->session)
        )->assertOk()->json();
        $this->assertNotSame($first['token'], $replacement['token']);
        $this->assertSame('replaced', app(AttendanceQrTokenService::class)->inspect($first['token'])['status']);
        $this->assertSame(1, AttendanceQrToken::where('active_session_id', $this->session->id)->count());
    }

    public function test_automatic_rotation_does_not_consume_manual_quota(): void
    {
        config()->set('student_assistants.max_manual_regenerations', 1);
        $first = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertOk()->json('token');
        $this->travel(61)->seconds();
        $next = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.rotate', $this->session)
        )->assertOk()->json('token');
        $this->assertNotSame($first, $next);
        $this->assertSame('replaced', app(AttendanceQrTokenService::class)->inspect($first)['status']);

        $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.replace', $this->session)
        )->assertOk();
        $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.replace', $this->session)
        )->assertStatus(422);
    }

    public function test_tampered_expired_and_revoked_assistant_qr_requests_are_rejected(): void
    {
        $raw = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertOk()->json('token');
        $tampered = substr($raw, 0, -1) . (substr($raw, -1) === 'a' ? 'b' : 'a');
        $this->assertSame('invalid', app(AttendanceQrTokenService::class)->inspect($tampered)['status']);
        $this->travel(61)->seconds();
        $this->assertSame('expired', app(AttendanceQrTokenService::class)->inspect($raw)['status']);

        $this->assignment->update(['revoked_at' => now(), 'active_slot' => null]);
        $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertForbidden();
    }

    public function test_two_assistants_share_one_qr_and_second_can_replace_it(): void
    {
        $second = User::factory()->create([
            'role' => 'student', 'is_active' => true,
            'course' => 'BSCS', 'section' => '3A', 'year_level' => 3, 'semester' => 1,
        ]);
        $this->subject->enrolledStudents()->attach($second->id);
        ClassStudentAssistant::create([
            'subject_id' => $this->subject->id,
            'student_id' => $second->id,
            'assigned_by_teacher_id' => $this->teacher->id,
            'active_slot' => 2,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $first = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertOk()->json();
        $this->actingAs($second)->getJson(route('student-assistant.qr.status', $this->session))
            ->assertOk()->assertJsonPath('token', $first['token']);
        $secondToken = $this->actingAs($second)->postJson(
            route('student-assistant.qr.replace', $this->session)
        )->assertOk()->json('token');

        $this->actingAs($this->assistant)->getJson(route('student-assistant.qr.status', $this->session))
            ->assertOk()->assertJsonPath('token', $secondToken);
        $this->assertSame(1, AttendanceQrToken::where('active_session_id', $this->session->id)->count());
    }

    public function test_replaced_qr_cannot_record_after_scan_started(): void
    {
        $first = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertOk()->json('token');
        $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.replace', $this->session)
        )->assertOk();

        $called = false;
        try {
            app(AttendanceQrTokenService::class)->recordWhileValid($first, $this->session, function () use (&$called) {
                $called = true;
            });
            $this->fail('A replaced QR should be rejected before attendance is written.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertFalse($called);
            $this->assertArrayHasKey('qr', $exception->errors());
        }
    }

    public function test_teacher_can_generate_emergency_qr_without_assistant_approval(): void
    {
        $this->assignment->update(['revoked_at' => now(), 'active_slot' => null]);
        $this->actingAs($this->teacher)->postJson(route('teacher.qr.emergency', $this->session))
            ->assertOk()->assertJsonPath('generator_type', 'teacher');
        $this->assertSame(1, AttendanceQrToken::where('active_session_id', $this->session->id)->count());
    }

    public function test_qr_change_queues_teacher_notification_without_exposing_token(): void
    {
        Queue::fake();
        $response = $this->actingAs($this->assistant)->postJson(
            route('student-assistant.qr.generate', $this->session)
        )->assertOk();

        Queue::assertPushed(SendTeacherQrNotification::class, fn ($job) =>
            $job->teacherId === $this->teacher->id && $job->tokenId === $response->json('token_id')
        );
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => AttendanceSession::class,
            'subject_id' => $this->session->id,
            'description' => 'qr_generated',
        ]);
    }
}
