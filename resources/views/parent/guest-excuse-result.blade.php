<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($status) && $status === 'success' ? 'Excuse Submitted Successfully' : 'Excuse Submission Status' }} - Smart Classroom Attendance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(145deg, #0b0f19 0%, #0f172a 50%, #1e1e38 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            margin: 0;
        }

        .result-container {
            max-width: 520px;
            width: 100%;
            margin: 0 auto;
        }

        .result-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            padding: 36px 32px 32px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .result-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: {{ isset($status) && $status === 'success' ? 'linear-gradient(90deg, #10b981, #3b82f6)' : 'linear-gradient(90deg, #3b82f6, #6366f1)' }};
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            position: relative;
        }

        .icon-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 2px solid rgba(16, 185, 129, 0.3);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.25);
        }

        .icon-info {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 2px solid rgba(59, 130, 246, 0.3);
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.25);
        }

        .result-title {
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .result-desc {
            font-size: 0.92rem;
            color: #94a3b8;
            line-height: 1.5;
            margin-bottom: 24px;
        }

        /* Summary Info Box */
        .summary-box {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 24px;
            text-align: left;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.85rem;
        }

        .summary-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-row:first-child {
            padding-top: 0;
        }

        .summary-label {
            color: #94a3b8;
            font-weight: 500;
        }

        .summary-val {
            color: #f1f5f9;
            font-weight: 600;
            text-align: right;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-approved {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Timeline Box */
        .timeline-box {
            background: rgba(59, 130, 246, 0.06);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 24px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            text-align: left;
        }

        .timeline-icon {
            font-size: 1.25rem;
            color: #60a5fa;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .timeline-text {
            font-size: 0.82rem;
            color: #cbd5e1;
            line-height: 1.45;
        }

        .result-footer {
            font-size: 0.75rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="result-container">
        <div class="result-card">
            @if(isset($status) && $status === 'success')
                <div class="icon-circle icon-success">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h1 class="result-title">Excuse Submitted</h1>
                <p class="result-desc">Your official excuse letter has been recorded in the attendance system and forwarded to the assigned class instructor.</p>

                @if(isset($attendance))
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="summary-label">Reference ID</span>
                            <span class="summary-val text-primary">#EXC-{{ $excuse->id ?? 'SUB' }}-{{ $attendance->id }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Student</span>
                            <span class="summary-val">{{ $attendance->user->name ?? 'N/A' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Subject</span>
                            <span class="summary-val">{{ $attendance->subject_code }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Class Date</span>
                            <span class="summary-val">{{ $attendance->date ? $attendance->date->format('M d, Y') : 'N/A' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Review Status</span>
                            <span class="summary-val">
                                <span class="status-badge status-pending">
                                    <i class="bi bi-hourglass-split"></i> Pending Teacher Review
                                </span>
                            </span>
                        </div>
                        @if(isset($excuse) && !empty($excuse->attachments))
                            <div class="summary-row">
                                <span class="summary-label">Attachment</span>
                                <span class="summary-val text-success">
                                    <i class="bi bi-paperclip me-1"></i> Document Attached
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="timeline-box">
                    <i class="bi bi-bell-fill timeline-icon"></i>
                    <div class="timeline-text">
                        The instructor has received an urgent in-app notification. Once they inspect your reason and medical certificate, the attendance status will automatically update.
                    </div>
                </div>
            @else
                <div class="icon-circle icon-info">
                    <i class="bi bi-info-lg"></i>
                </div>
                <h1 class="result-title">Excuse Notice</h1>
                <p class="result-desc">{{ $message ?? 'An excuse letter has already been submitted for this attendance session.' }}</p>

                @if(isset($attendance) && isset($excuse))
                    <div class="summary-box">
                        <div class="summary-row">
                            <span class="summary-label">Student</span>
                            <span class="summary-val">{{ $attendance->user->name ?? 'N/A' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Subject</span>
                            <span class="summary-val">{{ $attendance->subject_code }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Submission Date</span>
                            <span class="summary-val">{{ $excuse->created_at ? $excuse->created_at->format('M d, Y h:i A') : 'N/A' }}</span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Current Status</span>
                            <span class="summary-val">
                                @if($excuse->status === 'approved')
                                    <span class="status-badge status-approved">
                                        <i class="bi bi-check-circle-fill"></i> Approved
                                    </span>
                                @elseif($excuse->status === 'rejected')
                                    <span class="status-badge status-rejected">
                                        <i class="bi bi-x-circle-fill"></i> Declined
                                    </span>
                                @else
                                    <span class="status-badge status-pending">
                                        <i class="bi bi-hourglass-split"></i> Pending Review
                                    </span>
                                @endif
                            </span>
                        </div>
                        @if($excuse->admin_notes)
                            <div class="summary-row">
                                <span class="summary-label">Teacher Note</span>
                                <span class="summary-val text-warning">{{ $excuse->admin_notes }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="timeline-box">
                    <i class="bi bi-question-circle-fill timeline-icon"></i>
                    <div class="timeline-text">
                        If you need to submit additional medical records or update your justification, please contact the instructor directly or log in to your Parent Dashboard.
                    </div>
                </div>
            @endif

            <div class="result-footer">
                <i class="bi bi-shield-check text-primary"></i>
                <span>Smart Classroom Attendance System &bull; DepEd Compliant</span>
            </div>
        </div>
    </div>
</body>
</html>
