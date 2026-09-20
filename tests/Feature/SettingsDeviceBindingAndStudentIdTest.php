<?php

namespace Tests\Feature;

use App\Models\DeviceBinding;
use App\Models\User;
use App\Services\StudentIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsDeviceBindingAndStudentIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_id_generator_produces_7_character_id(): void
    {
        $service = app(StudentIdService::class);
        $id = $service->generateNextId('2026');

        $this->assertEquals(7, strlen($id), "Generated student ID '{$id}' must be exactly 7 characters.");
        $this->assertStringStartsWith('26', $id);
    }

    public function test_student_registration_accepts_7_character_student_id(): void
    {
        session(['reg_email_verified' => 'valid7@example.com']);

        $response = $this->post('/register', [
            'first_name'            => 'Valid',
            'surname'               => 'Seven',
            'student_number'        => '2311969', // 7 chars
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 2,
            'semester'              => '1',
            'email'                 => 'valid7@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => '1',
        ]);

        $response->assertRedirect('/home');
        $this->assertDatabaseHas('users', [
            'email'          => 'valid7@example.com',
            'student_number' => '2311969',
        ]);
    }

    public function test_student_registration_rejects_student_id_longer_than_7_characters(): void
    {
        session(['reg_email_verified' => 'invalid8@example.com']);

        $response = $this->post('/register', [
            'first_name'            => 'Invalid',
            'surname'               => 'Eight',
            'student_number'        => '20260001', // 8 chars (exceeds 7)
            'role'                  => 'student',
            'course'                => 'BSCS',
            'year_level'            => 1,
            'semester'              => '1',
            'email'                 => 'invalid8@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => '1',
        ]);

        $response->assertSessionHasErrors('student_number');
    }

    public function test_settings_page_displays_device_binding_tab_and_controls(): void
    {
        $student = User::factory()->create([
            'role'           => 'student',
            'student_number' => '2600001',
        ]);

        $response = $this->actingAs($student)->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('Device Binding');
        $response->assertSee('tab-device');
        $response->assertSee('Bind to This Device');
    }

    public function test_authenticated_user_can_check_device_status_via_api(): void
    {
        $student = User::factory()->create([
            'role'           => 'student',
            'student_number' => '2600002',
        ]);

        $response = $this->actingAs($student)->getJson(route('device.status'));

        $response->assertStatus(200);
        $response->assertJson([
            'success'  => true,
            'is_bound' => false,
        ]);
    }

    public function test_authenticated_user_can_bind_and_unbind_device_in_settings(): void
    {
        $student = User::factory()->create([
            'role'           => 'student',
            'student_number' => '2600003',
        ]);

        $deviceKey = 'browser_key_random_' . bin2hex(random_bytes(8));
        $deviceFp = 'hw_fp_' . bin2hex(random_bytes(8));

        // 1. Bind current device
        $bindResponse = $this->actingAs($student)->postJson(route('device.bind'), [
            'device_key'         => $deviceKey,
            'device_fingerprint' => $deviceFp,
            'device_model'       => 'MacBook Pro (Chrome)',
        ], [
            'X-Device-Key'         => $deviceKey,
            'X-Device-Fingerprint' => $deviceFp,
            'X-Device-Model'       => 'MacBook Pro (Chrome)',
        ]);

        $bindResponse->assertStatus(200);
        $bindResponse->assertJson([
            'success'           => true,
            'is_bound'          => true,
            'is_current_device' => true,
        ]);

        $this->assertDatabaseHas('device_bindings', [
            'user_id' => $student->id,
        ]);

        // 2. Status now reflects bound device
        $statusResponse = $this->actingAs($student)->getJson(route('device.status'), [
            'X-Device-Key' => $deviceKey,
        ]);
        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'success'  => true,
            'is_bound' => true,
        ]);

        // 3. Unbind current device
        $unbindResponse = $this->actingAs($student)->postJson(route('device.unbind'));

        $unbindResponse->assertStatus(200);
        $unbindResponse->assertJson([
            'success'  => true,
            'is_bound' => false,
        ]);

        $this->assertDatabaseMissing('device_bindings', [
            'user_id' => $student->id,
        ]);

        // 4. Status is now unbound
        $afterStatus = $this->actingAs($student)->getJson(route('device.status'));
        $afterStatus->assertJson([
            'success'  => true,
            'is_bound' => false,
        ]);
    }
}
