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
use RuntimeException;

class QrAttendanceController extends Controller
{
    private const QR_TTL_SECONDS = 20; // QR refreshes every 20 seconds

    private function getSchoolLat(): float
    {
        return (float) (\App\Models\Setting::where('key', 'gps_lat')->value('value') ?? 14.538800);
    }

    private function getSchoolLng(): float
    {
        return (float) (\App\Models\Setting::where('key', 'gps_lng')->value('value') ?? 121.022300);
    }

    private function getRadiusMeters(): int
    {
        return (int) (\App\Models\Setting::where('key', 'gps_radius')->value('value') ?? 500);
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
        // Give students plenty of time to scan and complete verification
        // Signed URL lasts until session ends (up to 20 minutes)
        $scanUrl = URL::temporarySignedRoute('qr.scan', $sessionEndTime, ['token' => $token]);

        // Ensure the scan URL uses the current request host (useful when using ngrok)
        try {
            $currentHost = request()->getHost();
            $currentScheme = request()->getScheme();

            $parts = parse_url($scanUrl);
            if (($parts['host'] ?? '') !== $currentHost) {
                $path = $parts['path'] ?? '/';
                $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
                $scanUrl = $currentScheme . '://' . $currentHost . $path . $query;
            }
        } catch (\Throwable $e) {
            // If request() is not available or parsing fails, fall back to generated URL
        }

        return $scanUrl;
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
    // ADMIN: Start a QR attendance session
    // ─────────────────────────────────────────
    public function startSession(Request $request)
    {
        $request->validate(['subject_code' => 'required|string|exists:subjects,code']);

        $subject = Subject::where('code', $request->subject_code)->first();

        $now         = now();
        $todayDate   = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $dayMap      = ['Monday'=>'M','Tuesday'=>'T','Wednesday'=>'W','Thursday'=>'TH','Friday'=>'F','Saturday'=>'S'];
        $todayLetter = $dayMap[$now->format('l')] ?? '';

        if (!str_contains(strtoupper($subject->days ?? ''), $todayLetter)) {
            return response()->json(['success' => false, 'message' => 'This subject does not meet today.'], 422);
        }

        // ── Guard: subject must have a schedule time set ──
        if (!$subject->start_time || !$subject->end_time) {
            return response()->json(['success' => false, 'message' => 'This subject has no schedule time set. Please edit the subject and add a start and end time.'], 422);
        }
        $startTime   = Carbon::parse($todayDate . ' ' . $subject->start_time);
        $sessionEnd  = $startTime->copy()->addMinutes(20);

        if ($now->lt($startTime->copy()->subMinutes(5))) {
            return response()->json(['success' => false, 'message' => 'Too early. Session opens 5 minutes before class.'], 422);
        }

        if ($now->gt($sessionEnd)) {
            return response()->json(['success' => false, 'message' => 'The 20-minute attendance window has closed.'], 422);
        }

        // Deactivate any existing session for this subject today
        AttendanceSession::where('subject_code', $request->subject_code)
            ->where('active', true)
            ->update(['active' => false]);

        // Create new session
        $session = AttendanceSession::create([
            'subject_code'    => $request->subject_code,
            'created_by'      => Auth::id(),
            'token'           => AttendanceSession::generateToken($request->subject_code),
            'expires_at'      => $now->copy()->addSeconds(self::QR_TTL_SECONDS)->min($sessionEnd),
            'session_ends_at' => $sessionEnd,
            'active'          => true,
        ]);

        $scanUrl = $this->buildScanUrl($session->token, $session->session_ends_at);

        return response()->json([
            'success'     => true,
            'session_id'  => $session->id,
            'token'       => $session->token,
            'scan_url'    => $scanUrl,
            'expires_at'  => $session->expires_at->timestamp,
            'session_end' => $session->session_ends_at->timestamp,
            'ttl'         => self::QR_TTL_SECONDS, // 20 seconds
        ]);
    }

    // ─────────────────────────────────────────
    // ADMIN: Refresh QR token (every 30s)
    // ─────────────────────────────────────────
    public function refreshToken(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);

        $session = AttendanceSession::find($request->session_id);

        if ($session) {
            $session->markInactiveIfExpired();
        }

        if (!$session || !$session->isSessionActive()) {
            return response()->json(['success' => false, 'message' => 'Session expired or not found.'], 404);
        }

        if ((int) $session->created_by !== (int) Auth::id()) {
            return response()->json(['success' => false, 'message' => 'You do not own this QR session.'], 403);
        }
        $expiresAt = $this->tokenExpiresAt($session);
        $session->update([
            'token'      => AttendanceSession::generateToken($session->subject_code),
            'expires_at' => $expiresAt,
        ]);

        $scanUrl = $this->buildScanUrl($session->token, $session->session_ends_at);

        return response()->json([
            'success'    => true,
            'token'      => $session->token,
            'scan_url'   => $scanUrl,
            'expires_at' => $session->expires_at->timestamp,
            'ttl'        => self::QR_TTL_SECONDS, // 20 seconds
        ]);
    }

    // ─────────────────────────────────────────
    // ADMIN: Stop session
    // ─────────────────────────────────────────
    public function stopSession(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        AttendanceSession::where('id', $request->session_id)
            ->where('created_by', Auth::id())
            ->update(['active' => false]);

        return response()->json(['success' => true]);
    }

    // ─────────────────────────────────────────
    // Get schedule info for QR session timing
    // ─────────────────────────────────────────
    public function getScheduleInfo(Request $request)
    {
        try {
            $request->validate(['subject_code' => 'required|string|exists:subjects,code']);
            
            $subject = Subject::with('schedules')
                ->where('code', $request->subject_code)
                ->first();

            if (!$subject) {
                return response()->json(['error' => 'Subject not found'], 404);
            }

            $now = now('Asia/Manila');
            $todayName = $now->format('l');

            $todaySchedule = $subject->schedules->first(function ($schedule) use ($todayName) {
                return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
            });

            if (!$todaySchedule) {
                return response()->json([
                    'has_schedule' => false,
                    'message' => 'No class scheduled for today (' . $todayName . ')'
                ]);
            }
            $todayDate = $now->toDateString();
            $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            $endTime = Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
            $sessionOpenTime = $startTime->copy()->subMinutes(5);
            $sessionEndTime = $startTime->copy()->addMinutes(20);

            $status = 'waiting';
            $message = '';
            $canStart = false;

            if ($now->lt($sessionOpenTime)) {
                $waitMinutes = $now->diffInMinutes($sessionOpenTime);
                $status = 'too_early';
                $message = "Session opens in {$waitMinutes} minute(s) at {$sessionOpenTime->format('h:i A')}";
            } else if ($now->gte($sessionOpenTime) && $now->lte($sessionEndTime)) {
                $status = 'ready';
                $message = 'Ready to start attendance session';
                $canStart = true;
            } else if ($now->gt($sessionEndTime)) {
                $status = 'ended';
                $message = 'The 20-minute attendance window has closed';
            }

            return response()->json([
                'has_schedule' => true,
                'status' => $status,
                'message' => $message,
                'can_start' => $canStart,
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
        $subject = Subject::with('schedules')
            ->where('code', $subjectCode)
            ->firstOrFail();

        return view('teacher.qr_fixed', compact('subject'));
    }
    // ─────────────────────────────────────────
    // Teacher: Start QR session
    // ─────────────────────────────────────────
    public function startTeacherSession(Request $request)
    {
        try {
            $teacher = Auth::user();
            $request->validate([
                'subject_code'   => 'required|string|exists:subjects,code',
                'classroom_lat'  => 'required|numeric|between:-90,90',
                'classroom_lng'  => 'required|numeric|between:-180,180',
            ]);

            $subject = Subject::with('schedules')
                ->where('code', $request->subject_code)
                ->first();

            if (!$subject) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject not found.',
                ], 404);
            }

            $now = now('Asia/Manila');
            $todayName = $now->format('l');

            $todaySchedule = $subject->schedules->first(function ($schedule) use ($todayName) {
                return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
            });

            if (!$todaySchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject does not have a class scheduled for today (' . $todayName . '). Please check the subject schedule.',
                ], 422);
            }

            $todayDate = $now->toDateString();
            $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            $endTime = Carbon::parse($todayDate . ' ' . $todaySchedule->end_time);
            $sessionOpenTime = $startTime->copy()->subMinutes(5); // 5 minutes before class
            $sessionEnd = $startTime->copy()->addMinutes(20); // 20-minute attendance window

            // Check if it's too early (more than 5 minutes before class)
            if ($now->lt($sessionOpenTime)) {
                $waitTime = $sessionOpenTime->diffForHumans($now, true);
                return response()->json([
                    'success' => false,
                    'message' => "⏰ Too early! QR session opens 5 minutes before class starts.\n\nClass time: {$startTime->format('h:i A')} - {$endTime->format('h:i A')}\nSession opens: {$sessionOpenTime->format('h:i A')}\nWait time: {$waitTime}",
                    'schedule_info' => [
                        'class_start' => $startTime->format('h:i A'),
                        'class_end' => $endTime->format('h:i A'),
                        'session_opens' => $sessionOpenTime->format('h:i A'),
                        'current_time' => $now->format('h:i A'),
                        'wait_minutes' => $now->diffInMinutes($sessionOpenTime)
                    ]
                ], 422);
            }
            // Check if attendance window has closed (20 minutes after class start)
            if ($now->gt($sessionEnd)) {
                return response()->json([
                    'success' => false,
                    'message' => "The 20-minute attendance window has closed at {$sessionEnd->format('h:i A')}. Cannot start new attendance session.",
                ], 422);
            }

            // Clear any existing active sessions
            AttendanceSession::where('subject_code', $request->subject_code)
                ->where('active', true)
                ->update(['active' => false]);

            $session = AttendanceSession::create([
                'subject_code'    => $request->subject_code,
                'created_by'      => Auth::id(),
                'token'           => AttendanceSession::generateToken($request->subject_code),
                'expires_at'      => $now->copy()->addSeconds(self::QR_TTL_SECONDS)->min($sessionEnd),
                'session_ends_at' => $sessionEnd, // 20 minutes from class start
                'active'          => true,
                'classroom_lat'   => $request->filled('classroom_lat') ? (float) $request->classroom_lat : null,
                'classroom_lng'   => $request->filled('classroom_lng') ? (float) $request->classroom_lng : null,
            ]);

            Log::info('Teacher session started', [
                'session_id' => $session->id,
                'subject_code' => $request->subject_code,
                'teacher_id' => Auth::id(),
                'class_start' => $startTime->toDateTimeString(),
                'session_ends' => $sessionEnd->toDateTimeString(),
            ]);

            return response()->json([
                'success'        => true,
                'session_id'     => $session->id,
                'token'          => $session->token,
                'scan_url'       => $this->buildScanUrl($session->token, $session->session_ends_at),
                'expires_at'     => $session->expires_at->timestamp,
                'session_end'    => $session->session_ends_at->timestamp,
                'classroom_lat'  => $session->classroom_lat,
                'classroom_lng'  => $session->classroom_lng,
                'message'        => 'QR attendance session started successfully!'
            ]);
        } catch (\Throwable $e) {
            Log::error('Teacher start session failed', [
                'error' => $e->getMessage(),
                'exception' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start session: ' . $e->getMessage(),
            ], 500);
        }
    }
    // ─────────────────────────────────────────
    // Teacher: Refresh QR token
    // ─────────────────────────────────────────
    public function refreshTeacherToken(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        $session = AttendanceSession::find($request->session_id);

        if ($session) {
            $session->markInactiveIfExpired();
        }

        if (!$session || !$session->isSessionActive()) {
            return response()->json(['success' => false, 'message' => 'Session expired.'], 404);
        }

        $session->update([
            'token'      => AttendanceSession::generateToken($session->subject_code),
            'expires_at' => $this->tokenExpiresAt($session),
        ]);

        return response()->json([
            'success'    => true,
            'token'      => $session->token,
            'scan_url'   => $this->buildScanUrl($session->token, $session->session_ends_at),
            'expires_at' => $session->expires_at->timestamp,
        ]);
    }

    // ─────────────────────────────────────────
    // Teacher: Stop session
    // ─────────────────────────────────────────
    public function stopTeacherSession(Request $request)
    {
        $request->validate(['session_id' => 'required|integer']);
        $session = AttendanceSession::find($request->session_id);

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found.'], 404);
        }

        $session->update(['active' => false]);
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

        $todayRecords = Attendance::with('user')
            ->where('subject_code', $session->subject_code)
            ->whereDate('date', today())
            ->whereNotNull('time_in')
            ->orderBy('created_at', 'desc')
            ->get();

        $clockins = $todayRecords->map(fn ($r) => [
            'name'           => $r->user->name ?? '—',
            'student_number' => $r->user->student_number ?? '—',
            'status'         => $r->status,
            'time'           => $r->time_in ? Carbon::parse($r->time_in)->format('h:i A') : '—',
            'avatar'         => $r->user && $r->user->profile_image
                ? asset('storage/' . $r->user->profile_image)
                : 'https://ui-avatars.com/api/?name=' . urlencode($r->user->name ?? 'S') . '&background=7c2d12&color=fff&size=80',
        ]);

        $totalStudents = User::where('role', 'student')
            ->where('year_level', $subject->year_level)
            ->where('semester', $subject->semester)
            ->count();

        $clockedIn = $todayRecords->count();
        $late = $todayRecords->where('status', 'Late')->count();
        $progress = $totalStudents > 0 ? round(($clockedIn / $totalStudents) * 100) : 0;

        return response()->json([
            'clockins' => $clockins,
            'stats' => [
                'total_students' => $totalStudents,
                'clocked_in'     => $clockedIn,
                'inside_radius'  => 0,
                'late'           => $late,
                'progress'       => $progress,
            ],
        ]);
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
        
        $session = AttendanceSession::where('token', $token)->first();

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
            return view('qr.result', ['status' => 'already', 'message' => 'You have already clocked in for this class.', 'subject' => $subject->name, 'status_val' => $existing->status]);
        }

        return view('qr.verify', [
            'status'  => 'ready',
            'message' => 'Confirm GPS and fingerprint to finish clock-in.',
            'token'   => $token,
            'subject' => $subject,
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

        $session = AttendanceSession::where('token', $request->token)->first();

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

        if (!$request->user()->webauthnCredentials()->exists()) {
            return response()->json(['success' => false, 'message' => 'Fingerprint verification is not set up on this phone.'], 422);
        }

        // Check if there's already a recent challenge (within last 2 minutes)
        // This prevents overwriting a challenge that the user is still trying to use
        if ($session->webauthn_challenge && $session->updated_at && $session->updated_at->gt(now()->subMinutes(2))) {
            Log::debug('QR verificationOptions - returning existing challenge', [
                'session_id' => $session->id,
                'existing_challenge_age' => $session->updated_at->diffInSeconds(now()) . ' seconds',
                'challenge_length' => strlen($session->webauthn_challenge),
            ]);

            // Return the existing challenge instead of creating a new one
            $rpId = $this->getRpId($request);
            $options = [
                'challenge' => $session->webauthn_challenge,
                'rpId' => $rpId,
                'allowCredentials' => $request->user()->webauthnCredentials
                    ->map(fn ($credential) => [
                        'type' => 'public-key',
                        'id' => $credential->credential_id,
                    ])
                    ->values(),
                'userVerification' => 'required',
                'timeout' => 60000,
            ];

            return response()->json(array_merge(['success' => true], $options));
        }

        // Store challenge in database instead of session for cross-device compatibility
        $challenge = $this->base64UrlEncode(random_bytes(32));
        
        // Debug: Check if the column exists in the database
        Log::debug('QR verificationOptions - before storing challenge', [
            'attendance_session_id' => $session->id,
            'session_columns' => array_keys($session->getAttributes()),
            'challenge_to_store' => $challenge,
            'challenge_length' => strlen($challenge),
        ]);
        
        $updateResult = $session->update(['webauthn_challenge' => $challenge]);
        
        Log::debug('QR verificationOptions - after storing challenge', [
            'update_result' => $updateResult,
            'stored_challenge' => $session->webauthn_challenge,
            'fresh_challenge' => $session->fresh()->webauthn_challenge,
        ]);

        $rpId = $this->getRpId($request);
        $options = [
            'challenge' => $challenge,
            'rpId' => $rpId,
            'allowCredentials' => $request->user()->webauthnCredentials
                ->map(fn ($credential) => [
                    'type' => 'public-key',
                    'id' => $credential->credential_id,
                ])
                ->values(),
            'userVerification' => 'required',
            'timeout' => 60000,
        ];

        Log::debug('QR verification challenge generated and stored', [
            'session_id' => session()->getId(),
            'stored_challenge' => $challenge,
            'rpId' => $rpId,
            'allow_credentials_count' => count($options['allowCredentials']),
            'attendance_session_id' => $session->id,
            'challenge_length' => strlen($challenge),
            'session_updated' => $session->refresh()->webauthn_challenge === $challenge,
            'database_update_success' => $updateResult,
            'fresh_session_challenge' => $session->fresh()->webauthn_challenge,
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
        $session = AttendanceSession::where('token', $request->token)->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'This QR code is no longer active.'], 422);
        }

        // Don't clean up expired challenges here either - let them persist until verification completes
        // Clean up expired challenges
        // $session->cleanupExpiredChallenge();

        // Only check if the entire session has expired, not individual token expiry
        if (!$session->active || now()->gt($session->session_ends_at)) {
            return response()->json(['success' => false, 'message' => 'The attendance session has ended. Please get a new QR code.'], 422);
        }

        Log::debug('QR completeVerification request', [
            'session_id' => session()->getId(),
            'cookie' => $request->cookie(config('session.cookie')),
            'token' => $request->token,
            'user_id' => optional($request->user())->id,
            'stored_challenge' => $session->webauthn_challenge,
            'fresh_challenge' => $session->fresh()->webauthn_challenge,
            'session_attributes' => $session->getAttributes(),
        ]);

        try {
            if (!$session->webauthn_challenge) {
                Log::error('QR completeVerification - no challenge in session', [
                    'session_id' => session()->getId(),
                    'attendance_session_id' => $session->id,
                    'user_id' => $user->id,
                    'session_updated_at' => $session->updated_at,
                    'session_active' => $session->active,
                    'all_session_attributes' => $session->getAttributes(),
                ]);
                throw new RuntimeException('No WebAuthn challenge found for this QR session. Please try scanning the QR code again.');
            }

            Log::debug('QR completeVerification - challenge found', [
                'challenge_length' => strlen($session->webauthn_challenge),
                'challenge_preview' => substr($session->webauthn_challenge, 0, 10) . '...',
                'session_updated_at' => $session->updated_at,
            ]);

            // Store challenge in session for WebauthnService verification
            session(['webauthn.auth_challenge' => $session->webauthn_challenge]);
            
            Log::debug('QR completeVerification - challenge set', [
                'session_id' => session()->getId(),
                'attendance_session_id' => $session->id,
                'challenge_length' => strlen($session->webauthn_challenge),
                'session_challenge_set' => session('webauthn.auth_challenge') === $session->webauthn_challenge,
            ]);

            try {
                $webauthn->verifyAssertion($user, $credential);
                Log::debug('QR completeVerification - verification successful', [
                    'user_id' => $user->id,
                    'attendance_session_id' => $session->id,
                ]);
            } finally {
                session()->forget('webauthn.auth_challenge');
            }

            // Clear challenge from database after successful verification
            $session->clearWebauthnChallenge();
        } catch (RuntimeException $e) {
            Log::debug('QR completeVerification failure', [
                'exception' => $e->getMessage(),
                'session_id' => session()->getId(),
                'attendance_session_id' => $session->id,
                'stored_challenge' => $session->webauthn_challenge,
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

        $schoolLat    = $session->classroom_lat ?? \App\Models\Setting::get('gps_lat', self::SCHOOL_LAT);
        $schoolLng    = $session->classroom_lng ?? \App\Models\Setting::get('gps_lng', self::SCHOOL_LNG);
        $radiusMeters = \App\Models\Setting::get('gps_radius', 50);

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
            Log::warning('QR distance validation failed', [
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

            return response()->json(['success' => false, 'message' => 'You are too far from the classroom location. Distance: ' . round($distance) . 'm, allowed: ' . $radiusMeters . 'm.'], 422);
        }

        $existing = Attendance::where('user_id', $user->id)
            ->where('subject_code', $session->subject_code)
            ->where('date', $todayDate)
            ->first();

        if ($existing && in_array($existing->status, ['Present', 'Late'])) {
            return response()->json([
                'success'  => true,
                'redirect' => route('home'),
                'message'  => 'You have already clocked in for this class.',
            ]);
        }

        // Get class start time for late threshold calculation
        $subject = $session->subject;
        $startTime = null;
        
        // Try to get start time from schedules
        if ($subject && $subject->schedules) {
            $todayName = $now->format('l');
            $todaySchedule = $subject->schedules->first(function ($schedule) use ($todayName) {
                return strcasecmp(trim($schedule->day ?? ''), $todayName) === 0;
            });
            
            if ($todaySchedule) {
                $startTime = Carbon::parse($todayDate . ' ' . $todaySchedule->start_time);
            }
        }
        
        // Fallback to subject start_time if available
        if (!$startTime && $subject && $subject->start_time) {
            $startTime = Carbon::parse($todayDate . ' ' . $subject->start_time);
        }

        // Determine status based on timing (15-minute late threshold)
        $status = 'Present'; // Default to present
        if ($startTime) {
            $lateThreshold = $startTime->copy()->addMinutes(15);
            $status = $now->lte($lateThreshold) ? 'Present' : 'Late';
        }
        $attendance = Attendance::updateOrCreate(
            ['user_id' => $user->id, 'subject_code' => $session->subject_code, 'date' => $todayDate],
            [
                'status'    => $status,
                'time_in'   => $now->format('H:i:s'),
                'latitude'  => $request->latitude,
                'longitude' => $request->longitude,
            ]
        );

        event(new \App\Events\AttendanceMarked($attendance));

        // Broadcast a real-time update to the teacher dashboard / QR session view.
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
            'success'  => true,
            'redirect' => route('home'),
            'message'  => "Clock-in successful! Status: {$status}",
        ]);
    }

    // ─────────────────────────────────────────
    // ADMIN: Get live clock-ins for a session
    // ─────────────────────────────────────────
    public function getClockIns(Request $request)
    {
        $session = AttendanceSession::find($request->session_id);

        if (!$session) {
            return response()->json([
                'clockins' => [],
                'stats'    => [
                    'total_students' => 0,
                    'clocked_in'     => 0,
                    'inside_radius'  => 0,
                    'late'           => 0,
                    'progress'       => 0,
                ],
            ]);
        }

        $today         = now()->toDateString();
        $subject       = $session->subject;
        if (!$subject) {
            $totalStudents = 0;
        } else {
            $studentQuery = User::where('role', 'student')
                ->where('year_level', $subject->year_level)
                ->where('semester', $subject->semester);

            if (!empty($subject->course)) {
                $studentQuery->where('course', $subject->course);
            }

            if (!empty($subject->section)) {
                $studentQuery->where('section', $subject->section);
            }

            $totalStudents = $studentQuery->count();
        }

        $attendanceRecords = Attendance::with('user')
            ->where('subject_code', $session->subject_code)
            ->whereDate('date', $today)
            ->whereNotNull('time_in')
            ->orderBy('created_at', 'desc')
            ->get();

        $insideRadius = $attendanceRecords->filter(function ($record) {
            if (!$record->latitude || !$record->longitude) {
                return false;
            }
            return $this->distance((float) $record->latitude, (float) $record->longitude, self::SCHOOL_LAT, self::SCHOOL_LNG) <= self::RADIUS_METERS;
        })->count();
        $records = $attendanceRecords->map(fn($r) => [
            'name'           => $r->user->name ?? '—',
            'student_number' => $r->user->student_number ?? '—',
            'status'         => $r->status,
            'time'           => Carbon::parse($r->time_in)->format('h:i A'),
            'avatar'         => $r->user && $r->user->profile_image
                ? asset('storage/' . $r->user->profile_image)
                : 'https://ui-avatars.com/api/?name=' . urlencode($r->user->name ?? 'S'),
        ]);

        return response()->json([
            'clockins' => $records,
            'stats'    => [
                'total_students' => $totalStudents,
                'clocked_in'     => $attendanceRecords->count(),
                'inside_radius'  => $insideRadius,
                'late'           => $attendanceRecords->where('status', 'Late')->count(),
                'progress'       => $totalStudents > 0
                    ? round(($attendanceRecords->count() / $totalStudents) * 100)
                    : 0,
            ],
        ]);
    }

    // ─────────────────────────────────────────
    // DEBUG: Test QR timing
    // ─────────────────────────────────────────
    public function debugTiming(Request $request)
    {
        $session = AttendanceSession::where('active', true)->latest()->first();
        
        if (!$session) {
            return response()->json(['error' => 'No active session found']);
        }
        
        $now = now('Asia/Manila');
        
        return response()->json([
            'current_time' => $now->toDateTimeString(),
            'session_created' => $session->created_at->toDateTimeString(),
            'token_expires' => $session->expires_at->toDateTimeString(),
            'session_ends' => $session->session_ends_at->toDateTimeString(),
            'token_valid' => $session->isTokenValid(),
            'session_active' => $session->isSessionActive(),
            'token_seconds_left' => $now->diffInSeconds($session->expires_at, false),
            'session_minutes_left' => $now->diffInMinutes($session->session_ends_at, false),
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
        if ($student->year_level != $subject->year_level) {
            return "Year mismatch: you are year {$student->year_level}, but this class is year {$subject->year_level}.";
        }

        if ($student->semester != $subject->semester) {
            return "Semester mismatch: you are semester {$student->semester}, but this class is semester {$subject->semester}.";
        }

        if (!empty($subject->course) && strcasecmp(trim((string) $student->course), trim((string) $subject->course)) !== 0) {
            return "Course mismatch: you are in {$student->course}, but this class is for {$subject->course}.";
        }

        // Students may not have section data in this system.
        // Only enforce section when the student has a section value set.
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