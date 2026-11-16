<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationContinueFlowTest extends TestCase
{
    use RefreshDatabase;

    private function createBscsSection(int $yearLevel = 1): Section
    {
        $department = Department::firstOrCreate(['code' => 'COMP'], ['name' => 'Computing']);
        $course = Course::firstOrCreate(['code' => 'BSCS'], ['department_id' => $department->id, 'name' => 'BSCS']);
        return Section::firstOrCreate(['course_id' => $course->id, 'name' => 'A', 'year_level' => $yearLevel]);
    }

    public function test_register_page_renders_step1_inputs_and_continue_button(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('id="btn-continue-step1"', false);
        $response->assertSee('id="first_name"', false);
        $response->assertSee('id="middle_name"', false);
        $response->assertSee('id="no_middle_name"', false);
        $response->assertSee('id="surname"', false);
        $response->assertSee('id="role_student"', false);
        $response->assertSee('id="role_parent"', false);
        $response->assertSee('id="student_number"', false);
        $response->assertSee('Student ID / Number', false);
        $response->assertSee('id="course"', false);
        $response->assertSee('id="year_level"', false);
        $response->assertSee('id="semester"', false);
    }

    public function test_register_page_has_no_inline_event_handlers_violating_csp(): void
    {
        $response = $this->get('/register');
        $content = $response->getContent();

        // Extract the <form id="regForm" ...> ... </form> HTML
        preg_match('/<form id="regForm".*?<\/form>/s', $content, $matches);
        $this->assertNotEmpty($matches, 'regForm should exist on register page');

        $formHtml = $matches[0];

        // Ensure no onclick, oninput, onchange, or onsubmit inline handlers are inside the form HTML
        $this->assertDoesNotMatchRegularExpression('/\bonclick\s*=/i', $formHtml, 'Inline onclick handlers must be avoided due to CSP nonce');
        $this->assertDoesNotMatchRegularExpression('/\boninput\s*=/i', $formHtml, 'Inline oninput handlers must be avoided due to CSP nonce');
        $this->assertDoesNotMatchRegularExpression('/\bonchange\s*=/i', $formHtml, 'Inline onchange handlers must be avoided due to CSP nonce');
        $this->assertDoesNotMatchRegularExpression('/\bonsubmit\s*=/i', $formHtml, 'Inline onsubmit handlers must be avoided due to CSP nonce');
    }

    public function test_register_page_includes_field_level_feedback_containers(): void
    {
        $response = $this->get('/register');

        $response->assertSee('id="feedback-first_name"', false);
        $response->assertSee('id="feedback-middle_name"', false);
        $response->assertSee('id="feedback-surname"', false);
        $response->assertSee('id="feedback-role"', false);
        $response->assertSee('id="feedback-student_number"', false);
        $response->assertSee('id="feedback-course"', false);
        $response->assertSee('id="feedback-year_level"', false);
        $response->assertSee('id="feedback-semester"', false);
    }

    public function test_register_page_course_is_auto_set_to_bscs(): void
    {
        $response = $this->get('/register');

        $response->assertSee('name="course" id="course" value="BSCS"', false);
        $response->assertDontSee('<select name="course"', false);
    }

    public function test_exact_student_registration_payload_succeeds(): void
    {
        $section = $this->createBscsSection(4);
        // Simulate email verification session flag
        session(['reg_email_verified' => 'janessa.herminado@gmail.com']);

        $payload = [
            'first_name' => 'Janessa',
            'middle_name' => 'Almosara',
            'student_number' => '2312001',
            'surname' => 'Herminado',
            'role' => 'student',
            'course' => 'BSCS',
            'section_id' => $section->id,
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.herminado@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('student.pending-approval'));
        $this->assertDatabaseHas('users', [
            'name' => 'Janessa Almosara Herminado',
            'email' => 'janessa.herminado@gmail.com',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
        ]);

        $user = User::where('email', 'janessa.herminado@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('2312001', $user->student_number);
    }

    public function test_student_registration_without_middle_name_succeeds(): void
    {
        $section = $this->createBscsSection(4);
        session(['reg_email_verified' => 'janessa.nomn@gmail.com']);

        $payload = [
            'first_name' => 'Janessa',
            'no_middle_name' => 1,
            'student_number' => '2312002',
            'middle_name' => '',
            'surname' => 'Herminado',
            'role' => 'student',
            'course' => 'BSCS',
            'section_id' => $section->id,
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.nomn@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('student.pending-approval'));
        $user = User::where('email', 'janessa.nomn@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('2312002', $user->student_number);
    }

    public function test_student_registration_requires_an_official_student_id(): void
    {
        $section = $this->createBscsSection(1);
        session(['reg_email_verified' => 'student1@gmail.com']);
        $payload1 = [
            'first_name' => 'Alice',
            'surname' => 'Smith',
            'role' => 'student',
            'course' => 'BSCS',
            'section_id' => $section->id,
            'year_level' => 1,
            'semester' => '1',
            'email' => 'student1@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];
        $this->post('/register', $payload1)
            ->assertSessionHasErrors('student_number');
        $this->assertDatabaseMissing('users', ['email' => 'student1@gmail.com']);

        $payload1['student_number'] = '2312101';
        $this->post('/register', $payload1)->assertRedirect(route('student.pending-approval'));

        $student1 = User::where('email', 'student1@gmail.com')->first();
        $this->assertNotNull($student1);
        $this->assertSame('2312101', $student1->student_number);

        auth()->logout();
        session(['reg_email_verified' => 'student2@gmail.com']);
        $payload2 = [
            'first_name' => 'Bob',
            'student_number' => '2312205',
            'surname' => 'Jones',
            'role' => 'student',
            'course' => 'BSCS',
            'section_id' => $section->id,
            'year_level' => 1,
            'semester' => '1',
            'email' => 'student2@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];
        $this->post('/register', $payload2)->assertRedirect(route('student.pending-approval'));

        $student2 = User::where('email', 'student2@gmail.com')->first();
        $this->assertNotNull($student2);
        $this->assertSame('2312205', $student2->student_number);

        // Student IDs must be strictly unique and permanent
        $this->assertNotEquals($student1->student_number, $student2->student_number);
    }

    public function test_admin_store_student_auto_generates_id_and_flashes_to_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
        $section = $this->createBscsSection(1);

        session(['admin_reg_email_verified' => 'admin.created@gmail.com']);

        $response = $this->actingAs($admin)->post(route('admin.student.store'), [
            'name' => 'Admin Created Student',
            'email' => 'admin.created@gmail.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'section_id' => $section->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = User::where('email', 'admin.created@gmail.com')->first();
        $this->assertNotNull($student);
        $this->assertNotEmpty($student->student_number);

        $response->assertSessionHas('created_student_id', $student->student_number);
        $response->assertSessionHas('created_student_name', $student->name);
    }

    public function test_parent_registration_does_not_require_student_fields(): void
    {
        session(['reg_email_verified' => 'parent.test@example.com']);

        $payload = [
            'first_name' => 'Maria',
            'surname' => 'Herminado',
            'role' => 'parent',
            'email' => 'parent.test@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Herminado',
            'email' => 'parent.test@example.com',
            'role' => 'parent',
        ]);
    }

    public function test_register_interface_renders_student_id_input_field(): void
    {
        $response = $this->get('/register');
        $response->assertOk();
        $response->assertSee('name="student_number"', false);
        $response->assertSee('Student ID / Number', false);
    }

    public function test_student_registration_with_custom_student_id_preserves_id(): void
    {
        $section = $this->createBscsSection(3);
        session(['reg_email_verified' => 'custom.student@gmail.com']);

        $payload = [
            'first_name' => 'Custom',
            'surname' => 'Student',
            'student_number' => '2319999',
            'role' => 'student',
            'course' => 'BSCS',
            'section_id' => $section->id,
            'year_level' => 3,
            'semester' => '2',
            'email' => 'custom.student@gmail.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);
        $response->assertRedirect(route('student.pending-approval'));

        $this->assertDatabaseHas('users', [
            'email' => 'custom.student@gmail.com',
            'student_number' => '2319999',
            'role' => 'student',
        ]);
    }

    public function test_student_registration_with_duplicate_student_id_fails_validation(): void
    {
        User::factory()->create([
            'student_number' => '2318888',
            'role' => 'student',
        ]);

        session(['reg_email_verified' => 'dup.student@example.com']);

        $payload = [
            'first_name' => 'Duplicate',
            'surname' => 'Student',
            'student_number' => '2318888',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 3,
            'semester' => '2',
            'email' => 'dup.student@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);
        $response->assertSessionHasErrors(['student_number']);
    }
}
