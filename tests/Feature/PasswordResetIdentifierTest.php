<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Otp;
use Tests\TestCase;

class PasswordResetIdentifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_non_enumerating_response()
    {
        // Try resetting with a non-existent email
        $response = $this->postJson('/api/reset-password', [
            'identifier' => 'nonexistent@example.com',
            'otp' => '123456',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Invalid or expired reset code.'
                 ]);
    }

    public function test_reset_password_works_with_identifier()
    {
        $user = User::factory()->create([
            'student_number' => 'stu-12345',
            'password' => Hash::make('oldpassword')
        ]);

        $otp = Otp::generate($user->id, 'forgot_password');

        $response = $this->postJson('/api/reset-password', [
            'identifier' => 'STU-12345',
            'otp' => $otp->code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Password reset successfully.'
                 ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}
