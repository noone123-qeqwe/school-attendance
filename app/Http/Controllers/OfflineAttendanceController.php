<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OfflineAttendanceController extends Controller
{
    /**
     * Sync a batch of offline attendance records from a teacher's device.
     *
     * Accepts an array of records, validates teacher ownership of each subject,
     * de-duplicates against existing records, and creates/updates attendance rows.
     *
     * POST /teacher/offline-attendance/sync
     */
    public function sync(Request $request)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher->isTeacher() && !$teacher->isDepartmentHead()) {
            return response()->json([
                'success' => false,
                'message' => 'Only teachers can sync offline attendance records.',
            ], 403);
        }

        $request->validate([
            'records' => 'required|array|min:1|max:200',
        ]);

        $records = $request->input('records');

        // Pre-fetch subjects owned by this teacher for validation
        $teacherSubjects = Subject::where('instructor_id', $teacher->id)->get()
            ->keyBy(fn (Subject $subject) => strtoupper(trim($subject->code)));
        $teacherSubjectCodes = $teacherSubjects->keys()->all();
        $rosterIdsBySubject = [];

        $synced = [];
        $failed = [];
        $skipped = [];

        foreach ($records as $index => $record) {
            $validator = Validator::make($record, [
                'subject_code' => 'required|string|max:50',
                'user_id'      => 'required|integer|exists:users,id',
                'status'       => 'required|string|in:Present,Absent,Late,Excused,present,absent,late,excused',
                'date'         => 'required|date',
            ]);

            if ($validator->fails()) {
                $failed[] = [
                    'index'    => $index,
                    'local_id' => $record['local_id'] ?? null,
                    'reason'   => $validator->errors()->first(),
                ];
                continue;
            }

            $subjectCode = strtoupper(trim($record['subject_code']));

            // Verify teacher owns this subject
            if (!in_array($subjectCode, $teacherSubjectCodes)) {
                $failed[] = [
                    'index'    => $index,
                    'local_id' => $record['local_id'] ?? null,
                    'reason'   => "You are not the instructor for subject '{$subjectCode}'.",
                ];
                continue;
            }

            $date = \Carbon\Carbon::parse($record['date'])->format('Y-m-d');
            $userId = (int) $record['user_id'];
            $statusNormalized = ucfirst(strtolower(trim($record['status'])));

            if (!isset($rosterIdsBySubject[$subjectCode])) {
                $rosterIdsBySubject[$subjectCode] = $teacherSubjects[$subjectCode]
                    ->getAllStudents()
                    ->where('role', 'student')
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
            if (!in_array($userId, $rosterIdsBySubject[$subjectCode], true)) {
                $failed[] = [
                    'index' => $index,
                    'local_id' => $record['local_id'] ?? null,
                    'reason' => 'Student is not enrolled in this subject.',
                ];
                continue;
            }

            // Check for existing record
            $existing = Attendance::where('user_id', $userId)
                ->where('subject_code', $subjectCode)
                ->where('date', $date)
                ->first();

            // If a record already exists and was created by the system (QR scan, etc.),
            // skip it to avoid overwriting verified attendance.
            if ($existing && $existing->method && $existing->method !== 'offline_sync') {
                $skipped[] = [
                    'index'    => $index,
                    'local_id' => $record['local_id'] ?? null,
                    'reason'   => 'Record already exists (created via ' . $existing->method . ').',
                    'existing_status' => $existing->status,
                ];
                continue;
            }

            try {
                $updateData = [
                    'status' => $statusNormalized,
                    'method' => 'offline_sync',
                ];

                // When teacher marks a student as Present/Late, clear excused flag
                if ($statusNormalized !== 'Absent') {
                    $updateData['excused'] = false;
                    $updateData['excuse_note'] = null;
                }

                // Store time_in if provided
                if (!empty($record['time'])) {
                    $updateData['time_in'] = $record['time'];
                }

                $updateData['subject_name'] = $teacherSubjects[$subjectCode]->name;

                $attendance = Attendance::updateOrCreate(
                    [
                        'user_id'      => $userId,
                        'subject_code' => $subjectCode,
                        'date'         => $date,
                    ],
                    $updateData
                );

                $synced[] = [
                    'index'         => $index,
                    'local_id'      => $record['local_id'] ?? null,
                    'attendance_id' => $attendance->id,
                    'status'        => $attendance->status,
                ];
            } catch (\Throwable $e) {
                Log::error('Offline attendance sync failed for record', [
                    'index' => $index,
                    'record' => $record,
                    'error' => $e->getMessage(),
                ]);

                $failed[] = [
                    'index'    => $index,
                    'local_id' => $record['local_id'] ?? null,
                    'reason'   => 'The record could not be synchronized. Please retry.',
                ];
            }
        }

        return response()->json([
            'success'  => true,
            'synced'   => $synced,
            'skipped'  => $skipped,
            'failed'   => $failed,
            'summary'  => [
                'total'   => count($records),
                'synced'  => count($synced),
                'skipped' => count($skipped),
                'failed'  => count($failed),
            ],
        ]);
    }

    /**
     * Return the teacher's assigned subjects with enrolled students.
     * Used by the offline module to pre-cache class rosters for offline use.
     *
     * GET /teacher/offline-attendance/roster
     */
    public function roster(Request $request)
    {
        $teacher = Auth::user();

        if (!$teacher || !$teacher->isTeacher() && !$teacher->isDepartmentHead()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $subjects = Subject::where('instructor_id', $teacher->id)
            ->with('schedules')
            ->get()
            ->map(function ($subject) {
                $students = $subject->getAllStudents()->map(function ($s) {
                    return [
                        'id'             => $s->id,
                        'name'           => $s->name,
                        'student_number' => $s->student_number,
                    ];
                })->values();

                return [
                    'code'      => $subject->code,
                    'name'      => $subject->name,
                    'section'   => $subject->section,
                    'students'  => $students,
                ];
            });

        return response()->json([
            'success'    => true,
            'teacher_id' => $teacher->id,
            'subjects'   => $subjects,
        ]);
    }
}
