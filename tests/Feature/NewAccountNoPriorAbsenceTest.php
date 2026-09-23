<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\AcademicYear;
use App\Actions\Attendance\CalculateMissedAttendanceAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;
use Tests\TestCase;

class NewAccountNoPriorAbsenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_newly_registered_student_has_zero_absences_and_zero_missed_classes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-23 15:30:00', 'Asia/Manila')); // Wednesday afternoon

        // Academic year started over 1 month ago
        AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-08-17',
            'end_date' => '2026-12-18',
            'is_current' => true,
        ]);

        $subject = Subject::create([
            'code' => 'CS401',
            'name' => 'Software Engineering',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        // Class has a morning session on Wednesday 08:00 - 10:00
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Wednesday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Another session on Monday 08:00 - 10:00
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Student creates account at 15:30:00 today (after morning class ended)
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '4000001',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'created_at' => Carbon::parse('2026-09-23 15:30:00', 'Asia/Manila'),
        ]);

        // 1. CalculateMissedAttendanceAction must return 0 misses (not 10+ past weeks)
        $action = app(CalculateMissedAttendanceAction::class);
        $misses = $action->execute($student);
        $this->assertEquals(0, $misses, 'New student must not have missed classes from prior days/months');

        // 2. Student dashboard /home must show 0 totalAbsent and 0 dynamic misses
        $response = $this->actingAs($student)->get('/home');
        $response->assertStatus(200);
        $response->assertViewHas('totalAbsent', 0);
        $response->assertViewHas('totalPresent', 0);
        $response->assertViewHas('totalLate', 0);

        // Schedule item for today morning should be marked past, not missed
        $todaySchedule = $response->viewData('todaySchedule');
        $this->assertNotEmpty($todaySchedule);
        $morningClass = $todaySchedule->firstWhere('subject.code', 'CS401');
        $this->assertNotNull($morningClass);
        $this->assertEquals('past', $morningClass->status);

        // 3. Command attendance:mark-absent must not mark this student absent
        Artisan::call('attendance:mark-absent');
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'status' => 'Absent',
        ]);

        Carbon::setTestNow();
    }

    public function test_auto_close_does_not_mark_new_student_absent_for_earlier_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-23 15:30:00', 'Asia/Manila'));

        $teacher = User::factory()->create(['role' => 'teacher']);

        $subject = Subject::create([
            'code' => 'CS402',
            'name' => 'Network Security',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'instructor_id' => $teacher->id,
        ]);

        // Teacher created an attendance session at 10:00 AM which expired at 11:00 AM
        $session = AttendanceSession::create([
            'subject_code' => 'CS402',
            'created_by' => $teacher->id,
            'token' => 'test_token_123',
            'active' => true,
            'created_at' => Carbon::parse('2026-09-23 10:00:00', 'Asia/Manila'),
            'session_starts_at' => Carbon::parse('2026-09-23 10:00:00', 'Asia/Manila'),
            'session_ends_at' => Carbon::parse('2026-09-23 11:00:00', 'Asia/Manila'),
            'expires_at' => Carbon::parse('2026-09-23 11:00:00', 'Asia/Manila'),
        ]);

        // Student registered at 14:00:00 (after session expired)
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '4000002',
            'year_level' => 4,
            'semester' => 1,
            'course' => 'BSCS',
            'created_at' => Carbon::parse('2026-09-23 14:00:00', 'Asia/Manila'),
        ]);

        // Auto close expired sessions
        $service = app(\App\Services\AttendanceAutoCloseService::class);
        $service->closeExpiredSessions();

        // The new student must NOT be marked absent
        $this->assertDatabaseMissing('attendances', [
            'user_id' => $student->id,
            'subject_code' => 'CS402',
            'status' => 'Absent',
        ]);

        Carbon::setTestNow();
    }
}
