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
        $response = $this->actingAs($teacher2)->postJson('/attendance/qr/start', [
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
        $response = $this->actingAs($teacher2)->postJson('/attendance/qr/stop', [
            'session_id' => $session->id
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthorized_user_gets_404_for_nonexistent_session()
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        // Try to stop non-existent session
        $response = $this->actingAs($teacher)->postJson('/attendance/qr/stop', [
            'session_id' => 9999
        ]);

        $response->assertStatus(404);
    }
}
