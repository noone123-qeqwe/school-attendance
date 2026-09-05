<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Create Account — {{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    @include('partials.pwa-tags')
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
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 84px 20px calc(48px + env(safe-area-inset-bottom, 16px));
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
            padding: 16px 40px;
            background: rgba(10, 3, 5, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            transition: all 0.3s ease;
        }
        .brand-logo {
            font-family: 'Outfit', sans-serif; font-size: 1.05rem; font-weight: 700; color: white;
            display: flex; align-items: center; gap: 10px; text-decoration: none;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        .brand-logo i { color: var(--accent); font-size: 1.3rem; }

        /* Main Container */
        .auth-container {
            position: relative; z-index: 10;
            width: 100%; max-width: 760px;
            padding: 0;
            margin: auto 0;
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
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-bottom: 24px;
        }
        .role-radio {
            position: absolute;
            opacity: 0;
            pointer-events: none;
            width: 0;
            height: 0;
            margin: 0;
        }
        .role-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 18px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            user-select: none;
            -webkit-user-select: none;
        }
        .role-card i {
            font-size: 1.6rem;
            color: var(--text-muted);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .role-card span {
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--text-muted);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .role-card:hover { 
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.2); 
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        /* Single Selected State - Strictly driven by checked radio sibling */
        .role-radio:checked + .role-card {
            background: rgba(232, 192, 100, 0.08) !important;
            border-color: var(--accent) !important;
            box-shadow: inset 0 0 0 1px var(--accent), 0 10px 20px rgba(232,192,100,0.1) !important;
            transform: translateY(-2px);
        }
        .role-radio:checked + .role-card i,
        .role-radio:checked + .role-card span {
            color: var(--accent) !important;
            transform: scale(1.05);
        }
        .role-selector.is-invalid .role-card {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.05) !important;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.2) !important;
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
        .form-floating-custom.is-invalid input,
        .form-floating-custom.is-invalid select {
            border-color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.05) !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15) !important;
        }
        .form-floating-custom.is-invalid label {
            color: #ef4444 !important;
        }
        .form-floating-custom.is-valid input,
        .form-floating-custom.is-valid select {
            border-color: rgba(34, 197, 94, 0.7) !important;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.12) !important;
        }
        .form-floating-custom.is-valid label {
            color: #4ade80 !important;
        }
        .field-feedback {
            font-size: 0.8rem;
            margin-top: 5px;
            margin-bottom: 10px;
            padding-left: 6px;
            display: none;
            align-items: center;
            gap: 6px;
            line-height: 1.3;
        }
        .field-feedback.error {
            display: flex;
            color: #f87171;
            animation: fieldFadeIn 0.2s ease-in;
        }
        .field-feedback.valid {
            display: flex;
            color: #4ade80;
            animation: fieldFadeIn 0.2s ease-in;
        }
        @keyframes fieldFadeIn {
            from { opacity: 0; transform: translateY(-3px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-floating-custom input:disabled {
            background: rgba(255, 255, 255, 0.02) !important;
            border-color: rgba(255, 255, 255, 0.05) !important;
            color: rgba(255, 255, 255, 0.3) !important;
            cursor: not-allowed !important;
        }
        .form-floating-custom input:disabled ~ label {
            color: rgba(255, 255, 255, 0.35) !important;
        }

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
            touch-action: manipulation;
        }
        .btn-premium.btn-loading {
            opacity: 0.88;
            cursor: wait !important;
            pointer-events: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-hover)); color: white;
            box-shadow: 0 8px 20px rgba(138, 21, 21, 0.3), inset 0 1px 0 rgba(255,255,255,0.2);
        }
        .btn-primary::after {
            content: ''; position: absolute; top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.25), transparent);
            transform: skewX(-20deg); transition: 0.5s;
            pointer-events: none;
        }
        .btn-primary i { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); pointer-events: none; }
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
            body { 
                padding: 68px 12px 28px; 
            }
            .card-header-premium { 
                padding: 32px 20px 20px; 
            }
            .card-header-premium h2 { 
                font-size: 1.55rem; 
            }
            .card-header-premium p { 
                font-size: 0.85rem; 
            }
            .card-body-premium { 
                padding: 16px 20px 36px; 
            }
            .stepper { 
                padding: 20px 16px; 
            }
            .top-bar { 
                padding: 11px 16px; 
                justify-content: center;
                background: rgba(10, 3, 5, 0.9);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            }
            .brand-logo { 
                font-size: 0.78rem; 
                gap: 8px;
                letter-spacing: 0.3px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 100%;
                justify-content: center;
            }
            .brand-logo i { 
                font-size: 1.05rem; 
            }
            .otp-box { width: 42px; height: 52px; font-size: 1.25rem; gap: 6px; }
            .role-selector { gap: 8px; }
            .role-card { padding: 12px 6px; }
            .role-card i { font-size: 1.2rem; }
            .role-card span { font-size: 0.75rem; }
        }

        @media (max-width: 380px) {
            body { padding: 62px 8px 24px; }
            .top-bar { padding: 9px 12px; }
            .brand-logo { font-size: 0.71rem; gap: 6px; }
            .brand-logo i { font-size: 0.95rem; }
            .card-header-premium h2 { font-size: 1.4rem; }
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
            <span>{{ config('app.name') }}</span>
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
                <div id="js-success" class="alert-modern" style="display:none; background:rgba(34,197,94,0.12); border-left:4px solid #22c55e; color:#86efac;"></div>

                <form id="regForm" method="POST" action="{{ route('register.submit') }}" novalidate>
                    @csrf
                    
                    <!-- STEP 1: Basic Info -->
                    <div id="step-1" class="form-step active">
                        <input type="hidden" name="name" id="name" value="{{ old('name') }}">
                        
                        <div class="form-floating-custom mb-1" id="wrap-first_name">
                            <input type="text" name="first_name" id="first_name" placeholder=" " value="{{ old('first_name') }}" required autocomplete="given-name">
                            <label for="first_name">First Name</label>
                        </div>
                        <div class="field-feedback" id="feedback-first_name"></div>

                        <div class="form-floating-custom mb-1" id="wrap-middle_name">
                            <input type="text" name="middle_name" id="middle_name" placeholder=" " value="{{ old('middle_name') }}" autocomplete="additional-name">
                            <label for="middle_name">Middle Name (Optional)</label>
                        </div>
                        <div class="field-feedback" id="feedback-middle_name"></div>

                        <div class="d-flex align-items-center gap-2 mb-3 mt-1 px-1" style="user-select: none;">
                            <input type="checkbox" id="no_middle_name" name="no_middle_name" value="1" {{ old('no_middle_name') ? 'checked' : '' }} style="width: 16px; height: 16px; accent-color: var(--accent); cursor: pointer; border-radius: 4px;">
                            <label for="no_middle_name" style="font-size: 0.82rem; color: var(--text-muted); cursor: pointer; margin: 0; font-weight: 500;">
                                I do not have a middle name
                            </label>
                        </div>

                        <div class="form-floating-custom mb-1" id="wrap-surname">
                            <input type="text" name="surname" id="surname" placeholder=" " value="{{ old('surname') }}" required autocomplete="family-name">
                            <label for="surname">Surname</label>
                        </div>
                        <div class="field-feedback" id="feedback-surname"></div>

                        <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 10px; margin-top: 14px; display: block; font-weight: 500;">I am registering as a:</label>
                        <div class="role-selector" id="wrap-role">
                            <input type="radio" name="role" id="role_student" value="student" class="role-radio" {{ old('role') == 'student' ? 'checked' : '' }} required>
                            <label for="role_student" class="role-card" id="role-card-student">
                                <i class="bi bi-mortarboard"></i>
                                <span>Student</span>
                            </label>

                            <input type="radio" name="role" id="role_parent" value="parent" class="role-radio" {{ old('role') == 'parent' ? 'checked' : '' }}>
                            <label for="role_parent" class="role-card" id="role-card-parent">
                                <i class="bi bi-people"></i>
                                <span>Parent</span>
                            </label>
                        </div>
                        <div class="field-feedback" id="feedback-role"></div>

                        <!-- Dynamic Fields -->
                        <div id="dynamic-fields" style="{{ old('role') == 'student' ? 'display:block;' : 'display:none;' }}">
                            <!-- Student Specific -->
                            <div id="student-fields" style="{{ old('role') == 'student' ? 'display:block;' : 'display:none;' }}">
                                <div class="form-floating-custom mb-1" id="wrap-student_number">
                                    <input type="text" name="student_number" id="student_number" placeholder=" " maxlength="7" value="{{ old('student_number') }}">
                                    <label for="student_number">Student ID (7 chars, e.g. 2101234)</label>
                                </div>
                                <div class="field-feedback" id="feedback-student_number"></div>

                                <div class="row g-2 mb-1">
                                    <div class="col-7">
                                        <div class="form-floating-custom mb-0" id="wrap-course">
                                            <select name="course" id="course">
                                                <option value="" disabled {{ old('course') ? '' : 'selected' }}></option>
                                                <option value="BSCS" {{ old('course', 'BSCS') == 'BSCS' ? 'selected' : '' }}>BSCS</option>
                                                <option value="BSIT" {{ old('course') == 'BSIT' ? 'selected' : '' }}>BSIT</option>
                                                <option value="BSIS" {{ old('course') == 'BSIS' ? 'selected' : '' }}>BSIS</option>
                                            </select>
                                            <label for="course">Course</label>
                                            <i class="bi bi-chevron-down select-arrow"></i>
                                        </div>
                                        <div class="field-feedback" id="feedback-course"></div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-floating-custom mb-0" id="wrap-year_level">
                                            <select name="year_level" id="year_level">
                                                <option value="" disabled {{ old('year_level') ? '' : 'selected' }}></option>
                                                @foreach([1,2,3,4] as $y)
                                                <option value="{{ $y }}" {{ old('year_level')==$y?'selected':'' }}>{{ $y }}{{ $y==1?'st':($y==2?'nd':($y==3?'rd':'th')) }}</option>
                                                @endforeach
                                            </select>
                                            <label for="year_level">Year Level</label>
                                            <i class="bi bi-chevron-down select-arrow"></i>
                                        </div>
                                        <div class="field-feedback" id="feedback-year_level"></div>
                                    </div>
                                </div>

                                <div class="form-floating-custom mb-1 mt-2" id="wrap-semester">
                                    <select name="semester" id="semester">
                                        <option value="" disabled {{ old('semester') ? '' : 'selected' }}></option>
                                        <option value="1" {{ old('semester', '1')=='1'?'selected':'' }}>1st Semester</option>
                                        <option value="2" {{ old('semester')=='2'?'selected':'' }}>2nd Semester</option>
                                        <option value="Summer" {{ old('semester')=='Summer'?'selected':'' }}>Summer</option>
                                    </select>
                                    <label for="semester">Semester</label>
                                    <i class="bi bi-chevron-down select-arrow"></i>
                                </div>
                                <div class="field-feedback" id="feedback-semester"></div>

                            </div>
                        </div>

                        <button type="button" class="btn-premium btn-primary mt-3" id="btn-continue-step1" aria-label="Continue to next registration step">
                            <span class="btn-text">Continue</span> <i class="bi bi-arrow-right ms-1 btn-icon"></i>
                        </button>
                    </div>

                    <!-- STEP 2: Account Details -->
                    <div id="step-2" class="form-step">
                        <div class="form-floating-custom mb-1" id="wrap-email">
                            <input type="email" name="email" id="email" placeholder=" " value="{{ old('email') }}" required autocomplete="email">
                            <label for="email">Email Address</label>
                        </div>
                        <div class="field-feedback" id="feedback-email"></div>
                        
                        <div class="form-floating-custom mb-1 mt-2" id="wrap-password">
                            <input type="password" name="password" id="password" placeholder=" " required autocomplete="new-password">
                            <label for="password">Password (Min 8 chars)</label>
                            <button type="button" class="eye-btn" id="btn-toggle-password" aria-label="Toggle password visibility"><i class="bi bi-eye-slash"></i></button>
                        </div>
                        <div class="field-feedback" id="feedback-password"></div>

                        <div class="form-floating-custom mb-1 mt-2" id="wrap-password_confirmation">
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder=" " required autocomplete="new-password">
                            <label for="password_confirmation">Confirm Password</label>
                            <button type="button" class="eye-btn" id="btn-toggle-password-conf" aria-label="Toggle password confirmation visibility"><i class="bi bi-eye-slash"></i></button>
                        </div>
                        <div class="field-feedback" id="feedback-password_confirmation"></div>

                        <div class="btn-group-row">
                            <button type="button" class="btn-premium btn-secondary" id="btn-back-step2" aria-label="Back to Step 1">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <button type="button" class="btn-premium btn-primary" id="btn-verify" aria-label="Send email verification code">
                                <span class="btn-text">Verify Email</span> <i class="bi bi-envelope-check ms-1 btn-icon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 3: OTP Verification -->
                    <div id="step-3" class="form-step">
                        <div class="text-center mb-4">
                            <div style="display:inline-flex; padding:16px; border-radius:50%; background:rgba(232, 192, 100, 0.15); color:var(--accent); margin-bottom:16px;">
                                <i class="bi bi-shield-check" style="font-size:2rem; line-height:1;"></i>
                            </div>
                            <h4 style="font-family:'Outfit'; font-weight:700; font-size:1.3rem; letter-spacing:0.5px;">VERIFY YOUR EMAIL</h4>
                            <p style="color:var(--text-muted); font-size:0.92rem; margin:6px 0 0;">We sent a verification code to:<br><b id="display-email" style="color:white; font-size:1.05rem;"></b></p>
                            <p style="color:rgba(255,255,255,0.65); font-size:0.8rem; margin-top:6px;">⚠️ Check your <strong>Spam / Junk folder</strong> if the email does not appear in your inbox.</p>
                        </div>

                        <div class="otp-container">
                            @for($i=1; $i<=6; $i++)
                                <input type="text" class="otp-box" maxlength="1" inputmode="numeric" id="otp-{{$i}}" autocomplete="off">
                            @endfor
                        </div>
                        <input type="hidden" name="email_otp" id="hidden_otp">

                        <div class="text-center mb-4 mt-3" style="font-size:0.85rem; color:var(--text-muted);">
                            Code expires in <span id="timer" style="color:var(--accent); font-weight:700; font-variant-numeric: tabular-nums;">10:00</span>
                        </div>

                        <div class="btn-group-row">
                            <button type="button" class="btn-premium btn-secondary" id="btn-back-step3" aria-label="Back to Step 2">
                                <i class="bi bi-arrow-left"></i>
                            </button>
                            <button type="button" class="btn-premium btn-primary" id="btn-submit" aria-label="Verify OTP and complete registration">
                                <span class="btn-text">VERIFY</span> <i class="bi bi-check-circle ms-1 btn-icon"></i>
                            </button>
                        </div>
                        
                        <div class="text-center mt-4 pt-3" style="border-top: 1px solid rgba(255,255,255,0.06);">
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px;">Didn't receive the code?</p>
                            <button type="button" class="btn-premium btn-secondary py-2 px-4 d-inline-flex align-items-center justify-content-center" id="btn-resend-otp" aria-label="Resend verification code" style="width:auto; font-size:0.9rem; margin:0 auto;">
                                <i class="bi bi-arrow-clockwise me-1"></i> RESEND OTP
                            </button>
                            <div id="resend-cooldown-text" style="font-size:0.82rem; color:var(--accent); margin-top:8px; display:none; font-weight:500;">
                                Resend available in <span id="resend-seconds" style="font-weight:700;">30</span> seconds
                            </div>
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

    <script @cspNonce>
        // Alert helpers
        function showAlert(msg) {
            const alertEl = document.getElementById('js-alert');
            const successEl = document.getElementById('js-success');
            if (successEl) successEl.style.display = 'none';
            if (!alertEl) return;
            alertEl.innerHTML = `<i class="bi bi-exclamation-triangle-fill mt-1"></i><div>${msg}</div>`;
            alertEl.style.display = 'flex';
            alertEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        function hideAlert() { 
            const alertEl = document.getElementById('js-alert');
            const successEl = document.getElementById('js-success');
            if (alertEl) alertEl.style.display = 'none';
            if (successEl) successEl.style.display = 'none';
        }
        function showSuccessAlert(msg) {
            const alertEl = document.getElementById('js-alert');
            const successEl = document.getElementById('js-success');
            if (alertEl) alertEl.style.display = 'none';
            if (!successEl) return;
            successEl.innerHTML = `<i class="bi bi-check-circle-fill mt-1"></i><div>${msg}</div>`;
            successEl.style.display = 'flex';
            successEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        // Field-level inline feedback
        function setFieldFeedback(fieldId, isValid, message) {
            const wrap = document.getElementById('wrap-' + fieldId) || document.getElementById(fieldId)?.closest('.form-floating-custom');
            const feedback = document.getElementById('feedback-' + fieldId);

            if (wrap) {
                wrap.classList.remove('is-invalid', 'is-valid');
                if (isValid === false) {
                    wrap.classList.add('is-invalid');
                } else if (isValid === true) {
                    wrap.classList.add('is-valid');
                }
            }

            if (feedback) {
                feedback.className = 'field-feedback';
                if (isValid === false) {
                    feedback.classList.add('error');
                    feedback.innerHTML = `<i class="bi bi-x-circle-fill"></i> <span>${message || 'Invalid value.'}</span>`;
                } else if (isValid === true) {
                    feedback.classList.add('valid');
                    feedback.innerHTML = `<i class="bi bi-check-circle-fill"></i> <span>${message || 'Valid'}</span>`;
                } else {
                    feedback.innerHTML = '';
                }
            }
        }

        function clearFieldFeedback(fieldId) {
            const wrap = document.getElementById('wrap-' + fieldId) || document.getElementById(fieldId)?.closest('.form-floating-custom');
            const feedback = document.getElementById('feedback-' + fieldId);
            if (wrap) wrap.classList.remove('is-invalid', 'is-valid');
            if (feedback) {
                feedback.className = 'field-feedback';
                feedback.innerHTML = '';
            }
        }

        // Role Selection
        function selectRole(role) {
            const radio = document.querySelector(`input[name="role"][value="${role}"]`);
            if (radio) {
                radio.checked = true;
            }
            toggleFields(role);
            clearFieldFeedback('role');
        }

        function toggleFields(explicitRole) {
            const roleRadio = document.querySelector('input[name="role"]:checked');
            const role = explicitRole || (roleRadio ? roleRadio.value : null);
            const dynamicFields = document.getElementById('dynamic-fields');
            const studentFields = document.getElementById('student-fields');

            const sNum = document.getElementById('student_number');
            const crs = document.getElementById('course');
            const yLvl = document.getElementById('year_level');
            const sem = document.getElementById('semester');

            if (role === 'student') {
                if (dynamicFields) dynamicFields.style.display = 'block';
                if (studentFields) studentFields.style.display = 'block';
                if (sNum) sNum.required = true;
                if (crs) crs.required = true;
                if (yLvl) yLvl.required = true;
                if (sem) sem.required = true;
            } else {
                if (studentFields) studentFields.style.display = 'none';
                if (dynamicFields) dynamicFields.style.display = 'none';
                if (sNum) {
                    sNum.required = false;
                    clearFieldFeedback('student_number');
                }
                if (crs) {
                    crs.required = false;
                    clearFieldFeedback('course');
                }
                if (yLvl) {
                    yLvl.required = false;
                    clearFieldFeedback('year_level');
                }
                if (sem) {
                    sem.required = false;
                    clearFieldFeedback('semester');
                }
            }
        }

        // Full Name Auto-composition
        function updateFullName() {
            let fn = document.getElementById('first_name')?.value.trim() || '';
            let noMn = document.getElementById('no_middle_name')?.checked;
            let mn = noMn ? '' : (document.getElementById('middle_name')?.value.trim() || '');
            if (mn.toUpperCase() === 'N/A') mn = '';
            let sn = document.getElementById('surname')?.value.trim() || '';
            let fullName = fn;
            if (mn) fullName += ' ' + mn;
            if (sn) fullName += ' ' + sn;
            const nameEl = document.getElementById('name');
            if (nameEl) nameEl.value = fullName;
        }

        function toggleNoMiddleName(checkbox) {
            const mnInput = document.getElementById('middle_name');
            if (!mnInput) return;
            if (checkbox && checkbox.checked) {
                mnInput.value = '';
                mnInput.disabled = true;
                clearFieldFeedback('middle_name');
            } else {
                mnInput.disabled = false;
                mnInput.focus();
            }
            updateFullName();
        }

        // Live Single-Field Validation
        function validateSingleField(fieldId, showValidState = false) {
            const el = document.getElementById(fieldId);
            if (!el) return true;

            if (fieldId === 'first_name') {
                const val = el.value.trim();
                if (!val) {
                    setFieldFeedback('first_name', false, 'Please enter your first name.');
                    return false;
                }
                if (showValidState) setFieldFeedback('first_name', true, 'Valid');
                else clearFieldFeedback('first_name');
                return true;
            }

            if (fieldId === 'surname') {
                const val = el.value.trim();
                if (!val) {
                    setFieldFeedback('surname', false, 'Please enter your surname.');
                    return false;
                }
                if (showValidState) setFieldFeedback('surname', true, 'Valid');
                else clearFieldFeedback('surname');
                return true;
            }

            if (fieldId === 'student_number') {
                const val = el.value.trim();
                if (!val) {
                    setFieldFeedback('student_number', false, 'Please enter your Student ID.');
                    return false;
                }
                if (!/^[a-zA-Z0-9]{7}$/.test(val)) {
                    setFieldFeedback('student_number', false, 'Student ID must contain 7 characters.');
                    return false;
                }
                setFieldFeedback('student_number', true, 'Valid');
                return true;
            }

            if (fieldId === 'course') {
                if (!el.value) {
                    setFieldFeedback('course', false, 'Please select your course.');
                    return false;
                }
                setFieldFeedback('course', true, 'Valid');
                return true;
            }

            if (fieldId === 'year_level') {
                if (!el.value) {
                    setFieldFeedback('year_level', false, 'Please select your year level.');
                    return false;
                }
                setFieldFeedback('year_level', true, 'Valid');
                return true;
            }

            if (fieldId === 'semester') {
                if (!el.value) {
                    setFieldFeedback('semester', false, 'Please select your semester.');
                    return false;
                }
                setFieldFeedback('semester', true, 'Valid');
                return true;
            }

            return true;
        }

        // Complete Step 1 Validation
        function validateStep1Full(showInlineErrors = true) {
            updateFullName();
            let isValid = true;
            let firstErrorMsg = null;

            const fn = document.getElementById('first_name');
            const mn = document.getElementById('middle_name');
            const noMn = document.getElementById('no_middle_name');
            const sn = document.getElementById('surname');
            const roleChecked = document.querySelector('input[name="role"]:checked');

            // 1. First Name
            const fnVal = fn ? fn.value.trim() : '';
            if (!fnVal) {
                if (showInlineErrors) setFieldFeedback('first_name', false, 'Please enter your first name.');
                isValid = false;
                if (!firstErrorMsg) firstErrorMsg = 'Please enter your first name.';
            } else {
                if (showInlineErrors) setFieldFeedback('first_name', true, 'Valid');
            }

            // 2. Middle Name (Optional)
            if (noMn && noMn.checked) {
                clearFieldFeedback('middle_name');
            } else if (mn && mn.value.trim()) {
                if (showInlineErrors) setFieldFeedback('middle_name', true, 'Valid');
            } else {
                clearFieldFeedback('middle_name');
            }

            // 3. Surname
            const snVal = sn ? sn.value.trim() : '';
            if (!snVal) {
                if (showInlineErrors) setFieldFeedback('surname', false, 'Please enter your surname.');
                isValid = false;
                if (!firstErrorMsg) firstErrorMsg = 'Please enter your surname.';
            } else {
                if (showInlineErrors) setFieldFeedback('surname', true, 'Valid');
            }

            // 4. Role
            if (!roleChecked) {
                const wrapRole = document.getElementById('wrap-role');
                if (wrapRole) wrapRole.classList.add('is-invalid');
                if (showInlineErrors) setFieldFeedback('role', false, 'Please select whether you are registering as a Student or Parent.');
                isValid = false;
                if (!firstErrorMsg) firstErrorMsg = 'Please select whether you are registering as a Student or Parent.';
            } else {
                const wrapRole = document.getElementById('wrap-role');
                if (wrapRole) wrapRole.classList.remove('is-invalid');
                clearFieldFeedback('role');

                // 5. Dynamic fields if Student
                if (roleChecked.value === 'student') {
                    const sNum = document.getElementById('student_number');
                    const crs = document.getElementById('course');
                    const yLvl = document.getElementById('year_level');
                    const sem = document.getElementById('semester');

                    // Student ID
                    const sNumVal = sNum ? sNum.value.trim() : '';
                    if (!sNumVal) {
                        if (showInlineErrors) setFieldFeedback('student_number', false, 'Please enter your Student ID.');
                        isValid = false;
                        if (!firstErrorMsg) firstErrorMsg = 'Please enter your 7-character Student ID.';
                    } else if (!/^[a-zA-Z0-9]{7}$/.test(sNumVal)) {
                        if (showInlineErrors) setFieldFeedback('student_number', false, 'Student ID must contain 7 characters.');
                        isValid = false;
                        if (!firstErrorMsg) firstErrorMsg = 'Student ID must contain 7 characters.';
                    } else {
                        if (showInlineErrors) setFieldFeedback('student_number', true, 'Valid');
                    }

                    // Course
                    const crsVal = crs ? crs.value : '';
                    if (!crsVal) {
                        if (showInlineErrors) setFieldFeedback('course', false, 'Please select your course.');
                        isValid = false;
                        if (!firstErrorMsg) firstErrorMsg = 'Please select your course.';
                    } else {
                        if (showInlineErrors) setFieldFeedback('course', true, 'Valid');
                    }

                    // Year Level
                    const yLvlVal = yLvl ? yLvl.value : '';
                    if (!yLvlVal) {
                        if (showInlineErrors) setFieldFeedback('year_level', false, 'Please select your year level.');
                        isValid = false;
                        if (!firstErrorMsg) firstErrorMsg = 'Please select your year level.';
                    } else {
                        if (showInlineErrors) setFieldFeedback('year_level', true, 'Valid');
                    }

                    // Semester
                    const semVal = sem ? sem.value : '';
                    if (!semVal) {
                        if (showInlineErrors) setFieldFeedback('semester', false, 'Please select your semester.');
                        isValid = false;
                        if (!firstErrorMsg) firstErrorMsg = 'Please select your semester.';
                    } else {
                        if (showInlineErrors) setFieldFeedback('semester', true, 'Valid');
                    }
                }
            }

            if (!isValid && firstErrorMsg && showInlineErrors) {
                showAlert(firstErrorMsg);
            } else if (isValid) {
                hideAlert();
            }

            return isValid;
        }

        // Double-tap guarded Continue Handler
        let isStep1Processing = false;

        function handleContinueStep1(e) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Double-tap protection
            if (isStep1Processing) {
                console.warn('Step 1 continue action already processing. Ignoring duplicate tap.');
                return;
            }

            const btn = document.getElementById('btn-continue-step1');
            const originalHtml = btn ? btn.innerHTML : '<span class="btn-text">Continue</span> <i class="bi bi-arrow-right ms-1 btn-icon"></i>';

            // Show immediate loading state
            if (btn) {
                btn.classList.add('btn-loading');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Processing...';
            }
            isStep1Processing = true;

            setTimeout(() => {
                const isValid = validateStep1Full(true);

                if (!isValid) {
                    // Restore button on validation failure
                    if (btn) {
                        btn.classList.remove('btn-loading');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                    isStep1Processing = false;

                    // Scroll to first invalid field
                    const firstInvalid = document.querySelector('#step-1 .is-invalid, #step-1 .field-feedback.error');
                    if (firstInvalid) {
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        const input = firstInvalid.matches('input, select') ? firstInvalid : firstInvalid.querySelector('input, select');
                        if (input) input.focus();
                    }
                    return;
                }

                // Proceed to Step 2
                goToStep(2);

                // Restore button for when user navigates back
                if (btn) {
                    btn.classList.remove('btn-loading');
                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }
                isStep1Processing = false;
            }, 80);
        }

        // Stepper Navigation
        function goToStep(step) {
            hideAlert();
            if (step === 2) {
                document.getElementById('indicator-1').classList.add('completed');
                document.getElementById('indicator-1').classList.remove('active');
                document.getElementById('div-1').classList.add('active');
                document.getElementById('indicator-2').classList.add('active');
                document.getElementById('step-desc').textContent = "Secure your account";
            } else if (step === 1) {
                document.getElementById('indicator-1').classList.remove('completed');
                document.getElementById('indicator-1').classList.add('active');
                document.getElementById('div-1').classList.remove('active');
                document.getElementById('indicator-2').classList.remove('active');
                document.getElementById('step-desc').textContent = "Tell us a bit about yourself";
            } else if (step === 3) {
                document.getElementById('indicator-2').classList.add('completed');
                document.getElementById('indicator-2').classList.remove('active');
                document.getElementById('div-2').classList.add('active');
                document.getElementById('indicator-3').classList.add('active');
                document.getElementById('step-desc').textContent = "Verify your email";
                const authLinks = document.getElementById('auth-links');
                if (authLinks) authLinks.style.display = 'none';
            }

            document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
            const nextStepEl = document.getElementById('step-' + step);
            if (nextStepEl) {
                nextStepEl.classList.add('active');
            }

            // Smooth scroll to top of glass card
            const card = document.querySelector('.glass-card');
            if (card) {
                card.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        // Password visibility toggle
        function togglePassword(id, btn) {
            const input = document.getElementById(id);
            if (!input) return;
            const icon = btn ? btn.querySelector('i') : null;
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) icon.className = 'bi bi-eye';
            } else {
                input.type = 'password';
                if (icon) icon.className = 'bi bi-eye-slash';
            }
        }

        // OTP Boxes & Countdown
        const otpBoxes = document.querySelectorAll('.otp-box');
        otpBoxes.forEach((box, i) => {
            box.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                if (this.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
            });
            box.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && i > 0) otpBoxes[i - 1].focus();
                if (e.key === 'Enter') {
                    e.preventDefault();
                    verifyOtpAndSubmit();
                }
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
        function startTimer(duration = 600) {
            clearInterval(timerInterval);
            let time = duration;
            const display = document.getElementById('timer');
            if (!display) return;
            display.style.color = 'var(--accent)';
            const updateDisplay = () => {
                const m = Math.floor(time / 60);
                const s = time % 60;
                display.textContent = m + ':' + (s < 10 ? '0' : '') + s;
            };
            updateDisplay();
            timerInterval = setInterval(() => {
                time--;
                if (time < 0) {
                    clearInterval(timerInterval);
                    display.textContent = 'Expired';
                    display.style.color = '#ef4444';
                } else {
                    updateDisplay();
                }
            }, 1000);
        }

        let resendCooldownInterval;
        function startResendCooldown(seconds = 30) {
            clearInterval(resendCooldownInterval);
            const resendBtn = document.getElementById('btn-resend-otp');
            const cooldownText = document.getElementById('resend-cooldown-text');
            const cooldownSecondsSpan = document.getElementById('resend-seconds');

            if (!resendBtn || !cooldownText || !cooldownSecondsSpan) return;

            resendBtn.disabled = true;
            cooldownText.style.display = 'block';
            let remaining = seconds;
            cooldownSecondsSpan.textContent = remaining;

            resendCooldownInterval = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(resendCooldownInterval);
                    resendBtn.disabled = false;
                    cooldownText.style.display = 'none';
                } else {
                    cooldownSecondsSpan.textContent = remaining;
                }
            }, 1000);
        }

        let isSendingOtp = false;

        function sendOtp() {
            if (isSendingOtp) {
                console.warn('OTP send already in progress. Ignoring duplicate click.');
                return;
            }

            hideAlert();
            clearFieldFeedback('email');
            clearFieldFeedback('password');
            clearFieldFeedback('password_confirmation');

            const email = document.getElementById('email')?.value.trim() || '';
            const pass = document.getElementById('password')?.value || '';
            const passConf = document.getElementById('password_confirmation')?.value || '';

            let hasStep2Error = false;
            if (!email) {
                setFieldFeedback('email', false, 'Please enter your email address.');
                hasStep2Error = true;
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                setFieldFeedback('email', false, 'Please enter a valid email address.');
                hasStep2Error = true;
            } else {
                setFieldFeedback('email', true, 'Valid');
            }

            if (!pass) {
                setFieldFeedback('password', false, 'Please enter a password.');
                hasStep2Error = true;
            } else if (pass.length < 8) {
                setFieldFeedback('password', false, 'Password must be at least 8 characters.');
                hasStep2Error = true;
            } else {
                setFieldFeedback('password', true, 'Valid');
            }

            if (!passConf) {
                setFieldFeedback('password_confirmation', false, 'Please confirm your password.');
                hasStep2Error = true;
            } else if (pass !== passConf) {
                setFieldFeedback('password_confirmation', false, 'Passwords do not match.');
                hasStep2Error = true;
            } else {
                setFieldFeedback('password_confirmation', true, 'Valid');
            }

            if (hasStep2Error) {
                showAlert('Please correct the highlighted errors before continuing.');
                return;
            }

            const requestId = (typeof crypto !== 'undefined' && crypto.randomUUID)
                ? crypto.randomUUID()
                : 'req_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

            isSendingOtp = true;
            const btn = document.getElementById('btn-verify');
            const originalBtnHtml = btn ? btn.innerHTML : 'Verify Email';
            if (btn) {
                btn.disabled = true; 
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Sending verification code...';
            }

            fetch('{{ route("otp.register.send") }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'X-Request-Id': requestId,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, request_id: requestId })
            }).then(async r => {
                const isJson = r.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await r.json() : null;
                if (!r.ok) {
                    const retrySecs = data ? (data.retryAfter || data.retry_after || data.cooldown) : null;
                    let errorMsg = data && data.message ? data.message : 'Unable to send verification code. Please try again.';
                    if (r.status === 429 && retrySecs) {
                        errorMsg = `Please wait ${retrySecs} seconds before requesting another code.`;
                    }
                    const err = new Error(errorMsg);
                    err.status = r.status;
                    err.cooldown = retrySecs;
                    throw err;
                }
                return data;
            }).then(data => {
                isSendingOtp = false;
                if (btn) {
                    btn.disabled = false; 
                    btn.innerHTML = originalBtnHtml;
                }
                if (data.success) {
                    document.getElementById('display-email').textContent = email;
                    goToStep(3);
                    startTimer(600);
                    startResendCooldown(data.cooldown || data.retryAfter || 30);
                    otpBoxes.forEach(b => b.value = '');
                    if (otpBoxes[0]) otpBoxes[0].focus();
                } else {
                    showAlert(data.message || 'Unable to send verification code. Please try again.');
                }
            }).catch((err) => {
                isSendingOtp = false;
                if (btn) {
                    btn.disabled = false; 
                    btn.innerHTML = originalBtnHtml;
                }
                console.error('OTP send error:', err);
                showAlert(err.message || "Unable to send verification code. Please try again.");
            });
        }

        function resendOtp() {
            if (isSendingOtp) {
                console.warn('OTP send already in progress. Ignoring duplicate click.');
                return;
            }

            hideAlert();
            const email = document.getElementById('email')?.value.trim();
            if (!email) { showAlert("Email address is required."); return; }

            const requestId = (typeof crypto !== 'undefined' && crypto.randomUUID)
                ? crypto.randomUUID()
                : 'req_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);

            isSendingOtp = true;
            const resendBtn = document.getElementById('btn-resend-otp');
            const originalHtml = resendBtn ? resendBtn.innerHTML : 'RESEND OTP';
            if (resendBtn) {
                resendBtn.disabled = true;
                resendBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending new code...';
            }

            fetch('{{ route("otp.register.send") }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                    'X-Request-Id': requestId,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email, request_id: requestId })
            }).then(async r => {
                const isJson = r.headers.get('content-type')?.includes('application/json');
                const data = isJson ? await r.json() : null;
                if (!r.ok) {
                    const retrySecs = data ? (data.retryAfter || data.retry_after || data.cooldown) : null;
                    let errorMsg = data && data.message ? data.message : 'Unable to send verification code. Please try again.';
                    if (r.status === 429 && retrySecs) {
                        errorMsg = `Please wait ${retrySecs} seconds before requesting another code.`;
                    }
                    const err = new Error(errorMsg);
                    err.status = r.status;
                    err.cooldown = retrySecs;
                    throw err;
                }
                return data;
            }).then(data => {
                isSendingOtp = false;
                if (resendBtn) resendBtn.innerHTML = originalHtml;
                if (data.success) {
                    showSuccessAlert("A new verification code has been sent.");
                    startTimer(600);
                    startResendCooldown(data.cooldown || data.retryAfter || 30);
                    otpBoxes.forEach(b => b.value = '');
                    if (otpBoxes[0]) otpBoxes[0].focus();
                } else {
                    if (resendBtn) resendBtn.disabled = false;
                    showAlert(data.message || 'Unable to send verification code. Please try again.');
                }
            }).catch((err) => {
                isSendingOtp = false;
                if (resendBtn) resendBtn.innerHTML = originalHtml;
                console.error('Resend OTP error:', err);
                showAlert(err.message || 'Unable to send verification code. Please try again.');
                if (err.cooldown) {
                    startResendCooldown(err.cooldown);
                } else if (resendBtn) {
                    resendBtn.disabled = false;
                }
            });
        }

        function verifyOtpAndSubmit() {
            hideAlert();
            const otp = Array.from(otpBoxes).map(b => b.value).join('');
            if (otp.length !== 6) { showAlert("Please enter the full 6-digit verification code."); return; }

            const email = document.getElementById('email')?.value.trim();
            const btn = document.getElementById('btn-submit');
            const originalBtnHtml = btn ? btn.innerHTML : 'VERIFY';
            if (btn) {
                btn.disabled = true; 
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Verifying...';
            }

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
                    const errorMsg = data && data.message ? data.message : 'Invalid verification code.';
                    throw new Error(errorMsg);
                }
                return data;
            }).then(data => {
                if (data.success) {
                    document.getElementById('hidden_otp').value = otp;
                    clearInterval(timerInterval);
                    clearInterval(resendCooldownInterval);
                    showSuccessAlert("Email verified successfully.");
                    document.getElementById('indicator-3').classList.add('completed');
                    
                    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
                    document.getElementById('step-success').classList.add('active');
                    document.getElementById('step-desc').textContent = "Almost there...";
                    
                    setTimeout(() => {
                        updateFullName();
                        const form = document.getElementById('regForm');
                        if (form) {
                            form.dataset.submitting = 'true';
                            form.submit();
                        }
                    }, 800);
                } else {
                    if (btn) {
                        btn.disabled = false; 
                        btn.innerHTML = originalBtnHtml;
                    }
                    showAlert(data.message || 'Invalid verification code.');
                }
            }).catch((err) => {
                if (btn) {
                    btn.disabled = false; 
                    btn.innerHTML = originalBtnHtml;
                }
                console.error('OTP verify error:', err);
                showAlert(err.message || "Invalid verification code.");
            });
        }

        // Attach Event Listeners securely via JavaScript
        function initRegisterPage() {
            // Role radios and cards
            const roleStudent = document.getElementById('role_student');
            const roleParent = document.getElementById('role_parent');
            const cardStudent = document.getElementById('role-card-student');
            const cardParent = document.getElementById('role-card-parent');

            if (roleStudent) {
                roleStudent.addEventListener('change', () => toggleFields('student'));
            }
            if (roleParent) {
                roleParent.addEventListener('change', () => toggleFields('parent'));
            }
            if (cardStudent) {
                cardStudent.addEventListener('click', (e) => {
                    selectRole('student');
                });
            }
            if (cardParent) {
                cardParent.addEventListener('click', (e) => {
                    selectRole('parent');
                });
            }

            // Middle name checkbox
            const noMnCheckbox = document.getElementById('no_middle_name');
            if (noMnCheckbox) {
                noMnCheckbox.addEventListener('change', function() {
                    toggleNoMiddleName(this);
                });
                if (noMnCheckbox.checked) {
                    toggleNoMiddleName(noMnCheckbox);
                }
            }

            // Live field input validation & full name composition
            const fnInput = document.getElementById('first_name');
            if (fnInput) {
                fnInput.addEventListener('input', () => {
                    updateFullName();
                    if (document.getElementById('wrap-first_name')?.classList.contains('is-invalid')) {
                        validateSingleField('first_name', true);
                    }
                });
                fnInput.addEventListener('blur', () => {
                    if (fnInput.value.trim()) validateSingleField('first_name', true);
                });
            }

            const mnInput = document.getElementById('middle_name');
            if (mnInput) {
                mnInput.addEventListener('input', updateFullName);
            }

            const snInput = document.getElementById('surname');
            if (snInput) {
                snInput.addEventListener('input', () => {
                    updateFullName();
                    if (document.getElementById('wrap-surname')?.classList.contains('is-invalid')) {
                        validateSingleField('surname', true);
                    }
                });
                snInput.addEventListener('blur', () => {
                    if (snInput.value.trim()) validateSingleField('surname', true);
                });
            }

            const sNumInput = document.getElementById('student_number');
            if (sNumInput) {
                sNumInput.addEventListener('input', () => {
                    if (document.getElementById('wrap-student_number')?.classList.contains('is-invalid')) {
                        validateSingleField('student_number', true);
                    }
                });
                sNumInput.addEventListener('blur', () => {
                    if (sNumInput.value.trim()) validateSingleField('student_number', true);
                });
            }

            const crsSelect = document.getElementById('course');
            if (crsSelect) {
                crsSelect.addEventListener('change', () => validateSingleField('course', true));
            }

            const yLvlSelect = document.getElementById('year_level');
            if (yLvlSelect) {
                yLvlSelect.addEventListener('change', () => validateSingleField('year_level', true));
            }

            const semSelect = document.getElementById('semester');
            if (semSelect) {
                semSelect.addEventListener('change', () => validateSingleField('semester', true));
            }

            // Step 1: Continue Button
            const btnContinueStep1 = document.getElementById('btn-continue-step1');
            if (btnContinueStep1) {
                btnContinueStep1.addEventListener('click', handleContinueStep1);
            }

            // Step 1: Enter Key submission
            document.querySelectorAll('#step-1 input, #step-1 select').forEach(el => {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        handleContinueStep1(e);
                    }
                });
            });

            // Step 2 Buttons
            const btnBackStep2 = document.getElementById('btn-back-step2');
            if (btnBackStep2) {
                btnBackStep2.addEventListener('click', (e) => {
                    e.preventDefault();
                    goToStep(1);
                });
            }

            const btnVerify = document.getElementById('btn-verify');
            if (btnVerify) {
                btnVerify.addEventListener('click', (e) => {
                    e.preventDefault();
                    sendOtp();
                });
            }

            document.querySelectorAll('#step-2 input').forEach(el => {
                el.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        sendOtp();
                    }
                });
            });

            // Password eye toggle buttons
            const togglePassBtn = document.getElementById('btn-toggle-password');
            if (togglePassBtn) {
                togglePassBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    togglePassword('password', togglePassBtn);
                });
            }

            const togglePassConfBtn = document.getElementById('btn-toggle-password-conf');
            if (togglePassConfBtn) {
                togglePassConfBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    togglePassword('password_confirmation', togglePassConfBtn);
                });
            }

            // Step 3 Buttons
            const btnBackStep3 = document.getElementById('btn-back-step3');
            if (btnBackStep3) {
                btnBackStep3.addEventListener('click', (e) => {
                    e.preventDefault();
                    goToStep(2);
                });
            }

            const btnSubmit = document.getElementById('btn-submit');
            if (btnSubmit) {
                btnSubmit.addEventListener('click', (e) => {
                    e.preventDefault();
                    verifyOtpAndSubmit();
                });
            }

            const btnResendOtp = document.getElementById('btn-resend-otp');
            if (btnResendOtp) {
                btnResendOtp.addEventListener('click', (e) => {
                    e.preventDefault();
                    resendOtp();
                });
            }

            // Form Submit guard
            const regForm = document.getElementById('regForm');
            if (regForm) {
                regForm.addEventListener('submit', function(e) {
                    if (!this.dataset.submitting) {
                        e.preventDefault();
                        return false;
                    }
                });
            }

            // Initialize dynamic fields display
            toggleFields();
        }

        // Global functions exposed on window
        window.showAlert = showAlert;
        window.hideAlert = hideAlert;
        window.showSuccessAlert = showSuccessAlert;
        window.setFieldFeedback = setFieldFeedback;
        window.clearFieldFeedback = clearFieldFeedback;
        window.selectRole = selectRole;
        window.toggleFields = toggleFields;
        window.updateFullName = updateFullName;
        window.toggleNoMiddleName = toggleNoMiddleName;
        window.validateSingleField = validateSingleField;
        window.validateStep1Full = validateStep1Full;
        window.handleContinueStep1 = handleContinueStep1;
        window.goToStep = goToStep;
        window.togglePassword = togglePassword;
        window.sendOtp = sendOtp;
        window.resendOtp = resendOtp;
        window.verifyOtpAndSubmit = verifyOtpAndSubmit;

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initRegisterPage);
        } else {
            initRegisterPage();
        }
    </script>
</body>
</html>
