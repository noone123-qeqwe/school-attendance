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

    public function test_class_before_explicit_enrollment_is_not_marked_absent_on_either_dashboard(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 11:00:00', 'Asia/Manila'));

        try {
            $student = User::factory()->create([
                'role' => 'student',
                'year_level' => 2,
                'semester' => 1,
                'course' => 'BSCS',
                'section' => 'A',
                'created_at' => Carbon::parse('2026-08-19 07:00:00', 'Asia/Manila'),
            ]);
            $subject = Subject::create([
                'code' => 'LATE-ENROLL',
                'name' => 'Late Enrollment Seminar',
                'year_level' => 3,
                'semester' => 1,
                'course' => 'BSED',
                'section' => 'B',
            ]);
            Schedule::create([
                'subject_id' => $subject->id,
                'day' => 'Thursday',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
            ]);
            $student->enrolledSubjects()->attach($subject->id);

            $this->assertSame(0, app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($student));

            $desktop = $this->actingAs($student)->get('/home');
            $desktop->assertOk()->assertViewHas('totalAbsent', 0);
            $desktop->assertViewHas('todaySchedule', fn ($schedule) => $schedule->first()?->status === 'past');

            $mobile = $this->get(route('mobile.home'));
            $mobile->assertOk()->assertViewHas('todayStatus', 'pending');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_class_after_explicit_enrollment_is_counted_when_missed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 07:00:00', 'Asia/Manila'));

        try {
            $student = User::factory()->create([
                'role' => 'student',
                'year_level' => 2,
                'semester' => 1,
                'course' => 'BSCS',
                'section' => 'A',
                'created_at' => Carbon::parse('2026-08-19 07:00:00', 'Asia/Manila'),
            ]);
            $subject = Subject::create([
                'code' => 'EARLY-ENROLL',
                'name' => 'Early Enrollment Seminar',
                'year_level' => 3,
                'semester' => 1,
                'course' => 'BSED',
                'section' => 'B',
            ]);
            Schedule::create([
                'subject_id' => $subject->id,
                'day' => 'Thursday',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
            ]);
            $student->enrolledSubjects()->attach($subject->id);

            Carbon::setTestNow(Carbon::parse('2026-08-20 11:00:00', 'Asia/Manila'));

            $this->assertSame(1, app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($student));

            $desktop = $this->actingAs($student)->get('/home');
            $desktop->assertOk()->assertViewHas('totalAbsent', 1);
            $desktop->assertViewHas('todaySchedule', fn ($schedule) => $schedule->first()?->status === 'missed');

            $mobile = $this->get(route('mobile.home'));
            $mobile->assertOk()->assertViewHas('todayStatus', 'absent');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_optional_explicit_enrollment_does_not_reset_implicit_subject_attendance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-20 11:00:00', 'Asia/Manila'));

        try {
            $student = User::factory()->create([
                'role' => 'student',
                'year_level' => 2,
                'semester' => 1,
                'course' => 'BSCS',
                'section' => 'A',
                'created_at' => Carbon::parse('2026-08-19 07:00:00', 'Asia/Manila'),
            ]);
            $subject = Subject::create([
                'code' => 'IMPLICIT-ENROLL',
                'name' => 'Implicit Enrollment Seminar',
                'year_level' => 2,
                'semester' => 1,
                'course' => 'BSCS',
                'section' => 'A',
            ]);
            Schedule::create([
                'subject_id' => $subject->id,
                'day' => 'Thursday',
                'start_time' => '08:00:00',
                'end_time' => '10:00:00',
            ]);
            $student->enrolledSubjects()->attach($subject->id);

            $this->assertSame(1, app(\App\Actions\Attendance\CalculateMissedAttendanceAction::class)->execute($student));
            $this->actingAs($student)->get('/home')->assertOk()->assertViewHas('totalAbsent', 1);
            $this->get(route('mobile.home'))->assertOk()->assertViewHas('todayStatus', 'absent');
        } finally {
            Carbon::setTestNow();
        }
    }

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
            'created_at' => Carbon::parse('2026-08-17 07:00:00', 'Asia/Manila'),
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
            'created_at' => Carbon::parse('2026-08-17 07:00:00', 'Asia/Manila'),
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

    public function test_calculate_missed_attendance_action_returns_correct_miss_count(): void
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
            'student_number' => '2000004',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'created_at' => Carbon::parse('2026-08-17 07:00:00', 'Asia/Manila'),
        ]);

        $subject = Subject::create([
            'code' => 'CS301',
            'name' => 'Database Systems',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Tuesday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $action = new \App\Actions\Attendance\CalculateMissedAttendanceAction();
        $misses = $action->execute($student);

        // Tuesday session had no clock-in, so 1 miss expected
        $this->assertEquals(1, $misses);

        $perSubject = $action->executePerSubject($student);
        $this->assertArrayHasKey('CS301', $perSubject);
        $this->assertEquals(1, $perSubject['CS301']);

        Carbon::setTestNow();
    }

    public function test_student_dashboard_populates_subject_stats_with_historical_misses(): void
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
            'student_number' => '2000005',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
            'created_at' => Carbon::parse('2026-08-17 07:00:00', 'Asia/Manila'),
        ]);

        $subject = Subject::create([
            'code' => 'CS302',
            'name' => 'Operating Systems',
            'year_level' => 3,
            'semester' => 1,
            'course' => 'BSCS',
        ]);

        Schedule::create([
            'subject_id' => $subject->id,
            'day' => 'Monday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->actingAs($student)->get('/home');
        $response->assertStatus(200);
        $response->assertViewHas('subjectStats');

        $subjectStats = $response->viewData('subjectStats');
        $cs302Stat = $subjectStats->firstWhere('code', 'CS302');
        $this->assertNotNull($cs302Stat);
        $this->assertEquals(1, $cs302Stat->absent);

        Carbon::setTestNow();
    }
}

