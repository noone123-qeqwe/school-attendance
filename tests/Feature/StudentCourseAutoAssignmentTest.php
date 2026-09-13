<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseAutoAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_view_does_not_contain_course_options_and_has_hidden_bscs(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        // Course options select dropdown should NOT be present
        $response->assertDontSee('<select name="course"', false);
        $response->assertDontSee('<option value="BSIT"', false);
        $response->assertDontSee('<option value="BSIS"', false);

        // Hidden BSCS input should be present
        $response->assertSee('type="hidden" name="course" id="course" value="BSCS"', false);
    }

    public function test_admin_create_student_view_does_not_contain_course_options_and_has_hidden_bscs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.student.create'));
        $response->assertStatus(200);

        // Course options select dropdown should NOT be present
        $response->assertDontSee('<select name="course"', false);
        $response->assertDontSee('<option value="BSIT"', false);
        $response->assertDontSee('<option value="BSIS"', false);

        // Hidden BSCS input should be present
        $response->assertSee('type="hidden" name="course" value="BSCS"', false);
    }

    public function test_student_registration_automatically_assigns_bscs_course(): void
    {
        // Set OTP verified session
        session(['reg_email_verified' => 'teststudent@example.com']);

        $response = $this->post(route('register'), [
            'name' => 'John Doe',
            'email' => 'teststudent@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'student',
            'year_level' => 1,
            'semester' => 1,
            'terms' => '1',
        ]);

        $student = User::where('email', 'teststudent@example.com')->first();
        $this->assertNotNull($student);
        $this->assertEquals('student', $student->role);
        $this->assertEquals('BSCS', $student->course);
    }

    public function test_student_model_creation_automatically_defaults_to_bscs(): void
    {
        $student = User::create([
            'name' => 'Auto Course Student',
            'email' => 'autocourse@example.com',
            'password' => bcrypt('Password123!'),
            'role' => 'student',
            'year_level' => 2,
            'semester' => 1,
        ]);

        $this->assertEquals('BSCS', $student->fresh()->course);
    }

    public function test_admin_store_student_automatically_assigns_bscs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        session(['admin_reg_email_verified' => 'newstudent@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.student.store'), [
            'name' => 'Jane Smith',
            'email' => 'newstudent@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => 1,
            'semester' => 1,
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = User::where('email', 'newstudent@example.com')->first();
        $this->assertNotNull($student);
        $this->assertEquals('BSCS', $student->course);
    }

    public function test_admin_edit_student_view_does_not_contain_course_options(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $student = User::factory()->create([
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 2,
            'semester' => 1,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.student.edit', $student));
        $response->assertStatus(200);

        // Course options select dropdown should NOT be present
        $response->assertDontSee('<select name="course"', false);
        $response->assertDontSee('<option value="BSIT"', false);
        $response->assertDontSee('<option value="BSIS"', false);

        // Hidden input should carry course
        $response->assertSee('type="hidden" name="course" value="BSCS"', false);
    }
}
