<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\FaceVerificationAttemptLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceVerificationAttemptLimiterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_locks_after_five_failed_checks_for_one_student_and_session(): void
    {
        $user = User::factory()->create(['role' => 'student']);
        $limiter = app(FaceVerificationAttemptLimiter::class);

        for ($attempt = 1; $attempt <= FaceVerificationAttemptLimiter::MAX_ATTEMPTS; $attempt++) {
            $this->assertSame($attempt, $limiter->recordFailure($user, 42));
        }

        $this->assertTrue($limiter->isLocked($user, 42));
        $this->assertFalse($limiter->isLocked($user, 43));
        $this->assertGreaterThan(0, $limiter->secondsUntilRetry($user, 42));

        $limiter->clear($user, 42);
        $this->assertFalse($limiter->isLocked($user, 42));
    }
}
