<?php

namespace App\Rules;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validate that only one attendance record exists per student, subject, and date.
 */
class UniqueAttendanceRecord implements ValidationRule
{
    public function __construct(
        private int $userId,
        private string $subjectCode,
        private ?string $date = null,
        private ?int $excludeAttendanceId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $date = $this->date
                ? CarbonImmutable::parse($this->date, 'Asia/Manila')->toDateString()
                : now('Asia/Manila')->toDateString();
        } catch (\Throwable) {
            $fail('The attendance date must be valid.');

            return;
        }

        $query = Attendance::withTrashed()
            ->where('user_id', $this->userId)
            ->where('subject_code', $this->subjectCode)
            ->whereDate('date', $date);

        if ($this->excludeAttendanceId) {
            $query->where('id', '!=', $this->excludeAttendanceId);
        }

        if ($query->exists()) {
            $fail('An attendance record already exists for this student in this subject today.');
        }
    }
}
