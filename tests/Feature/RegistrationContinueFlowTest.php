<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationContinueFlowTest extends TestCase
{
    use RefreshDatabase;

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
        // Simulate email verification session flag
        session(['reg_email_verified' => 'janessa.herminado@example.com']);

        $payload = [
            'first_name' => 'Janessa',
            'middle_name' => 'Almosara',
            'surname' => 'Herminado',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.herminado@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', [
            'name' => 'Janessa Almosara Herminado',
            'email' => 'janessa.herminado@example.com',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
        ]);

        $user = User::where('email', 'janessa.herminado@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->student_number);
    }

    public function test_student_registration_without_middle_name_succeeds(): void
    {
        session(['reg_email_verified' => 'janessa.nomn@example.com']);

        $payload = [
            'first_name' => 'Janessa',
            'no_middle_name' => 1,
            'middle_name' => '',
            'surname' => 'Herminado',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.nomn@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/home');
        $user = User::where('email', 'janessa.nomn@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->student_number);
    }

    public function test_student_registration_auto_generates_unique_sequential_student_ids(): void
    {
        session(['reg_email_verified' => 'student1@example.com']);
        $payload1 = [
            'first_name' => 'Alice',
            'surname' => 'Smith',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 1,
            'semester' => '1',
            'email' => 'student1@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];
        $this->post('/register', $payload1)->assertRedirect('/home');

        $student1 = User::where('email', 'student1@example.com')->first();
        $this->assertNotNull($student1);
        $this->assertNotEmpty($student1->student_number);

        auth()->logout();
        session(['reg_email_verified' => 'student2@example.com']);
        $payload2 = [
            'first_name' => 'Bob',
            'surname' => 'Jones',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 1,
            'semester' => '1',
            'email' => 'student2@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];
        $this->post('/register', $payload2)->assertRedirect('/home');

        $student2 = User::where('email', 'student2@example.com')->first();
        $this->assertNotNull($student2);
        $this->assertNotEmpty($student2->student_number);

        // Student IDs must be strictly unique and permanent
        $this->assertNotEquals($student1->student_number, $student2->student_number);
    }

    public function test_admin_store_student_auto_generates_id_and_flashes_to_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        session(['admin_reg_email_verified' => 'admin.created@example.com']);

        $response = $this->actingAs($admin)->post(route('admin.student.store'), [
            'name' => 'Admin Created Student',
            'email' => 'admin.created@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'year_level' => 1,
            'semester' => 1,
        ]);

        $response->assertRedirect(route('admin.students'));

        $student = User::where('email', 'admin.created@example.com')->first();
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
        session(['reg_email_verified' => 'custom.student@example.com']);

        $payload = [
            'first_name' => 'Custom',
            'surname' => 'Student',
            'student_number' => '2319999',
            'role' => 'student',
            'course' => 'BSCS',
            'year_level' => 3,
            'semester' => '2',
            'email' => 'custom.student@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'terms' => '1',
        ];

        $response = $this->post('/register', $payload);
        $response->assertRedirect('/home');

        $this->assertDatabaseHas('users', [
            'email' => 'custom.student@example.com',
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
