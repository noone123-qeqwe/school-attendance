@extends('layouts.app')

@section('portal-title', 'Page Expired (419)')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 65vh;">
    <div style="max-width: 480px; width: 100%; text-align: center; background: linear-gradient(145deg, rgba(32,20,15,0.85) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 20px; padding: 40px 30px; box-shadow: 0 12px 36px rgba(0,0,0,0.5); backdrop-filter: blur(12px);">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        
        <h2 style="color: #f3e7cd; font-weight: 800; font-size: 1.6rem; margin-bottom: 10px;">Session Expired</h2>
        <p style="color: #b39b82; font-size: 0.92rem; line-height: 1.5; margin-bottom: 25px;">
            For security, your session or security token has expired. Please refresh the page to continue.
        </p>

        <div class="d-flex flex-column gap-2">
            <a href="{{ url()->previous() ?: route('login') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <i class="bi bi-arrow-clockwise"></i> Refresh & Return
            </a>
            
            <a href="{{ route('login') }}" class="btn" style="background: rgba(207,164,111,0.1); color: #cfa46f; font-weight: 600; padding: 10px 20px; border-radius: 12px; border: 1px solid rgba(207,164,111,0.25); text-decoration: none;">
                Go to Sign In
            </a>
        </div>
    </div>
</div>
@endsection
