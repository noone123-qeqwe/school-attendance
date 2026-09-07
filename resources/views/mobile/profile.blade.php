@extends('layouts.mobile-app')

@section('title', 'Profile - Smart Attendance')

@section('content')
<div class="mobile-profile-page">
    <!-- Profile Card Hero -->
    <div class="mobile-profile-hero text-center mb-4">
        <div class="mobile-profile-avatar-container mx-auto mb-3">
            <x-profile-photo-manager :user="$user" size="110" align="center" avatar-id="mobileProfileAvatar" />
        </div>

        <h2 class="mobile-user-name fw-bold mb-1">{{ $user->name }}</h2>
        <div class="mobile-user-sub text-muted small mb-2">
            {{ $user->student_number ?? $user->email }}
        </div>

        <div class="d-flex justify-content-center gap-2 mb-2">
            <span class="badge ppm-role-pill">
                @if($user->isAdmin())
                    <i class="bi bi-shield-fill text-danger me-1"></i> Admin
                @elseif($user->isTeacher())
                    <i class="bi bi-person-workspace text-warning me-1"></i> Teacher
                @elseif($user->isParent())
                    <i class="bi bi-people-fill text-info me-1"></i> Parent
                @else
                    <i class="bi bi-mortarboard-fill text-warning me-1"></i> Student
                @endif
            </span>

            @if($user->course)
                <span class="badge ppm-course-pill">{{ $user->course }}</span>
            @endif

            @if($user->year_level)
                <span class="badge ppm-year-pill">Year {{ $user->year_level }}</span>
            @endif
        </div>
    </div>

    <!-- Account Details Section -->
    <div class="mobile-section mb-4">
        <h3 class="mobile-section-title">ACCOUNT DETAILS</h3>
        <div class="mobile-card">
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-person-fill"></i></div>
                <div class="flex-grow-1">
                    <div class="mobile-info-label">Full Name</div>
                    <div class="mobile-info-val">{{ $user->name }}</div>
                </div>
            </div>

            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-envelope-fill"></i></div>
                <div class="flex-grow-1">
                    <div class="mobile-info-label">Email Address</div>
                    <div class="mobile-info-val">{{ $user->email }}</div>
                </div>
            </div>

            @if($user->student_number)
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-card-text"></i></div>
                <div class="flex-grow-1">
                    <div class="mobile-info-label">Student ID</div>
                    <div class="mobile-info-val">{{ $user->student_number }}</div>
                </div>
            </div>
            @endif

            @if($user->department)
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-building"></i></div>
                <div class="flex-grow-1">
                    <div class="mobile-info-label">Department</div>
                    <div class="mobile-info-val">{{ $user->department }}</div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="mobile-section mb-4">
        <h3 class="mobile-section-title">QUICK LINKS</h3>
        <div class="mobile-card p-2">
            <a href="{{ route('mobile.home') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(207,164,111,0.15); color: #cfa46f;">
                    <i class="bi bi-house-door-fill"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Home Dashboard</span>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>

            @if($user->isStudent())
            <a href="{{ route('mobile.scan') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(34,197,94,0.15); color: #22c55e;">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Scan QR Code</span>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>

            <a href="{{ route('mobile.history') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(59,130,246,0.15); color: #3b82f6;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Attendance History</span>
                <i class="bi bi-chevron-right text-muted"></i>
            </a>
            @endif

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="mobile-menu-item text-danger w-100 border-0 bg-transparent text-start">
                    <div class="mobile-menu-icon" style="background: rgba(239,68,68,0.15); color: #ef4444;">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <span class="flex-grow-1 fw-semibold">Sign Out</span>
                    <i class="bi bi-chevron-right text-muted"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<style>
.mobile-profile-page {
    padding: 12px 4px 32px 4px;
}
.mobile-profile-hero {
    background: linear-gradient(180deg, rgba(207, 164, 111, 0.08) 0%, rgba(26, 26, 26, 0.4) 100%);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 20px;
    padding: 24px 16px;
}
.mobile-user-name {
    font-size: 1.3rem;
    color: #f3e7cd;
}
.ppm-role-pill {
    background: rgba(207, 164, 111, 0.15);
    color: #f3e7cd;
    border: 1px solid rgba(207, 164, 111, 0.3);
    font-weight: 600;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 99px;
}
.ppm-course-pill, .ppm-year-pill {
    background: rgba(255, 255, 255, 0.05);
    color: #b39b82;
    border: 1px solid rgba(255, 255, 255, 0.1);
    font-weight: 600;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 99px;
}
.mobile-section-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.8px;
    color: #b39b82;
    margin-bottom: 8px;
    padding-left: 4px;
}
.mobile-card {
    background: rgba(207, 164, 111, 0.05);
    border: 1px solid rgba(207, 164, 111, 0.12);
    border-radius: 16px;
    overflow: hidden;
}
.mobile-info-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}
.mobile-info-row:last-child {
    border-bottom: none;
}
.mobile-info-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.04);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.mobile-info-label {
    font-size: 0.75rem;
    color: #b39b82;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.mobile-info-val {
    font-size: 0.92rem;
    font-weight: 600;
    color: #f3e7cd;
}
.mobile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    color: #f3e7cd;
    text-decoration: none;
    transition: background-color 0.2s;
}
.mobile-menu-item:hover, .mobile-menu-item:active {
    background: rgba(255, 255, 255, 0.05);
}
.mobile-menu-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}
</style>
@endsection
