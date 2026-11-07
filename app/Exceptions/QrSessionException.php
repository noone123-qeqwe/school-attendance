<?php

namespace App\Exceptions;

/**
 * Exception for QR attendance session errors.
 */
class QrSessionException extends AppException
{
    public function __construct(
        string $message = 'A QR session error occurred.',
        string $errorCode = 'QR_SESSION_ERROR',
        array $context = [],
        int $httpStatus = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $context, $httpStatus, $previous);
    }

    public static function subjectNotFound(string $code = ''): static
    {
        return new static('Subject not found.', 'SUBJECT_NOT_FOUND', ['subject_code' => $code], 404);
    }

    public static function sessionExpired(): static
    {
        return new static('The QR attendance session has expired.', 'QR_SESSION_EXPIRED', [], 410);
    }

    public static function noActiveSession(string $subjectCode = ''): static
    {
        return new static(
            'No active attendance session found for this subject.',
            'NO_ACTIVE_SESSION',
            ['subject_code' => $subjectCode],
            404
        );
    }

    public static function signedRotationActive(): static
    {
        return new static(
            'Signed QR rotation is active for this session.',
            'SIGNED_ROTATION_ACTIVE',
            [],
            409
        );
    }
}
