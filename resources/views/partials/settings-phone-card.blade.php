<div class="sc {{ $user->isStudent() ? 'student-account-edit-card' : '' }}">
    <div class="sc-head">
        <div class="sc-icon" style="background:rgba(207,164,111,0.14);color:#cfa46f;"><i class="bi bi-person-lines-fill"></i></div>
        <div>
            <div class="sc-title">{{ $user->isStudent() ? 'Phone number' : 'Personal Information' }}</div>
            <div class="sc-sub">{{ $user->isStudent() ? 'Add or update the phone number listed on your school account.' : 'Update your contact details and emergency recovery info' }}</div>
        </div>
    </div>
    <div class="sc-body">
        <form action="{{ route('settings.update') }}" method="POST" id="personalInfoForm">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <label class="sl" for="phoneInput">Phone number</label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-telephone-fill input-icon-prefix"></i>
                        <input type="tel"
                               id="phoneInput"
                               name="phone"
                               class="si with-icon"
                               style="padding-left: 44px !important;"
                               value="{{ old('phone', Auth::user()->phone) }}"
                               placeholder="+63 900 000 0000"
                               inputmode="tel"
                               autocomplete="tel"
                               maxlength="20"
                               pattern="[0-9+\s\-]{0,20}"
                               aria-describedby="phoneInputHelp">
                    </div>
                    <div id="phoneInputHelp" class="account-edit-help">
                        <i class="bi bi-shield-check text-warning"></i>
                        Include your country code if needed. Use digits, spaces, +, or hyphens; leave it blank to remove the saved number.
                    </div>
                </div>
            </div>
            @if($user->isStudent())
                <div id="phoneFormStatus" class="account-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>
            @endif
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="submit" id="savePhoneBtn" class="sbtn btn-gold" style="width:auto;padding:11px 28px;">
                    <i class="bi bi-save me-2"></i>{{ $user->isStudent() ? 'Save phone number' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</div>
