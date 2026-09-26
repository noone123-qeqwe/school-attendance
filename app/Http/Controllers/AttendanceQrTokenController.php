<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateAttendanceQrRequest;
use App\Http\Requests\ReplaceAttendanceQrRequest;
use App\Http\Requests\TeacherEmergencyQrRequest;
use App\Models\AttendanceQrToken;
use App\Models\AttendanceSession;
use App\Services\AttendanceQrTokenService;
use Illuminate\Http\Request;

class AttendanceQrTokenController extends Controller
{
    public function status(Request $request, AttendanceSession $session, AttendanceQrTokenService $tokens)
    {
        $this->authorize('viewAssistantClass', $session);
        if (!$session->isSessionActive()) {
            return response()->json(['success' => false, 'message' => 'Attendance session has ended.'], 410);
        }
        $this->authorize('viewAssistantQr', $session);
        $token = $session->activeQrToken;

        return response()->json($token && $token->expires_at->isFuture()
            ? $this->payload($token, $tokens)
            : ['success' => true, 'active' => false, 'session_ends_at' => $session->session_ends_at->timestamp]);
    }

    public function generate(GenerateAttendanceQrRequest $request, AttendanceSession $session, AttendanceQrTokenService $tokens)
    {
        return response()->json($this->payload($tokens->issue($session, $request->user(), 'show'), $tokens));
    }

    public function rotate(GenerateAttendanceQrRequest $request, AttendanceSession $session, AttendanceQrTokenService $tokens)
    {
        return response()->json($this->payload($tokens->issue($session, $request->user(), 'auto'), $tokens));
    }

    public function replace(ReplaceAttendanceQrRequest $request, AttendanceSession $session, AttendanceQrTokenService $tokens)
    {
        return response()->json($this->payload($tokens->issue($session, $request->user(), 'manual'), $tokens));
    }

    public function emergency(TeacherEmergencyQrRequest $request, AttendanceSession $session, AttendanceQrTokenService $tokens)
    {
        return response()->json($this->payload($tokens->issue($session, $request->user(), 'emergency'), $tokens));
    }

    private function payload(AttendanceQrToken $token, AttendanceQrTokenService $tokens): array
    {
        $token->loadMissing('generator');
        $raw = $tokens->tokenFor($token);

        return [
            'success' => true,
            'active' => true,
            'token_id' => $token->id,
            'token' => $raw,
            'scan_url' => route('qr.scan', $raw),
            'issued_at' => $token->issued_at->timestamp,
            'expires_at' => $token->expires_at->timestamp,
            'session_ends_at' => $token->session?->session_ends_at?->timestamp,
            'generated_by' => $token->generator?->name,
            'generator_type' => $token->generator_type,
        ];
    }
}
