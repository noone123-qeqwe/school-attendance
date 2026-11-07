<?php

namespace App\Exceptions;

/**
 * Exception for biometric (face match / fingerprint) failures.
 */
class BiometricException extends AppException
{
    public function __construct(
        string $message = 'A biometric verification error occurred.',
        string $errorCode = 'BIOMETRIC_ERROR',
        array $context = [],
        int $httpStatus = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $context, $httpStatus, $previous);
    }

    public static function noProfilePhoto(): static
    {
        return new static(
            'No profile photo found. Please upload a clear, front-facing photo in Settings.',
            'NO_PROFILE_PHOTO',
            [],
            422
        );
    }

    public static function faceNotFound(string $imageType = 'image'): static
    {
        return new static(
            "No face detected in the {$imageType}. Please ensure your face is clearly visible.",
            'FACE_NOT_FOUND',
            ['image_type' => $imageType],
            422
        );
    }

    public static function mismatch(float $similarity = 0.0): static
    {
        return new static(
            'Your live face did not match your registered profile photo. Please try again in good lighting.',
            'BIOMETRIC_MISMATCH',
            ['similarity' => $similarity],
            422
        );
    }

    public static function matcherUnavailable(): static
    {
        return new static(
            'Face matching is temporarily unavailable. Please use fingerprint or ask your teacher for help.',
            'MATCHER_UNAVAILABLE',
            [],
            503
        );
    }
}
