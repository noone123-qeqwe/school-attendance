<?php

namespace App\Http\Middleware;

use App\Services\AttendanceQrTokenService;
use App\Jobs\SendTeacherScanAlert;
use App\Events\AttendanceScanAlert;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AuditAttendanceQrScan
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $input = (string) ($request->route('token') ?: $request->input('token', ''));
        if (!preg_match('/aqr\.\d+\.\d+\.[a-f0-9]{64}\.\d+\.\d+\.[a-f0-9]{64}/', $input, $match)) {
            return $response;
        }

        try {
            $inspection = app(AttendanceQrTokenService::class)->inspect($match[0]);
            $record = $inspection['token'];
            $body = json_decode($response->getContent() ?: '', true);
            $already = is_array($body) && !empty($body['already_clocked_in']);
            $opened = $request->isMethod('GET') && $response->isSuccessful()
                && $inspection['status'] === 'valid';
            $accepted = $request->isMethod('POST') && $response->isSuccessful()
                && $inspection['status'] === 'valid' && is_array($body)
                && !empty($body['success']) && !$already;
            $reason = $accepted || $opened ? null : ($body['error_type'] ?? ($already ? 'duplicate' : $inspection['status']));
            $deviceKey = (string) $request->header('X-Device-Key', '');

            $activity = activity('attendance-qr')->withProperties([
                'attendance_session_id' => $record?->attendance_session_id,
                'qr_token_id' => $record?->id,
                'user_id' => $request->user()?->id,
                'device_id_hash' => $deviceKey !== '' ? hash('sha256', $deviceKey) : null,
                'ip' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 200),
                'reason' => $reason,
                'http_status' => $response->getStatusCode(),
            ]);
            if ($request->user()) $activity->causedBy($request->user());
            if ($record?->session) $activity->performedOn($record->session);
            $activity->log($accepted ? 'qr_scanned' : ($opened ? 'qr_opened' : 'scan_rejected'));

            $suspicious = in_array($reason, ['proxy_device_detected', 'location_jump_review', 'device_mismatch'], true);
            if ($record && $suspicious) {
                $anomaly = activity('attendance-qr')->performedOn($record->session)
                    ->withProperties([
                        'attendance_session_id' => $record->attendance_session_id,
                        'qr_token_id' => $record->id,
                        'student_id' => $request->user()?->id,
                        'reason' => $reason,
                    ]);
                if ($request->user()) $anomaly->causedBy($request->user());
                $anomaly->log('anomaly_detected');

            }
            if ($record && ($suspicious || $reason === 'duplicate')) {
                $teacherId = (int) ($record->session?->subject?->instructor_id ?: $record->session?->created_by);
                if ($teacherId) {
                    if ($suspicious) {
                        SendTeacherScanAlert::dispatch(
                            $teacherId, $record->attendance_session_id,
                            $record->session->subject_code, $reason, $request->user()?->id
                        );
                    }
                    AttendanceScanAlert::dispatch($teacherId, $record->attendance_session_id, $reason);
                }
            }
        } catch (\Throwable $exception) {
            Log::warning('QR scan audit failed', ['error' => $exception->getMessage()]);
        }

        return $response;
    }
}
