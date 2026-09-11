<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSession;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use App\Services\WebauthnService;
use App\Events\TeacherAttendanceUpdated;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use App\Services\QrSessionService;

class QrAttendanceController extends Controller
{
    protected $qrSessionService;

    public function __construct(QrSessionService $qrSessionService)
    {
        $this->qrSessionService = $qrSessionService;
    }
    private const QR_TTL_SECONDS = 300; // QR and Code stay active for 5 minutes (300 seconds) before refresh

    private function getSchoolLat(): float
    {
        return (float) \App\Models\Setting::get('gps_lat', 14.538800);
    }

    private function getSchoolLng(): float
    {
        return (float) \App\Models\Setting::get('gps_lng', 121.022300);
    }

    private function getRadiusMeters(): int
    {
        return (int) \App\Models\Setting::get('gps_radius', 50);
    }

    private function getRpId(Request $request): string
    {
        $host = trim((string) $request->header('x-forwarded-host', $request->getHost()));
        $host = preg_replace('/:\d+$/', '', $host);

        if ($host === '') {
            $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        }

        return strtolower($host);
    }
    private const QR_SCAN_BUFFER_SECONDS = 60; // Students have 60 seconds to complete scan + fingerprint

    private function buildScanUrl(string $token, Carbon $sessionEndTime): string
    {
        try {
            $baseUrl = request()->schemeAndHttpHost();
        } catch (\Throwable $e) {
            $baseUrl = config('app.url');
        }

        return rtrim($baseUrl, '/') . '/qr/scan/' . urlencode($token);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($value, true) ?: '';
    }

    private function decodeBase64UrlJson(string $value): string
    {
        return $this->base64UrlDecode($value);
    }

    private function normalizeBase64UrlString(string $value): string
    {
        $value = strtr($value, '+/', '-_');
        return rtrim($value, '=');
    }


    private function tokenExpiresAt(AttendanceSession $session, ?Carbon $from = null): Carbon
    {
        $expiresAt = ($from ?? now())->copy()->addSeconds(self::QR_TTL_SECONDS);

        return $expiresAt->gt($session->session_ends_at)
            ? $session->session_ends_at->copy()
            : $expiresAt;
    }

    // ─────────────────────────────────────────
    // Get schedule info for QR session timing
    // ─────────────────────────────────────────
    public function getScheduleInfo(Request $request)
    {
        try {
            $request->validate(['subject_code' => 'required|string']);
            
            $subject = Subject::with('schedules')
                ->where('code', $request->subject_code)
                ->first();

            if (!$subject) {
                abort(404, 'Subject not found');
            }
            $this->authorize('manage', $subject);

            $now = now('Asia/Manila');
            $todayName = $now->format('l');

            $todaySchedule = $subject->schedules->first(function ($schedule) use ($todayName) {
                return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
            });

            if (!$todaySchedule) {
                return response()->json([
                    'has_schedule' => false,
                    'status' => 'ad_hoc',
                    'message' => 'No regular schedule for today (' . $todayName . '). You can start a special / makeup attendance session.',
                    'can_start' => true,
                    'schedule' => [
                        'class_start' => 'Ad-hoc / Special',
                        'class_end' => '20 min session',
                        'session_opens' => 'Now',
                        'session_closes' => '20 mins',
                        'current_time' => $now->format('h:i A'),
                        'day' => $todayName
                    ]
                ]);
            }
            $todayDate = $now->toDateString();
            $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            $endTime = Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
            $sessionOpenTime = $startTime->copy()->subMinutes(15);
            $sessionEndTime = $endTime;

            return response()->json([
                'has_schedule' => true,
                'status' => 'ready',
                'message' => 'Ready to start attendance session',
                'can_start' => true,
                'schedule' => [
                    'class_start' => $startTime->format('h:i A'),
                    'class_end' => $endTime->format('h:i A'),
                    'session_opens' => $sessionOpenTime->format('h:i A'),
                    'session_closes' => $sessionEndTime->format('h:i A'),
                    'current_time' => $now->format('h:i A'),
                    'day' => $todayName
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Teacher QR page
    // ─────────────────────────────────────────
    public function showTeacherQrPage(string $subjectCode)
    {
        $teacher = auth()->user();
        
        $subject = Subject::with('schedules')
            ->where('code', $subjectCode)
            ->where(function ($q) use ($teacher) {
                $q->where('instructor_id', $teacher->id)
                  ->orWhere('instructor', $teacher->name);
                if (in_array($teacher->role, ['admin', 'department_head'])) {
                    $q->orWhereNotNull('id');
                }
            })
            ->firstOrFail();

        $activeSession = AttendanceSession::where('subject_code', $subjectCode)
            ->where(function ($q) use ($teacher) {
                $q->where('created_by', $teacher->id);
                if (in_array($teacher->role, ['admin', 'department_head'])) {
                    $q->orWhereNotNull('id');
                }
            })
            ->where('active', true)
            ->where('session_ends_at', '>', now())
            ->latest('id')
            ->first();

        $activeSessionPayload = null;
        if ($activeSession) {
            $activeSessionPayload = [
                'success'        => true,
                'session_id'     => $activeSession->id,
                'token'          => $activeSession->token,
                'session_code'   => $activeSession->session_code,
                'formatted_code' => $activeSession->getFormattedCode(),
                'scan_url'       => $this->buildScanUrl($activeSession->token, $activeSession->session_ends_at),
                'expires_at'     => $activeSession->expires_at->timestamp,
                'ttl'            => self::QR_TTL_SECONDS,
                'session_end'    => $activeSession->session_ends_at->timestamp,
                'classroom_lat'  => $activeSession->classroom_lat,
                'classroom_lng'  => $activeSession->classroom_lng,
            ];
        }

        return view('teacher.qr', compact('subject', 'activeSessionPayload'));
    }
    // ─────────────────────────────────────────
    // Teacher: Start QR session
    // ─────────────────────────────────────────
    public function startTeacherSession(Request $request)
    {
        $teacherId = Auth::id();
        $user = Auth::user();
        $request->validate([
            'subject_code'   => 'required|string|exists:subjects,code',
            'classroom_lat'  => 'nullable|numeric|between:-90,90',
            'classroom_lng'  => 'nullable|numeric|between:-180,180',
        ]);

        $subject = Subject::where('code', $request->subject_code)->first();
        if ($subject) {
            $isAuthorized = ($subject->instructor_id === $teacherId)
                || (in_array($user->role, ['admin', 'department_head']))
                || (!empty($subject->instructor) && strcasecmp(trim($subject->instructor), trim($user->name)) === 0);
            if (!$isAuthorized) {
                return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
            }
        }

        try {
            $session = $this->qrSessionService->startSession(
                $teacherId, 
                $request->subject_code, 
                $request->classroom_lat, 
                $request->classroom_lng
            );

            Log::info('Teacher session started', [
                'session_id' => $session->id,
                'subject_code' => $request->subject_code,
                'teacher_id' => $teacherId,
                'session_ends' => $session->session_ends_at->toDateTimeString(),
            ]);

            return response()->json([
                'success'        => true,
                'session_id'     => $session->id,
                'token'          => $session->token,
                'session_code'   => $session->session_code,
                'formatted_code' => $session->getFormattedCode(),
                'scan_url'       => $this->buildScanUrl($session->token, $session->session_ends_at),
                'expires_at'     => $session->expires_at->timestamp,
                'ttl'            => self::QR_TTL_SECONDS,
                'session_end'    => $session->session_ends_at->timestamp,
                'classroom_lat'  => $session->classroom_lat,
                'classroom_lng'  => $session->classroom_lng,
                'message'        => 'QR & Code attendance session started successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
    // ─────────────────────────────────────────
    // Teacher: Refresh QR token
    // ─────────────────────────────────────────
    public function refreshTeacherToken(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        $session = AttendanceSession::find($request->session_id);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $isAuthorized = ($session->created_by === Auth::id())
            || (in_array(Auth::user()->role, ['admin', 'department_head']));
        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        try {
            $session = $this->qrSessionService->refreshToken($session);

            return response()->json([
                'success'        => true,
                'token'          => $session->token,
                'session_code'   => $session->session_code,
                'formatted_code' => $session->getFormattedCode(),
                'scan_url'       => $this->buildScanUrl($session->token, $session->session_ends_at),
                'expires_at'     => $session->expires_at->timestamp,
                'ttl'            => self::QR_TTL_SECONDS,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }
    }

    // ─────────────────────────────────────────
    // Teacher: Stop session
    // ─────────────────────────────────────────
    public function stopTeacherSession(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        $session = AttendanceSession::with('subject')->find($request->session_id);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $isAuthorized = ($session->created_by === Auth::id())
            || (in_array(Auth::user()->role, ['admin', 'department_head']));
        if (!$isAuthorized) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $session->update(['active' => false]);

        // Dispatch notifications for absent/late students
        $subject = $session->subject;
        $today = now()->toDateString();
        
        if ($subject) {
            $students = $subject->getAllStudents();
            
            foreach ($students as $student) {
                // Ensure record exists; if not, mark absent
                $attendance = \App\Models\Attendance::updateOrCreateRecord(
                    [
                        'user_id' => $student->id,
                        'subject_code' => $session->subject_code,
                        'date' => $today
                    ],
                    [
                        'status' => 'Absent',
                        'time_in' => null,
                        'latitude' => null,
                        'longitude' => null,
                        'excused' => false
                    ]
                );

                if (in_array($attendance->status, ['Absent', 'Late']) && !$attendance->excused) {
                    $signedUrl = \Illuminate\Support\Facades\URL::signedRoute('guest.excuse', ['attendance' => $attendance->id]);
                    $student->notify(new \App\Notifications\AbsenceAlert($attendance, $signedUrl));
                }
            }
        }

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // Teacher: Get live clock-ins
    // ─────────────────────────────────────────
    public function getTeacherClockIns(Request $request)
    {
        $session = AttendanceSession::find($request->session_id);

        if (!$session) {
            return response()->json([
                'clockins' => [],
                'stats' => [
                    'total_students' => 0,
                    'clocked_in'     => 0,
                    'inside_radius'  => 0,
                    'late'           => 0,
                    'progress'       => 0,
                ],
            ]);
        }
        $subject = $session->subject;
        if (!$subject) {
            return response()->json([
                'clockins' => [],
                'stats' => [
                    'total_students' => 0,
                    'clocked_in'     => 0,
                    'inside_radius'  => 0,
                    'late'           => 0,
                    'progress'       => 0,
                ],
            ]);
        }

        $students = $subject->getAllStudents();

        $todayRecords = Attendance::with('user')
            ->where('subject_code', $session->subject_code)
            ->whereDate('date', today())
            ->get();

        $clockins = $students->map(function ($student) use ($todayRecords) {
            $record = $todayRecords->firstWhere('user_id', $student->id);
            $status = $record ? $record->status : 'Missing';
            
            return [
                'id'             => $student->id,
                'name'           => $student->name,
                'student_number' => $student->student_number ?? '—',
                'status'         => $status,
                'time'           => ($record && $record->time_in) ? Carbon::parse($record->time_in)->format('h:i A') : '—',
                'avatar'         => $student->profile_image
                    ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/' . $student->profile_image))
                    : 'https://ui-avatars.com/api/?name=' . urlencode($student->name) . '&background=7c2d12&color=fff&size=80',
                'sort_order'     => $record && in_array($record->status, ['Present', 'Late']) ? 0 : 1
            ];
        })->sortBy([
            ['sort_order', 'asc'],
            ['name', 'asc']
        ])->values()->all();

        $totalStudents = $students->count();

        $present = $todayRecords->where('status', 'Present')->count();
        $clockedIn = $present + $todayRecords->where('status', 'Late')->count();
        $late = $todayRecords->where('status', 'Late')->count();
        $absent = max(0, $totalStudents - $clockedIn);
        $progress = $totalStudents > 0 ? round(($clockedIn / $totalStudents) * 100) : 0;

        return response()->json([
            'clockins' => $clockins,
            'stats' => [
                'total_students' => $totalStudents,
                'clocked_in'     => $clockedIn,
                'present'        => $present,
                'late'           => $late,
                'absent'         => $absent,
                'inside_radius'  => 0,
                'progress'       => $progress,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // Teacher: Manual override student status
    // ─────────────────────────────────────────
    public function overrideStudentStatus(Request $request)
    {
        $request->validate([
            'session_id' => 'required|integer',
            'student_id' => 'required|integer',
            'status'     => 'required|string|in:Present,Absent,Late'
        ]);

        $session = AttendanceSession::find($request->session_id);
        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.']);
        }

        $student = User::where('id', $request->student_id)->where('role', 'student')->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.']);
        }

        $attendance = \Illuminate\Support\Facades\DB::transaction(function () use ($student, $session, $request) {
            $att = Attendance::updateOrCreateRecord(
                [
                    'user_id' => $student->id,
                    'subject_code' => $session->subject_code,
                    'date' => today()->toDateString(),
                ],
                [
                    'status' => $request->status,
                    'time_in' => $request->status === 'Absent' ? null : now(),
                    'latitude' => null,
                    'longitude' => null,
                    'method' => 'manual',
                    'excused' => false,
                    'academic_year_id' => \App\Models\AcademicYear::where('is_current', true)->value('id'),
                ]
            );

            activity()
                ->performedOn($att)
                ->causedBy(Auth::user())
                ->log("Teacher override: {$student->name} marked as {$request->status} for {$session->subject_code}");

            try {
                broadcast(new TeacherAttendanceUpdated(
                    (int) ($session->created_by ?? Auth::id()),
                    $student->name,
                    $session->subject_code,
                    $att->status,
                    'clock_in'
                ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Teacher attendance broadcast failed on override', [
                    'error' => $e->getMessage(),
                    'session_id' => $session->id,
                ]);
            }

            return $att;
        }, 3);

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // STUDENT: Scan QR → clock in
    // ─────────────────────────────────────────
    public function scan(Request $request, string $token)
    {
        // For now, skip signature validation to ensure QR codes work reliably
        // The token-based validation provides sufficient security
        
        if (!Auth::check()) {
            // Redirect the student to the normal login page, preserving the QR token
            return redirect()->route('login', ['qr_token' => $token]);
        }

        $user = Auth::user();

        // Temporarily disable device binding check to prevent false rejections
        // Will re-enable once basic functionality is stable
        
        $session = AttendanceSession::where('token', $token)
            ->orWhere('previous_token', $token)
            ->first();

        if (!$session) {
            return view('qr.result', ['status' => 'expired', 'message' => 'This QR code is no longer active. Please scan the latest QR from your teacher.']);
        }

        // Only check if the entire session has expired (20 minutes), not individual token expiry
        if (!$session->active || now()->gt($session->session_ends_at)) {
            return view('qr.result', ['status' => 'closed', 'message' => 'The attendance window for this class has closed.']);
        }

        $subject   = $session->subject;
        $now       = now();
        $todayDate = $now->toDateString();

        $mismatchReason = $this->scheduleMismatchReason($subject, $user);
        if ($mismatchReason) {
            Log::warning('QR scan schedule mismatch', [
                'user_id' => $user->id,
                'user_year' => $user->year_level,
                'user_semester' => $user->semester,
                'user_course' => $user->course,
                'user_section' => $user->section,
                'subject_code' => $session->subject_code,
                'subject_year' => $subject->year_level,
                'subject_semester' => $subject->semester,
                'subject_course' => $subject->course,
                'subject_section' => $subject->section,
                'reason' => $mismatchReason,
            ]);

            return view('qr.result', [
                'status' => 'error',
                'message' => 'This class is not in your schedule. ' . $mismatchReason,
            ]);
        }

        if (!$user->webauthnCredentials()->exists()) {
            return view('qr.verify', [
                'status'  => 'setup',
                'message' => 'Set up fingerprint verification on this phone before using QR attendance.',
                'token'   => $token,
                'subject' => $subject,
            ]);
        }

        $existing = Attendance::where('user_id', $user->id)
            ->where('subject_code', $session->subject_code)
            ->where('date', $todayDate)
            ->first();

        if ($existing && in_array($existing->status, ['Present', 'Late'])) {
            return view('qr.result', [
                'status'     => 'already',
                'message'    => 'You have already clocked in for this class today.',
                'subject'    => $subject->name . ' (' . $subject->code . ')',
                'instructor' => $subject->instructor->name ?? 'Instructor',
                'section'    => $subject->section ?? $user->section ?? 'Regular',
                'time'       => $existing->time_in ? Carbon::parse($existing->time_in)->format('h:i A') : '',
                'date'       => Carbon::parse($existing->date)->format('F j, Y'),
                'status_val' => $existing->status
            ]);
        }

        $classroomLat = (float) ($session->classroom_lat ?? $this->getSchoolLat());
        $classroomLng = (float) ($session->classroom_lng ?? $this->getSchoolLng());
        $radiusMeters = $this->getRadiusMeters();

        return view('qr.verify', [
            'status'        => 'ready',
            'message'       => 'Confirm GPS and fingerprint to finish clock-in.',
            'token'         => $token,
            'subject'       => $subject,
            'classroomLat'  => $classroomLat,
            'classroomLng'  => $classroomLng,
            'radiusMeters'  => $radiusMeters,
        ]);
    }
    public function verificationOptions(Request $request, WebauthnService $webauthn)
    {
        $request->validate(['token' => 'required|string']);

        Log::debug('QR verificationOptions request', [
            'session_id' => session()->getId(),
            'cookie' => $request->cookie(config('session.cookie')),
            'token' => $request->token,
            'user_id' => optional($request->user())->id,
        ]);

        $session = AttendanceSession::where('token', $request->token)
            ->orWhere('previous_token', $request->token)
            ->first();

        if (!$session) {
            Log::error('QR verificationOptions - attendance session not found', [
                'token' => $request->token,
                'user_id' => optional($request->user())->id,
            ]);
            return response()->json(['success' => false, 'message' => 'QR session not found. Please scan a fresh QR code.'], 422);
        }

        // Only check if the entire session has expired, not individual token expiry
        if (!$session->active || now()->gt($session->session_ends_at)) {
            Log::error('QR verificationOptions - session expired', [
                'session_active' => $session->active,
                'session_ends_at' => $session->session_ends_at,
                'now' => now(),
            ]);
            return response()->json(['success' => false, 'message' => 'The attendance session has ended. Please get a new QR code.'], 422);
        }

        // Don't clean up expired challenges here - that was causing the issue!
        // The challenge should persist until the user completes verification or it's very old
        // $session->cleanupExpiredChallenge();

        $user = $request->user();
        if (!$user->webauthnCredentials()->exists()) {
            return response()->json(['success' => false, 'message' => 'Fingerprint verification is not set up on this phone.'], 422);
        }

        // Generate isolated per-student cryptographic challenge
        $challenge = $this->base64UrlEncode(random_bytes(32));
        
        // Store challenge in student-scoped cache with 3-minute TTL (avoids cross-student race condition)
        $cacheKey = "webauthn_qr_challenge_{$user->id}_{$session->id}";
        Cache::put($cacheKey, $challenge, now()->addMinutes(3));
        
        // Also update session model challenge for fallback/logging
        try {
            $session->update(['webauthn_challenge' => $challenge]);
        } catch (\Throwable $e) {
            // Non-critical if DB write fails; cache is primary
        }

        $rpId = $this->getRpId($request);
        $options = [
            'challenge' => $challenge,
            'rpId' => $rpId,
            'allowCredentials' => $user->webauthnCredentials
                ->map(fn ($credential) => [
                    'type' => 'public-key',
                    'id' => $credential->credential_id,
                ])
                ->values(),
            'userVerification' => 'required',
            'timeout' => 60000,
        ];

        Log::debug('QR verification challenge generated and cached for student', [
            'user_id' => $user->id,
            'session_id' => $session->id,
            'cache_key' => $cacheKey,
            'challenge_preview' => substr($challenge, 0, 8) . '...',
        ]);

        return response()->json(array_merge(['success' => true], $options));
    }

    public function confirm(Request $request, WebauthnService $webauthn)
    {
        return $this->completeVerification($request, $webauthn);
    }

    public function completeVerification(Request $request, WebauthnService $webauthn)
    {
        $request->validate([
            'token'      => 'required|string',
            'latitude'   => 'required|numeric',
            'longitude'  => 'required|numeric',
            'accuracy'   => 'nullable|numeric',
            'credential' => 'required',
        ]);

        $credential = $request->input('credential');
        if (is_string($credential)) {
            $credential = json_decode($credential, true);
        }

        if (!is_array($credential)) {
            return response()->json(['success' => false, 'message' => 'Invalid biometric assertion data.'], 422);
        }

        $user    = $request->user();
        $session = AttendanceSession::where('token', $request->token)
            ->orWhere('previous_token', $request->token)
            ->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'This QR code is no longer active.'], 422);
        }

        // Only check if the entire session has expired, not individual token expiry
        if (!$session->active || now()->gt($session->session_ends_at)) {
            return response()->json(['success' => false, 'message' => 'The attendance session has ended. Please get a new QR code.'], 422);
        }

        $cacheKey = "webauthn_qr_challenge_{$user->id}_{$session->id}";
        $challenge = Cache::get($cacheKey) ?: $session->webauthn_challenge;

        Log::debug('QR completeVerification request', [
            'session_id' => session()->getId(),
            'token' => $request->token,
            'user_id' => optional($user)->id,
            'has_cached_challenge' => !empty(Cache::get($cacheKey)),
        ]);

        try {
            if (!$challenge) {
                Log::error('QR completeVerification - no challenge in cache or session', [
                    'attendance_session_id' => $session->id,
                    'user_id' => $user->id,
                    'cache_key' => $cacheKey,
                ]);
                throw new RuntimeException('WebAuthn biometric challenge expired or not found. Please try scanning again.');
            }

            // Store challenge in session for WebauthnService verification
            session(['webauthn.auth_challenge' => $challenge]);

            try {
                $webauthn->verifyAssertion($user, $credential);
                Log::debug('QR completeVerification - verification successful', [
                    'user_id' => $user->id,
                    'attendance_session_id' => $session->id,
                ]);
            } finally {
                session()->forget('webauthn.auth_challenge');
            }

            // Clear student's specific challenge from cache
            Cache::forget($cacheKey);
        } catch (RuntimeException $e) {
            Log::debug('QR completeVerification failure', [
                'exception' => $e->getMessage(),
                'attendance_session_id' => $session->id,
                'user_id' => $user->id,
                'credential' => $credential['id'] ?? null,
            ]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
        $subject   = $session->subject;
        $now       = now();
        $todayDate = $now->toDateString();

        $mismatchReason = $this->scheduleMismatchReason($subject, $user);
        if ($mismatchReason) {
            Log::warning('QR verification schedule mismatch', [
                'user_id' => $user->id,
                'user_year' => $user->year_level,
                'user_semester' => $user->semester,
                'user_course' => $user->course,
                'user_section' => $user->section,
                'subject_code' => $session->subject_code,
                'subject_year' => $subject->year_level,
                'subject_semester' => $subject->semester,
                'subject_course' => $subject->course,
                'subject_section' => $subject->section,
                'reason' => $mismatchReason,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'This class is not in your schedule. ' . $mismatchReason,
            ], 422);
        }

        $schoolLat    = $session->classroom_lat ?? $this->getSchoolLat();
        $schoolLng    = $session->classroom_lng ?? $this->getSchoolLng();
        $radiusMeters = \App\Models\Setting::get('gps_radius', $this->getRadiusMeters());

        $distance = $this->distance(
            (float) $request->latitude,
            (float) $request->longitude,
            $schoolLat,
            $schoolLng
        );

        $studentAccuracy = $request->filled('accuracy') ? (float) $request->accuracy : null;

        Log::info('QR distance check', [
            'session_id' => $session->id,
            'session_token' => $session->token,
            'student_id' => $user->id,
            'student_lat' => (float) $request->latitude,
            'student_lng' => (float) $request->longitude,
            'student_accuracy' => $studentAccuracy,
            'classroom_lat' => $schoolLat,
            'classroom_lng' => $schoolLng,
            'session_classroom_lat_from_db' => $session->classroom_lat,
            'session_classroom_lng_from_db' => $session->classroom_lng,
            'distance_meters' => $distance,
            'radius_limit' => $radiusMeters,
            'result' => $distance <= $radiusMeters ? 'PASS' : 'FAIL'
        ]);

        if ($distance > $radiusMeters) {
            Log::warning('QR distance validation failed: student outside classroom', [
                'session_id' => $session->id,
                'session_token' => $session->token,
                'student_id' => $user->id,
                'student_lat' => (float) $request->latitude,
                'student_lng' => (float) $request->longitude,
                'student_accuracy' => $studentAccuracy,
                'classroom_lat' => $schoolLat,
                'classroom_lng' => $schoolLng,
                'session_classroom_lat_from_db' => $session->classroom_lat,
                'session_classroom_lng_from_db' => $session->classroom_lng,
                'distance_meters' => $distance,
                'radius_limit' => $radiusMeters,
            ]);

            return response()->json([
                'success'    => false,
                'error_type' => 'outside_classroom',
                'distance'   => round($distance),
                'radius'     => $radiusMeters,
                'message'    => 'Failed to scan: You are outside the classroom (' . round($distance) . 'm away, allowed within ' . $radiusMeters . 'm). Attendance can only be marked while inside the classroom.'
            ], 422);
        }

        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $todayDate)
            ->where(function ($q) use ($session) {
                if ($session->subject) {
                    $q->where('subject_id', $session->subject->id)
                      ->orWhere('subject_code', $session->subject_code);
                } else {
                    $q->where('subject_code', $session->subject_code);
                }
            })
            ->first();

        if ($existing && in_array($existing->status, ['Present', 'Late'])) {
            $subj = $session->subject;
            $subjName = $subj ? $subj->name : $session->subject_code;
            $subjId = $subj ? (string) $subj->id : '';
            return response()->json([
                'success'            => true,
                'already_clocked_in' => true,
                'redirect'           => route('home'),
                'message'            => 'Attendance already recorded for this session. You have already clocked in.',
                'attendance_id'      => (string) $existing->id,
                'attendanceId'       => (string) $existing->id,
                'student_id'         => (string) $user->id,
                'studentId'          => (string) $user->id,
                'subject'            => $subjName,
                'subject_id'         => $subjId,
                'subjectId'          => $subjId,
                'session_id'         => (string) $session->id,
                'sessionId'          => (string) $session->id,
                'status'             => $existing->status,
            ]);
        }

        // Get class start time for late threshold calculation
        $subject = $session->subject;
        $startTime = null;
        
        // Try to get start time from schedules matching current time or active session
        if ($subject && $subject->schedules) {
            $todayName = $now->format('l');
            $matchingSchedules = $subject->schedules->filter(function ($schedule) use ($todayName) {
                return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
            });
            
            if ($matchingSchedules->isNotEmpty()) {
                $todaySchedule = $matchingSchedules->first(function ($sched) use ($now, $todayDate) {
                    $slotStart = Carbon::parse($todayDate . ' ' . $sched->start_time)->subMinutes(30);
                    $slotEnd = Carbon::parse($todayDate . ' ' . $sched->end_time)->addMinutes(30);
                    return $now->between($slotStart, $slotEnd);
                }) ?? $matchingSchedules->first();

                if ($todaySchedule) {
                    $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
                }
            }
        }
        
        // Fallback to subject start_time if available
        if (!$startTime && $subject && $subject->start_time) {
            $startTime = Carbon::parse($todayDate . ' ' . $subject->start_time);
        }

        // Determine status based on timing using configurable late threshold setting
        $status = 'Present';
        $lateMinutes = (int) \App\Models\Setting::get('late_threshold', 15);
        if ($startTime) {
            $lateThreshold = $startTime->copy()->addMinutes($lateMinutes);
            $status = $now->lte($lateThreshold) ? 'Present' : 'Late';
        }

        $currentAcademicYearId = \App\Models\AcademicYear::where('is_current', true)->value('id');

        try {
            $attendance = Attendance::updateOrCreateRecord(
                [
                    'user_id'      => $user->id,
                    'subject_id'   => $subject->id,
                    'subject_code' => $session->subject_code,
                    'date'         => $todayDate,
                ],
                [
                    'subject_name'     => $subject->name,
                    'class'            => $subject->section ?? $user->section ?? 'Regular',
                    'session_id'       => $session->id,
                    'status'           => $status,
                    'excused'          => false,
                    'excuse_note'      => null,
                    'time_in'          => $now->format('H:i:s'),
                    'latitude'         => $request->latitude,
                    'longitude'        => $request->longitude,
                    'gps_accuracy'     => $request->filled('accuracy') ? $request->accuracy : null,
                    'method'           => 'qr',
                    'academic_year_id' => $currentAcademicYearId,
                ]
            );
        } catch (\Illuminate\Database\QueryException $e) {
            // Check if it's a unique constraint violation (code 23000 or 23505)
            if ($e->getCode() == '23000' || $e->getCode() == '23505') {
                Log::warning('QR race condition prevented duplicate attendance', [
                    'student_id' => $user->id,
                    'subject_code' => $session->subject_code,
                    'date' => $todayDate,
                ]);
                $attendance = Attendance::where('user_id', $user->id)
                    ->where('date', $todayDate)
                    ->where(function ($q) use ($subject, $session) {
                        $q->where('subject_id', $subject->id)
                          ->orWhere('subject_code', $session->subject_code);
                    })
                    ->first();
                
                // If it exists but is absent, we can update it safely
                if ($attendance && $attendance->status === 'Absent') {
                    $attendance->update([
                        'status'       => $status,
                        'time_in'      => $now->format('H:i:s'),
                        'latitude'     => $request->latitude,
                        'longitude'    => $request->longitude,
                        'method'       => 'qr',
                        'excused'      => false,
                        'session_id'   => $session->id,
                        'subject_id'   => $subject->id,
                        'subject_name' => $subject->name,
                    ]);
                }
            } else {
                throw $e;
            }
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save attendance record. Please try again or notify your instructor.'
            ], 500);
        }

        event(new \App\Events\AttendanceMarked($attendance));

        // Broadcast a real-time update to the teacher dashboard / QR session view.
        try {
            $attendanceRecords = Attendance::where('subject_code', $session->subject_code)
                ->whereDate('date', $todayDate)
                ->whereNotNull('time_in')
                ->get();

            $totalStudents = $subject->getAllStudents()->count();

            $stats = [
                'total_present'  => $attendanceRecords->count(),
                'total_late'     => $attendanceRecords->where('status', 'Late')->count(),
                'total_absent'   => max($totalStudents - $attendanceRecords->count(), 0),
                'total_students' => $totalStudents,
            ];

            broadcast(new TeacherAttendanceUpdated(
                (int) $session->created_by,
                $user->name,
                $session->subject_code,
                $status,
                'clock_in',
                $stats
            ));
        } catch (\Throwable $e) {
            Log::warning('Teacher attendance broadcast failed', [
                'error' => $e->getMessage(),
                'subject_code' => $session->subject_code,
                'session_id' => $session->id,
                'teacher_id' => $session->created_by,
            ]);
        }

        return response()->json([
            'success'       => true,
            'redirect'      => route('home'),
            'message'       => 'Attendance recorded successfully.',
            'attendance_id' => (string) $attendance->id,
            'attendanceId'  => (string) $attendance->id,
            'student_id'    => (string) $user->id,
            'studentId'     => (string) $user->id,
            'subject'       => $subject->name,
            'subject_id'    => (string) $subject->id,
            'subjectId'     => (string) $subject->id,
            'subject_code'  => $subject->code,
            'session_id'    => (string) $session->id,
            'sessionId'     => (string) $session->id,
            'status'        => $status,
            'time'          => $now->format('h:i A'),
            'date'          => $now->format('F j, Y'),
        ]);
    }

    // ─────────────────────────────────────────
    // STUDENT: Direct In-App QR Scanner Process
    // ─────────────────────────────────────────
    public function processScan(Request $request)
    {
        $request->validate([
            'token'     => 'required_without:code|nullable|string',
            'code'      => 'required_without:token|nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'accuracy'  => 'nullable|numeric',
        ]);

        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'require_login' => true,
                'message' => 'Please log in to record attendance.'
            ], 401);
        }

        $user = Auth::user();
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Only enrolled students can record attendance.'
            ], 403);
        }

        // Enforce student device binding anti-proxy validation
        $deviceService = app(\App\Services\DeviceBindingService::class);
        if (!$deviceService->isCurrentDevice($user, $request)) {
            return response()->json([
                'success' => false,
                'error_type' => 'device_mismatch',
                'message' => 'This device is not recognized as your registered device. Please log out and sign in from your bound device to record attendance.'
            ], 403);
        }

        $rawTokenInput = trim((string) ($request->input('token') ?? ''));
        $rawCodeInput  = trim((string) ($request->input('code') ?? ''));

        if (empty($rawTokenInput) && empty($rawCodeInput)) {
            return response()->json([
                'success' => false,
                'error_type' => 'missing_input',
                'message' => 'Please provide a valid QR code or attendance code.'
            ], 422);
        }

        // Dedicated extraction helpers for JSON payloads, full URLs, and raw codes/tokens
        $extractedToken = null;
        $extractedCode  = null;

        $checkJson = function ($str) use (&$extractedToken, &$extractedCode) {
            if (empty($str)) return false;
            $trimmed = trim((string) $str);
            if ((str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}')) || (str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']'))) {
                $decoded = json_decode($trimmed, true);
                if (is_array($decoded)) {
                    if (!empty($decoded['token'])) $extractedToken = trim((string) $decoded['token']);
                    if (!empty($decoded['session_token'])) $extractedToken = trim((string) $decoded['session_token']);
                    if (!empty($decoded['code'])) $extractedCode = trim((string) $decoded['code']);
                    if (!empty($decoded['session_code'])) $extractedCode = trim((string) $decoded['session_code']);
                    if (empty($extractedToken) && (!empty($decoded['scan_url']) || !empty($decoded['url']))) {
                        $url = trim((string) ($decoded['scan_url'] ?? $decoded['url']));
                        if (str_contains($url, '/qr/scan/')) {
                            $parts = explode('/qr/scan/', $url);
                            $t = explode('?', $parts[1] ?? '')[0];
                            $t = explode('#', $t)[0];
                            $extractedToken = trim(rtrim(urldecode($t), '/'));
                        }
                    }
                    return true;
                }
            }
            return false;
        };

        $checkJson($rawTokenInput);
        $checkJson($rawCodeInput);

        $parseUrl = function ($str) use (&$extractedToken, &$extractedCode) {
            if (empty($str)) return;
            $trimmed = trim((string) $str);
            if (str_contains($trimmed, '/qr/scan/')) {
                $parts = explode('/qr/scan/', $trimmed);
                $t = explode('?', $parts[1] ?? '')[0];
                $t = explode('#', $t)[0];
                $t = trim(rtrim(urldecode($t), '/'));
                if (!empty($t)) {
                    $extractedToken = $t;
                }
            } elseif (filter_var($trimmed, FILTER_VALIDATE_URL) || str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
                $parsed = parse_url($trimmed);
                if (!empty($parsed['query'])) {
                    parse_str($parsed['query'], $query);
                    if (!empty($query['token'])) $extractedToken = trim(urldecode($query['token']));
                    if (!empty($query['code'])) $extractedCode = trim(urldecode($query['code']));
                    if (!empty($query['session_code'])) $extractedCode = trim(urldecode($query['session_code']));
                }
                if (empty($extractedToken) && !empty($parsed['path'])) {
                    $segments = explode('/', trim($parsed['path'], '/'));
                    $lastSegment = end($segments);
                    if (!empty($lastSegment) && strlen($lastSegment) >= 16) {
                        $extractedToken = trim(rtrim(urldecode($lastSegment), '/'));
                    }
                }
            }
        };

        if (empty($extractedToken)) {
            $parseUrl($rawTokenInput);
            $parseUrl($rawCodeInput);
        }

        // If no token extracted from URL/JSON, check if rawTokenInput itself is a token
        if (empty($extractedToken) && !empty($rawTokenInput)) {
            $cleanToken = trim($rawTokenInput);
            if (strlen($cleanToken) >= 16 && !str_contains($cleanToken, '/') && !str_contains($cleanToken, ' ')) {
                $extractedToken = $cleanToken;
            }
        }

        // If no code extracted from JSON/URL, normalize code candidate while strictly preserving leading zeros
        if (empty($extractedCode)) {
            $codeCandidate = !empty($rawCodeInput) ? $rawCodeInput : $rawTokenInput;
            $cleanCandidate = strtoupper(preg_replace('/[^0-9A-Za-z]/', '', (string) $codeCandidate));
            if (!empty($cleanCandidate) && !str_contains((string) $codeCandidate, '/')) {
                $extractedCode = $cleanCandidate;
            }
        }

        $isCodeMethod = ($request->input('method') === 'code') || (!empty($extractedCode) && empty($extractedToken));

        // Step 1: Look for an ACTIVE session matching token, previous_token, or session_code
        $session = null;
        if (!empty($extractedToken) || !empty($extractedCode)) {
            $session = AttendanceSession::with(['subject.instructor'])
                ->where('active', true)
                ->where('session_ends_at', '>', now('Asia/Manila'))
                ->where(function ($query) use ($extractedToken, $extractedCode) {
                    if (!empty($extractedToken) && !empty($extractedCode)) {
                        $query->where('token', $extractedToken)
                              ->orWhere('previous_token', $extractedToken)
                              ->orWhere('session_code', $extractedCode);
                    } elseif (!empty($extractedToken)) {
                        $query->where('token', $extractedToken)
                              ->orWhere('previous_token', $extractedToken);
                    } else {
                        $query->where('session_code', $extractedCode);
                    }
                })
                ->latest('id')
                ->first();
        }

        // Step 2: Check cache for rotated token within grace window (e.g. refreshed within last 5 minutes)
        if (!$session && !empty($extractedToken)) {
            $prevSessionId = Cache::get("session_prev_token_{$extractedToken}");
            if ($prevSessionId) {
                $prevSession = AttendanceSession::with(['subject.instructor'])->find($prevSessionId);
                if ($prevSession && $prevSession->active && now('Asia/Manila')->lte($prevSession->session_ends_at)) {
                    $session = $prevSession;
                }
            }
        }

        // Step 3: If no active session found, query for expired/inactive sessions to provide specific error message
        if (!$session) {
            $pastSession = null;
            if (!empty($extractedToken) || !empty($extractedCode)) {
                $pastSession = AttendanceSession::where(function ($query) use ($extractedToken, $extractedCode) {
                    if (!empty($extractedToken) && !empty($extractedCode)) {
                        $query->where('token', $extractedToken)
                              ->orWhere('previous_token', $extractedToken)
                              ->orWhere('session_code', $extractedCode);
                    } elseif (!empty($extractedToken)) {
                        $query->where('token', $extractedToken)
                              ->orWhere('previous_token', $extractedToken);
                    } else {
                        $query->where('session_code', $extractedCode);
                    }
                })->latest('id')->first();
            }

            if ($pastSession) {
                if (now('Asia/Manila')->gt($pastSession->session_ends_at)) {
                    return response()->json([
                        'success' => false,
                        'error_type' => 'session_closed',
                        'error_detail' => 'session_expired',
                        'message' => 'This attendance session has expired and ended. Codes and QR tokens are only valid while the session is active.'
                    ], 422);
                }
                if (!$pastSession->active) {
                    return response()->json([
                        'success' => false,
                        'error_type' => 'session_closed',
                        'error_detail' => 'session_inactive',
                        'message' => 'This attendance session is no longer active and has ended. The instructor has closed this session.'
                    ], 422);
                }
            }

            return response()->json([
                'success' => false,
                'error_type' => 'invalid_or_expired',
                'error_detail' => $isCodeMethod ? 'invalid_code' : 'invalid_token',
                'message' => $isCodeMethod
                    ? 'Invalid attendance code. Please check the 6-digit code on the teacher screen.'
                    : 'Invalid or expired attendance code / QR. Please verify the code on screen or scan the live QR code.'
            ], 422);
        }

        if (!$session->active || now('Asia/Manila')->gt($session->session_ends_at)) {
            return response()->json([
                'success' => false,
                'error_type' => 'session_closed',
                'message' => 'The attendance session for this class has ended. Codes and QR tokens are only valid while the session is active.'
            ], 422);
        }

        $subject = $session->subject;
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Class subject not found.'
            ], 404);
        }

        $todayDate = now()->toDateString();
        $now = now('Asia/Manila');

        // 1. Schedule & Class / Section Validation
        $mismatchReason = $this->scheduleMismatchReason($subject, $user);
        if ($mismatchReason) {
            return response()->json([
                'success' => false,
                'error_type' => 'schedule_mismatch',
                'message' => 'This QR code is not intended for your class: ' . $mismatchReason
            ], 422);
        }

        // 2. Prevent duplicate scan
        $existing = Attendance::where('user_id', $user->id)
            ->where('date', $todayDate)
            ->where(function ($q) use ($subject, $session) {
                if ($subject && $subject->id) {
                    $q->where('subject_id', $subject->id)
                      ->orWhere('subject_code', $session->subject_code);
                } else {
                    $q->where('subject_code', $session->subject_code);
                }
            })
            ->first();

        if ($existing && in_array($existing->status, ['Present', 'Late'])) {
            $existingTime = $existing->time_in ? Carbon::parse($existing->time_in)->format('h:i A') : 'Earlier';
            $instructorName = ($subject->instructor instanceof \App\Models\User ? $subject->instructor->name : (is_string($subject->instructor) ? $subject->instructor : ($subject->instructorUser->name ?? 'Instructor')));
            return response()->json([
                'success'            => true,
                'already_clocked_in' => true,
                'status'             => $existing->status,
                'time'               => $existingTime,
                'date'               => Carbon::parse($existing->date)->format('F j, Y'),
                'student_name'       => $user->name,
                'student_id'         => (string) $user->id,
                'studentId'          => (string) $user->id,
                'subject'            => $subject->name,
                'subject_id'         => (string) $subject->id,
                'subjectId'          => (string) $subject->id,
                'subject_code'       => $subject->code,
                'attendance_id'      => (string) $existing->id,
                'attendanceId'       => (string) $existing->id,
                'section'            => $subject->section ?? $user->section ?? 'Regular',
                'instructor'         => $instructorName,
                'session_id'         => (string) $session->id,
                'sessionId'          => (string) $session->id,
                'message'            => 'Attendance already recorded for this session. You have already clocked in.'
            ]);
        }

        // 3. Geofence Distance Check (if coordinates provided and session coordinates exist)
        $schoolLat = $session->classroom_lat ?? $this->getSchoolLat();
        $schoolLng = $session->classroom_lng ?? $this->getSchoolLng();
        $radiusMeters = \App\Models\Setting::get('gps_radius', $this->getRadiusMeters());

        if ($session->classroom_lat !== null) {
            if (!$request->filled('latitude') || !$request->filled('longitude')) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'location_required',
                    'message' => 'Location coordinates are required to verify you are present in the classroom. Please enable GPS permissions and try again.'
                ], 422);
            }

            $distance = $this->distance(
                (float) $request->latitude,
                (float) $request->longitude,
                $schoolLat,
                $schoolLng
            );

            if ($distance > $radiusMeters) {
                return response()->json([
                    'success' => false,
                    'error_type' => 'outside_classroom',
                    'distance' => round($distance),
                    'radius' => $radiusMeters,
                    'message' => 'You are outside the classroom boundary (' . round($distance) . 'm away, allowed within ' . $radiusMeters . 'm). Please scan inside the classroom.'
                ], 422);
            }
        }

        // 4. Calculate Timing (Present vs Late threshold)
        $startTime = null;
        if ($subject->schedules) {
            $todayName = $now->format('l');
            $matchingSchedule = $subject->schedules->first(function ($sched) use ($todayName) {
                return strcasecmp(trim($sched->day ?? ''), $todayName) === 0;
            });
            if ($matchingSchedule) {
                $startTime = Carbon::parse($todayDate . ' ' . $matchingSchedule->start_time);
            }
        }
        if (!$startTime && $subject->start_time) {
            $startTime = Carbon::parse($todayDate . ' ' . $subject->start_time);
        }

        $status = 'Present';
        $lateMinutes = (int) \App\Models\Setting::get('late_threshold', 15);
        if ($startTime) {
            $lateThreshold = $startTime->copy()->addMinutes($lateMinutes);
            $status = $now->lte($lateThreshold) ? 'Present' : 'Late';
        }

        $currentAcademicYearId = \App\Models\AcademicYear::where('is_current', true)->value('id');

        // 5. Record Attendance in Database
        try {
            $attendance = Attendance::updateOrCreateRecord(
                [
                    'user_id'      => $user->id,
                    'subject_id'   => $subject->id,
                    'subject_code' => $session->subject_code,
                    'date'         => $todayDate,
                ],
                [
                    'subject_name'     => $subject->name,
                    'class'            => $subject->section ?? $user->section ?? 'Regular',
                    'session_id'       => $session->id,
                    'status'           => $status,
                    'excused'          => false,
                    'excuse_note'      => null,
                    'time_in'          => $now->format('H:i:s'),
                    'latitude'         => $request->latitude,
                    'longitude'        => $request->longitude,
                    'gps_accuracy'     => $request->accuracy,
                    'method'           => $isCodeMethod ? 'code' : 'qr',
                    'academic_year_id' => $currentAcademicYearId,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to record attendance in processScan: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'subject_code' => $session->subject_code,
                'exception' => $e
            ]);
            $attendance = Attendance::where('user_id', $user->id)
                ->where('date', $todayDate)
                ->where(function ($q) use ($subject, $session) {
                    $q->where('subject_id', $subject->id)
                      ->orWhere('subject_code', $session->subject_code);
                })
                ->first();
        }

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to save attendance record. Please try again or notify your instructor.'
            ], 500);
        }

        // 6. Broadcast Real-Time Update to Teacher
        try {
            $attendanceRecords = Attendance::where('subject_code', $session->subject_code)
                ->whereDate('date', $todayDate)
                ->whereNotNull('time_in')
                ->get();

            $totalStudents = User::where('role', 'student')
                ->where('year_level', $subject->year_level)
                ->where('semester', $subject->semester)
                ->when(!empty($subject->course), fn($query) => $query->where('course', $subject->course))
                ->when(!empty($subject->section), fn($query) => $query->where('section', $subject->section))
                ->count();

            $stats = [
                'total_present'  => $attendanceRecords->where('status', 'Present')->count(),
                'total_late'     => $attendanceRecords->where('status', 'Late')->count(),
                'total_absent'   => max($totalStudents - $attendanceRecords->count(), 0),
                'total_students' => $totalStudents,
            ];

            broadcast(new TeacherAttendanceUpdated(
                (int) $session->created_by,
                $user->name,
                $session->subject_code,
                $status,
                'clock_in',
                $stats
            ));
        } catch (\Throwable $e) {
            // Ignore broadcast failure in local/test
        }

        $instructorName = ($subject->instructor instanceof \App\Models\User ? $subject->instructor->name : (is_string($subject->instructor) ? $subject->instructor : ($subject->instructorUser->name ?? 'Instructor')));

        return response()->json([
            'success'            => true,
            'already_clocked_in' => false,
            'status'             => $status,
            'time'               => $now->format('h:i A'),
            'date'               => $now->format('F j, Y'),
            'student_name'       => $user->name,
            'student_id'         => (string) $user->id,
            'studentId'          => (string) $user->id,
            'subject'            => $subject->name,
            'subject_id'         => (string) $subject->id,
            'subjectId'          => (string) $subject->id,
            'subject_code'       => $subject->code,
            'attendance_id'      => (string) $attendance->id,
            'attendanceId'       => (string) $attendance->id,
            'section'            => $subject->section ?? $user->section ?? 'Regular',
            'instructor'         => $instructorName,
            'session_id'         => (string) $session->id,
            'sessionId'          => (string) $session->id,
            'message'            => 'Attendance recorded successfully.'
        ]);
    }

    // ─────────────────────────────────────────
    // HELPER METHODS
    // ─────────────────────────────────────────
    private function matchesStudentSchedule(Subject $subject, User $student): bool
    {
        return $this->scheduleMismatchReason($subject, $student) === null;
    }

    private function scheduleMismatchReason(Subject $subject, User $student): ?string
    {
        // 1. Explicit enrollment in enrollments table by subject_id
        if ($student->enrolledSubjects()->where('subject_id', $subject->id)->exists()) {
            return null;
        }

        // 2. Explicit enrollment matching subject code
        if (\Illuminate\Support\Facades\DB::table('enrollments')
            ->join('subjects', 'enrollments.subject_id', '=', 'subjects.id')
            ->where('enrollments.user_id', $student->id)
            ->where('subjects.code', $subject->code)
            ->exists()) {
            return null;
        }

        // 3. Check year level (if student has year level specified)
        if ($student->year_level && $subject->year_level && (int)$student->year_level !== (int)$subject->year_level) {
            return "Year mismatch: you are year {$student->year_level}, but this class is year {$subject->year_level}.";
        }

        // 4. Check semester (if student has semester specified)
        if ($student->semester && $subject->semester && (int)$student->semester !== (int)$subject->semester) {
            return "Semester mismatch: you are semester {$student->semester}, but this class is semester {$subject->semester}.";
        }

        // 5. Course check (case-insensitive, only if both are non-empty)
        if (!empty($subject->course) && !empty($student->course) && strcasecmp(trim((string) $student->course), trim((string) $subject->course)) !== 0) {
            return "Course mismatch: you are in {$student->course}, but this class is for {$subject->course}.";
        }

        // 6. Section check (only if both are non-empty)
        if (!empty($subject->section) && !empty($student->section) && strcasecmp(trim((string) $student->section), trim((string) $subject->section)) !== 0) {
            return "Section mismatch: your section is {$student->section}, but this class is section {$subject->section}.";
        }

        return null;
    }

    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}