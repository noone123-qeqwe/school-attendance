@extends('layouts.app')

@section('portal-title', 'Server Error (500)')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 65vh; padding: 20px;">
    <div style="max-width: 480px; width: 100%; text-align: center; background: linear-gradient(145deg, rgba(32,20,15,0.85) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(239,68,68,0.3); border-radius: 20px; padding: 40px 30px; box-shadow: 0 12px 36px rgba(0,0,0,0.5); backdrop-filter: blur(12px);">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #f87171; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        
        <h2 style="color: #f3e7cd; font-weight: 800; font-size: 1.6rem; margin-bottom: 10px;">Something Went Wrong</h2>
        <p style="color: #b39b82; font-size: 0.92rem; line-height: 1.5; margin-bottom: 25px;">
            Our servers encountered an unexpected issue while processing your request. The technical team has been notified.
        </p>

        <div class="d-flex flex-column gap-2">
            <button onclick="window.location.reload()" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-arrow-clockwise"></i> Try Again
            </button>
            <a href="/" class="btn" style="background: rgba(255,255,255,0.06); color: #f3e7cd; font-weight: 600; padding: 10px 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.12); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-house-door"></i> Back to Homepage
            </a>
        </div>
    </div>
</div>
@endsection
