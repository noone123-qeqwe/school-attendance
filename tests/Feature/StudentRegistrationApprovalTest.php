<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentRegistrationApprovalTest extends TestCase
{
    use RefreshDatabase;

    private function section(string $courseCode, string $name = 'A'): Section
    {
        $department = Department::create([
            'name' => 'Computing',
            'code' => 'COMP',
        ]);
        $course = Course::create([
            'department_id' => $department->id,
            'name' => $courseCode,
            'code' => $courseCode,
        ]);

        return Section::create([
            'course_id' => $course->id,
            'name' => $name,
            'year_level' => 2,
        ]);
    }

    private function registrationPayload(Section $section, string $course): array
    {
        return [
            'name' => 'New Student',
            'first_name' => 'New',
            'surname' => 'Student',
            'role' => 'student',
            'student_number' => '2310456',
            'course' => $course,
            'section_id' => $section->id,
            'year_level' => 2,
            'semester' => '1',
            'email' => 'new.student@gmail.com',
            'password' => 'SafePassword123!',
            'password_confirmation' => 'SafePassword123!',
            'terms' => 1,
        ];
    }

    public function test_public_registration_cannot_tamper_course_and_section(): void
    {
        $section = $this->section('BSIT');
        $this->withSession(['reg_email_verified' => 'new.student@gmail.com'])
            ->post(route('register.submit'), $this->registrationPayload($section, 'BSIT'))
            ->assertSessionHasErrors('section_id');

        $this->assertDatabaseMissing('users', ['email' => 'new.student@gmail.com']);
    }

    public function test_public_student_registration_remains_blocked_until_admin_approval(): void
    {
        $section = $this->section('BSCS');
        Subject::create([
            'code' => 'COMP101',
            'name' => 'Computer Fundamentals',
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $this->withSession(['reg_email_verified' => 'new.student@gmail.com'])
            ->post(route('register.submit'), $this->registrationPayload($section, 'BSCS'))
            ->assertRedirect(route('student.pending-approval'));

        $student = User::where('email', 'new.student@gmail.com')->firstOrFail();
        $this->assertTrue($student->student_registration_pending);
        $this->assertSame('BSCS', $student->course);
        $this->assertSame(0, $student->getAllSubjects()->count());

        $this->actingAs($student)
            ->get(route('student.schedule'))
            ->assertRedirect(route('student.pending-approval'));

        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_sub_role' => 'super_admin',
        ]);
        // The pending student's authenticated session is intentionally replaced
        // with a fresh administrator session before exercising the approval path.
        $this->flushSession()->actingAs($admin)
            ->patch(route('admin.student.approve-registration', $student))
            ->assertRedirect(route('admin.students', ['status' => 'pending']));

        $this->assertFalse($student->fresh()->student_registration_pending);

        $student->refresh();
        $this->assertCount(1, $student->getAllSubjects());
        $this->flushSession()->actingAs($student)
            ->get(route('student.pending-approval'))
            ->assertRedirect(route('home'));
    }

    public function test_student_cannot_approve_own_registration(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_registration_pending' => true,
        ]);

        $this->actingAs($student)
            ->patch(route('admin.student.approve-registration', $student))
            ->assertRedirect(route('student.pending-approval'));

        $this->assertTrue($student->fresh()->student_registration_pending);
    }

    public function test_pending_student_api_requests_are_blocked(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_registration_pending' => true,
        ]);

        $this->actingAs($student, 'sanctum')
            ->getJson('/api/user')
            ->assertForbidden()
            ->assertJsonPath('error', 'STUDENT_REGISTRATION_PENDING');
    }
}
