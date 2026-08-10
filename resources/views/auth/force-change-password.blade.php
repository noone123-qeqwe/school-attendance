@extends('layouts.app')
@section('page-title', 'Update Password Required')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card glass-card shadow-lg" style="border:1px solid rgba(255,255,255,0.1); background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border-radius: 20px; overflow: hidden;">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div style="width:60px;height:60px;border-radius:50%;background:rgba(99,102,241,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="bi bi-shield-lock text-primary" style="font-size:24px;"></i>
                    </div>
                    <h4 style="font-weight:700;color:#fff;">Update Your Password</h4>
                    <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;">For security reasons, you must change your temporary password before continuing.</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger" style="background:rgba(220,38,38,0.1);border:1px solid rgba(220,38,38,0.2);color:#fca5a5;border-radius:12px;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('password.change.submit') }}">
                    @csrf
                    
                    <div class="form-floating-custom mb-3">
                        <input type="password" name="password" id="password" placeholder=" " required minlength="8" class="form-control" style="background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <label for="password" style="color:rgba(255,255,255,0.5);">New Password</label>
                    </div>

                    <div class="form-floating-custom mb-4">
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " required minlength="8" class="form-control" style="background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); color:#fff;">
                        <label for="password_confirmation" style="color:rgba(255,255,255,0.5);">Confirm New Password</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); border: none; padding: 12px; border-radius: 12px; font-weight: 600;">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
