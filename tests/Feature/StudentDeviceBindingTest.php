<?php

namespace Tests\Feature;

use App\Models\DeviceBinding;
use App\Models\User;
use App\Services\DeviceBindingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class StudentDeviceBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_device_is_bound_on_login(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => 'S260001',
            'password' => bcrypt('Password123!'),
        ]);

        $deviceUuid = 'dev_uuid_' . bin2hex(random_bytes(16));

        $service = app(DeviceBindingService::class);
        $request = Request::create('/login', 'POST', [
            'device_key' => $deviceUuid,
            'device_fingerprint' => $deviceUuid,
        ]);
        $request->setUserResolver(fn () => $student);

        $service->bind($student, $request);

        $binding = DeviceBinding::where('user_id', $student->id)->first();
        $this->assertNotNull($binding);
        $this->assertNotEmpty($binding->device_hash);

        // Verification via matching header
        $checkRequest = Request::create('/qr/scan-process', 'POST');
        $checkRequest->headers->set('X-Device-Key', $deviceUuid);
        $this->assertTrue($service->isCurrentDevice($student, $checkRequest));

        // Verification via matching cookie
        $cookieRequest = Request::create('/qr/scan-process', 'POST');
        $cookieRequest->cookies->set(DeviceBindingService::COOKIE_NAME, $deviceUuid);
        $this->assertTrue($service->isCurrentDevice($student, $cookieRequest));

        // Verification via matching request body
        $bodyRequest = Request::create('/qr/scan-process', 'POST', [
            'device_key' => $deviceUuid,
        ]);
        $this->assertTrue($service->isCurrentDevice($student, $bodyRequest));

        // Unknown device key fails
        $unknownRequest = Request::create('/qr/scan-process', 'POST');
        $unknownRequest->headers->set('X-Device-Key', 'random_unrecognized_device');
        $this->assertFalse($service->isCurrentDevice($student, $unknownRequest));
    }

    public function test_authenticated_student_on_same_platform_passes_tier_4(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => 'S260002',
        ]);

        $ua = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1';

        $service = app(DeviceBindingService::class);
        $bindReq = Request::create('/login', 'POST');
        $bindReq->headers->set('User-Agent', $ua);
        $bindReq->setUserResolver(fn () => $student);

        $service->bind($student, $bindReq);

        $this->actingAs($student);

        // Mobile data IP change with same browser platform core
        $mobileRoamingReq = Request::create('/qr/scan-process', 'POST', [], [], [], [
            'REMOTE_ADDR' => '120.29.111.222', // roaming IP
            'HTTP_USER_AGENT' => $ua,
        ]);

        $this->assertTrue($service->isCurrentDevice($student, $mobileRoamingReq));
    }

    public function test_api_login_binds_student_device_automatically(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => 'S260003',
            'password' => bcrypt('Secret123!'),
        ]);

        $devKey = 'native_android_key_123';
        $response = $this->postJson('/api/login', [
            'identifier' => 'S260003',
            'password' => 'Secret123!',
            'device_key' => $devKey,
            'device_model' => 'Samsung Galaxy S23',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'device_bound' => true,
        ]);

        $binding = DeviceBinding::where('user_id', $student->id)->first();
        $this->assertNotNull($binding);
        $this->assertStringContainsString('Samsung Galaxy S23', (string)$binding->device_name);
    }

    public function test_hardware_fingerprint_verification(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'student_number' => 'S260004',
        ]);

        $service = app(DeviceBindingService::class);
        $hwFp = 'hw_fp_canvas_screen_1080x2400';
        $bindReq = Request::create('/login', 'POST', [
            'device_key' => 'cookie_key_initial',
            'device_fingerprint' => $hwFp,
        ]);
        $bindReq->setUserResolver(fn () => $student);
        $service->bind($student, $bindReq);

        $binding = DeviceBinding::where('user_id', $student->id)->first();
        $this->assertNotNull($binding->hardware_fingerprint);

        // Even if cookie is absent, presenting the matching hardware fingerprint verifies
        $fpReq = Request::create('/qr/scan-process', 'POST', [
            'device_fingerprint' => $hwFp,
        ]);
        $this->assertTrue($service->isCurrentDevice($student, $fpReq));
    }

    public function test_admin_can_reset_student_device_binding(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['role' => 'student']);

        DeviceBinding::create([
            'user_id' => $student->id,
            'device_hash' => 'dummy_hash',
            'device_name' => 'Old Device',
        ]);

        $this->assertDatabaseHas('device_bindings', ['user_id' => $student->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.student.reset_device', $student->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('device_bindings', ['user_id' => $student->id]);
    }

    public function test_student_profile_displays_bound_device(): void
    {
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'John Doe',
        ]);

        DeviceBinding::create([
            'user_id' => $student->id,
            'device_hash' => 'dummy_hash',
            'device_name' => 'Apple iPhone 15 Pro',
            'ip_address' => '192.168.1.50',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($student)
            ->get(route('profile'));

        $response->assertStatus(200);
        $response->assertSee('Bound Attendance Device');
        $response->assertSee('Apple iPhone 15 Pro');
        $response->assertSee('Verified');
    }

    public function test_admin_student_detail_displays_bound_device_and_reset_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create([
            'role' => 'student',
            'name' => 'Jane Smith',
        ]);

        DeviceBinding::create([
            'user_id' => $student->id,
            'device_hash' => 'dummy_hash',
            'device_name' => 'Google Pixel 8',
            'ip_address' => '10.0.0.12',
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.student', $student->id));

        $response->assertStatus(200);
        $response->assertSee('Bound Attendance Device');
        $response->assertSee('Google Pixel 8');
        $response->assertSee('Reset Binding');
    }
}
