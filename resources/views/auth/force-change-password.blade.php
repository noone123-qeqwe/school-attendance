@extends('layouts.app')
@section('page-title', 'Update Password Required')

@section('content')
@if(Auth::user()?->isStudent())
<div style="max-width:460px;margin:28px auto;">
    <section class="sf-card" style="padding:26px 24px;">
        <div style="text-align:center;margin-bottom:20px;">
            <div class="sf-bio-icon is-rose" style="width:52px;height:52px;margin:0 auto 14px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <h1 style="margin:0 0 6px;font-size:19px;font-weight:700;color:var(--sf-text);letter-spacing:-0.02em;">Update Your Password</h1>
            <p style="margin:0;color:var(--sf-text-secondary);font-size:12.5px;line-height:1.5;">
                Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.
            </p>
        </div>

        @if($errors->any())
            <div class="sf-flash is-error" role="alert" data-alert-required style="flex-direction:column;align-items:flex-start;gap:4px;">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.change.submit') }}" style="display:flex;flex-direction:column;gap:14px;">
            @csrf

            <div>
                <label for="password" class="sf-label">New Password</label>
                <div style="position:relative;">
                    <input type="password"
                           name="password"
                           id="password"
                           class="sf-input"
                           style="padding-right:44px;min-height:42px;"
                           placeholder="At least 12 characters"
                           autocomplete="new-password"
                           required
                           minlength="12">
                    <button type="button"
                            class="sf-icon-btn"
                            data-sf-toggle-pw="password"
                            aria-controls="password"
                            aria-label="Show password"
                            title="Show password"
                            aria-pressed="false"
                            style="position:absolute;right:5px;top:50%;transform:translateY(-50%);border:none;background:transparent;">
                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <div>
                <label for="password_confirmation" class="sf-label">Confirm New Password</label>
                <div style="position:relative;">
                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           class="sf-input"
                           style="padding-right:44px;min-height:42px;"
                           placeholder="Repeat new password"
                           autocomplete="new-password"
                           required
                           minlength="12">
                    <button type="button"
                            class="sf-icon-btn"
                            data-sf-toggle-pw="password_confirmation"
                            aria-controls="password_confirmation"
                            aria-label="Show password confirmation"
                            title="Show password confirmation"
                            aria-pressed="false"
                            style="position:absolute;right:5px;top:50%;transform:translateY(-50%);border:none;background:transparent;">
                        <i class="bi bi-eye-slash" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="sf-btn sf-btn-primary sf-btn-lg sf-btn-block" style="margin-top:4px;">
                Update Password
            </button>
        </form>
    </section>
</div>

<script @cspNonce>
(function () {
    document.querySelectorAll('[data-sf-toggle-pw]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(btn.getAttribute('data-sf-toggle-pw'));
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            var icon = btn.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye', show);
                icon.classList.toggle('bi-eye-slash', !show);
            }
        });
    });
})();
</script>
@else
<div class="row justify-content-center my-4 my-md-5">
    <div class="col-md-7 col-lg-5">
        <div class="card" style="border:1px solid rgba(212,175,55,0.2); border-top:2px solid #7A1A1A; background:#1E1515; border-radius:14px; overflow:hidden;">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <div style="width:56px;height:56px;border-radius:12px;background:#261B1B;border:1px solid rgba(212,175,55,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="bi bi-shield-lock" style="font-size:24px;color:#D4AF37;" aria-hidden="true"></i>
                    </div>
                    <h4 style="font-weight:700;color:#FAF8F5;letter-spacing:-0.02em;">Update Your Password</h4>
                    <p style="color:#D8CEC8;font-size:0.88rem;line-height:1.5;margin-bottom:0;">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.</p>
                </div>

                @if($errors->any())
                <div class="alert alert-danger" role="alert" data-alert-required style="background:rgba(153,27,45,0.18);border:1px solid rgba(248,113,113,0.38);color:#FFE4E8;border-radius:10px;font-size:0.86rem;">
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
                        <label for="password" style="color:#ACA099;">New Password</label>
                        <button type="button" class="eye-toggle" onclick="togglePassword('password', this, event)" data-toggle-password="password" aria-controls="password" aria-label="Show password" title="Show password" aria-pressed="false" style="min-width:44px;min-height:44px;">
                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="form-floating-custom mb-4" style="position: relative;">
                        <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " required minlength="12" class="form-control" style="background:#141010; border:1px solid rgba(255,255,255,0.14); color:#FAF8F5; min-height:48px; border-radius:10px; padding-right: 46px;">
                        <label for="password_confirmation" style="color:#ACA099;">Confirm New Password</label>
                        <button type="button" class="eye-toggle" onclick="togglePassword('password_confirmation', this, event)" data-toggle-password="password_confirmation" aria-controls="password_confirmation" aria-label="Show password confirmation" title="Show password confirmation" aria-pressed="false" style="min-width:44px;min-height:44px;">
                            <i class="bi bi-eye-slash" aria-hidden="true"></i>
                        </button>
                    </div>

                    <button type="submit" class="btn w-100" style="background:#FAF8F5; color:#7A1A1A; border:1px solid #FAF8F5; min-height:46px; padding:11px 16px; border-radius:10px; font-weight:700;">
                        Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
