<?php

namespace App\Exceptions;

/**
 * Exception for attendance recording failures.
 */
class AttendanceException extends AppException
{
    public function __construct(
        string $message = 'An attendance error occurred.',
        string $errorCode = 'ATTENDANCE_ERROR',
        array $context = [],
        int $httpStatus = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $context, $httpStatus, $previous);
    }

    public static function sessionExpired(): static
    {
        return new static('The attendance session has expired.', 'SESSION_EXPIRED', [], 410);
    }

    public static function alreadyRecorded(): static
    {
        return new static('Attendance has already been recorded for this session.', 'ALREADY_RECORDED', [], 409);
    }

    public static function invalidToken(string $token = ''): static
    {
        return new static(
            'The QR code is invalid or has expired. Please scan the latest code.',
            'INVALID_TOKEN',
            ['token_present' => $token !== ''],
            422
        );
    }

    public static function outsideGeofence(float $distance, int $radius): static
    {
        return new static(
            'You are outside the classroom area. Please move closer and try again.',
            'OUTSIDE_GEOFENCE',
            ['distance' => $distance, 'radius' => $radius],
            422
        );
    }

    public static function notEnrolled(): static
    {
        return new static('You are not enrolled in this subject.', 'NOT_ENROLLED', [], 403);
    }
}
