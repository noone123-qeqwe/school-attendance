<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineAttendanceSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createTeacherWithSubject(): array
    {
        $teacher = User::factory()->create([
            'role' => 'teacher',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to Computer Science',
            'instructor_id' => $teacher->id,
            'year_level' => 1,
            'semester' => '1st',
        ]);

        $students = User::factory()->count(3)->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        return [$teacher, $subject, $students];
    }

    public function test_teacher_can_sync_a_batch_of_offline_records()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();

        $records = $students->map(function ($student, $i) use ($subject) {
            return [
                'local_id' => $i + 1,
                'subject_code' => $subject->code,
                'user_id' => $student->id,
                'status' => $i === 0 ? 'Present' : ($i === 1 ? 'Late' : 'Absent'),
                'date' => '2026-09-22',
            ];
        })->values()->toArray();

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => $records]
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'synced',
                'skipped',
                'failed',
                'summary' => ['total', 'synced', 'skipped', 'failed'],
            ])
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 3,
                    'synced' => 3,
                    'skipped' => 0,
                    'failed' => 0,
                ],
            ]);

        // Verify records in database
        foreach ($students as $i => $student) {
            $this->assertDatabaseHas('attendances', [
                'user_id' => $student->id,
                'subject_code' => $subject->code,
                'date' => '2026-09-22',
                'method' => 'offline_sync',
            ]);
        }
    }

    public function test_duplicate_records_are_skipped_when_existing_method_is_not_offline()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();
        $student = $students->first();

        // Create an existing record via QR scan
        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => '2026-09-22',
            'status' => 'Present',
            'method' => 'qr_scan',
        ]);

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => [[
                'local_id' => 1,
                'subject_code' => $subject->code,
                'user_id' => $student->id,
                'status' => 'Late',
                'date' => '2026-09-22',
            ]]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 1,
                    'synced' => 0,
                    'skipped' => 1,
                    'failed' => 0,
                ],
            ]);

        // Original record should be unchanged
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => '2026-09-22',
            'status' => 'Present',
            'method' => 'qr_scan',
        ]);
    }

    public function test_offline_sync_can_update_existing_offline_records()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();
        $student = $students->first();

        // Create an existing offline record
        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => '2026-09-22',
            'status' => 'Absent',
            'method' => 'offline_sync',
        ]);

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => [[
                'local_id' => 1,
                'subject_code' => $subject->code,
                'user_id' => $student->id,
                'status' => 'Present',
                'date' => '2026-09-22',
            ]]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => ['synced' => 1],
            ]);

        // Record should be updated
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => '2026-09-22',
            'status' => 'Present',
            'method' => 'offline_sync',
        ]);
    }

    public function test_non_teacher_users_cannot_access_sync_endpoint()
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $response = $this->actingAs($student)->postJson(
            route('teacher.offline.sync'),
            ['records' => [['subject_code' => 'CS101', 'user_id' => 1, 'status' => 'Present', 'date' => '2026-09-22']]]
        );

        // The 'teacher' middleware on the route group redirects non-teacher users
        $response->assertStatus(302);
    }

    public function test_teacher_cannot_sync_for_subjects_they_do_not_own()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();

        // Create another teacher's subject
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $otherSubject = Subject::create([
            'code' => 'MATH201',
            'name' => 'Calculus II',
            'instructor_id' => $otherTeacher->id,
            'year_level' => 2,
            'semester' => '1st',
        ]);

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => [[
                'local_id' => 1,
                'subject_code' => 'MATH201',
                'user_id' => $students->first()->id,
                'status' => 'Present',
                'date' => '2026-09-22',
            ]]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 1,
                    'synced' => 0,
                    'failed' => 1,
                ],
            ]);

        // Verify no record was created
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $students->first()->id,
            'subject_code' => 'MATH201',
        ]);
    }

    public function test_invalid_records_are_rejected()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => [
                // Missing required fields
                ['local_id' => 1, 'subject_code' => 'CS101'],
                // Invalid status
                ['local_id' => 2, 'subject_code' => 'CS101', 'user_id' => $students->first()->id, 'status' => 'Invalid', 'date' => '2026-09-22'],
                // Valid record
                ['local_id' => 3, 'subject_code' => 'CS101', 'user_id' => $students->first()->id, 'status' => 'Present', 'date' => '2026-09-22'],
            ]]
        );

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 3,
                    'synced' => 1,
                    'failed' => 2,
                ],
            ]);
    }

    public function test_empty_records_array_is_rejected()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();

        $response = $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => []]
        );

        $response->assertStatus(422);
    }

    public function test_teacher_can_fetch_roster_for_offline_caching()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();

        $response = $this->actingAs($teacher)->getJson(
            route('teacher.offline.roster')
        );

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'teacher_id',
                'subjects' => [
                    ['code', 'name', 'students'],
                ],
            ])
            ->assertJson([
                'success' => true,
                'teacher_id' => $teacher->id,
            ]);
    }

    public function test_ping_endpoint_returns_pong()
    {
        $response = $this->getJson('/api/ping');

        $response->assertOk()
            ->assertJson(['pong' => true]);
    }

    public function test_synced_records_have_method_set_to_offline_sync()
    {
        [$teacher, $subject, $students] = $this->createTeacherWithSubject();
        $student = $students->first();

        $this->actingAs($teacher)->postJson(
            route('teacher.offline.sync'),
            ['records' => [[
                'local_id' => 1,
                'subject_code' => $subject->code,
                'user_id' => $student->id,
                'status' => 'Present',
                'date' => '2026-09-22',
                'time' => '08:30:00',
                'subject_name' => 'Intro to Computer Science',
            ]]]
        );

        $attendance = Attendance::where('user_id', $student->id)
            ->where('subject_code', $subject->code)
            ->where('date', '2026-09-22')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertEquals('offline_sync', $attendance->method);
        $this->assertEquals('Present', $attendance->status);
        $this->assertEquals('08:30:00', $attendance->time_in);
        $this->assertEquals('Intro to Computer Science', $attendance->subject_name);
    }
}
