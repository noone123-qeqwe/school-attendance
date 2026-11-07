<?php

namespace Tests\Unit;

use App\Exceptions\AttendanceException;
use Tests\TestCase;

class AppExceptionTest extends TestCase
{
    public function test_domain_exception_has_safe_metadata_and_http_status(): void
    {
        $exception = AttendanceException::invalidToken('sensitive-token-value');

        $this->assertSame('INVALID_TOKEN', $exception->errorCode());
        $this->assertSame(422, $exception->httpStatus());
        $this->assertStringNotContainsString('sensitive-token-value', json_encode($exception->safeContext()));
    }

    public function test_sensitive_context_is_omitted_from_logs(): void
    {
        $exception = new AttendanceException(
            context: [
                'attempt' => 3,
                'token' => 'secret-token',
                'distance_meters' => 15.5,
                'nested' => ['otp' => '123456', 'reason' => 'outside_radius'],
            ],
        );

        $this->assertSame([
            'attempt' => 3,
            'distance_meters' => 15.5,
            'nested' => ['reason' => 'outside_radius'],
        ], $exception->safeContext());
    }
}
