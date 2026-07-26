@extends('layouts.app')

@section('content')
<style>
    .result-wrapper { display:flex;align-items:center;justify-content:center;min-height:calc(100vh - 64px);padding:40px 20px;background:linear-gradient(135deg,#f8f0f0 0%,#f1f5f9 50%,#f0f4ff 100%); }
    .result-card { max-width:380px;width:100%;border-radius:24px;padding:44px 36px;background:white;box-shadow:0 20px 60px rgba(0,0,0,0.1);text-align:center; }
    .result-icon { width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem; }
    .result-title { font-size:1.4rem;font-weight:800;color:#1e293b;margin-bottom:6px; }
    .result-sub { font-size:.875rem;color:#64748b;margin-bottom:24px;line-height:1.5; }
    .result-badge { display:inline-block;padding:6px 18px;border-radius:99px;font-size:.85rem;font-weight:700;margin-bottom:20px; }
    .home-btn { display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#800000,#a00000);color:white;border-radius:12px;text-decoration:none;font-weight:700;font-size:.9rem;transition:all .2s;box-shadow:0 4px 14px rgba(128,0,0,.25); }
    .home-btn:hover { transform:translateY(-2px);box-shadow:0 8px 22px rgba(128,0,0,.35);color:white; }
</style>

<div class="result-wrapper">
    <div class="result-card">
        @if($status === 'success')
            <div class="result-icon" style="background:#f0fdf4;">
                <i class="bi bi-check2-circle" style="color:#16a34a;"></i>
            </div>
            <div class="result-title">Clocked In!</div>
            <div class="result-sub">{{ $message }}</div>
            <div class="result-badge" style="background:{{ $status_val==='Present'?'#f0fdf4':'#fffbeb' }};color:{{ $status_val==='Present'?'#16a34a':'#d97706' }};border:1px solid {{ $status_val==='Present'?'#bbf7d0':'#fde68a' }};">
                {{ $status_val }} — {{ $time }}
            </div>
            <div style="font-size:.82rem;color:#94a3b8;margin-bottom:20px;">{{ $subject }}</div>

        @elseif($status === 'already')
            <div class="result-icon" style="background:#eff6ff;">
                <i class="bi bi-info-circle-fill" style="color:#2563eb;"></i>
            </div>
            <div class="result-title">Already Clocked In</div>
            <div class="result-sub">{{ $message }}</div>
            <div class="result-badge" style="background:{{ $status_val==='Present'?'#f0fdf4':'#fffbeb' }};color:{{ $status_val==='Present'?'#16a34a':'#d97706' }};border:1px solid {{ $status_val==='Present'?'#bbf7d0':'#fde68a' }};">
                {{ $status_val }}
            </div>
            <div style="font-size:.82rem;color:#94a3b8;margin-bottom:20px;">{{ $subject }}</div>

        @elseif($status === 'expired')
            <div class="result-icon" style="background:#fffbeb;">
                <i class="bi bi-clock-history" style="color:#d97706;"></i>
            </div>
            <div class="result-title">QR Code Expired</div>
            <div class="result-sub">{{ $message }}</div>

        @elseif($status === 'closed')
            <div class="result-icon" style="background:#fef2f2;">
                <i class="bi bi-lock-fill" style="color:#dc2626;"></i>
            </div>
            <div class="result-title">Session Closed</div>
            <div class="result-sub">{{ $message }}</div>

        @else
            <div class="result-icon" style="background:#fef2f2;">
                <i class="bi bi-x-circle-fill" style="color:#dc2626;"></i>
            </div>
            <div class="result-title">Invalid QR Code</div>
            <div class="result-sub">{{ $message }}</div>
        @endif

        <a href="{{ route('home') }}" class="home-btn">
            <i class="bi bi-house-fill"></i> Go to Dashboard
        </a>
    </div>
</div>
@endsection
