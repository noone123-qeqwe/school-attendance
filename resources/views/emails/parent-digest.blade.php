<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Daily Attendance Digest</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #800000; /* Primary brand color */
            margin: 0;
            font-size: 24px;
        }
        .student-section {
            margin-bottom: 30px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .student-header {
            background-color: #f8fafc;
            padding: 15px 20px;
            font-weight: 600;
            font-size: 18px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
        }
        .attendance-table th, .attendance-table td {
            padding: 12px 20px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .attendance-table th {
            background-color: #fef7f7;
            color: #800000;
            font-weight: 600;
            font-size: 14px;
        }
        .attendance-table tr:last-child td {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-present {
            background-color: #dcfce7;
            color: #166534;
        }
        .status-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-late {
            background-color: #fef3c7;
            color: #92400e;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #94a3b8;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daily Attendance Digest</h1>
            <p style="color: #64748b; margin-top: 5px;">{{ \Carbon\Carbon::today()->format('l, F j, Y') }}</p>
        </div>

        <p>Dear {{ $parentUser->name }},</p>
        <p>Here is the attendance summary for your child(ren) for today:</p>

        @foreach($digestData as $data)
            <div class="student-section">
                <div class="student-header">
                    {{ $data['child']->name }}
                </div>
                <table class="attendance-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['attendances'] as $attendance)
                            <tr>
                                <td>
                                    <strong>{{ $attendance->subject_code }}</strong><br>
                                    <span style="font-size: 12px; color: #64748b;">
                                        {{ $attendance->subject ? $attendance->subject->name : '' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-{{ strtolower($attendance->status) }}">
                                        {{ $attendance->status }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="footer">
            <p>This is an automated message from the Smart Classroom Attendance System.</p>
        </div>
    </div>
</body>
</html>
