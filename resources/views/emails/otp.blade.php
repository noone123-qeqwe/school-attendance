<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
</head>
<body style="margin:0; padding:32px 16px; background-color:#f8fafc; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1e293b;">
    <div style="max-width:480px; margin:0 auto; background:#ffffff; border-radius:12px; border:1px solid #e2e8f0; padding:32px 28px; box-sizing:border-box;">
        <h2 style="margin:0 0 16px 0; font-size:18px; font-weight:700; color:#0f172a;">Hello,</h2>
        
        <p style="margin:0 0 20px 0; font-size:15px; color:#334155; line-height:1.5;">Your verification code is:</p>
        
        <div style="margin:24px 0; text-align:center;">
            <div style="display:inline-block; font-family:Consolas, 'Courier New', Courier, monospace; font-size:36px; font-weight:800; letter-spacing:8px; color:#8a1515; background-color:#fef2f2; border:1px solid #fecaca; padding:14px 28px; border-radius:10px;">
                {{ $otp }}
            </div>
        </div>
        
        <p style="margin:20px 0 0 0; font-size:14px; color:#475569; line-height:1.5;">This code will expire in 10 minutes.</p>
        
        <div style="margin-top:24px; padding-top:20px; border-top:1px solid #f1f5f9;">
            <p style="margin:0; font-size:13px; color:#94a3b8; line-height:1.5;">If you did not request this code, you can safely ignore this email.</p>
        </div>
    </div>
</body>
</html>
