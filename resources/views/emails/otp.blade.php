<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
    body { font-family: 'Inter', Arial, sans-serif; background: #f1f5f9; margin: 0; padding: 30px 20px; }
    .container { max-width: 480px; margin: 0 auto; background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.08); }
    .header { background: linear-gradient(135deg, #800000, #a00000); padding: 32px; text-align: center; }
    .header img { width: 60px; height: 60px; border-radius: 50%; border: 3px solid rgba(255,255,255,0.3); margin-bottom: 12px; }
    .header h1 { color: white; font-size: 18px; font-weight: 800; margin: 0; letter-spacing: -0.3px; }
    .header p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 6px 0 0; }
    .body { padding: 32px; }
    .greeting { font-size: 15px; color: #1e293b; font-weight: 600; margin-bottom: 12px; }
    .message { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 28px; }
    .otp-box { background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 14px; padding: 24px; text-align: center; margin-bottom: 24px; }
    .otp-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
    .otp-code { font-size: 42px; font-weight: 800; color: #800000; letter-spacing: 12px; font-family: monospace; }
    .otp-expiry { font-size: 12px; color: #94a3b8; margin-top: 10px; }
    .warning { background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-bottom: 24px; }
    .footer { background: #f8fafc; padding: 20px 32px; text-align: center; border-top: 1px solid #f1f5f9; }
    .footer p { font-size: 12px; color: #94a3b8; margin: 0; }
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>{{ env('APP_SUBTITLE', 'QR, GPS & Biometric System') }}</p>
    </div>
    <div class="body">
        <div class="greeting">Hi, {{ $name }}! 👋</div>
        <div class="message">
            @if($purpose === 'forgot_password')
                We received a request to reset your password. Use the OTP below to proceed. If you didn't request this, you can safely ignore this email.
            @elseif($purpose === 'change_email')
                You requested to change your email address. Use the OTP below to verify it's really you. This was sent to your current email.
            @elseif($purpose === 'register')
                Welcome! Use the OTP below to verify your email address and complete your registration.
            @else
                You requested to change your password. Use the OTP below to verify it's really you.
            @endif
        </div>

        <div class="otp-box">
            <div class="otp-label">Your One-Time Password</div>
            <div class="otp-code">{{ $otp }}</div>
            <div class="otp-expiry">⏱ Expires in 10 minutes</div>
        </div>

        <div class="warning">
            ⚠️ Never share this OTP with anyone. System administrators will never ask for your OTP.
        </div>
    </div>
    <div class="footer">
        <p>© {{ date('Y') }} {{ config('app.name') }}</p>
        <p style="margin-top:4px;">If you didn't request this, please contact your administrator.</p>
    </div>
</div>
</body>
</html>
