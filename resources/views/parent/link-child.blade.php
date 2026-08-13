@extends('layouts.app')
@section('page-title', 'Link Child')

@section('content')
<style>
.ent-glass-input {
    width: 100%;
    background: rgba(0,0,0,0.2) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: #f3e7cd !important;
    padding: 14px 16px;
    border-radius: 12px;
    outline: none;
    transition: all 0.2s ease;
    font-size: 1rem;
}
.ent-glass-input:focus {
    border-color: var(--gold) !important;
    background: rgba(0,0,0,0.3) !important;
    box-shadow: 0 0 0 0.25rem rgba(207,164,111,0.2) !important;
}
.ent-glass-input::placeholder {
    color: rgba(255,255,255,0.3);
}
</style>

<div class="p-4" style="max-width: 650px; margin: 0 auto;">
    <h2 style="color: #f3e7cd; font-weight: 800; margin-bottom: 24px; text-align: center;">
        <i class="bi bi-link-45deg" style="color: #cfa46f; margin-right: 8px;"></i>Link a Child
    </h2>

    <x-card type="section" style="padding: 40px;">
        <div id="step1">
            <h4 style="color: #f3e7cd; margin-bottom: 16px; font-weight: 700;">Step 1: Enter Student ID</h4>
            <p style="color: #b39b82; margin-bottom: 32px; font-size: 0.95rem; line-height: 1.5;">Please enter your child's 7-character Student ID. We will send a one-time password (OTP) to their registered school email address for verification.</p>
            
            <div class="mb-4">
                <label style="color: #cfa46f; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">Student ID</label>
                <input type="text" id="student_number" class="ent-glass-input" placeholder="e.g. 2021001" maxlength="7">
            </div>

            <button type="button" id="btn-send-otp" class="ent-btn ent-btn-primary w-100" style="padding: 14px; font-size: 1.05rem; font-weight: 700;">
                Send OTP to Student <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>

        <div id="step2" style="display: none; animation: fadeIn 0.4s ease;">
            <h4 style="color: #f3e7cd; margin-bottom: 16px; font-weight: 700;">Step 2: Enter Verification Code</h4>
            <p style="color: #b39b82; margin-bottom: 32px; font-size: 0.95rem; line-height: 1.5;">An OTP has been sent to the student's email. Please ask your child for the 6-digit code to complete the link.</p>
            
            <div class="mb-4">
                <label style="color: #cfa46f; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; display: block;">6-Digit OTP</label>
                <input type="text" id="otp_code" class="ent-glass-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 2rem; letter-spacing: 16px; font-weight: 700; padding: 20px;">
            </div>

            <div class="d-flex gap-3">
                <button type="button" id="btn-back" class="ent-btn ent-btn-ghost flex-fill" style="padding: 14px; font-weight: 600;">
                    <i class="bi bi-arrow-left me-2"></i> Back
                </button>
                <button type="button" id="btn-verify-otp" class="ent-btn ent-btn-primary flex-fill" style="padding: 14px; font-weight: 700;">
                    Verify & Link <i class="bi bi-check-circle ms-2"></i>
                </button>
            </div>
        </div>

        <div id="status-message" style="margin-top: 24px; display: none; padding: 16px; border-radius: 12px; font-weight: 500; text-align: center;"></div>
    </x-card>
</div>

<style>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const btnSendOtp = document.getElementById('btn-send-otp');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const btnBack = document.getElementById('btn-back');
    const statusMsg = document.getElementById('status-message');

    function showStatus(message, isError) {
        statusMsg.style.display = 'block';
        statusMsg.innerHTML = message;
        if(isError) {
            statusMsg.style.backgroundColor = 'rgba(239,83,80,0.1)';
            statusMsg.style.color = '#ef5350';
            statusMsg.style.border = '1px solid rgba(239,83,80,0.2)';
        } else {
            statusMsg.style.backgroundColor = 'rgba(16,185,129,0.1)';
            statusMsg.style.color = '#34d399';
            statusMsg.style.border = '1px solid rgba(16,185,129,0.2)';
        }
    }

    btnSendOtp.addEventListener('click', async () => {
        const studentNumber = document.getElementById('student_number').value;
        if(studentNumber.length !== 7) {
            showStatus('Please enter a valid 7-character student ID.', true);
            return;
        }

        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 8px;"></span> Sending...';
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
            } else {
                showStatus(data.message || 'Failed to send OTP.', true);
            }
        } catch (error) {
            showStatus('An unexpected error occurred.', true);
        }

        btnSendOtp.disabled = false;
        btnSendOtp.innerHTML = 'Send OTP to Student <i class="bi bi-arrow-right ms-2"></i>';
    });

    btnBack.addEventListener('click', () => {
        step2.style.display = 'none';
        step1.style.display = 'block';
        statusMsg.style.display = 'none';
    });

    btnVerifyOtp.addEventListener('click', async () => {
        const studentNumber = document.getElementById('student_number').value;
        const otp = document.getElementById('otp_code').value;
        
        if(otp.length !== 6) {
            showStatus('Please enter the 6-digit OTP.', true);
            return;
        }

        btnVerifyOtp.disabled = true;
        btnVerifyOtp.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" style="margin-right: 8px;"></span> Verifying...';
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
                showStatus(data.message + ' Redirecting...', false);
                setTimeout(() => {
                    window.location.href = '{{ route("parent.dashboard") }}';
                }, 1500);
            } else {
                showStatus(data.message || 'Invalid OTP.', true);
                btnVerifyOtp.disabled = false;
                btnVerifyOtp.innerHTML = 'Verify & Link <i class="bi bi-check-circle ms-2"></i>';
            }
        } catch (error) {
            showStatus('An unexpected error occurred.', true);
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = 'Verify & Link <i class="bi bi-check-circle ms-2"></i>';
        }
    });
});
</script>
@endsection
