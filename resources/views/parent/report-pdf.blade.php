<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Attendance Report - {{ $child->name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #800000; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #800000; font-size: 24px; text-transform: uppercase; }
        .header p { margin: 5px 0 0; font-size: 14px; color: #666; }
        .info-section { margin-bottom: 20px; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 4px 0; }
        .info-table strong { color: #800000; }
        .stats-grid { width: 100%; border-collapse: collapse; margin-bottom: 30px; text-align: center; }
        .stats-grid td { border: 1px solid #ddd; padding: 15px; width: 25%; }
        .stats-val { font-size: 24px; font-weight: bold; color: #800000; }
        .stats-lbl { font-size: 11px; text-transform: uppercase; color: #666; margin-top: 5px; }
        h2 { font-size: 16px; color: #800000; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-top: 30px; margin-bottom: 15px; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .data-table th { background-color: #f9f9f9; color: #800000; font-weight: bold; }
        .badge { padding: 3px 6px; border-radius: 3px; font-size: 10px; font-weight: bold; color: #fff; }
        .badge-present { background-color: #4ade80; }
        .badge-late { background-color: #fbbf24; }
        .badge-absent { background-color: #ef4444; }
        .badge-excused { background-color: #3b82f6; }
    </style>
</head>
<body>

    <div class="header">
        <h1>School Attendance System</h1>
        <p>Official Student Attendance Report</p>
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Student Name:</strong> {{ $child->name }}</td>
            <td><strong>Student ID:</strong> {{ $child->student_number }}</td>
        </tr>
        <tr>
            <td><strong>Course & Year:</strong> {{ $child->course }} - Year {{ $child->year_level }}</td>
            <td><strong>Report Date:</strong> {{ now()->format('F d, Y') }}</td>
        </tr>
    </table>

    <table class="stats-grid">
        <tr>
            <td>
                <div class="stats-val">{{ $rate }}%</div>
                <div class="stats-lbl">Attendance Rate</div>
            </td>
            <td>
                <div class="stats-val" style="color: #16a34a;">{{ $present }}</div>
                <div class="stats-lbl">Present</div>
            </td>
            <td>
                <div class="stats-val" style="color: #d97706;">{{ $late }}</div>
                <div class="stats-lbl">Late</div>
            </td>
            <td>
                <div class="stats-val" style="color: #dc2626;">{{ $absent }}</div>
                <div class="stats-lbl">Absent</div>
            </td>
        </tr>
    </table>

    @if($warnings->count() > 0)
    <h2>Recent Warnings</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="25%">Subject</th>
                <th width="55%">Message</th>
            </tr>
        </thead>
        <tbody>
            @foreach($warnings as $warning)
            <tr>
                <td>{{ $warning->created_at->format('M d, Y') }}</td>
                <td>{{ $warning->subject->name ?? $warning->subject_code }}</td>
                <td>{{ $warning->message }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <h2>Attendance Records</h2>
    @if($attendances->count() > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="35%">Subject</th>
                <th width="20%">Status</th>
                <th width="25%">Time In</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attendances as $record)
            <tr>
                <td>{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                <td>{{ $record->subject->name ?? $record->subject_code }}</td>
                <td>
                    @if($record->excused)
                        <span class="badge badge-excused">Excused</span>
                    @elseif($record->status === 'Present')
                        <span class="badge badge-present">Present</span>
                    @elseif($record->status === 'Late')
                        <span class="badge badge-late">Late</span>
                    @else
                        <span class="badge badge-absent">Absent</span>
                    @endif
                </td>
                <td>{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : '--' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p>No attendance records found.</p>
    @endif

</body>
</html>
