@extends('layouts.app')
@section('page-title', 'Update Password Required')

@section('content')
<div class="row justify-content-center my-4 my-md-5">
    <div class="col-md-7 col-lg-5">
        <div class="card" style="border:1px solid rgba(212,175,55,0.2); border-top:2px solid #7A1A1A; background:#1E1515; border-radius:14px; overflow:hidden;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div style="width:56px;height:56px;border-radius:12px;background:#261B1B;border:1px solid rgba(212,175,55,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="bi bi-shield-lock" style="font-size:24px;color:#D4AF37;" aria-hidden="true"></i>
                    </div>
                    <h4 style="font-weight:700;color:#FAF8F5;letter-spacing:-0.02em;">Update Your Password</h4>
                    <p style="color:#D6CCC6;font-size:0.88rem;line-height:1.5;margin-bottom:0;">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger" role="alert" style="background:rgba(153,27,45,0.18);border:1px solid rgba(251,113,133,0.34);border-left:3px solid #FB7185;color:#FFE4E8;border-radius:10px;font-size:0.86rem;">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('password.change.submit') }}">
                    @csrf
                    
                    <div class="form-floating-custom mb-3" style="position: relative;">
                        <input type="password" name="password" id="password" placeholder=" " required minlength="12" class="form-control" style="background:#141010; border:1px solid rgba(255,255,255,0.14); color:#FAF8F5; min-height:48px; border-radius:10px; padding-right: 46px;">
                        <label for="password" style="color:#A49890;">New Password</label>
                        <button type="button" class="eye-toggle" onclick="togglePassword('password', this, event)" data-toggle-password="password" aria-controls="password" aria-label="Show password" title="Show password" aria-pressed="false" style="min-width:44px;min-height:44px;">
                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="form-floating-custom mb-4" style="position: relative;">
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " required minlength="12" class="form-control" style="background:#141010; border:1px solid rgba(255,255,255,0.14); color:#FAF8F5; min-height:48px; border-radius:10px; padding-right: 46px;">
                        <label for="password_confirmation" style="color:#A49890;">Confirm New Password</label>
                        <button type="button" class="eye-toggle" onclick="togglePassword('password_confirmation', this, event)" data-toggle-password="password_confirmation" aria-controls="password_confirmation" aria-label="Show password confirmation" title="Show password confirmation" aria-pressed="false" style="min-width:44px;min-height:44px;">
                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button type="submit" class="btn w-100" style="background:#FAF8F5; color:#800000; border:1px solid #FAF8F5; min-height:46px; padding:11px 16px; border-radius:10px; font-weight:700;">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
