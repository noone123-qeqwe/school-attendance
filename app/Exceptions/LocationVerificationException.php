<?php

namespace App\Exceptions;

/**
 * Exception for GPS / location verification failures.
 */
class LocationVerificationException extends AppException
{
    public function __construct(
        string $message = 'A location verification error occurred.',
        string $errorCode = 'LOCATION_ERROR',
        array $context = [],
        int $httpStatus = 422,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $errorCode, $context, $httpStatus, $previous);
    }

    public static function outsideRadius(float $distance, int $radius): static
    {
        return new static(
            'You are too far from the classroom. Please move closer and try again.',
            'OUTSIDE_RADIUS',
            ['distance_meters' => round($distance), 'radius_meters' => $radius],
            422
        );
    }

    public static function staleFix(int $ageMs): static
    {
        return new static(
            'Your location data is outdated. Please enable GPS and try again.',
            'STALE_LOCATION_FIX',
            ['age_ms' => $ageMs],
            422
        );
    }

    public static function impossibleJump(float $distanceMeters, float $speedKmh): static
    {
        return new static(
            'An impossible location jump was detected. This has been flagged for review.',
            'IMPOSSIBLE_JUMP',
            ['distance_meters' => round($distanceMeters), 'speed_kmh' => round($speedKmh)],
            422
        );
    }

    public static function lowAccuracy(float $accuracy): static
    {
        return new static(
            'Your GPS accuracy is too low. Please move to an open area or enable Wi-Fi for better accuracy.',
            'LOW_ACCURACY',
            ['accuracy_meters' => round($accuracy)],
            422
        );
    }
}
