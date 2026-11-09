<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class LegacyParentAlertBackfillService
{
    public function run(): int
    {
        $updated = 0;

        DB::table('notifications')
            ->where('type', 'absence_alert')
            ->whereNull('related_user_id')
            ->orderBy('id')
            ->chunkById(200, function ($notifications) use (&$updated): void {
                foreach ($notifications as $notification) {
                    $studentId = $this->reliablyRelatedStudentId($notification);
                    if ($studentId === null) {
                        continue;
                    }

                    $updated += DB::table('notifications')
                        ->where('id', $notification->id)
                        ->whereNull('related_user_id')
                        ->update(['related_user_id' => $studentId]);
                }
            });

        return $updated;
    }

    private function reliablyRelatedStudentId(object $notification): ?int
    {
        if (!preg_match('/\A(.+?) was marked Absent in (.+?) on ([A-Z][a-z]{2} \d{2}, \d{4})\.\z/', (string) $notification->message, $matches)) {
            return null;
        }

        $subjectCode = trim((string) ($notification->subject_code ?? ''));
        if ($subjectCode === '' || !hash_equals($subjectCode, trim($matches[2]))) {
            return null;
        }

        try {
            $parsedDate = CarbonImmutable::createFromFormat('M d, Y', $matches[3]);
            if ($parsedDate === null) {
                return null;
            }

            $attendanceDate = $parsedDate->toDateString();
        } catch (\Throwable) {
            return null;
        }

        $matchingStudentIds = DB::table('parent_student')
            ->join('users', 'users.id', '=', 'parent_student.student_id')
            ->where('parent_student.parent_id', $notification->user_id)
            ->where('users.role', 'student')
            ->whereNull('users.deleted_at')
            ->get(['users.id', 'users.name'])
            ->filter(fn (object $student): bool => hash_equals((string) $student->name, $matches[1]))
            ->pluck('id')
            ->unique()
            ->values();

        if ($matchingStudentIds->count() !== 1) {
            return null;
        }

        $studentId = (int) $matchingStudentIds->first();
        $hasMatchingAbsence = DB::table('attendances')
            ->where('user_id', $studentId)
            ->where('subject_code', $subjectCode)
            ->whereDate('date', $attendanceDate)
            ->where('status', 'Absent')
            ->whereNull('deleted_at')
            ->exists();

        return $hasMatchingAbsence ? $studentId : null;
    }
}
