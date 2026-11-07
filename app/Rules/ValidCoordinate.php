<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate that a coordinate value is within acceptable GPS range.
 */
class ValidCoordinate implements ValidationRule
{
    public function __construct(
        private string $type = 'latitude'
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_numeric($value)) {
            $fail("The {$attribute} must be a valid number.");
            return;
        }

        $val = (float) $value;

        if ($this->type === 'latitude') {
            if ($val < -90 || $val > 90) {
                $fail("The {$attribute} must be between -90 and 90 degrees.");
            }
        } else {
            if ($val < -180 || $val > 180) {
                $fail("The {$attribute} must be between -180 and 180 degrees.");
            }
        }
    }

    public static function latitude(): static
    {
        return new static('latitude');
    }

    public static function longitude(): static
    {
        return new static('longitude');
    }
}
