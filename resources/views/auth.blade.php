<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OCM Attendance Checker | Auth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
    background:
        radial-gradient(circle at top left, rgba(128,0,0,0.08), transparent 30%),
        radial-gradient(circle at bottom right, rgba(128,0,0,0.08), transparent 30%),
        #f8fafc;

    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
}

.auth-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    gap: 28px;
    width: 100%;
    max-width: 900px;
}

.auth-box {
    flex: 1 1 380px;
    max-width: 420px;
    padding: 35px 30px;
    border-radius: 24px;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(12px);

    border: 1px solid rgba(255,255,255,0.4);

    box-shadow:
        0 10px 30px rgba(0,0,0,0.08),
        0 2px 10px rgba(128,0,0,0.08);

    position: relative;
    overflow: hidden;

    transition: all 0.3s ease;
}

.auth-box:hover {
    transform: translateY(-4px);
    box-shadow:
        0 18px 40px rgba(0,0,0,0.12),
        0 4px 14px rgba(128,0,0,0.12);
}

.auth-box::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        linear-gradient(
            135deg,
            rgba(128,0,0,0.04),
            transparent 35%
        );
    pointer-events: none;
}

.auth-box h2 {
    color: #5c0000;
    font-weight: 800;
    text-align: center;
    margin-bottom: 25px;
    font-size: 1.55rem;
    letter-spacing: -.5px;
}

.form-label {
    color: #475569;
    font-weight: 600;
    margin-bottom: 6px;
    font-size: 0.9rem;
}

.form-control {
    border-radius: 12px;
    border: 1.5px solid #e2e8f0;
    padding: 12px 14px;
    font-size: 0.92rem;
    background: #f8fafc;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #800000;
    box-shadow: 0 0 0 4px rgba(128,0,0,0.08);
    background: white;
}

.btn-oc {
    background: linear-gradient(135deg, #5c0000, #800000);
    color: white;
    font-weight: 700;
    border: none;
    border-radius: 12px;
    padding: 12px;
    transition: all 0.25s ease;
    box-shadow: 0 4px 14px rgba(128,0,0,0.2);
}

.btn-oc:hover {
    background: linear-gradient(135deg, #4a0000, #6a0000);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 10px 22px rgba(128,0,0,0.28);
}

.school-logo-placeholder {
    width: 70px;
    height: 70px;
    margin: 0 auto 18px;

    border-radius: 50%;

    background: linear-gradient(135deg, #800000, #4a0000);

    color: white;
    font-size: 0.78rem;
    font-weight: 800;

    display: flex;
    align-items: center;
    justify-content: center;

    box-shadow: 0 6px 18px rgba(128,0,0,0.25);
}

.form-check-label {
    color: #64748b;
    font-size: 0.85rem;
}

@media (max-width: 768px) {

    body {
        padding: 14px;
        align-items: flex-start;
    }

    .auth-container {
        gap: 18px;
    }

    .auth-box {
        padding: 28px 22px;
        border-radius: 20px;
    }

    .auth-box h2 {
        font-size: 1.35rem;
    }

    .school-logo-placeholder {
        width: 62px;
        height: 62px;
        font-size: 0.72rem;
    }
}
    </style>
</head>
<body>

    <div class="auth-container">
        <div class="auth-box">
            <div class="school-logo-placeholder">OC MOBO</div>
            <h2>Register</h2>
            <form method="POST" action="{{ route('register.submit') }}">
                @csrf
                <input type="hidden" name="role" value="student">
                <div class="mb-3">
                    <label class="form-label" for="reg-name">Full Name</label>
                    <input type="text" id="reg-name" name="name" class="form-control" placeholder="Enter Full Name" autocomplete="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reg-student-number">Student ID Number</label>
                    <input type="text" id="reg-student-number" name="student_number" class="form-control" placeholder="e.g. 1234567" autocomplete="off" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reg-course">Course</label>
                    <input type="text" id="reg-course" name="course" class="form-control" placeholder="e.g. BSCS" autocomplete="off" required>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label" for="reg-year-level">Year Level</label>
                        <select id="reg-year-level" name="year_level" class="form-control" required>
                            <option value="1">1st Year</option>
                            <option value="2">2nd Year</option>
                            <option value="3">3rd Year</option>
                            <option value="4">4th Year</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label" for="reg-semester">Semester</label>
                        <select id="reg-semester" name="semester" class="form-control" required>
                            <option value="1">1st Semester</option>
                            <option value="2">2nd Semester</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reg-email">Email Address</label>
                    <input type="email" id="reg-email" name="email" class="form-control" placeholder="name@example.com" autocomplete="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reg-password">Password</label>
                    <input type="password" id="reg-password" name="password" class="form-control" autocomplete="new-password" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="reg-password-confirm">Confirm Password</label>
                    <input type="password" id="reg-password-confirm" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
                <button type="submit" class="btn btn-oc w-100">CREATE ACCOUNT</button>
            </form>
        </div>

        <div class="auth-box">
            <div class="school-logo-placeholder">OC MOBO</div>
            <h2>Student Login</h2>
            <form method="POST" action="{{ route('login.submit') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="login-identifier">Student ID / Email</label>
                    <input type="text" id="login-identifier" name="identifier" class="form-control" placeholder="Enter ID or Email" autocomplete="username" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" class="form-control" autocomplete="current-password" required>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <button type="submit" class="btn btn-oc w-100">SIGN IN</button>
            </form>
        </div>
    </div>

</body>
</html>