<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Holiday;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentHomeActionsTest extends TestCase
{
    use RefreshDatabase;

    private function student(string $number): User
    {
        return User::create([
            'name' => 'Student '.$number, 'student_number' => $number,
            'email' => $number.'@example.test', 'password' => 'password',
            'role' => 'student', 'is_active' => true,
            'course' => 'BSCS', 'year_level' => '1', 'semester' => '1', 'section' => 'A',
            'created_at' => now()->subMonth(),
        ]);
    }

    private function subject(User $student): Subject
    {
        $subject = Subject::create([
            'code' => 'CS101', 'name' => 'Computer Science',
            'course' => 'BSCS', 'year_level' => 1, 'semester' => 1, 'section' => 'A',
        ]);
        $student->enrolledSubjects()->attach($subject->id);
        return $subject;
    }

    private function record(User $student, Subject $subject, string $date, array $attributes = []): Attendance
    {
        return Attendance::withoutEvents(fn () => Attendance::create(array_merge([
            'user_id' => $student->id, 'subject_code' => $subject->code,
            'date' => $date, 'status' => 'Absent', 'excused' => false,
        ], $attributes)));
    }

    public function test_home_actions_are_private_and_exclude_handled_or_future_absences(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 15));
        $student = $this->student('one');
        $other = $this->student('two');
        $subject = $this->subject($student);
        $open = $this->record($student, $subject, '2026-11-24');
        $foreign = $this->record($other, $subject, '2026-11-24');
        $this->record($student, $subject, '2026-11-26');
        $this->record($student, $subject, '2026-11-23', ['excused' => true]);
        foreach (['pending', 'approved'] as $offset => $status) {
            $record = $this->record($student, $subject, '2026-11-'.(21 + $offset));
            ExcuseSubmission::create([
                'user_id' => $student->id, 'attendance_id' => $record->id,
                'reason' => 'Medical', 'description' => 'Clinic visit', 'status' => $status,
            ]);
        }
        ExcuseSubmission::create([
            'user_id' => $other->id, 'attendance_id' => $foreign->id,
            'reason' => 'Private reason', 'description' => 'Private details', 'status' => 'pending',
        ]);

        $response = $this->actingAs($student)->get(route('home'))->assertOk();
        $response->assertViewHas('unresolvedAbsencesCount', 1);
        $this->assertSame([$open->id], $response->viewData('unresolvedAbsences')->modelKeys());
        $this->assertTrue($response->viewData('recentAttendance')->every(fn ($row) => $row->user_id === $student->id && $row->date->lte(today())));
        $this->assertCount(2, $response->viewData('recentExcuseRequests'));
        $response->assertDontSee('Private reason')->assertSee(route('excuses.create', $open->id), false);
    }

    public function test_next_class_skips_holidays_and_uses_earliest_future_slot(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(12, 0));
        $student = $this->student('one');
        $subject = $this->subject($student);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Thursday', 'start_time' => '09:00:00', 'end_time' => '10:00:00']);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Friday', 'start_time' => '14:00:00', 'end_time' => '15:00:00']);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Friday', 'start_time' => '08:00:00', 'end_time' => '09:00:00']);
        Holiday::create(['name' => 'School holiday', 'date' => '2026-11-26', 'is_active' => true, 'type' => 'school', 'created_by' => $student->id]);
        $response = $this->actingAs($student)->get(route('home'))->assertOk();
        $next = $response->viewData('upcomingClass');
        $this->assertSame('2026-11-27 08:00', $next->start_time->format('Y-m-d H:i'));
        $response->assertSee('Fri, Nov 27')->assertSee('8:00 AM');
    }

    public function test_live_class_and_each_timetable_slot_preserve_the_daily_late_record(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 15));
        $student = $this->student('one');
        $subject = $this->subject($student);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Wednesday', 'start_time' => '08:00:00', 'end_time' => '09:00:00', 'room' => 'Morning Lab']);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Wednesday', 'start_time' => '10:00:00', 'end_time' => '11:00:00', 'room' => 'Afternoon Lab']);
        $this->record($student, $subject, '2026-11-25', ['status' => 'Late', 'time_in' => '08:20:00']);
        $this->actingAs($student)->get(route('home'))->assertOk()
            ->assertSee('Happening now')->assertSee('Afternoon Lab')->assertSee('Morning Lab')
            ->assertSee('Late · day record')->assertSee('Attendance recorded for CS101 today.')
            ->assertDontSee('All classes completed today');
    }

    public function test_recent_record_links_filter_calendar_to_the_record_subject_and_date(): void
    {
        $student = $this->student('one');
        $subject = $this->subject($student);
        $record = $this->record($student, $subject, today()->toDateString(), ['status' => 'Late']);
        $url = route('student.calendar', ['q' => $subject->code, 'record_date' => $record->date->toDateString()]);
        $this->actingAs($student)->get(route('home'))->assertOk()->assertSee(e($url).'#attendance-records', false);
        $calendar = $this->get($url)->assertOk();
        $this->assertSame([$record->id], $calendar->viewData('records')->getCollection()->modelKeys());
    }

    public function test_holiday_has_no_live_class_or_missing_attendance_prompts(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 25)->setTime(10, 15));
        $student = $this->student('one');
        $subject = $this->subject($student);
        Schedule::create(['subject_id' => $subject->id, 'day' => 'Wednesday', 'start_time' => '10:00:00', 'end_time' => '11:00:00']);
        Holiday::create(['name' => 'School holiday', 'date' => '2026-11-25', 'is_active' => true, 'type' => 'school', 'created_by' => $student->id]);
        $response = $this->actingAs($student)->get(route('home'))->assertOk()
            ->assertSee('School holiday')->assertDontSee('>Happening now<', false);
        $this->assertNull($response->viewData('currentSlot'));
        $this->assertCount(0, $response->viewData('todaySchedule'));
        $this->assertSame(0, $response->viewData('unresolvedAbsencesCount'));
    }

    public function test_historical_record_remains_visible_after_subject_is_removed(): void
    {
        $student = $this->student('one');
        $record = Attendance::withoutEvents(fn () => Attendance::create([
            'user_id' => $student->id, 'subject_code' => 'OLD101', 'subject_name' => 'Former class',
            'date' => today()->toDateString(), 'status' => 'Absent', 'excused' => false,
        ]));
        $this->actingAs($student)->get(route('home'))->assertOk()
            ->assertSee('Former class')->assertSee(route('excuses.create', $record->id), false);
    }
}
