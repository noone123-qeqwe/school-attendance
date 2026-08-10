<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #8a1515;
            --primary-hover: #a11f1f;
            --accent: #e8c064;
            --accent-hover: #fce096;
            --bg-dark: #0a0305;
            --glass-bg: rgba(25, 10, 15, 0.4);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-main: #fcfcfc;
            --text-muted: #a39b9d;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Inter', sans-serif; 
            min-height: 100vh; 
            background-color: var(--bg-dark); 
            color: var(--text-main);
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dynamic Background */
        .bg-scene {
            position: fixed; inset: 0;
            background: url('/images/background.png') center center / cover no-repeat;
            z-index: 0;
        }
        .bg-overlay {
            position: fixed; inset: 0;
            background: radial-gradient(circle at top right, rgba(138, 21, 21, 0.15), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(232, 192, 100, 0.1), transparent 40%),
                        linear-gradient(135deg, rgba(10,3,5,0.85) 0%, rgba(20,5,10,0.95) 100%);
            z-index: 1;
        }

        /* Animated Blobs */
        .blob {
            position: fixed; filter: blur(80px); z-index: 2; opacity: 0.5;
            animation: float 20s infinite alternate ease-in-out;
        }
        .blob-1 { top: -10%; left: -10%; width: 50vw; height: 50vw; background: rgba(138, 21, 21, 0.3); }
        .blob-2 { bottom: -20%; right: -10%; width: 60vw; height: 60vw; background: rgba(232, 192, 100, 0.15); animation-delay: -10s; }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(5%, 10%) scale(1.1); }
        }

        /* Top Bar */
        .top-bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            display: flex; align-items: center; justify-content: space-between;
            padding: 24px 40px;
        }
        .brand-logo {
            font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: white;
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            letter-spacing: 0.5px;
        }
        .brand-logo i { color: var(--accent); font-size: 1.4rem; }

        /* Main Container */
        .auth-container {
            position: relative; z-index: 10;
            width: 100%; max-width: 760px;
            padding: 20px;
            perspective: 1000px;
        }

        /* Premium Glass Card */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px rgba(0,0,0,0.6), inset 0 1px 0 rgba(255,255,255,0.15);
            overflow: hidden;
            transform-style: preserve-3d;
            animation: slideUpFade 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(30px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Header */
        .card-header-premium {
            padding: 50px 40px 30px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            background: linear-gradient(180deg, rgba(255,255,255,0.04) 0%, transparent 100%);
        }
        .card-header-premium h2 {
            font-family: 'Outfit', sans-serif; font-size: 1.85rem; font-weight: 700;
            margin-bottom: 8px; color: white; letter-spacing: -0.5px;
        }
        .card-header-premium p {
            font-size: 0.95rem; color: var(--text-muted); margin: 0;
        }

        /* Step Indicators */
        .stepper {
            display: flex; align-items: center; justify-content: center;
            padding: 30px 40px; gap: 8px;
        }
        .step {
            display: flex; align-items: center; gap: 12px;
            opacity: 0.4; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step.active { opacity: 1; }
        .step.completed { opacity: 0.9; }
        .step-icon {
            width: 34px; height: 34px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.85rem; font-weight: 700;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15);
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .step.active .step-icon {
            background: var(--primary); border-color: var(--primary);
            box-shadow: 0 0 20px rgba(138,21,21,0.5); color: white; transform: scale(1.1);
        }
        .step.completed .step-icon {
            background: var(--accent); border-color: var(--accent); color: var(--bg-dark);
        }
        .step-divider {
            flex: 1; max-width: 60px; height: 3px; background: rgba(255,255,255,0.06);
            border-radius: 3px; transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            margin: 0 4px;
        }
        .step-divider.active { background: linear-gradient(90deg, var(--primary), var(--accent)); }

        /* Form Body */
        .card-body-premium {
            padding: 20px 50px 60px;
            position: relative;
            overflow: hidden;
        }

        /* Form Steps Animation */
        .form-step {
            display: none;
            animation: fadeInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .form-step.active { display: block; }

        @keyframes fadeInRight {
            0% { opacity: 0; transform: translateX(20px); }
            100% { opacity: 1; transform: translateX(0); }
        }

        /* Role Selector Cards */
        .role-selector {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-bottom: 24px;
        }
        .role-card {
            background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px; padding: 18px 10px; text-align: center;
            cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .role-card i { font-size: 1.6rem; color: var(--text-muted); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .role-card span { font-size: 0.85rem; font-weight: 500; color: var(--text-muted); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .role-card:hover { 
            background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); 
            transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .role-card input:checked + i, .role-card input:checked ~ span { color: var(--accent); transform: scale(1.05); }
        .role-card:has(input:checked) {
            background: rgba(232, 192, 100, 0.08);
            border-color: var(--accent);
            box-shadow: inset 0 0 0 1px var(--accent), 0 10px 20px rgba(232,192,100,0.1);
            transform: translateY(-2px);
        }

        /* Floating Label Inputs */
        .form-floating-custom {
            position: relative; margin-bottom: 16px;
        }
        .form-floating-custom input, .form-floating-custom select, .form-floating-custom textarea {
            width: 100%; padding: 18px; padding-top: 26px; padding-bottom: 6px;
            background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px; color: white; font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .form-floating-custom select { appearance: none; cursor: pointer; }
        .form-floating-custom select option { background: var(--bg-dark); color: white; }
        .select-arrow {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            color: var(--text-muted); pointer-events: none;
        }
        .form-floating-custom label {
            position: absolute; left: 16px; top: 18px;
            color: var(--text-muted); font-size: 0.95rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none;
        }
        .form-floating-custom input:focus, .form-floating-custom select:focus, .form-floating-custom textarea:focus {
            outline: none; border-color: var(--accent); background: rgba(0,0,0,0.3);
            box-shadow: 0 0 0 4px rgba(232, 192, 100, 0.15), inset 0 0 0 1px var(--accent);
        }
        .form-floating-custom input:focus ~ label, 
        .form-floating-custom input:not(:placeholder-shown) ~ label,
        .form-floating-custom select:focus ~ label,
        .form-floating-custom select:valid ~ label,
        .form-floating-custom textarea:focus ~ label,
        .form-floating-custom textarea:not(:placeholder-shown) ~ label {
            top: 6px; font-size: 0.7rem; color: var(--accent); font-weight: 600;
        }
        .form-floating-custom.invalid input { border-color: #ef4444; }
        .form-floating-custom.invalid label { color: #ef4444; }

        /* Password Toggle */
        .eye-btn {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--text-muted);
            cursor: pointer; padding: 4px; transition: color 0.2s;
        }
        .eye-btn:hover { color: white; }

        /* Premium Buttons */
        .btn-premium {
            width: 100%; padding: 18px; border-radius: 14px;
            font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 700;
            border: none; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative; overflow: hidden;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white;
            box-shadow: 0 8px 20px rgba(138, 21, 21, 0.3), inset 0 1px 0 rgba(255,255,255,0.2);
        }
        .btn-primary::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transform: skewX(-20deg); transition: 0.5s;
        }
        .btn-primary i { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(138, 21, 21, 0.5), inset 0 1px 0 rgba(255,255,255,0.2); }
        .btn-primary:hover::after { left: 150%; }
        .btn-primary:hover i { transform: translateX(4px); }
        
        .btn-secondary {
            background: rgba(255,255,255,0.03); color: white;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-secondary:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.3); transform: translateY(-2px); }

        .btn-group-row { display: flex; gap: 12px; margin-top: 24px; }
        .btn-group-row .btn-secondary { flex: 0 0 auto; width: auto; padding: 16px 24px; }
        .btn-group-row .btn-primary { flex: 1; }

        /* OTP Boxes */
        .otp-container { display: flex; gap: 10px; justify-content: center; margin: 24px 0; }
        .otp-box {
            width: 50px; height: 60px; border-radius: 12px;
            background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15);
            color: white; font-size: 1.5rem; font-weight: 700; text-align: center;
            transition: all 0.3s;
        }
        .otp-box:focus {
            outline: none; border-color: var(--accent); background: rgba(0,0,0,0.5);
            box-shadow: 0 0 15px rgba(232, 192, 100, 0.2); transform: translateY(-2px);
        }

        /* Alerts */
        .alert-modern {
            background: rgba(239, 68, 68, 0.1); border-left: 4px solid #ef4444;
            padding: 12px 16px; border-radius: 0 8px 8px 0; color: #fca5a5;
            font-size: 0.85rem; display: flex; align-items: flex-start; gap: 10px;
            margin-bottom: 20px; animation: slideIn 0.3s ease;
        }
        @keyframes slideIn { from { opacity: 0; transform: translateX(-10px); } to { opacity: 1; transform: translateX(0); } }

        /* Links */
        .auth-links {
            text-align: center; margin-top: 24px; font-size: 0.9rem; color: var(--text-muted);
            border-top: 1px solid rgba(255,255,255,0.05); padding-top: 20px;
        }
        .auth-links a {
            color: var(--accent); text-decoration: none; font-weight: 600;
            transition: color 0.2s; position: relative;
        }
        .auth-links a::after {
            content: ''; position: absolute; bottom: -2px; left: 0; width: 0; height: 1px;
            background: var(--accent); transition: width 0.3s ease;
        }
        .auth-links a:hover::after { width: 100%; }

        /* Responsive */
        @media (max-width: 576px) {
            .card-header-premium, .card-body-premium, .stepper { padding-left: 24px; padding-right: 24px; }
            .top-bar { padding: 16px 20px; }
            .otp-box { width: 42px; height: 52px; font-size: 1.25rem; gap: 6px; }
            .role-selector { gap: 8px; }
            .role-card { padding: 12px 6px; }
            .role-card i { font-size: 1.2rem; }
            .role-card span { font-size: 0.75rem; }
        }
    </style>
</head>
<body>

    <div class="bg-scene"></div>
    <div class="bg-overlay"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <div class="top-bar">
        <a href="/" class="brand-logo">
            <i class="bi bi-shield-check"></i>
            {{ config('app.name') }}
        </a>
    </div>

    <div class="auth-container">
        <div class="glass-card">
            
            <div class="card-header-premium">
                <h2>Create Account</h2>
                <p id="step-desc">Tell us a bit about yourself</p>
            </div>

            <div class="stepper">
                <div class="step active" id="indicator-1">
                    <div class="step-icon">1</div>
                </div>
                <div class="step-divider" id="div-1"></div>
                <div class="step" id="indicator-2">
                    <div class="step-icon">2</div>
                </div>
                <div class="step-divider" id="div-2"></div>
                <div class="step" id="indicator-3">
                    <div class="step-icon">3</div>
                </div>
            </div>

            <div class="card-body-premium">
                
                @if($errors->any())
                    <div class="alert-modern">
                        <i class="bi bi-exclamation-triangle-fill mt-1"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif
                <div id="js-alert" class="alert-modern" style="display:none;"></div>

                <form id="regForm" method="POST" action="{{ route('register.submit') }}">
                    @csrf
                    
                    <!-- STEP 1: Basic Info -->
                    <div id="step-1" class="form-step active">
                        <input type="hidden" name="name" id="name" value="{{ old('name') }}">
                        <div class="form-floating-custom mb-3">
                            <input type="text" name="first_name" id="first_name" placeholder=" " value="{{ old('first_name') }}" oninput="updateFullName()" required>
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="form-floating-custom mb-3">
                            <input type="text" name="middle_name" id="middle_name" placeholder=" " value="{{ old('middle_name') }}" oninput="updateFullName()">
                            <label for="middle_name">Middle Name</label>
                        </div>
                        <div class="form-floating-custom mb-4">
                            <input type="text" name="surname" id="surname" placeholder=" " value="{{ old('surname') }}" oninput="updateFullName()" required>
                            <label for="surname">Surname</label>
                        </div>

                        <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px; display: block; font-weight: 500;">I am registering as a:</label>
                        <div class="role-selector">
                            <label class="role-card">
                                <input type="radio" name="role" value="student" style="display:none;" onchange="toggleFields()" {{ old('role')=='student'?'checked':'' }} required>
                                <i class="bi bi-mortarboard"></i>
                                <span>Student</span>
                            </label>
                            <label class="role-card">
                                <input type="radio" name="role" value="teacher" style="display:none;" onchange="toggleFields()" {{ old('role')=='teacher'?'checked':'' }}>
                                <i class="bi bi-person-video3"></i>
                                <span>Teacher</span>
                            </label>
                            <label class="role-card">
                                <input type="radio" name="role" value="parent" style="display:none;" onchange="toggleFields()" {{ old('role')=='parent'?'checked':'' }}>
                                <i class="bi bi-people"></i>
                                <span>Parent</span>
                            </label>
                        </div>

                        <!-- Dynamic Fields -->
                        <div id="dynamic-fields" style="display:none;">
                            <!-- Student Specific -->
                            <div id="student-fields" style="display:none;">
                                <div class="form-floating-custom">
                                    <input type="text" name="student_number" id="student_number" placeholder=" " maxlength="7" value="{{ old('student_number') }}">
                                    <label for="student_number">Student ID (7 characters)</label>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-7">
                                        <div class="form-floating-custom mb-0">
                                            <input type="text" name="course" id="course" value="BSCS" readonly style="background:rgba(255,255,255,0.05);color:#fcfcfc;cursor:default;">
                                            <label for="course">Course</label>
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-floating-custom mb-0">
                                            <select name="year_level" id="year_level" required>
                                                <option value="" disabled selected></option>
                                                @foreach([1,2,3,4] as $y)
                                                <option value="{{ $y }}" {{ old('year_level')==$y?'selected':'' }}>{{ $y }}{{ $y==1?'st':($y==2?'nd':($y==3?'rd':'th')) }}</option>
                                                @endforeach
                                            </select>
                                            <label for="year_level">Year Level</label>
                                            <i class="bi bi-chevron-down select-arrow"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-floating-custom">
                                    <select name="semester" id="semester" required>
                                        <option value="" disabled selected></option>
                                        <option value="1" {{ old('semester')=='1'?'selected':'' }}>1st Semester</option>
                                        <option value="2" {{ old('semester')=='2'?'selected':'' }}>2nd Semester</option>
                                        <option value="Summer" {{ old('semester')=='Summer'?'selected':'' }}>Summer</option>
                                    </select>
                                    <label for="semester">Semester</label>
                                    <i class="bi bi-chevron-down select-arrow"></i>
                                </div>

                            </div>

                            <!-- Teacher Specific -->
                            <div id="teacher-fields" style="display:none;">
                                <div class="form-floating-custom">
                                    <input type="text" name="employee_id" id="employee_id" placeholder=" " value="{{ old('employee_id') }}">
                                    <label for="employee_id">Employee ID (Optional)</label>
                                </div>
                                <div class="form-floating-custom">
                                    <select name="department" id="department" onchange="toggleCustomDept()">
                                        <option value="" disabled selected></option>
                                        <option value="Computer Science" {{ old('department')=='Computer Science'?'selected':'' }}>Computer Science</option>
                                        <option value="Information Technology" {{ old('department')=='Information Technology'?'selected':'' }}>Information Technology</option>
                                        <option value="Mathematics" {{ old('department')=='Mathematics'?'selected':'' }}>Mathematics</option>
                                        <option value="Other" {{ (old('department')=='Other' || old('custom_department'))?'selected':'' }}>Other (Specify)</option>
                                    </select>
                                    <label for="department">Department</label>
                                    <i class="bi bi-chevron-down select-arrow"></i>
                                </div>
                                <div class="form-floating-custom" id="custom-dept-wrapper" style="display:{{ (old('department')=='Other' || old('custom_department')) ? 'block' : 'none' }};">
                                    <input type="text" name="custom_department" id="custom_department" placeholder=" " value="{{ old('custom_department') }}">
                                    <label for="custom_department">Specify Department</label>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-premium btn-primary mt-2" onclick="goToStep(2)">
                            Continue <i class="bi bi-arrow-right ms-1"></i>
                        </button>
                    </div>

                    <!-- STEP 2: Account Details -->
                    <div id="step-2" class="form-step">
                        <div class="form-floating-custom">
                            <input type="email" name="email" id="email" placeholder=" " value="{{ old('email') }}" required>
                            <label for="email">Email Address</label>
                        </div>
                        
                        <div class="form-floating-custom">
                            <input type="password" name="password" id="password" placeholder=" " required>
                            <label for="password">Password (Min 8 chars)</label>
                            <button type="button" class="eye-btn" onclick="togglePassword('password', this)"><i class="bi bi-eye-slash"></i></button>
                        </div>

                        <div class="form-floating-custom">
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " required>
                            <label for="password_confirmation">Confirm Password</label>
                            <button type="button" class="eye-btn" onclick="togglePassword('password_confirmation', this)"><i class="bi bi-eye-slash"></i></button>
                        </div>

                        <div class="btn-group-row">
                            <button type="button" class="btn-premium btn-secondary" onclick="goToStep(1)">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <button type="button" class="btn-premium btn-primary" id="btn-verify" onclick="sendOtp()">
                                Verify Email <i class="bi bi-envelope-check ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: OTP Verification -->
                    <div id="step-3" class="form-step">
                        <div class="text-center mb-4">
                            <div style="display:inline-flex; padding:16px; border-radius:50%; background:rgba(232, 192, 100, 0.15); color:var(--accent); margin-bottom:16px;">
                                <i class="bi bi-envelope-paper" style="font-size:2rem; line-height:1;"></i>
                            </div>
                            <h4 style="font-family:'Outfit'; font-weight:700; font-size:1.2rem;">Check your inbox</h4>
                            <p style="color:var(--text-muted); font-size:0.9rem; margin:0;">We sent a 6-digit code to <br><b id="display-email" style="color:white;"></b></p>
                        </div>

                        <div class="otp-container">
                            @for($i=1; $i<=6; $i++)
                                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" id="otp-{{$i}}">
                            @endfor
                        </div>
                        <input type="hidden" name="email_otp" id="hidden_otp">

                        <div class="text-center mb-4 mt-3" style="font-size:0.85rem; color:var(--text-muted);">
                            Code expires in <span id="timer" style="color:var(--accent); font-weight:600; font-variant-numeric: tabular-nums;">10:00</span>
                        </div>

                        <div class="btn-group-row">
                            <button type="button" class="btn-premium btn-secondary" onclick="goToStep(2)">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <button type="button" class="btn-premium btn-primary" id="btn-submit" onclick="verifyOtpAndSubmit()">
                                Complete Registration <i class="bi bi-check-circle ms-1"></i>
                            </button>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="#" onclick="sendOtp(); return false;" style="color:var(--text-muted); text-decoration:underline; font-size:0.85rem;">Didn't receive it? Resend</a>
                        </div>
                    </div>

                    <!-- SUCCESS STATE -->
                    <div id="step-success" class="form-step text-center py-4">
                        <div style="display:inline-flex; padding:20px; border-radius:50%; background:rgba(34, 197, 94, 0.15); color:#22c55e; margin-bottom:24px;">
                            <i class="bi bi-check-lg" style="font-size:3rem; line-height:1;"></i>
                        </div>
                        <h2 style="font-family:'Outfit'; font-weight:700;">Verified Successfully!</h2>
                        <p style="color:var(--text-muted); margin-top:12px;">Redirecting you momentarily...</p>
                    </div>

                </form>

                <div class="auth-links" id="auth-links">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showAlert(msg) {
            const alertEl = document.getElementById('js-alert');
            alertEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill mt-1"></i><div>${msg}</div>`;
            alertEl.style.display = 'flex';
        }
        function hideAlert() { document.getElementById('js-alert').style.display = 'none'; }

        function toggleFields() {
            const role = document.querySelector('input[name="role"]:checked')?.value;
            const dynamicFields = document.getElementById('dynamic-fields');
            const studentFields = document.getElementById('student-fields');
            const teacherFields = document.getElementById('teacher-fields');

            if (!role) { dynamicFields.style.display = 'none'; return; }
            dynamicFields.style.display = 'block';

            if (role === 'student') {
                studentFields.style.display = 'block'; teacherFields.style.display = 'none';
                document.getElementById('student_number').required = true;
                document.getElementById('course').required = true;
                document.getElementById('year_level').required = true;
                document.getElementById('semester').required = true;
            } else if (role === 'teacher') {
                studentFields.style.display = 'none'; teacherFields.style.display = 'block';
                document.getElementById('student_number').required = false;
                document.getElementById('course').required = false;
                document.getElementById('year_level').required = false;
                document.getElementById('semester').required = false;
            } else {
                studentFields.style.display = 'none'; teacherFields.style.display = 'none';
                document.getElementById('student_number').required = false;
            }
        }

        function toggleCustomDept() {
            const dept = document.getElementById('department').value;
            const custom = document.getElementById('custom-dept-wrapper');
            const input = document.getElementById('custom_department');
            if (dept === 'Other') { custom.style.display = 'block'; input.required = true; } 
            else { custom.style.display = 'none'; input.required = false; input.value = ''; }
        }

        function updateFullName() {
            let fn = document.getElementById('first_name').value.trim();
            let mn = document.getElementById('middle_name').value.trim();
            let sn = document.getElementById('surname').value.trim();
            let fullName = fn;
            if (mn) fullName += ' ' + mn;
            if (sn) fullName += ' ' + sn;
            document.getElementById('name').value = fullName;
        }

        function validateStep1() {
            updateFullName();
            const fn = document.getElementById('first_name').value.trim();
            const sn = document.getElementById('surname').value.trim();
            const role = document.querySelector('input[name="role"]:checked');
            if (!fn || !sn) return "Please enter your first name and surname.";
            if (!role) return "Please select a role.";

            if (role.value === 'student') {
                const sn = document.getElementById('student_number').value.trim();
                if (!/^[a-zA-Z0-9]{7}$/.test(sn)) return "Student number must be exactly 7 characters and contain only letters and numbers.";
                if (!document.getElementById('course').value || !document.getElementById('year_level').value || !document.getElementById('semester').value) {
                    return "Please fill in all student details.";
                }
            }
            return null;
        }

        function goToStep(step) {
            hideAlert();
            if (step === 2) {
                const err = validateStep1();
                if (err) { showAlert(err); return; }
                document.getElementById('indicator-1').classList.add('completed');
                document.getElementById('div-1').classList.add('active');
                document.getElementById('indicator-2').classList.add('active');
                document.getElementById('step-desc').textContent = "Secure your account";
            } else if (step === 1) {
                document.getElementById('indicator-1').classList.remove('completed');
                document.getElementById('div-1').classList.remove('active');
                document.getElementById('indicator-2').classList.remove('active');
                document.getElementById('step-desc').textContent = "Tell us a bit about yourself";
            } else if (step === 3) {
                document.getElementById('indicator-2').classList.add('completed');
                document.getElementById('div-2').classList.add('active');
                document.getElementById('indicator-3').classList.add('active');
                document.getElementById('step-desc').textContent = "Verify your email";
                document.getElementById('auth-links').style.display = 'none';
            }

            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            document.getElementById('step-' + step).classList.add('active');
        }

        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            const icon = btn.querySelector('i');
            if (input.type === 'password') { input.type = 'text'; icon.className = 'bi bi-eye'; }
            else { input.type = 'password'; icon.className = 'bi bi-eye-slash'; }
        }

        // OTP logic
        const otpBoxes = document.querySelectorAll('.otp-box');
        otpBoxes.forEach((box, i) => {
            box.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
            });
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && i > 0) otpBoxes[i - 1].focus();
            });
            box.addEventListener('paste', function(e) {
                e.preventDefault();
                const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
                pasteData.split('').forEach((char, index) => {
                    if(otpBoxes[index]) otpBoxes[index].value = char;
                });
                if (pasteData.length === 6) otpBoxes[5].focus();
            });
        });

        let timerInterval;
        function startTimer() {
            clearInterval(timerInterval);
            let time = 600;
            const display = document.getElementById('timer');
            display.style.color = 'var(--accent)';
            timerInterval = setInterval(() => {
                const m = Math.floor(time / 60);
                const s = time % 60;
                display.textContent = m + ':' + (s < 10 ? '0' : '') + s;
                if (--time < 0) {
                    clearInterval(timerInterval);
                    display.textContent = 'Expired';
                    display.style.color = '#ef4444';
                }
            }, 1000);
        }

        function sendOtp() {
            hideAlert();
            const email = document.getElementById('email').value.trim();
            const pass = document.getElementById('password').value;
            const passConf = document.getElementById('password_confirmation').value;

            if (!email || !pass) { showAlert("Email and password are required."); return; }
            if (pass !== passConf) { showAlert("Passwords do not match."); return; }
            if (pass.length < 8) { showAlert("Password must be at least 8 characters."); return; }

            const btn = document.getElementById('btn-verify');
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending...';

            fetch('{{ route("otp.register.send") }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email })
            }).then(async r => {
                const isJson = r.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await r.json() : null;
                if (!r.ok) {
                    const errorMsg = data && data.message ? data.message : 'Server error ' + r.status;
                    throw new Error(errorMsg);
                }
                return data;
            }).then(data => {
                btn.disabled = false; btn.innerHTML = originalBtnHtml;
                if (data.success) {
                    document.getElementById('display-email').textContent = email;
                    goToStep(3);
                    startTimer();
                    otpBoxes[0].focus();
                } else {
                    showAlert(data.message || 'Failed to send OTP.');
                }
            }).catch((err) => {
                btn.disabled = false; btn.innerHTML = originalBtnHtml;
                console.error('OTP send error:', err);
                showAlert(err.message || "Network error. Please try again.");
            });
        }

        function verifyOtpAndSubmit() {
            hideAlert();
            const otp = Array.from(otpBoxes).map(b => b.value).join('');
            if (otp.length !== 6) { showAlert("Please enter the full 6-digit code."); return; }

            const email = document.getElementById('email').value.trim();
            const btn = document.getElementById('btn-submit');
            const originalBtnHtml = btn.innerHTML;
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';

            fetch('{{ route("otp.register.verify") }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, otp: otp })
            }).then(async r => {
                const isJson = r.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await r.json() : null;
                if (!r.ok) {
                    const errorMsg = data && data.message ? data.message : 'Server error ' + r.status;
                    throw new Error(errorMsg);
                }
                return data;
            }).then(data => {
                if (data.success) {
                    document.getElementById('hidden_otp').value = otp;
                    clearInterval(timerInterval);
                    document.getElementById('indicator-3').classList.add('completed');
                    
                    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
                    document.getElementById('step-success').classList.add('active');
                    document.getElementById('step-desc').textContent = "Almost there...";
                    
                    setTimeout(() => {
                        updateFullName(); // Ensure name is up-to-date before submit
                        document.getElementById('regForm').submit();
                    }, 1200);
                } else {
                    btn.disabled = false; btn.innerHTML = originalBtnHtml;
                    showAlert(data.message || 'Invalid OTP.');
                }
            }).catch((err) => {
                btn.disabled = false; btn.innerHTML = originalBtnHtml;
                console.error('OTP verify error:', err);
                showAlert(err.message || "Network error. Please try again.");
            });
        }

        // Init on load
        document.addEventListener('DOMContentLoaded', () => {
            toggleFields();
            toggleCustomDept();
        });
    </script>
</body>
</html>
