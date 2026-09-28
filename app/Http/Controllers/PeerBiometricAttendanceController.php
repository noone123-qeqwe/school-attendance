<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmPeerBiometricAttendanceRequest;
use App\Services\PeerBiometricAttendanceService;
use Illuminate\Http\Request;

class PeerBiometricAttendanceController extends Controller
{
    public function index()
    {
        return view('mobile.peer-snap');
    }

    public function sessions(Request $request, PeerBiometricAttendanceService $service)
    {
        return response()->json([
            'available' => $service->isAvailable(),
            'sessions' => $service->eligibleSessions($request->user()),
        ]);
    }

    public function start(Request $request, PeerBiometricAttendanceService $service)
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer', 'min:1'],
            'student_number' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);
        return response()->json($service->start($request->user(), (int) $data['session_id'], $data['student_number']), 201);
    }

    public function confirm(ConfirmPeerBiometricAttendanceRequest $request, PeerBiometricAttendanceService $service)
    {
        return response()->json($service->confirm(
            $request->user(), $request->validated('verification_id'),
            $request->validated('nonce'), $request->file('frames'),
        ));
    }
}
