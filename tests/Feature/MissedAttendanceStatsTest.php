<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\AcademicYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class MissedAttendanceStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_dashboard_calculates_absent_for_unrecorded_scheduled_classes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 18:00:00', 'Asia/Manila')); // Thursday evening

        AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-08-17', // Monday
            'end_date' => '2026-12-18',
            'is_current' => true,
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000002',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $subject = Subject::create([
            'code' => 'CS102',
            'name' => 'Data Structures',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        // Class meets Monday and Wednesday 08:00 - 10:00
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Wednesday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Student did NOT clock in for Monday or Wednesday (0 DB records)
        $response = $this->actingAs($student)->get('/home');

        $response->assertStatus(200);
        // Should have 2 absent counted dynamically
        $response->assertViewHas('totalAbsent', 2);
        $response->assertViewHas('totalPresent', 0);
        $response->assertViewHas('totalLate', 0);
        $response->assertViewHas('attendanceRate', 0);

        Carbon::setTestNow();
    }

    public function test_parent_dashboard_calculates_absent_for_unrecorded_scheduled_classes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 18:00:00', 'Asia/Manila')); // Thursday evening

        AcademicYear::create([
            'name' => '2026-2027',
            'start_date' => '2026-08-17', // Monday
            'end_date' => '2026-12-18',
            'is_current' => true,
        ]);

        $parent = User::factory()->create([
            'role' => 'parent',
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => '2000003',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        $parent->children()->attach($student->id);

        $subject = Subject::create([
            'code' => 'CS103',
            'name' => 'Algorithms',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        // Class meets Monday and Wednesday 08:00 - 10:00
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);
        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Wednesday',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Student did NOT clock in for Monday or Wednesday
        $response = $this->actingAs($parent)->get('/parent/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('childrenData');

        $childrenData = $response->viewData('childrenData');
        $childStats = $childrenData->first();

        $this->assertEquals(2, $childStats->absent);
        $this->assertEquals(0, $childStats->present);
        $this->assertEquals(0, $childStats->late);
        $this->assertEquals(2, $childStats->total);
        $this->assertEquals(0, $childStats->rate);

        Carbon::setTestNow();
    }
}
