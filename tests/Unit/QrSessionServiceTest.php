<?php

namespace Tests\Unit;

use App\Models\AttendanceSession;
use App\Models\Subject;
use App\Models\User;
use App\Services\QrSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QrSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_a_session_closes_the_previous_session_and_sets_a_viable_window(): void
    {
        $teacher = User::factory()->teacher()->create();
        Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'QR-101']);
        $previous = AttendanceSession::create([
            'subject_code' => 'QR-101', 'created_by' => $teacher->id, 'token' => 'old-qr-session-token',
            'expires_at' => now()->addMinutes(5), 'session_ends_at' => now()->addHour(), 'active' => true,
        ]);

        $session = app(QrSessionService::class)->startSession($teacher->id, 'QR-101');

        $this->assertFalse($previous->fresh()->active);
        $this->assertTrue($session->active);
        $this->assertNotSame('', $session->token);
        $this->assertGreaterThan(now()->timestamp, $session->session_ends_at->timestamp);
    }

    public function test_refresh_rotates_token_and_keeps_the_previous_value_for_the_grace_window(): void
    {
        Cache::flush();
        $teacher = User::factory()->teacher()->create();
        $subject = Subject::factory()->create(['instructor_id' => $teacher->id, 'code' => 'QR-ROTATE']);
        $session = AttendanceSession::create([
            'subject_code' => $subject->code, 'created_by' => $teacher->id, 'token' => 'old-rotation-token',
            'session_code' => '123456', 'expires_at' => now()->addSeconds(10),
            'session_ends_at' => now()->addHour(), 'active' => true,
        ]);

        $rotated = app(QrSessionService::class)->refreshToken($session);

        $this->assertNotSame('old-rotation-token', $rotated->token);
        $this->assertSame('old-rotation-token', $rotated->previous_token);
        $this->assertSame($session->id, Cache::get('session_prev_token_old-rotation-token')['session_id'] ?? null);
    }
}
