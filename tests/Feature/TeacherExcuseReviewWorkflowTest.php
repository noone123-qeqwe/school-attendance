<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\ExcuseSubmission;
use App\Models\Notification;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherExcuseReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $otherTeacher;
    private User $student;
    private User $parent;
    private Subject $subject;
    private Subject $otherSubject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Elena Ramos',
            'email' => 'teacher.ramos@example.com',
        ]);

        $this->otherTeacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Prof. Carlos Rivera',
            'email' => 'teacher.rivera@example.com',
        ]);

        $this->subject = Subject::create([
            'code' => 'CS101',
            'name' => 'Computer Science I',
            'year_level' => 1,
            'semester' => 1,
            'instructor_id' => $this->teacher->id,
            'units' => 3,
        ]);

        $this->otherSubject = Subject::create([
            'code' => 'MATH201',
            'name' => 'Calculus II',
            'year_level' => 2,
            'semester' => 1,
            'instructor_id' => $this->otherTeacher->id,
            'units' => 3,
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Juan Dela Cruz',
            'student_number' => '2026-00101',
            'email' => 'juan.student@example.com',
        ]);

        $this->parent = User::factory()->create([
            'role' => 'parent',
            'name' => 'Maria Dela Cruz',
            'email' => 'maria.parent@example.com',
        ]);

        $this->parent->children()->attach($this->student->id);
    }

    public function test_teacher_can_view_excuse_reviews_page()
    {
        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'reason' => 'Severe Dengue Fever',
            'description' => 'Hospitalized for 3 days',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)->get(route('teacher.excuse.reviews'));

        $response->assertOk();
        $response->assertSee('Excuse Reviews');
        $response->assertSee('Severe Dengue Fever');
        $response->assertSee('bulkActionBar');
        $response->assertSee('selectAllCheckbox');
    }

    public function test_teacher_can_approve_single_excuse_with_status_override_and_audit_note()
    {
        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'reason' => 'Medical Hospitalization',
            'description' => 'Doctor certified',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.excuse.approve', $excuse), [
                'admin_notes' => 'Valid medical certificate verified.',
                'status_override' => 'Excused',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $excuse->refresh();
        $attendance->refresh();

        $this->assertEquals('approved', $excuse->status);
        $this->assertEquals('Valid medical certificate verified.', $excuse->admin_notes);
        $this->assertTrue($attendance->excused);
        $this->assertEquals('Excused', $attendance->status);

        // Notified student and parent
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->student->id,
            'type' => 'excuse_approved',
        ]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->parent->id,
            'type' => 'excuse_approved',
        ]);
    }

    public function test_teacher_can_reject_single_excuse_with_feedback()
    {
        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'reason' => 'Woke up late',
            'description' => 'Alarm clock malfunction',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.excuse.reject', $excuse), [
                'admin_notes' => 'Oversleeping is not an official excused absence reason.',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $excuse->refresh();
        $attendance->refresh();

        $this->assertEquals('rejected', $excuse->status);
        $this->assertFalse($attendance->excused);
        $this->assertStringContainsString('Oversleeping is not an official excused absence', $excuse->admin_notes);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->student->id,
            'type' => 'warning_2',
        ]);
    }

    public function test_teacher_cannot_approve_excuse_for_another_teachers_subject()
    {
        $attendance = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->otherSubject->id,
            'subject_code' => $this->otherSubject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse = ExcuseSubmission::create([
            'attendance_id' => $attendance->id,
            'user_id' => $this->student->id,
            'reason' => 'Calculus missed',
            'description' => 'Missed calculus class',
            'status' => 'pending',
        ]);

        // Attempting approval by teacher who doesn't teach MATH201
        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.excuse.approve', $excuse), [
                'admin_notes' => 'Attempting unauthorized approval',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_can_bulk_approve_multiple_excuses()
    {
        $att1 = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->subDay()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse1 = ExcuseSubmission::create([
            'attendance_id' => $att1->id,
            'user_id' => $this->student->id,
            'reason' => 'Flu Day 1',
            'description' => 'Sick at home',
            'status' => 'pending',
        ]);

        $att2 = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse2 = ExcuseSubmission::create([
            'attendance_id' => $att2->id,
            'user_id' => $this->student->id,
            'reason' => 'Flu Day 2',
            'description' => 'Doctor requested extended bed rest',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.excuse.bulk.approve'), [
                'ids' => [$excuse1->id, $excuse2->id],
                'admin_notes' => 'Approved batch excuses for flu illness',
                'status_override' => 'Excused',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'count' => 2]);

        $this->assertEquals('approved', $excuse1->fresh()->status);
        $this->assertEquals('approved', $excuse2->fresh()->status);
        $this->assertTrue($att1->fresh()->excused);
        $this->assertTrue($att2->fresh()->excused);
        $this->assertEquals('Excused', $att1->fresh()->status);
        $this->assertEquals('Excused', $att2->fresh()->status);
    }

    public function test_teacher_can_bulk_reject_multiple_excuses()
    {
        $att1 = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->subDay()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse1 = ExcuseSubmission::create([
            'attendance_id' => $att1->id,
            'user_id' => $this->student->id,
            'reason' => 'No show 1',
            'description' => 'Unverified note',
            'status' => 'pending',
        ]);

        $att2 = Attendance::create([
            'user_id' => $this->student->id,
            'subject_id' => $this->subject->id,
            'subject_code' => $this->subject->code,
            'date' => now()->toDateString(),
            'status' => 'Absent',
            'excused' => false,
        ]);

        $excuse2 = ExcuseSubmission::create([
            'attendance_id' => $att2->id,
            'user_id' => $this->student->id,
            'reason' => 'No show 2',
            'description' => 'Unverified note',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson(route('teacher.excuse.bulk.reject'), [
                'ids' => [$excuse1->id, $excuse2->id],
                'admin_notes' => 'No medical certificate provided after repeated requests.',
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true, 'count' => 2]);

        $this->assertEquals('rejected', $excuse1->fresh()->status);
        $this->assertEquals('rejected', $excuse2->fresh()->status);
        $this->assertFalse($att1->fresh()->excused);
        $this->assertFalse($att2->fresh()->excused);
    }
}
