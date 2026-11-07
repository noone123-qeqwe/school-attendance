<?php

namespace Tests\Unit;

use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceQrTokenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceQrTokenServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_token_round_trips_and_tampering_is_rejected(): void
    {
        config(['app.key' => 'base64:' . base64_encode(str_repeat('k', 32))]);
        $teacher = User::factory()->teacher()->create();
        Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'SIGNED-101']);
        $session = AttendanceSession::create([
            'subject_code' => 'SIGNED-101', 'created_by' => $teacher->id, 'token' => 'legacy-signed-token',
            'expires_at' => now()->addSeconds(15), 'session_ends_at' => now()->addHour(), 'active' => true,
        ]);
        $service = app(AttendanceQrTokenService::class);

        $issued = $service->issue($session, $teacher);
        $signed = $service->tokenFor($issued);

        $this->assertSame('valid', $service->inspect($signed)['status']);
        $tampered = substr($signed, 0, -1) . (str_ends_with($signed, 'a') ? 'b' : 'a');
        $this->assertSame('invalid', $service->inspect($tampered)['status']);
    }

    public function test_expired_signed_token_is_rejected(): void
    {
        config(['app.key' => 'base64:' . base64_encode(str_repeat('q', 32))]);
        $teacher = User::factory()->teacher()->create();
        Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'SIGNED-EXP']);
        $session = AttendanceSession::create([
            'subject_code' => 'SIGNED-EXP', 'created_by' => $teacher->id, 'token' => 'legacy-expired-token',
            'expires_at' => now()->addSeconds(15), 'session_ends_at' => now()->addHour(), 'active' => true,
        ]);
        $service = app(AttendanceQrTokenService::class);
        $issued = $service->issue($session, $teacher);
        $issued->update(['expires_at' => now()->subSecond()]);

        $this->assertSame('expired', $service->inspect($service->tokenFor($issued))['status']);
    }
}
