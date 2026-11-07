<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate a Philippine phone number format.
 * Accepts: 09xxxxxxxxx, +639xxxxxxxxx, 639xxxxxxxxx
 */
class ValidPhoneNumber implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (self::normalize($value) === null) {
            $fail('The :attribute must be a valid Philippine mobile number (e.g., 09xxxxxxxxx).');
        }
    }

    /** Normalize accepted Philippine mobile formats to Semaphore's 639... form. */
    public static function normalize(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $cleaned = preg_replace('/[\s\-()]/', '', trim($value));
        if (!is_string($cleaned)) {
            return null;
        }

        if (preg_match('/^09\d{9}$/', $cleaned)) {
            return '63' . substr($cleaned, 1);
        }

        if (preg_match('/^\+639\d{9}$/', $cleaned)) {
            return substr($cleaned, 1);
        }

        return preg_match('/^639\d{9}$/', $cleaned) ? $cleaned : null;
    }
}
