<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParentPhoneLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $parentWithEmail;
    private User $parentWithoutEmail;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Parent with an email and phone
        $this->parentWithEmail = User::create([
            'name'      => 'Clarito Ole',
            'email'     => 'clarito.ole@gmail.com',
            'phone'     => '09248901234',
            'role'      => 'parent',
            'password'  => Hash::make('secret123'),
            'is_active' => true,
        ]);

        // 2. Parent who DOES NOT have a Gmail/email address (email is NULL)
        $this->parentWithoutEmail = User::create([
            'name'      => 'Nanay Maria',
            'email'     => null,
            'phone'     => '09175556677',
            'role'      => 'parent',
            'password'  => Hash::make('parentPass456'),
            'is_active' => true,
        ]);

        // 3. Regular student account
        $this->student = User::create([
            'name'           => 'Jack C. Ole',
            'email'          => 'jack.ole@osmena.edu',
            'student_number' => '1234567',
            'role'           => 'student',
            'course'         => 'BSCS',
            'year_level'     => 1,
            'semester'       => 1,
            'password'       => Hash::make('student123'),
            'is_active'      => true,
        ]);
    }

    public function test_parent_can_login_with_local_phone_format(): void
    {
        $response = $this->post('/login', [
            'identifier' => '09248901234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_can_login_with_international_plus_format(): void
    {
        $response = $this->post('/login', [
            'identifier' => '+639248901234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_can_login_with_international_without_plus(): void
    {
        $response = $this->post('/login', [
            'identifier' => '639248901234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_can_login_with_ten_digit_phone(): void
    {
        $response = $this->post('/login', [
            'identifier' => '9248901234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_can_login_with_hyphenated_phone(): void
    {
        $response = $this->post('/login', [
            'identifier' => '0924-890-1234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_can_login_with_spaced_phone(): void
    {
        $response = $this->post('/login', [
            'identifier' => '0924 890 1234',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_parent_without_gmail_can_login_with_phone_and_password(): void
    {
        $response = $this->post('/login', [
            'identifier' => '09175556677',
            'password'   => 'parentPass456',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithoutEmail);
    }

    public function test_parent_without_gmail_can_login_via_international_format(): void
    {
        $response = $this->post('/login', [
            'identifier' => '+639175556677',
            'password'   => 'parentPass456',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithoutEmail);
    }

    public function test_parent_phone_login_fails_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'identifier' => '09248901234',
            'password'   => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest();
    }

    public function test_student_can_still_login_with_student_number(): void
    {
        $response = $this->post('/login', [
            'identifier' => '1234567',
            'password'   => 'student123',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($this->student);
    }

    public function test_user_can_still_login_with_email(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'clarito.ole@gmail.com',
            'password'   => 'secret123',
        ]);

        $response->assertRedirect(route('parent.dashboard'));
        $this->assertAuthenticatedAs($this->parentWithEmail);
    }

    public function test_api_login_supports_phone_number(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'identifier' => '09175556677',
            'password'   => 'parentPass456',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status'  => 'success',
            'success' => true,
        ]);
        $this->assertNotEmpty($response->json('data.token') ?? $response->json('token'));
    }
}
