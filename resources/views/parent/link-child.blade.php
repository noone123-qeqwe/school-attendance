@extends('layouts.app')
@section('page-title', 'Link Student Account')

@section('content')
<style>
.link-wizard-container {
    max-width: 780px;
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
    margin-bottom: 24px;
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

/* Tab Switcher for Methods */
.link-method-nav {
    display: flex;
    gap: 8px;
    background: rgba(14, 8, 5, 0.7);
    padding: 6px;
    border-radius: 16px;
    border: 1px solid rgba(207, 164, 111, 0.2);
    margin-bottom: 28px;
}
.link-method-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px;
    border-radius: 12px;
    border: none;
    background: transparent;
    color: #b39b82;
    font-weight: 700;
    font-size: 0.88rem;
    cursor: pointer;
    transition: all 0.25s ease;
}
.link-method-btn:hover {
    color: #f3e7cd;
}
.link-method-btn.active {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.2) 0%, rgba(207, 164, 111, 0.08) 100%);
    color: #f5dfa8;
    border: 1px solid rgba(207, 164, 111, 0.4);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
}
.link-method-btn .badge-rec {
    background: rgba(34, 197, 94, 0.2);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.4);
    font-size: 0.68rem;
    padding: 2px 7px;
    border-radius: 99px;
    font-weight: 800;
    text-transform: uppercase;
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

/* Currently Linked Children Section */
.linked-children-card {
    background: linear-gradient(145deg, rgba(34, 20, 14, 0.85) 0%, rgba(18, 9, 6, 0.95) 100%);
    border: 1px solid rgba(207, 164, 111, 0.25);
    border-radius: 24px;
    padding: 24px 28px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(16px);
    margin-bottom: 24px;
}
.child-item-tile {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    padding: 16px;
    background: rgba(255, 235, 190, 0.03);
    border: 1px solid rgba(207, 164, 111, 0.15);
    border-radius: 16px;
    margin-bottom: 12px;
    transition: all 0.2s ease;
}
.child-item-tile:hover {
    background: rgba(255, 235, 190, 0.06);
    border-color: rgba(207, 164, 111, 0.3);
    transform: translateY(-1px);
}
.child-item-tile:last-child {
    margin-bottom: 0;
}
.child-item-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(207, 164, 111, 0.5);
}
.child-item-info {
    flex: 1;
    min-width: 0;
}
.child-item-name {
    font-size: 1rem;
    font-weight: 800;
    color: #f3e7cd;
    margin-bottom: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.child-item-meta {
    font-size: 0.78rem;
    color: #b39b82;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .link-wizard-container {
        padding: 0 12px;
        margin: 10px auto 30px;
    }
    .link-wizard-card, .linked-children-card {
        padding: 22px 18px;
        border-radius: 18px;
    }
    .link-method-nav {
        flex-direction: column;
        gap: 6px;
    }
    .child-item-tile {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    .child-item-actions {
        display: flex;
        gap: 8px;
        width: 100%;
    }
    .child-item-actions > * {
        flex: 1;
        text-align: center;
        justify-content: center;
    }
}
</style>

<div class="link-wizard-container">
    {{-- Back to Dashboard Link --}}
    <a href="{{ route('parent.dashboard') }}" class="student-back-link mb-3 d-inline-flex align-items-center gap-2" style="color: #b39b82; text-decoration: none; font-size: 0.88rem; font-weight: 600;">
        <i class="bi bi-arrow-left"></i> Back to Dashboard
    </a>

    {{-- Currently Linked Children (if any) --}}
    @if(isset($linkedChildren) && $linkedChildren->isNotEmpty())
    <div class="linked-children-card">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-people-fill text-gold" style="font-size: 1.2rem;"></i>
                <h3 style="color: #f3e7cd; font-size: 1.1rem; font-weight: 800; margin: 0;">Connected Students</h3>
            </div>
            <span class="badge" style="background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); font-size: 0.75rem; border-radius: 99px; padding: 4px 10px;">
                {{ $linkedChildren->count() }} Linked
            </span>
        </div>

        <div>
            @foreach($linkedChildren as $child)
            <div class="child-item-tile" id="child-tile-{{ $child->id }}">
                <div class="d-flex align-items-center gap-3 min-w-0 flex-fill">
                    <img src="{{ $child->profile_photo_url }}" alt="{{ $child->name }}" class="child-item-avatar">
                    <div class="child-item-info">
                        <div class="child-item-name">{{ $child->name }}</div>
                        <div class="child-item-meta">
                            <span><i class="bi bi-person-badge me-1"></i>{{ $child->student_number ?? 'ID: ' . $child->id }}</span>
                            @if($child->course)
                                <span>•</span>
                                <span><i class="bi bi-mortarboard me-1"></i>{{ $child->course }}</span>
                            @endif
                            @if($child->year_level)
                                <span>•</span>
                                <span>Year {{ $child->year_level }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 child-item-actions">
                    <a href="{{ route('parent.child', $child) }}" class="btn-action-ghost d-inline-flex align-items-center gap-1" style="padding: 8px 14px; font-size: 0.82rem; border-radius: 10px; text-decoration: none; color: #f3e7cd;">
                        <i class="bi bi-bar-chart-fill"></i> Attendance
                    </a>
                    <button type="button" class="btn-action-danger d-inline-flex align-items-center gap-1" style="padding: 8px 14px; font-size: 0.82rem; border-radius: 10px; border: 1px solid rgba(239, 68, 68, 0.35); background: rgba(239, 68, 68, 0.12); color: #fca5a5; cursor: pointer;"
                        onclick="confirmUnlinkChild({{ $child->id }}, '{{ addslashes($child->name) }}')">
                        <i class="bi bi-link-45deg"></i> Unlink
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Connect New Student Card --}}
    <div class="link-wizard-card">
        <div class="link-wizard-icon">
            <i class="bi bi-person-plus-fill"></i>
        </div>

        <div class="text-center mb-4">
            <h2 style="color: #ffffff; font-weight: 800; font-size: clamp(1.3rem, 3vw, 1.7rem); margin: 0 0 6px 0; letter-spacing: -0.5px;">
                Connect a Student Profile
            </h2>
            <p style="color: #b39b82; font-size: 0.9rem; margin: 0;">
                Choose your preferred method to link with your child's attendance account
            </p>
        </div>

        <!-- Method Navigation -->
        <div class="link-method-nav">
            <button type="button" class="link-method-btn active" id="tabBtnCode" onclick="switchMethodTab('code')">
                <i class="bi bi-upc-scan"></i>
                <span>Instant Link Code</span>
                <span class="badge-rec">Fastest</span>
            </button>
            <button type="button" class="link-method-btn" id="tabBtnOtp" onclick="switchMethodTab('otp')">
                <i class="bi bi-envelope-check"></i>
                <span>Student ID &amp; Email OTP</span>
            </button>
        </div>

        <!-- METHOD 1: INSTANT LINK CODE (RECOMMENDED) -->
        <div id="methodCodeSection">
            <div class="security-note-box">
                <i class="bi bi-lightning-charge-fill text-gold" style="font-size: 1.25rem; flex-shrink: 0; margin-top: 2px;"></i>
                <div style="font-size: 0.85rem; color: #e6dbce; line-height: 1.5;">
                    Enter the <strong>6-digit Link Code</strong> generated on your child's device under <strong>Student Portal &gt; Settings &gt; Family / Guardian</strong>. No email delay required.
                </div>
            </div>

            <div class="mb-4 text-center">
                <label style="color: #cfa46f; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; display: block;">
                    Enter 6-Digit Link Code
                </label>
                <input type="text" id="instant_code" class="ent-glass-input text-center" placeholder="000000" maxlength="6" autocomplete="off"
                    style="font-size: 2.2rem; letter-spacing: 12px; font-weight: 800; padding: 16px; font-family: monospace; max-width: 320px; margin: 0 auto; display: block;">
                <div style="font-size: 0.78rem; color: #a8947f; margin-top: 8px;">
                    Link Codes expire 15 minutes after student generates them.
                </div>
            </div>

            <button type="button" id="btn-verify-code" class="btn-modern-gold w-100 justify-content-center" style="padding: 14px; font-size: 1rem;">
                Verify &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>
            </button>
        </div>

        <!-- METHOD 2: STUDENT NUMBER + OTP -->
        <div id="methodOtpSection" style="display: none;">
            <!-- Step Indicator -->
            <div class="wizard-step-tracker" style="display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 24px;">
                <div class="step-dot active" id="dot1" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 700; color: #f3e7cd; text-transform: uppercase;">
                    <span style="width: 22px; height: 22px; border-radius: 50%; background: var(--gold); color: #140d07; display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">1</span>
                    <span>Student ID</span>
                </div>
                <div style="width: 36px; height: 1px; background: rgba(207, 164, 111, 0.2);"></div>
                <div class="step-dot" id="dot2" style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 700; color: #8f826f; text-transform: uppercase;">
                    <span style="width: 22px; height: 22px; border-radius: 50%; border: 1px solid rgba(207, 164, 111, 0.3); background: rgba(255, 255, 255, 0.05); display: flex; align-items: center; justify-content: center; font-size: 0.72rem;">2</span>
                    <span>Email OTP</span>
                </div>
            </div>

            <!-- STEP 1: ENTER STUDENT NUMBER -->
            <div id="step1">
                <div class="security-note-box">
                    <i class="bi bi-shield-lock-fill text-gold" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                    <div style="font-size: 0.84rem; color: #e6dbce; line-height: 1.5;">
                        Enter your child's <strong>Student ID / Number</strong> (e.g. 20260001). A 6-digit confirmation code will be sent to their registered school inbox.
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
            <div id="step2" style="display: none;">
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
        </div>

        <!-- Status Message Banner -->
        <div id="status-message" style="margin-top: 20px; display: none; padding: 14px 18px; border-radius: 12px; font-size: 0.88rem; text-align: center;"></div>
    </div>
</div>

<script>
function switchMethodTab(method) {
    const codeSection = document.getElementById('methodCodeSection');
    const otpSection = document.getElementById('methodOtpSection');
    const tabCode = document.getElementById('tabBtnCode');
    const tabOtp = document.getElementById('tabBtnOtp');
    const statusMsg = document.getElementById('status-message');
    if (statusMsg) statusMsg.style.display = 'none';

    if (method === 'code') {
        codeSection.style.display = 'block';
        otpSection.style.display = 'none';
        tabCode.classList.add('active');
        tabOtp.classList.remove('active');
        document.getElementById('instant_code').focus();
    } else {
        codeSection.style.display = 'none';
        otpSection.style.display = 'block';
        tabCode.classList.remove('active');
        tabOtp.classList.add('active');
        document.getElementById('student_number').focus();
    }
}

function showStatus(message, isError) {
    const statusMsg = document.getElementById('status-message');
    if (!statusMsg) return;
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

async function confirmUnlinkChild(studentId, studentName) {
    if (!confirm(`Are you sure you want to disconnect ${studentName} from your account? You will no longer receive attendance notifications or access their records.`)) {
        return;
    }

    try {
        const response = await fetch('{{ route("parent.link.unlink") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ student_id: studentId })
        });

        const data = await response.json();
        if (data.success) {
            const tile = document.getElementById('child-tile-' + studentId);
            if (tile) {
                tile.style.opacity = '0.3';
                tile.style.transform = 'scale(0.96)';
                setTimeout(() => tile.remove(), 300);
            }
            showStatus(data.message || 'Student unlinked successfully.', false);
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showStatus(data.message || 'Failed to unlink student.', true);
        }
    } catch (e) {
        showStatus('Network error while unlinking student.', true);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const btnVerifyCode = document.getElementById('btn-verify-code');
    const instantCodeInput = document.getElementById('instant_code');
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const dot1 = document.getElementById('dot1');
    const dot2 = document.getElementById('dot2');
    const btnSendOtp = document.getElementById('btn-send-otp');
    const btnVerifyOtp = document.getElementById('btn-verify-otp');
    const btnBack = document.getElementById('btn-back');

    // Auto-check URL query param ?code=...
    const urlParams = new URLSearchParams(window.location.search);
    const codeParam = urlParams.get('code');
    if (codeParam && codeParam.length === 6) {
        instantCodeInput.value = codeParam;
        switchMethodTab('code');
    }

    // Method 1: Instant Code Verification
    if (btnVerifyCode) {
        btnVerifyCode.addEventListener('click', async () => {
            const code = instantCodeInput.value.trim();
            if (code.length !== 6 || !/^\d+$/.test(code)) {
                showStatus('Please enter the 6-digit Link Code provided by the student.', true);
                instantCodeInput.focus();
                return;
            }

            btnVerifyCode.disabled = true;
            btnVerifyCode.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';

            try {
                const response = await fetch('{{ route("parent.link.code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code: code })
                });

                const data = await response.json();
                if (data.success) {
                    showStatus(data.message + ' Redirecting to dashboard...', false);
                    setTimeout(() => {
                        window.location.href = '{{ route("parent.dashboard") }}';
                    }, 1200);
                } else {
                    showStatus(data.message || 'Invalid or expired code.', true);
                    btnVerifyCode.disabled = false;
                    btnVerifyCode.innerHTML = 'Verify &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>';
                }
            } catch (err) {
                showStatus('Network error while connecting. Please try again.', true);
                btnVerifyCode.disabled = false;
                btnVerifyCode.innerHTML = 'Verify &amp; Link Student <i class="bi bi-check-circle-fill ms-2"></i>';
            }
        });
    }

    // Method 2: OTP Verification
    if (btnSendOtp) {
        btnSendOtp.addEventListener('click', async () => {
            const studentNumber = document.getElementById('student_number').value.trim();
            if(!studentNumber || studentNumber.length < 3) {
                showStatus('Please enter a valid student ID.', true);
                return;
            }

            btnSendOtp.disabled = true;
            btnSendOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';

            try {
                const response = await fetch('{{ route("parent.link.send-otp") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ student_number: studentNumber })
                });

                const data = await response.json();
                
                if(data.success) {
                    showStatus(data.message, false);
                    step1.style.display = 'none';
                    step2.style.display = 'block';
                    if (dot1 && dot2) {
                        dot1.style.color = '#8f826f';
                        dot2.style.color = '#f3e7cd';
                    }
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
    }

    if (btnBack) {
        btnBack.addEventListener('click', () => {
            step2.style.display = 'none';
            step1.style.display = 'block';
            if (dot1 && dot2) {
                dot1.style.color = '#f3e7cd';
                dot2.style.color = '#8f826f';
            }
        });
    }

    if (btnVerifyOtp) {
        btnVerifyOtp.addEventListener('click', async () => {
            const studentNumber = document.getElementById('student_number').value.trim();
            const otp = document.getElementById('otp_code').value.trim();
            
            if(otp.length !== 6) {
                showStatus('Please enter the complete 6-digit OTP.', true);
                return;
            }

            btnVerifyOtp.disabled = true;
            btnVerifyOtp.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';

            try {
                const response = await fetch('{{ route("parent.link.verify-otp") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
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
    }
});
</script>
@endsection
