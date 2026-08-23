<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ParentApiController;
use App\Http\Controllers\Api\OtpApiController;

// Authentication & Brute-Force Protected Login
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// OTP & 2FA Protected Endpoints
Route::post('/otp', [OtpApiController::class, 'sendOtp'])->middleware('throttle:otp.send');
Route::post('/otp/send', [OtpApiController::class, 'sendOtp'])->middleware('throttle:otp.send');
Route::post('/otp/verify', [OtpApiController::class, 'verifyOtp'])->middleware('throttle:otp.verify');

// Password Reset & Flood-Protected Endpoints
Route::post('/reset', [OtpApiController::class, 'requestPasswordReset'])->middleware('throttle:password.reset');
Route::post('/forgot-password', [OtpApiController::class, 'requestPasswordReset'])->middleware('throttle:password.reset');
Route::post('/reset-password', [OtpApiController::class, 'resetPassword'])->middleware('throttle:password.change');

// Email Verification & Anti-Abuse Endpoints
Route::post('/email/verify', [OtpApiController::class, 'sendEmailVerification'])->middleware('throttle:email.verify');
Route::post('/email/resend', [OtpApiController::class, 'sendEmailVerification'])->middleware('throttle:email.verify');

// Authenticated API Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    // Parent specific mobile API endpoints
    Route::prefix('parent')->middleware('parent')->group(function () {
        Route::get('/dashboard', [ParentApiController::class, 'dashboard']);
        Route::get('/child/{child}', [ParentApiController::class, 'childDetail']);
    });
});

// API Fallback: Catches any unmatched /api/* requests so global rate limiting applies
Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API endpoint not found.'
    ], 404);
});
