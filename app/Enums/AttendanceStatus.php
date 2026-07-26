<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case PRESENT = 'Present';
    case LATE = 'Late';
    case ABSENT = 'Absent';

    public function color(): string
    {
        return match($this) {
            self::PRESENT => 'success',
            self::LATE => 'warning',
            self::ABSENT => 'danger',
        };
    }
}
