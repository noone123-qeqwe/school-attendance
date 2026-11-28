@extends('layouts.app')

@section('title', 'Attendance Status')

@section('content')
<div style="max-width:460px;margin:32px auto;">
    <section class="sf-card" style="padding:28px 24px;text-align:center;">
        @if($status === 'success')
            <div class="sf-bio-icon is-emerald" style="width:58px;height:58px;margin:0 auto 16px;font-size:26px;">
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
            </div>
            <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:var(--sf-text);">Attendance Recorded</h1>
            <p style="margin:0 0 16px;font-size:13px;color:var(--sf-text-secondary);">{{ $message ?? 'Your attendance has been recorded for this class session.' }}</p>
            <div style="margin-bottom:16px;">
                <span class="sf-pill {{ ($status_val ?? 'Present') === 'Present' ? 'is-emerald' : 'is-amber' }}">
                    {{ $status_val ?? 'Present' }} @if(!empty($time)) · {{ $time }} @endif
                </span>
            </div>
            <div style="text-align:left;padding:12px 14px;border-radius:11px;background:var(--sf-surface-2);border:1px solid var(--sf-border);margin-bottom:18px;font-size:12.5px;display:flex;flex-direction:column;gap:6px;">
                @if(!empty($subject))<div><span style="color:var(--sf-text-muted);">Subject:</span> <strong style="color:var(--sf-text);">{{ $subject }}</strong></div>@endif
                @if(!empty($instructor))<div><span style="color:var(--sf-text-muted);">Instructor:</span> <span style="color:var(--sf-text-soft);">{{ $instructor }}</span></div>@endif
                @if(!empty($section))<div><span style="color:var(--sf-text-muted);">Section:</span> <span style="color:var(--sf-text-soft);">{{ $section }}</span></div>@endif
                @if(!empty($date))<div><span style="color:var(--sf-text-muted);">Date:</span> <span class="sf-mono" style="color:var(--sf-text-soft);">{{ $date }}</span></div>@endif
            </div>
            <script @cspNonce>document.addEventListener('DOMContentLoaded', () => window.sfConfettiBurst?.());</script>
        @elseif($status === 'already')
            <div class="sf-bio-icon" style="width:58px;height:58px;margin:0 auto 16px;font-size:26px;background:var(--sf-sky-bg);border:1px solid var(--sf-sky-border);color:var(--sf-sky);">
                <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
            </div>
            <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:var(--sf-text);">Already Checked In</h1>
            <p style="margin:0 0 16px;font-size:13px;color:var(--sf-text-secondary);">{{ $message ?? 'You have already recorded attendance for this class today.' }}</p>
            @if(!empty($status_val))
                <div style="margin-bottom:16px;"><span class="sf-pill is-sky">{{ $status_val }} @if(!empty($time)) · {{ $time }} @endif</span></div>
            @endif
        @else
            <div class="sf-bio-icon" style="width:58px;height:58px;margin:0 auto 16px;font-size:26px;background:var(--sf-danger-bg);border:1px solid var(--sf-danger-border);color:var(--sf-danger);">
                <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i>
            </div>
            <h1 style="margin:0 0 6px;font-size:20px;font-weight:700;color:var(--sf-text);">
                {{ match ($status) {
                    'outside_classroom' => 'Outside Classroom Range',
                    'expired' => 'QR Code Expired',
                    'closed' => 'Session Closed',
                    default => 'Attendance Notice',
                } }}
            </h1>
            <p style="margin:0 0 18px;font-size:13px;color:var(--sf-text-secondary);line-height:1.55;">
                {{ $message ?? 'Unable to process QR attendance.' }}
            </p>
        @endif

        <div style="display:flex;flex-direction:column;gap:8px;">
            <a href="{{ route('home') }}" class="sf-btn sf-btn-primary sf-btn-lg sf-btn-block">Go to Dashboard</a>
            @if(in_array($status, ['outside_classroom', 'expired']))
                <a href="{{ route('mobile.scan') }}" class="sf-btn sf-btn-secondary sf-btn-block">Try Again</a>
            @endif
        </div>
    </section>
</div>
@endsection
