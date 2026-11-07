<?php

namespace App\Rules;

use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validate an academic year label and its submitted date range together. */
class ValidAcademicYear implements ValidationRule
{
    public function __construct(
        private readonly mixed $startDate,
        private readonly mixed $endDate,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || preg_match('/^(\d{4})-(\d{4})$/D', trim($value), $years) !== 1) {
            $fail('The academic year must use the format YYYY-YYYY.');

            return;
        }

        $firstYear = (int) $years[1];
        $secondYear = (int) $years[2];
        if ($firstYear < 2000 || $firstYear > 2199 || $secondYear !== $firstYear + 1) {
            $fail('The academic year must contain two consecutive valid years.');

            return;
        }

        $start = self::parseDate($this->startDate);
        $end = self::parseDate($this->endDate);
        if ($start === null || $end === null || $end <= $start) {
            $fail('The academic year start and end dates must be valid, and the end date must be later.');

            return;
        }

        $startYear = (int) $start->format('Y');
        $endYear = (int) $end->format('Y');
        if ($startYear < $firstYear || $startYear > $secondYear || $endYear < $firstYear || $endYear > $secondYear) {
            $fail('The academic year dates must fall within the years named in the academic year.');
        }
    }

    private static function parseDate(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value) !== 1) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }
}
