@extends('layouts.app')
@section('page-title', 'Link Student Account')

@section('content')
<style>
.link-wizard-container {
    max-width: 620px;
    margin: 20px auto 40px;
}
.link-wizard-card {
    background: linear-gradient(145deg, rgba(34, 20, 14, 0.85) 0%, rgba(18, 9, 6, 0.95) 100%);
    border: 1px solid rgba(207, 164, 111, 0.25);
    border-radius: 24px;
    padding: 36px 32px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(16px);
    position: relative;
    overflow: hidden;
}
.link-wizard-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #cfa46f 0%, #f59e0b 50%, #8f6e4a 100%);
}
.link-wizard-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: rgba(207, 164, 111, 0.12);
    border: 2px solid rgba(207, 164, 111, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 18px;
    color: var(--gold);
    font-size: 1.8rem;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3);
}
.wizard-step-tracker {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 28px;
}
.step-dot {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #8f826f;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.step-dot.active {
    color: #f3e7cd;
}
.step-dot.active .dot-num {
    background: var(--gold);
    color: #140d07;
    border-color: var(--gold);
}
.dot-num {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 1px solid rgba(207, 164, 111, 0.3);
    background: rgba(255, 255, 255, 0.05);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
}
.step-line {
    width: 36px;
    height: 1px;
    background: rgba(207, 164, 111, 0.2);
}

.ent-glass-input {
    width: 100%;
    background: rgba(14, 8, 5, 0.6) !important;
    border: 1px solid rgba(207, 164, 111, 0.25) !important;
    color: #ffffff !important;
    padding: 14px 18px;
    border-radius: 14px;
    outline: none;
    transition: all 0.25s ease;
    font-size: 1rem;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.3);
}
.ent-glass-input:focus {
    border-color: var(--gold) !important;
    background: rgba(20, 11, 7, 0.8) !important;
    box-shadow: 0 0 0 4px rgba(207, 164, 111, 0.2), inset 0 2px 4px rgba(0,0,0,0.2) !important;
}
.ent-glass-input::placeholder {
    color: rgba(255, 255, 255, 0.3);
}
.security-note-box {
    background: rgba(207, 164, 111, 0.06);
    border: 1px solid rgba(207, 164, 111, 0.15);
    border-radius: 14px;
    padding: 14px 16px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

@media (max-width: 768px) {
    .link-wizard-container {
        padding: 0 12px;
        margin: 10px auto 30px;
    }
    .link-wizard-card {
        padding: 24px 20px;
        border-radius: 18px;
    }
}
</style>

<div class="link-wizard-container">
    {{-- Back to Dashboard Link --}}
    <a href="{{ route('parent.dashboard') }}" class="student-back-link mb-3 d-inline-flex align-items-center gap-2" style="color: #b39b82; text-decoration: none; font-size: 0.88rem; font-weight: 600;">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    <div class="link-wizard-card">
        <div class="link-wizard-icon">
            <i class="bi bi-person-check-fill"></i>
        </div>

        <div class="text-center mb-4">
            <h2 style="color: #ffffff; font-weight: 800; font-size: clamp(1.3rem, 3vw, 1.7rem); margin: 0 0 6px 0; letter-spacing: -0.5px;">
                Link Student Account
            </h2>
            <p style="color: #b39b82; font-size: 0.9rem; margin: 0;">
                Connect to your child's profile to receive verified attendance records
            </p>
        </div>

        <!-- Step Indicator -->
        <div class="wizard-step-tracker">
            <div class="step-dot active" id="dot1">
                <span class="dot-num">1</span>
                <span>Student ID</span>
            </div>
            <div class="step-line"></div>
            <div class="step-dot" id="dot2">
                <span class="dot-num">2</span>
                <span>OTP Verification</span>
            </div>
        </div>

        <!-- STEP 1: ENTER STUDENT NUMBER -->
        <div id="step1">
            <div class="security-note-box">
                <i class="bi bi-shield-lock-fill text-gold" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                <div style="font-size: 0.84rem; color: #e6dbce; line-height: 1.5;">
                    Enter your child's registered <strong>Student ID</strong> (e.g. 20260001). A 6-digit security code will be sent to their official school email for authorization.
                </div>
            </div>

            <div class="mb-4">
                <label style="color: #cfa46f; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">
                    Student ID / Number
                </label>
                <input type="text" id="student_number" class="ent-glass-input" placeholder="e.g. 20260001" maxlength="50" autocomplete="off">
            </div>

            <button type="button" id="btn-send-otp" class="btn-modern-gold w-100 justify-content-center" style="padding: 14px; font-size: 1rem;">
                Send OTP Verification Code <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>

        <!-- STEP 2: ENTER OTP -->
        <div id="step2" style="display: none; animation: cardFadeIn 0.3s ease;">
            <div class="security-note-box" style="background: rgba(52, 211, 153, 0.08); border-color: rgba(52, 211, 153, 0.25);">
                <i class="bi bi-envelope-check-fill" style="color: #34d399; font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                <div style="font-size: 0.84rem; color: #e6dbce; line-height: 1.5;">
                    Verification code sent! Ask your child for the 6-digit code received on their school inbox.
                </div>
            </div>

            <div class="mb-4">
                <label style="color: #cfa46f; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block; text-align: center;">
                    6-Digit Security Code
                </label>
                <input type="text" id="otp_code" class="ent-glass-input" placeholder="000000" maxlength="6" autocomplete="off"
                    style="text-align: center; font-size: 2rem; letter-spacing: 14px; font-weight: 800; padding: 16px; font-family: monospace;">
            </div>

            <div class="d-flex gap-3">
                <button type="button" id="btn-back" class="parent-btn-action btn-action-ghost flex-fill justify-content-center" style="padding: 14px; font-size: 0.95rem;">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </button>
                <button type="button" id="btn-verify-otp" class="btn-modern-gold flex-fill justify-content-center" style="padding: 14px; font-size: 0.95rem;">
                    Confirm &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>
                </button>
            </div>
        </div>

        <!-- Status Message Banner -->
        <div id="status-message" style="margin-top: 20px; display: none; padding: 14px 18px; border-radius: 12px; font-size: 0.88rem; text-align: center;"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const dot1 = document.getElementById('dot1');
    const dot2 = document.getElementById('dot2');
    const btnSendOtp = document.getElementById('btn-send-otp');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const btnBack = document.getElementById('btn-back');
    const statusMsg = document.getElementById('status-message');

    function showStatus(message, isError) {
        statusMsg.style.display = 'block';
        statusMsg.innerHTML = message;
        if(isError) {
            statusMsg.style.backgroundColor = 'rgba(239, 68, 68, 0.15)';
            statusMsg.style.color = '#f87171';
            statusMsg.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        } else {
            statusMsg.style.backgroundColor = 'rgba(52, 211, 153, 0.15)';
            statusMsg.style.color = '#34d399';
            statusMsg.style.border = '1px solid rgba(52, 211, 153, 0.3)';
        }
    }

    btnSendOtp.addEventListener('click', async () => {
        const studentNumber = document.getElementById('student_number').value.trim();
        if(!studentNumber || studentNumber.length < 3) {
            showStatus('Please enter a valid student ID.', true);
            return;
        }

        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Sending...';
        statusMsg.style.display = 'none';

        try {
            const response = await fetch('{{ route("parent.link.send-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ student_number: studentNumber })
            });

            const data = await response.json();
            
            if(data.success) {
                showStatus(data.message, false);
                step1.style.display = 'none';
                step2.style.display = 'block';
                dot1.classList.remove('active');
                dot2.classList.add('active');
                document.getElementById('otp_code').focus();
            } else {
                showStatus(data.message || 'Failed to send OTP.', true);
            }
        } catch (error) {
            showStatus('An unexpected error occurred.', true);
        }

        btnSendOtp.disabled = false;
        btnSendOtp.innerHTML = 'Send OTP Verification Code <i class="bi bi-arrow-right ms-2"></i>';
    });

    btnBack.addEventListener('click', () => {
        step2.style.display = 'none';
        step1.style.display = 'block';
        dot2.classList.remove('active');
        dot1.classList.add('active');
        statusMsg.style.display = 'none';
    });

    btnVerifyOtp.addEventListener('click', async () => {
        const studentNumber = document.getElementById('student_number').value.trim();
        const otp = document.getElementById('otp_code').value.trim();
        
        if(otp.length !== 6) {
            showStatus('Please enter the complete 6-digit OTP.', true);
            return;
        }

        btnVerifyOtp.disabled = true;
        btnVerifyOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Verifying...';
        statusMsg.style.display = 'none';

        try {
            const response = await fetch('{{ route("parent.link.verify-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ student_number: studentNumber, otp: otp })
            });

            const data = await response.json();
            
            if(data.success) {
                showStatus(data.message + ' Redirecting to dashboard...', false);
                setTimeout(() => {
                    window.location.href = '{{ route("parent.dashboard") }}';
                }, 1200);
            } else {
                showStatus(data.message || 'Invalid or expired OTP.', true);
                btnVerifyOtp.disabled = false;
                btnVerifyOtp.innerHTML = 'Confirm &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>';
            }
        } catch (error) {
            showStatus('An unexpected error occurred.', true);
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = 'Confirm &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>';
        }
    });
});
</script>
@endsection
