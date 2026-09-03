@extends('layouts.app')

@section('portal-title', 'Page Not Found (404)')

@section('content')
<div class="d-flex align-items-center justify-content-center" style="min-height: 65vh; padding: 20px;">
    <div style="max-width: 480px; width: 100%; text-align: center; background: linear-gradient(145deg, rgba(32,20,15,0.85) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 20px; padding: 40px 30px; box-shadow: 0 12px 36px rgba(0,0,0,0.5); backdrop-filter: blur(12px);">
        <div style="width: 70px; height: 70px; border-radius: 50%; background: rgba(207,164,111,0.15); border: 1px solid rgba(207,164,111,0.3); color: #cfa46f; display: flex; align-items: center; justify-content: center; font-size: 2rem; margin: 0 auto 20px;">
            <i class="bi bi-compass"></i>
        </div>
        
        <h2 style="color: #f3e7cd; font-weight: 800; font-size: 1.6rem; margin-bottom: 10px;">Page Not Found</h2>
        <p style="color: #b39b82; font-size: 0.92rem; line-height: 1.5; margin-bottom: 25px;">
            The page or record you are looking for does not exist, has been removed, or the link may be broken.
        </p>

        <div class="d-flex flex-column gap-2">
            @auth
                @if(Auth::user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="bi bi-grid-fill"></i> Back to Dashboard
                    </a>
                @elseif(Auth::user()->isTeacher())
                    <a href="{{ route('teacher.dashboard') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="bi bi-grid-fill"></i> Back to Dashboard
                    </a>
                @elseif(Auth::user()->isParent())
                    <a href="{{ route('parent.dashboard') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="bi bi-grid-fill"></i> Back to Dashboard
                    </a>
                @else
                    <a href="{{ route('home') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="bi bi-house-door-fill"></i> Back to Home
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="btn" style="background: linear-gradient(135deg, #cfa46f 0%, #8f6e4a 100%); color: #ffffff; font-weight: 700; padding: 12px 24px; border-radius: 12px; border: none; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="bi bi-box-arrow-in-right"></i> Sign In
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection
