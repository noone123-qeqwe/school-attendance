<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use App\Models\AttendanceSession;
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
