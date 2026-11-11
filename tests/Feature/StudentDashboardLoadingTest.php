<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentDashboardLoadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_keeps_live_totals_without_loading_hidden_calendar_relationships(): void
    {
        $student = User::factory()->create();
        Attendance::create([
            'user_id' => $student->id,
            'subject_code' => 'HISTORY101',
            'subject_name' => 'Historical class',
            'date' => today(),
            'status' => 'Present',
        ]);

        $this->actingAs($student);
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $response = $this->get('/home');
            $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        } finally {
            DB::disableQueryLog();
        }

        $response->assertOk()
            ->assertViewHas('totalPresent', 1)
            ->assertViewHas('attendanceRecordCount', 1)
            ->assertViewHas('attendanceRate', 100);
        $this->assertDoesNotMatchRegularExpression('/from ["`]?events["`]?/i', $queries);
        $this->assertDoesNotMatchRegularExpression('/from ["`]?excuse_submissions["`]?/i', $queries);
        $this->assertDoesNotMatchRegularExpression('/select \* from ["`]?attendances["`]?.*between/i', $queries);
        $response->assertDontSee('Historical class');
    }

    public function test_calendar_details_are_loaded_for_only_the_authenticated_student_and_selected_month(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $subject = Subject::create([
            'code' => 'DETAIL101', 'name' => 'Calendar class',
            'year_level' => 1, 'semester' => 1,
        ]);
        Schedule::create([
            'subject_id' => $subject->id, 'day' => 'Monday',
            'start_time' => '08:00:00', 'end_time' => '10:00:00',
        ]);
        $own = Attendance::create([
            'user_id' => $student->id, 'subject_id' => $subject->id,
            'subject_code' => $subject->code, 'date' => '2026-10-05',
            'time_in' => '08:02:00', 'status' => 'Present',
        ]);
        Attendance::create([
            'user_id' => $otherStudent->id, 'subject_code' => 'PRIVATE101',
            'subject_name' => 'Another student private class',
            'date' => '2026-10-05', 'status' => 'Present',
        ]);
        Attendance::create([
            'user_id' => $student->id, 'subject_code' => 'OLD101',
            'date' => '2026-09-30', 'status' => 'Present',
        ]);
        Holiday::create([
            'name' => 'School holiday', 'date' => '2026-10-06',
            'type' => 'school', 'is_active' => true, 'created_by' => $student->id,
        ]);
        Event::create([
            'name' => 'School gathering', 'date' => '2026-10-07',
            'start_time' => '09:00:00', 'end_time' => '10:00:00',
            'type' => 'school_event', 'status' => 'scheduled', 'created_by' => $student->id,
        ]);

        $response = $this->actingAs($student)->getJson(route('student.dashboard.calendar.data', [
            'year' => 2026, 'month' => 10,
        ]));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(1, 'attendance')
            ->assertJsonCount(1, 'attendance.2026-10-05')
            ->assertJsonPath('attendance.2026-10-05.0.id', $own->id)
            ->assertJsonPath('attendance.2026-10-05.0.subject', 'Calendar class')
            ->assertJsonPath('attendance.2026-10-05.0.clock_in', '8:02 AM')
            ->assertJsonPath('attendance.2026-10-05.0.schedule', '8:00 AM – 10:00 AM')
            ->assertJsonPath('events.2026-10-06.0.title', 'School holiday')
            ->assertJsonPath('events.2026-10-07.0.title', 'School gathering')
            ->assertDontSee('Another student private class')
            ->assertDontSee('OLD101');
    }

    public function test_calendar_details_require_a_student_session(): void
    {
        $this->getJson(route('student.dashboard.calendar.data'))->assertUnauthorized();
        foreach (['teacher', 'parent', 'admin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->getJson(route('student.dashboard.calendar.data'))->assertForbidden();
        }
    }

    public function test_calendar_details_reject_invalid_date_ranges(): void
    {
        $this->actingAs(User::factory()->create());
        foreach ([['month' => 13], ['month' => 0], ['year' => 1999], ['year' => '2026invalid']] as $date) {
            $this->getJson(route('student.dashboard.calendar.data', $date))->assertUnprocessable();
        }
    }
}
