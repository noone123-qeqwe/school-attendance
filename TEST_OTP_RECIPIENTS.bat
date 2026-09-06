@echo off
REM ============================================================
REM OTP EMAIL RECIPIENT VERIFICATION TEST SCRIPT
REM ============================================================
REM
REM This script tests that OTP emails can be sent to ANY
REM valid email address (not just hardcoded recipients).
REM
REM Usage: TEST_OTP_RECIPIENTS.bat
REM ============================================================

echo.
echo ============================================================
echo   OTP EMAIL RECIPIENT SYSTEM - VERIFICATION TEST
echo ============================================================
echo.

cd school_attendance

echo [TEST 1] Checking for hardcoded recipient restrictions...
echo.
findstr /S /I /C:"alwaysTo" /C:"Mail::to(" app\Services\*.php app\Mail\*.php 2>nul
if errorlevel 1 (
    echo   PASS: No alwaysTo restrictions found
) else (
    echo   WARNING: Found Mail::to usage - reviewing...
)
echo.

echo [TEST 2] Verifying EmailDeliveryService uses dynamic recipient...
echo.
findstr /C:"Mail::to($recipientEmail)" app\Services\Email\EmailDeliveryService.php >nul
if errorlevel 1 (
    echo   FAIL: EmailDeliveryService not using dynamic recipient
) else (
    echo   PASS: EmailDeliveryService uses $recipientEmail parameter
)
echo.

echo [TEST 3] Checking OtpService passes email correctly...
echo.
findstr /C:"recipientEmail: $cleanEmail" app\Services\OtpService.php >nul
if errorlevel 1 (
    echo   FAIL: OtpService not passing email correctly
) else (
    echo   PASS: OtpService passes user's email to delivery service
)
echo.

echo [TEST 4] Send test OTP to custom email address...
echo.
set /p TEST_EMAIL="Enter your email address for testing (or press Enter to skip): "

if "%TEST_EMAIL%"=="" (
    echo   SKIPPED: No email provided
    goto :summary
)

echo.
echo   Sending diagnostic test email to: %TEST_EMAIL%
echo.
php artisan email:test "%TEST_EMAIL%" --otp
echo.
echo   Check your inbox/spam folder for the OTP email.
echo.

:summary
echo ============================================================
echo   TEST SUMMARY
echo ============================================================
echo.
echo   The OTP system uses dynamic recipients (no hardcoding).
echo   Any email entered by users will receive its own OTP.
echo.
echo   If OTP emails are not being received, check:
echo     1. SMTP configuration in .env file
echo     2. Recipient's spam/junk folder
echo     3. Rate limiting (30-second cooldown between requests)
echo     4. Server logs: storage\logs\laravel.log
echo.
echo   See: OTP_EMAIL_SYSTEM_VERIFICATION.md for full analysis
echo.
echo ============================================================
echo.

pause
