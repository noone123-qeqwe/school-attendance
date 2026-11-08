<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\Course;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassScheduleConflictTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private User $otherTeacher;
    private User $student;
    private Subject $subject;
    private Subject $otherSubject;
    private Section $section;
    private Section $otherSection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
        ]);
        $this->teacher = User::factory()->teacher()->create();
        $this->otherTeacher = User::factory()->teacher()->create();
        $this->student = User::factory()->create(['role' => 'student']);

        $department = Department::create(['name' => 'Computer Science', 'code' => 'CS']);
        $course = Course::create(['department_id' => $department->id, 'name' => 'Computer Science', 'code' => 'BSCS']);
        $this->section = Section::create(['course_id' => $course->id, 'name' => 'A', 'year_level' => 1]);
        $this->otherSection = Section::create(['course_id' => $course->id, 'name' => 'B', 'year_level' => 1]);

        $this->subject = $this->createSubject('CS101', $this->teacher);
        $this->otherSubject = $this->createSubject('CS102', $this->otherTeacher);
    }

    public function test_overlapping_classes_are_rejected_for_the_same_section_teacher_or_room(): void
    {
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');

        $sectionConflict = $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->otherSubject,
            $this->otherTeacher,
            $this->section,
            'Room 2',
            '09:30',
            '10:30',
        ));
        $sectionConflict->assertSessionHasErrors('room');

        $teacherConflict = $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->otherSubject,
            $this->teacher,
            $this->otherSection,
            'Room 2',
            '09:30',
            '10:30',
        ));
        $teacherConflict->assertSessionHasErrors('room');

        $roomConflict = $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->otherSubject,
            $this->otherTeacher,
            $this->otherSection,
            'Room 1',
            '09:30',
            '10:30',
        ));
        $roomConflict->assertSessionHasErrors('room');

        $this->assertSame(1, ClassSchedule::count());
    }

    public function test_adjacent_schedule_slots_do_not_count_as_overlapping(): void
    {
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');
        $this->otherSubject->update(['instructor_id' => $this->teacher->id, 'section' => 'B']);

        $response = $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->otherSubject,
            $this->teacher,
            $this->otherSection,
            'Room 2',
            '10:00',
            '11:00',
        ));

        $response->assertSessionHasNoErrors();
        $this->assertSame(2, ClassSchedule::count());
    }

    public function test_teacher_assignment_is_required_and_must_be_a_teacher_account(): void
    {
        $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->subject,
            null,
            $this->section,
            'Room 1',
        ))->assertSessionHasErrors('teacher_id');

        $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->subject,
            $this->student,
            $this->section,
            'Room 1',
        ))->assertSessionHasErrors('teacher_id');

        $this->assertSame(0, ClassSchedule::count());
    }

    public function test_subject_owner_and_cohort_cannot_be_changed_by_a_schedule_assignment(): void
    {
        $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->subject,
            $this->otherTeacher,
            $this->section,
            'Room 1',
        ))->assertSessionHasErrors('teacher_id');

        $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->subject,
            $this->teacher,
            $this->otherSection,
            'Room 1',
        ))->assertSessionHasErrors('section_id');

        $this->assertSame($this->teacher->id, $this->subject->fresh()->instructor_id);
        $this->assertSame(0, ClassSchedule::count());
    }

    public function test_first_schedule_can_assign_an_unowned_subject_without_changing_later_ownership(): void
    {
        $this->subject->update(['instructor_id' => null]);

        $this->asAdmin()->post(route('admin.class-schedules.store'), $this->payload(
            $this->subject,
            $this->teacher,
            $this->section,
            'Room 1',
        ))->assertSessionHasNoErrors();

        $this->assertSame($this->teacher->id, $this->subject->fresh()->instructor_id);

        $classSchedule = ClassSchedule::firstOrFail();
        $this->asAdmin()->put(route('admin.class-schedules.update', $classSchedule), $this->payload(
            $this->subject,
            $this->otherTeacher,
            $this->section,
            'Room 1',
        ))->assertSessionHasErrors('teacher_id');

        $this->assertSame($this->teacher->id, $classSchedule->fresh()->teacher_id);
        $this->assertSame($this->teacher->id, $this->subject->fresh()->instructor_id);
    }

    public function test_editing_subject_details_preserves_class_schedule_slots_and_blocks_owner_changes(): void
    {
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');
        $payload = [
            'code' => $this->subject->code,
            'name' => 'Renamed Subject',
            'year_level' => $this->subject->year_level,
            'semester' => $this->subject->semester,
            'course' => $this->subject->course,
            'units' => $this->subject->units,
            'section' => $this->subject->section,
            'instructor_id' => $this->teacher->id,
            'days' => 'T',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ];

        $this->asAdmin()->put(route('admin.subjects.update', $this->subject), $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Subject', $this->subject->fresh()->name);
        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        $this->asAdmin()->put(route('admin.subjects.update', $this->subject), [
            ...$payload,
            'instructor_id' => $this->otherTeacher->id,
        ])->assertSessionHasErrors('instructor_id');

        $this->assertSame($this->teacher->id, $this->subject->fresh()->instructor_id);
    }

    public function test_admin_cannot_assign_a_student_as_subject_instructor(): void
    {
        $this->asAdmin()->post(route('admin.subjects.store'), [
            'code' => 'CS103',
            'name' => 'Invalid Instructor',
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'instructor_id' => $this->student->id,
            'section' => 'A',
        ])->assertSessionHasErrors('instructor_id');

        $this->assertDatabaseMissing('subjects', ['code' => 'CS103']);
    }

    public function test_teacher_subject_edit_preserves_admin_class_schedule_slots(): void
    {
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');

        $this->actingAs($this->teacher)->put(route('teacher.subjects.update', $this->subject), [
            'code' => $this->subject->code,
            'name' => 'Updated by Teacher',
            'year_level' => $this->subject->year_level,
            'semester' => $this->subject->semester,
            'units' => $this->subject->units,
            'section' => $this->subject->section,
            'days' => 'T',
            'start_time' => '11:00',
            'end_time' => '12:00',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Updated by Teacher', $this->subject->fresh()->name);
        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    }

    public function test_rescheduling_removes_the_old_attendance_slot_and_adds_the_new_slot(): void
    {
        $classSchedule = $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');

        $this->asAdmin()->put(route('admin.class-schedules.update', $classSchedule), $this->payload(
            $this->subject,
            $this->teacher,
            $this->section,
            'Room 1',
            '10:00',
            '11:00',
            'Tuesday',
        ))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Tuesday',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_rescheduling_keeps_an_old_slot_still_used_by_another_section(): void
    {
        $this->subject->update(['section' => null]);
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');
        $classSchedule = $this->createClassSchedule($this->subject, $this->teacher, $this->otherSection, 'Room 2');

        $this->asAdmin()->put(route('admin.class-schedules.update', $classSchedule), $this->payload(
            $this->subject,
            $this->teacher,
            $this->otherSection,
            'Room 2',
            '10:00',
            '11:00',
            'Tuesday',
        ))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
    }

    public function test_deleting_a_schedule_removes_only_its_unshared_attendance_slot(): void
    {
        $classSchedule = $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');
        Schedule::create([
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);

        $this->asAdmin()->delete(route('admin.class-schedules.destroy', $classSchedule))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ]);
    }

    public function test_deleting_one_section_keeps_a_shared_attendance_slot_for_the_other_section(): void
    {
        $this->subject->update(['section' => null]);
        $this->createClassSchedule($this->subject, $this->teacher, $this->section, 'Room 1');
        $classSchedule = $this->createClassSchedule($this->subject, $this->teacher, $this->otherSection, 'Room 2');

        $this->asAdmin()->delete(route('admin.class-schedules.destroy', $classSchedule))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('schedules', [
            'subject_id' => $this->subject->id,
            'day' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);
        $this->assertSame(1, ClassSchedule::count());
    }

    private function createSubject(string $code, User $teacher): Subject
    {
        return Subject::create([
            'code' => $code,
            'name' => $code.' Class',
            'units' => 3,
            'year_level' => 1,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
            'instructor_id' => $teacher->id,
        ]);
    }

    private function createClassSchedule(Subject $subject, User $teacher, Section $section, string $room): ClassSchedule
    {
        $classSchedule = ClassSchedule::create([
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
            'section_id' => $section->id,
            'room' => $room,
            'day_of_week' => 'Monday',
            'start_time' => '09:00',
            'end_time' => '10:00',
        ]);

        Schedule::updateOrCreate(
            [
                'subject_id' => $subject->id,
                'day' => 'Monday',
                'start_time' => '09:00',
                'end_time' => '10:00',
            ],
            ['room' => $room],
        );

        return $classSchedule;
    }

    private function payload(Subject $subject, ?User $teacher, Section $section, string $room, string $start = '09:00', string $end = '10:00', string $day = 'Monday'): array
    {
        return [
            'subject_id' => $subject->id,
            'teacher_id' => $teacher?->id,
            'section_id' => $section->id,
            'day_of_week' => $day,
            'start_time' => $start,
            'end_time' => $end,
            'room' => $room,
        ];
    }

    private function asAdmin(): self
    {
        return $this->actingAs($this->admin)->withSession(['admin_2fa_verified' => true]);
    }
}
