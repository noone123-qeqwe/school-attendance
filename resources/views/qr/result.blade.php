@extends('layouts.app')

@section('title', 'Attendance Status')

@section('content')
<style>
    .result-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 120px);
        padding: 24px 16px;
        background: transparent;
    }
    .result-card {
        max-width: 460px;
        width: 100%;
        border-radius: 16px;
        padding: 34px 28px;
        background: #14080C;
        border: 1px solid rgba(250, 247, 248, 0.12);
        border-top: 2px solid #6B192B;
        text-align: center;
        color: #FAF7F8;
    }
    .result-icon {
        width: 68px;
        height: 68px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2rem;
    }
    .result-title {
        font-size: 1.38rem;
        font-weight: 700;
        color: #FAF7F8;
        margin-bottom: 8px;
        letter-spacing: -0.02em;
    }
    .result-sub {
        font-size: 0.88rem;
        color: #D4C6CA;
        margin-bottom: 20px;
        line-height: 1.55;
    }
    .result-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 700;
        margin-bottom: 20px;
        font-variant-numeric: tabular-nums;
    }
    .subject-box {
        background: #0B0406;
        border: 1px solid rgba(250, 247, 248, 0.08);
        border-radius: 12px;
        padding: 14px 16px;
        margin-bottom: 22px;
        font-size: 0.85rem;
        color: #D4C6CA;
    }
    .subject-box strong {
        color: #FAF7F8;
    }
    .subject-box i {
        color: #E898A8 !important;
    }
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        min-height: 46px;
        padding: 12px 20px;
        background: #FAF7F8;
        color: #14080C;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        transition: background 0.15s ease;
        border: 1px solid #FAF7F8;
    }
    .action-btn:hover {
        background: #FFFFFF;
        color: #0B0406;
    }
    .secondary-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        min-height: 44px;
        padding: 11px 20px;
        background: #1C0C11;
        color: #FAF7F8;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.88rem;
        transition: background 0.15s ease, border-color 0.15s ease;
        margin-top: 10px;
        border: 1px solid rgba(250, 247, 248, 0.16);
    }
    .secondary-btn:hover {
        background: #261118;
        border-color: rgba(250, 247, 248, 0.28);
        color: #FFFFFF;
    }
    @media (max-width: 480px) {
        .result-card {
            padding: 26px 20px;
        }
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
