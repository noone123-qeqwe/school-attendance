<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validate the system's generated 7 digit YY + 5 digit sequence Student ID. */
class ValidStudentId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!self::isValid($value)) {
            $fail('The :attribute must be a valid 7-digit Student ID.');
        }
    }

    public static function isValid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^\d{7}$/D', $value) === 1
            && (int) substr($value, 2, 5) > 0;
    }
}
