<?php

namespace App\Rules;

use App\Models\ClassSchedule;
use Closure;
use DateTimeImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Schema;

/** Validate a class slot against the canonical class_schedules table. */
class ValidScheduleSlot implements ValidationRule
{
    public function __construct(
        private readonly string $day,
        private readonly string $startTime,
        private readonly string $endTime,
        private readonly ?int $teacherId = null,
        private readonly ?string $room = null,
        private readonly ?int $excludeScheduleId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $start = self::normalizeTime($this->startTime);
        $end = self::normalizeTime($this->endTime);

        if (!in_array($this->day, $validDays, true) || $start === null || $end === null || $start >= $end) {
            $fail('The schedule day and time range must be valid, with the end time after the start time.');

            return;
        }

        if (!Schema::hasTable('class_schedules')) {
            return;
        }

        $conflicts = ClassSchedule::query()
            ->where('day_of_week', $this->day)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start);

        if ($this->excludeScheduleId !== null) {
            $conflicts->whereKeyNot($this->excludeScheduleId);
        }

        if ($this->teacherId !== null && (clone $conflicts)->where('teacher_id', $this->teacherId)->exists()) {
            $fail('This teacher already has a class scheduled during this time slot on ' . $this->day . '.');

            return;
        }

        $room = trim((string) $this->room);
        if ($room !== '' && (clone $conflicts)
            ->whereRaw('LOWER(TRIM(room)) = ?', [mb_strtolower($room)])
            ->exists()) {
            $fail('This room is already booked during this time slot on ' . $this->day . '.');
        }
    }

    private static function normalizeTime(string $time): ?string
    {
        foreach (['!H:i', '!H:i:s'] as $format) {
            $parsed = DateTimeImmutable::createFromFormat($format, trim($time));
            $errors = DateTimeImmutable::getLastErrors();
            if ($parsed !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $parsed->format('H:i:s');
            }
        }

        return null;
    }
}
