<?php

namespace Tests\Feature;

use App\Models\ClassStudentAssistant;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssistantAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private Subject $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        $this->subject = Subject::factory()->create([
            'instructor_id' => $this->teacher->id,
            'course' => 'BSCS', 'section' => '3A', 'year_level' => 3, 'semester' => 1,
        ]);
    }

    private function enrolledStudent(): User
    {
        $student = User::factory()->create([
            'role' => 'student', 'is_active' => true,
            'course' => 'BSCS', 'section' => '3A', 'year_level' => 3, 'semester' => 1,
        ]);
        $this->subject->enrolledStudents()->attach($student->id);

        return $student;
    }

    private function assignmentData(User $student): array
    {
        return [
            'student_id' => $student->id,
            'starts_at' => today()->toDateString(),
            'expires_at' => today()->addMonths(4)->toDateString(),
        ];
    }

    private function assign(User $student)
    {
        return $this->actingAs($this->teacher)->post(
            route('teacher.classroom.assistants.store', $this->subject->code),
            $this->assignmentData($student)
        );
    }

    public function test_teacher_can_assign_two_existing_students_but_not_a_third(): void
    {
        $first = $this->enrolledStudent();
        $second = $this->enrolledStudent();
        $third = $this->enrolledStudent();

        $this->assign($first)->assertSessionHasNoErrors();
        $this->assign($second)->assertSessionHasNoErrors();
        $this->assign($third)->assertSessionHasErrors('student_id');

        $this->assertSame(2, ClassStudentAssistant::where('subject_id', $this->subject->id)
            ->whereNotNull('active_slot')->count());
        $this->assertSame('student', $first->fresh()->role);
    }

    public function test_duplicate_and_unenrolled_student_are_rejected(): void
    {
        $student = $this->enrolledStudent();
        $outsider = User::factory()->create([
            'role' => 'student', 'course' => 'BSIT', 'section' => '9Z',
            'year_level' => 1, 'semester' => 2,
        ]);

        $this->assign($student)->assertSessionHasNoErrors();
        $this->assign($student)->assertSessionHasErrors('student_id');
        $this->assign($outsider)->assertSessionHasErrors('student_id');
        $this->assertDatabaseCount('class_student_assistants', 1);
    }

    public function test_another_teacher_and_student_cannot_manage_assignments(): void
    {
        $student = $this->enrolledStudent();
        $otherTeacher = User::factory()->create(['role' => 'teacher']);
        $url = route('teacher.classroom.assistants.store', $this->subject->code);

        $this->actingAs($otherTeacher)->post($url, $this->assignmentData($student))->assertForbidden();
        $this->actingAs($student)->post($url, $this->assignmentData($student))
            ->assertRedirect(route('home'));
        $this->assertDatabaseCount('class_student_assistants', 0);
    }

    public function test_teacher_can_revoke_and_replace_without_changing_student_accounts(): void
    {
        $first = $this->enrolledStudent();
        $second = $this->enrolledStudent();
        $third = $this->enrolledStudent();
        $this->assign($first)->assertSessionHasNoErrors();
        $this->assign($second)->assertSessionHasNoErrors();
        $original = ClassStudentAssistant::where('student_id', $first->id)->firstOrFail();

        $this->actingAs($this->teacher)->put(
            route('teacher.classroom.assistants.replace', [$this->subject->code, $original->id]),
            $this->assignmentData($third)
        )->assertSessionHasNoErrors();

        $this->assertNotNull($original->fresh()->revoked_at);
        $replacement = ClassStudentAssistant::where('student_id', $third->id)->firstOrFail();
        $this->assertTrue($replacement->isActive());
        $this->actingAs($this->teacher)->delete(
            route('teacher.classroom.assistants.destroy', [$this->subject->code, $replacement->id])
        )->assertSessionHasNoErrors();
        $this->assertFalse($replacement->fresh()->isActive());
        $this->assertSame('student', $third->fresh()->role);
    }

    public function test_expired_assignment_is_not_active_and_frees_a_slot(): void
    {
        $first = $this->enrolledStudent();
        $second = $this->enrolledStudent();
        $third = $this->enrolledStudent();
        $this->assign($first)->assertSessionHasNoErrors();
        $this->assign($second)->assertSessionHasNoErrors();
        $firstAssignment = ClassStudentAssistant::where('student_id', $first->id)->firstOrFail();
        $firstAssignment->update(['expires_at' => now()->subMinute()]);

        $this->assertFalse($firstAssignment->fresh()->isActive());
        $this->assign($third)->assertSessionHasNoErrors();
        $this->assertNull($firstAssignment->fresh()->active_slot);
        $this->assertTrue(ClassStudentAssistant::where('student_id', $third->id)->firstOrFail()->isActive());
    }

    public function test_teacher_class_page_lists_assistants_but_student_cannot_open_it(): void
    {
        $student = $this->enrolledStudent();
        $this->assign($student)->assertSessionHasNoErrors();

        $this->actingAs($this->teacher)->get(route('teacher.classroom.show', $this->subject->code))
            ->assertOk()->assertSee('Student Assistants')->assertSee($student->name);
        $this->actingAs($student)->get(route('teacher.classroom.show', $this->subject->code))
            ->assertRedirect(route('home'));
    }
}
