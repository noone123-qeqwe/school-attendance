<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class ClassScheduleLock
{
    /** Acquire the database row lock inside a transaction before validating schedule writes. */
    public static function acquire(): void
    {
        if (!DB::table('class_schedule_locks')->where('id', 1)->lockForUpdate()->first()) {
            throw new \RuntimeException('The class schedule lock row is missing. Run database migrations.');
        }
    }
}
