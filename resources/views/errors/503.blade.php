@extends('layouts.app')

@section('portal-title', 'Service Unavailable (503)')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 65vh; padding: 20px;">
    <div style="max-width: 480px; width: 100%; text-align: center; background: linear-gradient(145deg, rgba(32,20,15,0.85) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 20px; padding: 40px 30px; box-shadow: 0 12px 36px rgba(0,0,0,0.5); backdrop-filter: blur(12px);">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
            <i class="bi bi-tools"></i>
        </div>
        
        <h2 style="color: #f3e7cd; font-weight: 800; font-size: 1.6rem; margin-bottom: 10px;">Under Scheduled Maintenance</h2>
        <p style="color: #b39b82; font-size: 0.92rem; line-height: 1.5; margin-bottom: 25px;">
            The Smart Attendance System is temporarily performing database optimization and upgrades. We will be back online shortly.
        </p>

        <button onclick="window.location.reload()" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
            <i class="bi bi-arrow-clockwise"></i> Check Again
        </button>
    </div>
</div>
@endsection
