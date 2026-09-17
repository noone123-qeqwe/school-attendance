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
            'student_number' => 'STU-2026-99999',
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
            'student_number' => 'STU-2026-88888',
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
}
