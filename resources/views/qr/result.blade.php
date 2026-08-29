@extends('layouts.app')

@section('title', 'Attendance Status')

@section('content')
<style>
    .result-wrapper { 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        min-height: calc(100vh - 70px); 
        padding: 30px 20px; 
        background: radial-gradient(circle at top right, rgba(128, 0, 0, 0.08), transparent 40%),
                    radial-gradient(circle at bottom left, rgba(217, 119, 6, 0.06), transparent 40%),
                    #0f172a;
    }
    .result-card { 
        max-width: 440px; 
        width: 100%; 
        border-radius: 28px; 
        padding: 44px 32px; 
        background: rgba(30, 41, 59, 0.75); 
        backdrop-filter: blur(20px); 
        -webkit-backdrop-filter: blur(20px); 
        border: 1px solid rgba(255, 255, 255, 0.1); 
        box-shadow: 0 25px 70px rgba(0, 0, 0, 0.4); 
        text-align: center; 
        color: #f8fafc;
        animation: cardPop 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    @keyframes cardPop {
        from { opacity: 0; transform: scale(0.95) translateY(10px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .result-icon { 
        width: 88px; 
        height: 88px; 
        border-radius: 50%; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        margin: 0 auto 24px; 
        font-size: 2.5rem; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.25);
    }
    .result-title { 
        font-size: 1.5rem; 
        font-weight: 800; 
        color: #f8fafc; 
        margin-bottom: 8px; 
        letter-spacing: -0.02em;
    }
    .result-sub { 
        font-size: 0.92rem; 
        color: #94a3b8; 
        margin-bottom: 24px; 
        line-height: 1.6; 
    }
    .result-badge { 
        display: inline-flex; 
        align-items: center; 
        gap: 6px; 
        padding: 8px 20px; 
        border-radius: 99px; 
        font-size: 0.88rem; 
        font-weight: 700; 
        margin-bottom: 20px; 
    }
    .subject-box {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 16px;
        padding: 14px 18px;
        margin-bottom: 24px;
        font-size: 0.88rem;
        color: #cbd5e1;
    }
    .action-btn { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px; 
        width: 100%; 
        padding: 14px 24px; 
        background: linear-gradient(135deg, #800000, #991b1b); 
        color: white; 
        border-radius: 14px; 
        text-decoration: none; 
        font-weight: 700; 
        font-size: 0.95rem; 
        transition: all 0.25s ease; 
        box-shadow: 0 4px 16px rgba(128, 0, 0, 0.35); 
        border: none;
    }
    .action-btn:hover { 
        transform: translateY(-2px); 
        box-shadow: 0 8px 24px rgba(128, 0, 0, 0.5); 
        color: white; 
    }
    .secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 12px 24px;
        background: rgba(255, 255, 255, 0.06);
        color: #cbd5e1;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        margin-top: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .secondary-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
</style>

<div class="result-wrapper">
    <div class="result-card">
        @if($status === 'success')
            <div class="result-icon" style="background: rgba(16, 185, 129, 0.15); border: 2px solid rgba(16, 185, 129, 0.4);">
                <i class="bi bi-check2-circle" style="color: #34d399;"></i>
            </div>
            <div class="result-title">Attendance Recorded Successfully!</div>
            <div class="result-sub">{{ $message ?? 'Your attendance has been recorded for this class session.' }}</div>
            
            <div class="result-badge" style="background: {{ ($status_val ?? 'Present') === 'Present' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)' }}; color: {{ ($status_val ?? 'Present') === 'Present' ? '#34d399' : '#fbbf24' }}; border: 1px solid {{ ($status_val ?? 'Present') === 'Present' ? 'rgba(16, 185, 129, 0.3)' : 'rgba(245, 158, 11, 0.3)' }};">
                <i class="bi bi-patch-check-fill me-1"></i> {{ $status_val ?? 'Present' }} @if(!empty($time)) — {{ $time }} @endif
            </div>
            
            <div class="subject-box text-start" style="line-height: 1.8;">
                @if(!empty($subject))
                    <div><strong><i class="bi bi-journal-text me-1 text-warning"></i> Subject:</strong> {{ $subject }}</div>
                @endif
                @if(!empty($instructor))
                    <div><strong><i class="bi bi-person-badge me-1 text-warning"></i> Instructor:</strong> {{ $instructor }}</div>
                @endif
                @if(!empty($section))
                    <div><strong><i class="bi bi-people me-1 text-warning"></i> Section:</strong> {{ $section }}</div>
                @endif
                @if(!empty($date))
                    <div><strong><i class="bi bi-calendar-event me-1 text-warning"></i> Date:</strong> {{ $date }}</div>
                @endif
            </div>

        @elseif($status === 'already')
            <div class="result-icon" style="background: rgba(59, 130, 246, 0.15); border: 2px solid rgba(59, 130, 246, 0.4);">
                <i class="bi bi-info-circle-fill" style="color: #60a5fa;"></i>
            </div>
            <div class="result-title">Already Clocked In</div>
            <div class="result-sub">{{ $message ?? 'You have already recorded your attendance for this class today.' }}</div>
            
            @if(!empty($status_val))
            <div class="result-badge" style="background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3);">
                Status: {{ $status_val }} @if(!empty($time)) (at {{ $time }}) @endif
            </div>
            @endif

            <div class="subject-box text-start" style="line-height: 1.8;">
                @if(!empty($subject))
                    <div><strong><i class="bi bi-journal-text me-1 text-info"></i> Subject:</strong> {{ $subject }}</div>
                @endif
                @if(!empty($instructor))
                    <div><strong><i class="bi bi-person-badge me-1 text-info"></i> Instructor:</strong> {{ $instructor }}</div>
                @endif
                @if(!empty($section))
                    <div><strong><i class="bi bi-people me-1 text-info"></i> Section:</strong> {{ $section }}</div>
                @endif
            </div>

        @elseif($status === 'outside_classroom')
            <div class="result-icon" style="background: rgba(239, 68, 68, 0.15); border: 2px solid rgba(239, 68, 68, 0.4);">
                <i class="bi bi-geo-slash-fill" style="color: #f87171;"></i>
            </div>
            <div class="result-title">Failed to Scan</div>
            <div class="result-sub">{{ $message ?? 'You are outside of the classroom range. Attendance can only be marked while physically present inside the classroom.' }}</div>
            
            <div class="subject-box" style="border-color: rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.05);">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i> Proximity verification required within classroom boundaries.
            </div>

        @elseif($status === 'expired')
            <div class="result-icon" style="background: rgba(245, 158, 11, 0.15); border: 2px solid rgba(245, 158, 11, 0.4);">
                <i class="bi bi-clock-history" style="color: #fbbf24;"></i>
            </div>
            <div class="result-title">QR Code Expired</div>
            <div class="result-sub">{{ $message ?? 'This dynamic QR code has expired. Please scan the newly refreshed QR code from the teacher.' }}</div>

        @elseif($status === 'closed')
            <div class="result-icon" style="background: rgba(239, 68, 68, 0.15); border: 2px solid rgba(239, 68, 68, 0.4);">
                <i class="bi bi-lock-fill" style="color: #f87171;"></i>
            </div>
            <div class="result-title">Session Closed</div>
            <div class="result-sub">{{ $message ?? 'The attendance window for this class session has ended.' }}</div>

        @else
            <div class="result-icon" style="background: rgba(239, 68, 68, 0.15); border: 2px solid rgba(239, 68, 68, 0.4);">
                <i class="bi bi-x-circle-fill" style="color: #f87171;"></i>
            </div>
            <div class="result-title">Attendance Notice</div>
            <div class="result-sub">{{ $message ?? 'Unable to process QR attendance.' }}</div>
        @endif

        <a href="{{ route('home') }}" class="action-btn">
            <i class="bi bi-house-fill"></i> Go to Dashboard
        </a>

        @if(in_array($status, ['outside_classroom', 'expired']))
            <a href="javascript:history.back()" class="secondary-btn">
                <i class="bi bi-arrow-left"></i> Try Again
            </a>
        @endif
    </div>
</div>
@endsection
