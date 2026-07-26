<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f9fafb; color: #1f2937; line-height: 1.6; }
        .container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header { background: #800000; padding: 30px; text-align: center; color: white; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 1px; }
        .content { padding: 40px 30px; }
        .warning-box { background: #fee2e2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 6px; margin: 25px 0; }
        .footer { background: #f3f4f6; padding: 20px; text-align: center; font-size: 13px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Attendance Warning</h1>
        </div>
        <div class="content">
            <p>Dear <strong>{{ $user->name }}</strong>,</p>
            
            <p>This is an automated notification regarding your attendance in <strong>{{ $subject->name }} ({{ $subject->code }})</strong>.</p>
            
            <div class="warning-box">
                <h3 style="margin-top: 0; color: #991b1b;">⚠️ Accumulated Absences: {{ $absencesCount }}</h3>
                <p style="margin-bottom: 0; color: #7f1d1d;">
                    @if($absencesCount >= 5)
                        <strong>CRITICAL WARNING:</strong> You have reached a critical level of absences. Further absences may result in being dropped from the course or failing.
                    @else
                        Please be mindful of your attendance. Consistent attendance is crucial to passing the course.
                    @endif
                </p>
            </div>
            
            <p>If you believe this is an error, please reach out to your instructor immediately or submit a formal excuse letter via the student portal.</p>
            
            <p>Best regards,<br>School Administration</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} School Attendance System. This is an automated message.
        </div>
    </div>
</body>
</html>
