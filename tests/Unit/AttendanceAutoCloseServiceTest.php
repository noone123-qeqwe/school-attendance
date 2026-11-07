<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceAutoCloseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAutoCloseServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_closes_only_expired_sessions_and_marks_enrolled_students_absent_for_the_session_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-07 12:00:00', 'Asia/Manila'));
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create(['role' => 'student', 'created_at' => now()->subDays(5)]);
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'AUTO-101']);
        $subject->enrolledStudents()->attach($student->id, [
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $expired = AttendanceSession::create([
            'subject_code' => $subject->code,
            'created_by' => $teacher->id,
            'token' => 'expired-auto-close-token',
            'expires_at' => now()->subDay(),
            'session_ends_at' => now()->subDay(),
            'active' => true,
        ]);
        $active = AttendanceSession::create([
            'subject_code' => 'OTHER-101',
            'created_by' => $teacher->id,
            'token' => 'active-auto-close-token',
            'expires_at' => now()->addMinutes(15),
            'session_ends_at' => now()->addHour(),
            'active' => true,
        ]);

        $closed = app(AttendanceAutoCloseService::class)->closeExpiredSessions();

        $this->assertSame(1, $closed);
        $this->assertFalse($expired->fresh()->active);
        $this->assertTrue($active->fresh()->active);
        $this->assertDatabaseHas('attendances', [
            'user_id' => $student->id,
            'subject_code' => $subject->code,
            'date' => now()->subDay()->toDateString(),
            'status' => 'Absent',
        ]);
        $this->assertSame(1, Attendance::where('user_id', $student->id)->count());
        Carbon::setTestNow();
    }
}
