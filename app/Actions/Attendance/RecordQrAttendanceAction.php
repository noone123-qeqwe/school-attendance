<?php

namespace App\Actions\Attendance;

use App\Events\TeacherAttendanceUpdated;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceSession;
use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use App\Services\AttendanceQrTokenService;
use App\Services\SubjectRosterService;
use Carbon\Carbon;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared atomic QR/code attendance check-in persistence and real-time broadcast action.
 */
class RecordQrAttendanceAction
{
    public function __construct(
        protected AttendanceQrTokenService $tokenService
    ) {}

    /**
     * Resolve whether check-in is Present or Late based on today's subject schedule.
     */
    public function resolveStatus(Subject $subject, Carbon $now, bool $matchWindowSlot = false): string
    {
        $todayDate = $now->toDateString();
        $todayName = $now->format('l');
        $startTime = null;

        $matchingSchedules = $subject->schedules->filter(
            fn ($sched) => strcasecmp(trim((string) ($sched->day ?? '')), $todayName) === 0
        );

        if ($matchingSchedules->isNotEmpty()) {
            $todaySchedule = $matchWindowSlot
                ? ($matchingSchedules->first(function ($sched) use ($now, $todayDate) {
                    $slotStart = Carbon::parse($todayDate . ' ' . $sched->start_time)->subMinutes(30);
                    $slotEnd = Carbon::parse($todayDate . ' ' . $sched->end_time)->addMinutes(30);
                    return $now->between($slotStart, $slotEnd);
                }) ?? $matchingSchedules->first())
                : $matchingSchedules->first();

            if (!empty($todaySchedule->start_time)) {
                $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            }
        }

        if (!$startTime && $subject->start_time) {
            $startTime = Carbon::parse($todayDate . ' ' . $subject->start_time);
        }

        if (!$startTime) {
            return 'Present';
        }

        $lateMinutes = (int) Setting::get('late_threshold', 15);

        return $now->lte($startTime->copy()->addMinutes($lateMinutes)) ? 'Present' : 'Late';
    }

    /**
     * Atomically insert or update an absent row into Present/Late within token validity.
     *
     * @return array{attendance: Attendance, already_recorded: bool}
     */
    public function persistCheckIn(
        User $user,
        Subject $subject,
        AttendanceSession $session,
        Carbon $now,
        string $status,
        mixed $latitude,
        mixed $longitude,
        mixed $accuracy,
        float $distance,
        string $recordedMethod,
        ?string $signedToken = null,
        bool $allowOverwritePresent = false,
        ?Closure $beforeWrite = null,
        bool $allowChallengeGrace = false
    ): array {
        $todayDate = $now->toDateString();
        $academicYearId = AcademicYear::where('is_current', true)->value('id');

        $attendanceData = [
            'user_id'                   => $user->id,
            'subject_id'                => $subject->id,
            'subject_code'              => $session->subject_code,
            'date'                      => $todayDate,
            'subject_name'              => $subject->name,
            'class'                     => $subject->section ?? $user->section ?? 'Regular',
            'session_id'                => $session->id,
            'status'                    => $status,
            'excused'                   => false,
            'excuse_note'               => null,
            'time_in'                   => $now->format('H:i:s'),
            'checked_in_at'             => $now,
            'last_location_check_at'    => $now,
            'last_latitude'             => $latitude,
            'last_longitude'            => $longitude,
            'last_accuracy'             => $accuracy,
            'last_distance_meters'      => $distance,
            'outside_since'             => null,
            'consecutive_outside_count' => 0,
            'escaped_at'                => null,
            'monitoring_status'         => 'active',
            'latitude'                  => $latitude,
            'longitude'                 => $longitude,
            'gps_accuracy'              => $accuracy,
            'method'                    => $recordedMethod,
            'academic_year_id'          => $academicYearId,
        ];

        $alreadyRecorded = false;

        $attendance = DB::transaction(function () use (
            $signedToken,
            $session,
            $user,
            $subject,
            $todayDate,
            $attendanceData,
            $allowOverwritePresent,
            $beforeWrite,
            $allowChallengeGrace,
            &$alreadyRecorded
        ) {
            if ($beforeWrite !== null) {
                $beforeWrite();
            }

            return $this->tokenService->recordWhileValid(
                $signedToken,
                $session,
                function () use ($user, $subject, $session, $todayDate, $attendanceData, $allowOverwritePresent, &$alreadyRecorded) {
                    if ($allowOverwritePresent) {
                        return Attendance::updateOrCreateRecord(
                            [
                                'user_id'      => $user->id,
                                'subject_id'   => $subject->id,
                                'subject_code' => $session->subject_code,
                                'date'         => $todayDate,
                            ],
                            collect($attendanceData)->except(['user_id', 'subject_id', 'subject_code', 'date'])->all()
                        );
                    }

                    $current = Attendance::withTrashed()
                        ->where('user_id', $user->id)
                        ->where('subject_code', $session->subject_code)
                        ->whereDate('date', $todayDate)
                        ->lockForUpdate()
                        ->first();

                    if ($current && ($current->status !== 'Absent' || $current->excused)) {
                        $alreadyRecorded = true;
                        return $current;
                    }

                    if ($current) {
                        if ($current->trashed()) {
                            $current->restore();
                        }
                        $current->fill($attendanceData)->save();
                        return $current;
                    }

                    return Attendance::create($attendanceData);
                },
                $allowChallengeGrace
            );
        });

        return [
            'attendance' => $attendance,
            'already_recorded' => $alreadyRecorded,
        ];
    }

    /**
     * Broadcast unified live roster stats to the teacher session view.
     */
    public function broadcastTeacherUpdate(AttendanceSession $session, Subject $subject, User $student, string $status, string $todayDate): void
    {
        try {
            $attendanceRecords = Attendance::where('subject_code', $session->subject_code)
                ->whereDate('date', $todayDate)
                ->whereNotNull('time_in')
                ->get(['id', 'status']);

            $totalStudents = count(SubjectRosterService::studentIds(collect([$subject])));
            $checkedInCount = $attendanceRecords->count();

            $stats = [
                'total_present'  => $attendanceRecords->where('status', 'Present')->count(),
                'total_late'     => $attendanceRecords->where('status', 'Late')->count(),
                'total_absent'   => max($totalStudents - $checkedInCount, 0),
                'total_students' => $totalStudents,
            ];

            broadcast(new TeacherAttendanceUpdated(
                (int) $session->created_by,
                $student->name,
                $session->subject_code,
                $status,
                'clock_in',
                null,
                null,
                $stats
            ));
        } catch (\Throwable $e) {
            Log::warning('Teacher attendance broadcast failed', [
                'error'        => $e->getMessage(),
                'subject_code' => $session->subject_code,
                'session_id'   => $session->id,
                'teacher_id'   => $session->created_by,
            ]);
        }
    }
}
