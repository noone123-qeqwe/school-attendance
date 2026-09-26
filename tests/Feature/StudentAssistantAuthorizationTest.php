<?php

namespace Tests\Feature;

use App\Models\AttendanceSession;
use App\Models\ClassStudentAssistant;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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

        $this->session->update(['active' => true, 'session_ends_at' => now()->subSecond()]);
        $this->actingAs($this->assistant)->get($this->sessionUrl())->assertForbidden();
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
}
