@extends('parent.layout')
@section('page-title', 'Link Child')

@section('content')
<div class="p-4" style="max-width: 600px; margin: 0 auto;">
    <h2 style="color: #f3e7cd; font-weight: 800; margin-bottom: 24px;">
        <i class="bi bi-link-45deg" style="color: #cfa46f; margin-right: 8px;"></i>Link a Child
    </h2>

    <div class="adm-card" style="padding: 32px;">
        <div id="step1">
            <h4 style="color: #f3e7cd; margin-bottom: 16px;">Step 1: Enter Student ID</h4>
            <p style="color: #8f826f; margin-bottom: 24px;">Please enter your child's 7-digit Student ID. We will send a one-time password (OTP) to their registered school email address for verification.</p>
            
            <div class="mb-3">
                <label style="color: #cfa46f; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">Student ID</label>
                <input type="text" id="student_number" class="glass-input" placeholder="e.g. 2021001" maxlength="7">
            </div>

            <button type="button" id="btn-send-otp" class="adm-btn adm-btn-primary w-100" style="padding: 12px; font-size: 1rem;">
                Send OTP to Student
            </button>
        </div>

        <div id="step2" style="display: none;">
            <h4 style="color: #f3e7cd; margin-bottom: 16px;">Step 2: Enter Verification Code</h4>
            <p style="color: #8f826f; margin-bottom: 24px;">An OTP has been sent to the student's email. Please ask your child for the 6-digit code to complete the link.</p>
            
            <div class="mb-3">
                <label style="color: #cfa46f; font-weight: 600; font-size: 0.85rem; text-transform: uppercase;">6-Digit OTP</label>
                <input type="text" id="otp_code" class="glass-input" placeholder="000000" maxlength="6" style="text-align: center; font-size: 1.5rem; letter-spacing: 12px;">
            </div>

            <div class="d-flex gap-2">
                <button type="button" id="btn-back" class="adm-btn adm-btn-ghost w-50">Back</button>
                <button type="button" id="btn-verify-otp" class="adm-btn adm-btn-primary w-50">Verify & Link</button>
            </div>
        </div>

        <div id="status-message" style="margin-top: 16px; display: none; padding: 12px; border-radius: 8px;"></div>
    </div>
</div>

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
            statusMsg.style.backgroundColor = 'rgba(102,187,106,0.1)';
            statusMsg.style.color = '#66bb6a';
            statusMsg.style.border = '1px solid rgba(102,187,106,0.2)';
        }
    }

    btnSendOtp.addEventListener('click', async () => {
        const studentNumber = document.getElementById('student_number').value;
        if(studentNumber.length !== 7) {
            showStatus('Please enter a valid 7-digit student ID.', true);
            return;
        }

        btnSendOtp.disabled = true;
        btnSendOtp.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
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
        btnSendOtp.innerHTML = 'Send OTP to Student';
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
        btnVerifyOtp.innerHTML = 'Verifying...';
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
                btnVerifyOtp.innerHTML = 'Verify & Link';
            }
        } catch (error) {
            showStatus('An unexpected error occurred.', true);
            btnVerifyOtp.disabled = false;
            btnVerifyOtp.innerHTML = 'Verify & Link';
        }
    });
});
</script>

<style>
.glass-input {
    width: 100%;
    background: rgba(30,30,30,0.6);
    border: 1px solid rgba(255,255,255,0.1);
    color: #f3e7cd;
    padding: 12px 16px;
    border-radius: 8px;
    outline: none;
    transition: all 0.2s ease;
}
.glass-input:focus {
    border-color: rgba(207,164,111,0.5);
    background: rgba(40,40,40,0.8);
    box-shadow: 0 0 0 3px rgba(207,164,111,0.1);
}
</style>
@endsection
