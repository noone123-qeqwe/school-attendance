<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Attendance Check-in — Smart Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.pwa-tags')
    <style>
        :root {
            --primary: #800000;
            --primary-dark: #500000;
            --gold: #d97706;
            --dark-bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.75);
            --border: rgba(255, 255, 255, 0.1);
        }
        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        body {
            background: radial-gradient(circle at top right, rgba(128, 0, 0, 0.18), transparent 50%),
                        radial-gradient(circle at bottom left, rgba(217, 119, 6, 0.12), transparent 50%),
                        var(--dark-bg);
            min-height: 100vh;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: 28px;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.5);
            border: 1px solid var(--border);
            max-width: 420px;
            width: 100%;
            padding: 40px 32px;
            animation: cardFadeIn 0.4s ease;
        }
        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(12px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .qr-badge-icon {
            width: 72px;
            height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), #991b1b);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 10px 25px rgba(128, 0, 0, 0.4);
        }
        .form-label {
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .input-group-custom {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        .input-group-custom:focus-within {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.2);
        }
        .input-icon {
            padding: 0 16px;
            color: #64748b;
            font-size: 1.1rem;
        }
        .custom-input {
            background: transparent;
            border: none;
            color: #f8fafc;
            padding: 14px 16px 14px 0;
            width: 100%;
            outline: none;
            font-size: 0.95rem;
        }
        .custom-input::placeholder {
            color: #475569;
        }
        .submit-btn {
            background: linear-gradient(135deg, var(--primary), #991b1b);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 700;
            font-size: 0.95rem;
            width: 100%;
            box-shadow: 0 6px 20px rgba(128, 0, 0, 0.4);
            transition: all 0.25s ease;
            cursor: pointer;
            margin-top: 8px;
        }
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(128, 0, 0, 0.55);
            color: white;
        }
        .qr-banner-pill {
            background: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.25);
            border-radius: 99px;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: #fbbf24;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-3">
            <div class="qr-badge-icon">
                <i class="bi bi-qr-code-scan"></i>
            </div>
            <h3 style="font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; color: #ffffff;">Class Attendance</h3>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 12px;">Sign in to complete your QR attendance scan</p>
            <div class="qr-banner-pill">
                <i class="bi bi-shield-check"></i> Proximity Protected Session
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; border-radius: 12px; font-size: 0.85rem; padding: 10px 14px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-circle me-1"></i> {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.submit') }}" method="POST">
            @csrf
            <input type="hidden" name="qr_token" value="{{ $token }}">
            
            <div class="mb-3">
                <label for="identifier" class="form-label">Student ID or Email</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-person-fill"></i></span>
                    <input type="text" class="custom-input" id="identifier" name="identifier" 
                           required autocomplete="username" placeholder="e.g. 2310843" value="{{ old('identifier') }}">
                </div>
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group-custom">
                    <span class="input-icon"><i class="bi bi-lock-fill"></i></span>
                    <input type="password" class="custom-input" id="password" name="password" 
                           required autocomplete="current-password" placeholder="Enter your password">
                </div>
            </div>

            <button type="submit" class="submit-btn">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In & Record Attendance
            </button>
        </form>

        <div class="text-center mt-4">
            <small style="color: #64748b; font-size: 0.78rem; display: block; line-height: 1.4;">
                <i class="bi bi-geo-alt me-1"></i> Attendance requires location verification within classroom proximity.
            </small>
        </div>
    </div>
</body>
</html>