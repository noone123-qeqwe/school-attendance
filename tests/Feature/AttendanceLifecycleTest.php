<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Holiday;

class AttendanceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create teacher
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        
        // Create student
        $this->student = User::factory()->create([
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A'
        ]);
        
        // Create subject
        $this->subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Intro to CS',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $this->teacher->id
        ]);
    }

    public function test_mark_absent_command_respects_schedule_end_time()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 14:00:00', 'Asia/Manila'));
        $now = now('Asia/Manila');
        $dayName = $now->format('l');
        
        // Schedule ended 1 hour ago (12:00 to 13:00)
        $schedule = Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => $dayName,
            'start_time' => $now->copy()->subHours(2)->format('H:i:s'),
            'end_time' => $now->copy()->subHours(1)->format('H:i:s'),
        ]);

        Artisan::call('attendance:mark-absent');

        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
            'status' => 'Absent'
        ]);

        Carbon::setTestNow();
    }

    public function test_mark_absent_command_does_not_mark_before_class_ends()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 14:00:00', 'Asia/Manila'));
        $now = now('Asia/Manila');
        $dayName = $now->format('l');
        
        // Schedule ends in 1 hour (13:00 to 15:00)
        $schedule = Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => $dayName,
            'start_time' => $now->copy()->subHours(1)->format('H:i:s'),
            'end_time' => $now->copy()->addHours(1)->format('H:i:s'),
        ]);

        Artisan::call('attendance:mark-absent');

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
        ]);

        Carbon::setTestNow();
    }

    public function test_mark_absent_command_respects_holidays()
    {
        Carbon::setTestNow(Carbon::parse('2026-08-25 14:00:00', 'Asia/Manila'));
        $now = now('Asia/Manila');
        $dayName = $now->format('l');
        
        Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => $dayName,
            'start_time' => $now->copy()->subHours(2)->format('H:i:s'),
            'end_time' => $now->copy()->subHours(1)->format('H:i:s'),
        ]);

        // Create holiday for today
        Holiday::create([
            'name' => 'Test Holiday',
            'date' => $now->toDateString(),
            'description' => 'A test holiday',
            'created_by' => $this->teacher->id,
            'is_active' => true,
        ]);

        Artisan::call('attendance:mark-absent');

        $this->assertDatabaseMissing('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
        ]);

        Carbon::setTestNow();
    }
    
    public function test_attendance_auto_close_enforces_unique_constraint()
    {
        $now = now('Asia/Manila');
        
        // Create an expired session
        $session = AttendanceSession::create([
            'subject_code' => $this->subject->code,
            'created_by' => $this->teacher->id,
            'token' => 'test_token',
            'active' => true,
            'session_ends_at' => $now->copy()->subMinutes(10),
            'expires_at' => $now->copy()->addDays(1),
        ]);
        
        // Manually create an attendance record
        Attendance::create([
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
            'subject_id' => $this->subject->id,
            'date' => $now->toDateString(),
            'status' => 'Present',
            'time_in' => $now->copy()->subMinutes(30)
        ]);
        
        // Run auto-close service
        Artisan::call('attendance:auto-close');
        
        // Should still be Present, not overwritten by Absent
        $this->assertDatabaseHas('attendances', [
            'user_id' => $this->student->id,
            'subject_code' => $this->subject->code,
            'status' => 'Present'
        ]);
        
        // Should not have multiple records for the same day (enforced by DB unique index anyway)
        $this->assertEquals(1, Attendance::where('user_id', $this->student->id)
            ->where('subject_code', $this->subject->code)
            ->whereDate('date', $now->toDateString())
            ->count());
    }
}
