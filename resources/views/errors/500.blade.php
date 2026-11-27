@extends('layouts.app')

@section('portal-title', 'Server Error (500)')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 65vh; padding: 20px;">
    <div style="max-width: 480px; width: 100%; text-align: center; background: #1E1515; border: 1px solid rgba(255,255,255,0.14); border-top: 2px solid #7A1A1A; border-radius: 16px; padding: 36px 28px;">
        <div style="width: 56px; height: 56px; border-radius: 12px; background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.3); color: #F87171; display: flex; align-items: center; justify-content: center; font-size: 1.6rem; margin: 0 auto 18px;">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
        </div>
        
        <h2 style="color: #FAF8F5; font-weight: 800; font-size: 1.45rem; letter-spacing: -0.02em; margin-bottom: 10px;">Something Went Wrong</h2>
        <p style="color: #ACA099; font-size: 0.9rem; line-height: 1.55; margin-bottom: 24px;">
            Our servers encountered an unexpected issue while processing your request. The technical team has been notified.
        </p>

        <div class="d-flex flex-column gap-2">
            <button onclick="window.location.reload()" class="btn" style="background: #FAF8F5; color: #7A1A1A; font-weight: 750; min-height: 44px; padding: 11px 22px; border-radius: 10px; border: 1px solid #FAF8F5; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Try Again
            </button>
            <a href="/" class="btn" style="background: #261B1B; color: #FAF8F5; font-weight: 650; min-height: 44px; padding: 10px 20px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.14); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-house-door" aria-hidden="true"></i> Back to Homepage
            </a>
        </div>
    </div>
</div>
@endsection
