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

    public function test_register_page_course_is_a_selectable_dropdown(): void
    {
        $response = $this->get('/register');

        $response->assertSee('<select name="course" id="course">', false);
        $response->assertSee('<option value="BSCS"', false);
        $response->assertSee('<option value="BSIT"', false);
        $response->assertSee('<option value="BSIS"', false);
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
            'student_number' => '2311969',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.herminado@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', [
            'name' => 'Janessa Almosara Herminado',
            'email' => 'janessa.herminado@example.com',
            'role' => 'student',
            'student_number' => '2311969',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
        ]);
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
            'student_number' => '2311970',
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
            'email' => 'janessa.nomn@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', [
            'name' => 'Janessa Herminado',
            'email' => 'janessa.nomn@example.com',
            'role' => 'student',
            'student_number' => '2311970',
        ]);
    }

    public function test_student_registration_rejects_invalid_student_number_length(): void
    {
        session(['reg_email_verified' => 'invalid.id@example.com']);

        $payload = [
            'first_name' => 'Janessa',
            'surname' => 'Herminado',
            'role' => 'student',
            'student_number' => '231196', // Only 6 characters
            'course' => 'BSCS',
            'year_level' => 4,
            'semester' => '1',
            'email' => 'invalid.id@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors(['student_number']);
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid.id@example.com',
        ]);
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
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertDatabaseHas('users', [
            'name' => 'Maria Herminado',
            'email' => 'parent.test@example.com',
            'role' => 'parent',
        ]);
    }
}
