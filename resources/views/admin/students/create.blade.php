@extends('layouts.app')
@section('page-title', 'Add Student')

@section('content')
<a href="{{ route('admin.students') }}" class="adm-btn adm-btn-ghost" style="margin-bottom:20px;text-decoration:none;">
    <i class="bi bi-arrow-left"></i> Back
</a>

<div class="adm-card" style="max-width:700px;">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-plus-fill"></i></div>
            Add New Student
        </div>
    </div>
    <div style="padding:24px;">
        @if($errors->any())
        <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">
            {{ $errors->first() }}
        </div>
        @endif

        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#16a34a;border-radius:10px;padding:10px 14px;font-size:.85rem;margin-bottom:16px;">
            {{ session('success') }}
        </div>
        @endif

        <form action="{{ route('admin.student.store') }}" method="POST" id="addStudentForm">
            @csrf
            <div class="row g-3">
                <!-- Basic Information -->
                <div class="col-12">
                    <h6 style="color:#374151;font-weight:600;margin-bottom:15px;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">
                        <i class="bi bi-person-fill me-2"></i>Basic Information
                    </h6>
                </div>
                
                <div class="col-md-6">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Full Name *
                    </label>
                    <input type="text" 
                           name="name" 
                           class="adm-input" 
                           value="{{ old('name') }}" 
                           required 
                           placeholder="Enter student's full name"
                           style="width: 100%;">
                </div>
                
                <div class="col-md-6">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Student ID *
                    </label>
                    <input type="text" 
                           name="student_number" 
                           class="adm-input" 
                           value="{{ old('student_number') }}" 
                           maxlength="7" 
                           pattern="[a-zA-Z0-9]{7}" 
                           required 
                           placeholder="e.g. A123456"
                           style="width: 100%;">
                </div>

                <!-- Academic Information -->
                <div class="col-12" style="margin-top:20px;">
                    <h6 style="color:#374151;font-weight:600;margin-bottom:15px;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">
                        <i class="bi bi-mortarboard-fill me-2"></i>Academic Information
                    </h6>
                </div>
                
                <div class="col-md-4">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Course *
                    </label>
                    <select name="course" 
                            class="adm-input" 
                            required 
                            style="width: 100%;">
                        <option value="">Select Course</option>
                        @foreach(['BSCS' => 'BS Computer Science', 'BSIT' => 'BS Information Technology', 'BSIS' => 'BS Information Systems'] as $code => $name)
                        <option value="{{ $code }}" {{ old('course')==$code?'selected':'' }}>{{ $code }} - {{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Year Level *
                    </label>
                    <select name="year_level" 
                            class="adm-input" 
                            required 
                            style="width: 100%;">
                        <option value="">Select Year</option>
                        @foreach([1,2,3,4] as $y)
                        <option value="{{ $y }}" {{ old('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Semester *
                    </label>
                    <select name="semester" 
                            class="adm-input" 
                            required 
                            style="width: 100%;">
                        <option value="">Select Semester</option>
                        <option value="1" {{ old('semester')=='1'?'selected':'' }}>1st Semester</option>
                        <option value="2" {{ old('semester')=='2'?'selected':'' }}>2nd Semester</option>
                    </select>
                </div>

                <!-- Account Information -->
                <div class="col-12" style="margin-top:20px;">
                    <h6 style="color:#374151;font-weight:600;margin-bottom:15px;border-bottom:1px solid #e5e7eb;padding-bottom:8px;">
                        <i class="bi bi-shield-lock-fill me-2"></i>Account Information
                    </h6>
                </div>
                
                <div class="col-md-8">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Email Address *
                    </label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input id="emailInput" 
                               type="email" 
                               name="email" 
                               class="adm-input" 
                               value="{{ old('email') }}" 
                               required 
                               placeholder="student@example.com"
                               style="flex:1;">
                        <button id="sendStudentEmailOtpBtn" 
                                type="button" 
                                class="adm-btn adm-btn-ghost">
                            Send OTP
                        </button>
                    </div>
                    <div id="emailOtpStatus" style="margin-top:6px;font-size:.85rem;"></div>
                </div>

                <div id="emailOtpVerifySection" class="col-md-4" style="display:none;">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Enter OTP Code
                    </label>
                    <div style="display:flex;gap:10px;align-items:center;">
                        <input id="emailOtpInput" 
                               type="text" 
                               class="adm-input" 
                               maxlength="6" 
                               placeholder="123456"
                               style="flex:1;">
                        <button id="verifyStudentEmailOtpBtn" 
                                type="button" 
                                class="adm-btn adm-btn-primary">
                            Verify
                        </button>
                    </div>
                </div>
                
                <input type="hidden" id="emailVerifiedInput" name="email_verified" value="">
                
                <div class="col-md-6">
                    <label style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">
                        Password *
                    </label>
                    <input type="password" 
                           name="password" 
                           class="adm-input" 
                           required 
                           minlength="8"
                           placeholder="Minimum 8 characters"
                           style="width: 100%;">
                </div>

                <!-- Submit Section -->
                <div class="col-12" style="margin-top:30px;padding-top:20px;border-top:1px solid #e5e7eb;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <p style="margin:0;font-size:0.85rem;color:#6b7280;">
                            <i class="bi bi-info-circle me-1"></i>
                            Email verification is required before adding the student.
                        </p>
                        <button id="addStudentBtn" 
                                type="submit" 
                                class="adm-btn adm-btn-primary"
                                disabled>
                            <i class="bi bi-check2 me-2"></i>Add Student
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Form elements
    const sendOtpBtn = document.getElementById('sendStudentEmailOtpBtn');
    const verifyOtpBtn = document.getElementById('verifyStudentEmailOtpBtn');
    const emailInput = document.getElementById('emailInput');
    const emailOtpInput = document.getElementById('emailOtpInput');
    const emailOtpStatus = document.getElementById('emailOtpStatus');
    const emailOtpVerifySection = document.getElementById('emailOtpVerifySection');
    const addStudentBtn = document.getElementById('addStudentBtn');
    const emailVerifiedInput = document.getElementById('emailVerifiedInput');
    const addStudentForm = document.getElementById('addStudentForm');
    const csrfToken = document.querySelector('input[name="_token"]').value;

    // Real-time form validation
    const requiredFields = ['name', 'student_number', 'course', 'year_level', 'semester', 'password'];
    const formInputs = requiredFields.map(name => document.querySelector(`[name="${name}"]`));

    function validateForm() {
        const allFieldsFilled = formInputs.every(input => input && input.value.trim() !== '');
        const emailVerified = emailVerifiedInput.value !== '';
        
        addStudentBtn.disabled = !(allFieldsFilled && emailVerified);
        
        if (allFieldsFilled && !emailVerified) {
            setStatus('Please verify the email address before adding the student.', '#f59e0b');
        }
    }

    // Add event listeners to form inputs
    formInputs.forEach(input => {
        if (input) {
            input.addEventListener('input', validateForm);
            input.addEventListener('change', validateForm);
        }
    });

    // Email input change handler
    emailInput.addEventListener('input', () => {
        if (emailVerifiedInput.value && emailVerifiedInput.value !== emailInput.value.trim()) {
            emailVerifiedInput.value = '';
            addStudentBtn.disabled = true;
            setStatus('Email changed. Please verify the new email address.', '#f59e0b');
            emailOtpVerifySection.style.display = 'none';
        }
        validateForm();
    });

    function setStatus(message, color = '#6b7280') {
        emailOtpStatus.textContent = message;
        emailOtpStatus.style.color = color;
    }

    // Send OTP handler
    sendOtpBtn.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        if (!email || !isValidEmail(email)) {
            setStatus('Please enter a valid email address.', '#dc2626');
            return;
        }

        sendOtpBtn.disabled = true;
        sendOtpBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Sending...';
        setStatus('Sending OTP to the email address...');

        try {
            const response = await fetch('{{ route('admin.otp.register.send') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ email, scope: 'admin_student' }),
            });

            const data = await response.json();
            if (data.success) {
                setStatus(data.message || 'OTP sent successfully. Check the student email.', '#16a34a');
                emailOtpVerifySection.style.display = 'block';
                emailOtpInput.focus();
            } else {
                setStatus(data.message || 'Failed to send OTP. Please try again.', '#dc2626');
            }
        } catch (error) {
            console.error('OTP send error:', error);
            setStatus('Network error. Please check your connection and try again.', '#dc2626');
        }

        sendOtpBtn.disabled = false;
        sendOtpBtn.innerHTML = 'Send OTP';
    });

    // Verify OTP handler
    verifyOtpBtn.addEventListener('click', async () => {
        const email = emailInput.value.trim();
        const otp = emailOtpInput.value.trim();

        if (!email || !otp) {
            setStatus('Please enter the OTP code sent to the email address.', '#dc2626');
            return;
        }

        if (otp.length !== 6 || !/^\d{6}$/.test(otp)) {
            setStatus('OTP must be 6 digits.', '#dc2626');
            return;
        }

        verifyOtpBtn.disabled = true;
        verifyOtpBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-1"></i>Verifying...';
        setStatus('Verifying OTP code...');

        try {
            const response = await fetch('{{ route('admin.otp.register.verify') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ email, otp, scope: 'admin_student' }),
            });

            const data = await response.json();
            if (data.success) {
                setStatus('âœ“ Email verified successfully!', '#16a34a');
                emailVerifiedInput.value = email;
                emailOtpVerifySection.style.display = 'none';
                emailOtpInput.value = '';
                validateForm(); // Re-check form validation
            } else {
                setStatus(data.message || 'Invalid or expired OTP. Please try again.', '#dc2626');
            }
        } catch (error) {
            console.error('OTP verify error:', error);
            setStatus('Network error. Please check your connection and try again.', '#dc2626');
        }

        verifyOtpBtn.disabled = false;
        verifyOtpBtn.innerHTML = 'Verify';
    });

    // Form submission handler
    addStudentForm.addEventListener('submit', function(e) {
        if (addStudentBtn.disabled) {
            e.preventDefault();
            setStatus('Please complete all required fields and verify email.', '#dc2626');
            return false;
        }

        addStudentBtn.disabled = true;
        addStudentBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Adding Student...';
    });

    // Utility functions
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Initialize form validation
    validateForm();

    // Add spinning animation CSS
    const style = document.createElement('style');
    style.textContent = `
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
</script>
@endsection
