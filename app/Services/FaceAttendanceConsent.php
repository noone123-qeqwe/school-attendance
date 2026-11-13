<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FaceAttendanceConsent
{
    public const NOTICE_VERSION = '2026-11-13';
    public const SOURCES = ['adult_student', 'guardian'];

    public function requiredForSelfCheckIn(User $student): bool
    {
        return config('face_match.attendance_enabled')
            && app(FeatureFlagService::class)->enabled('feature.face_verification')
            && $this->approved($student);
    }

    public function approved(User $student): bool
    {
        return $student->isStudent() && DB::table('face_attendance_consents')
            ->where('student_id', $student->id)
            ->where('notice_version', self::NOTICE_VERSION)
            ->whereIn('source', self::SOURCES)
            ->exists();
    }

    public function update(User $student, User $actor, ?string $source): void
    {
        abort_unless($student->isStudent(), 404);
        abort_unless($actor->isSuperAdmin() || ($source === null && $student->is($actor)), 403);
        abort_if($source !== null && !in_array($source, self::SOURCES, true), 422);

        DB::transaction(function () use ($student, $actor, $source) {
            User::query()->lockForUpdate()->findOrFail($student->id);
            $records = DB::table('face_attendance_consents');
            if ($source === null) {
                $records->where('student_id', $student->id)->delete();
            } else {
                $records->updateOrInsert(['student_id' => $student->id], [
                    'source' => $source,
                    'notice_version' => self::NOTICE_VERSION,
                    'recorded_by' => $actor->id,
                    'recorded_at' => now(),
                ]);
            }
        });
    }
}
