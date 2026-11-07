<?php

namespace App\Exceptions;

/**
 * Exception for device binding / verification failures.
 */
class DeviceBindingException extends AppException
{
    public function __construct(
        string $message = 'A device verification error occurred.',
        string $errorCode = 'DEVICE_ERROR',
        array $context = [],
        int $httpStatus = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $context, $httpStatus, $previous);
    }

    public static function deviceMismatch(): static
    {
        return new static(
            'This device does not match your registered device. Please use your bound device or contact an administrator.',
            'DEVICE_MISMATCH',
            [],
            403
        );
    }

    public static function deviceLocked(string $reason = 'policy'): static
    {
        return new static(
            'Your device has been locked by an administrator. Please contact the school office.',
            'DEVICE_LOCKED',
            ['reason' => $reason],
            403
        );
    }

    public static function proxyDetected(string $peerName = 'Another student'): static
    {
        return new static(
            'This device was already used by another student in this session. Proxy attendance is not allowed.',
            'PROXY_DETECTED',
            ['peer_name' => $peerName],
            403
        );
    }

    public static function conflictingKeys(): static
    {
        return new static(
            'Conflicting device identifiers detected. Please clear your browser data and try again.',
            'CONFLICTING_KEYS',
            [],
            400
        );
    }
}
