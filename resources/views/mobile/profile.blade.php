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
        <div class="mobile-user-sub small mb-2">
            @if($user->isStudent())
                <span class="d-inline-flex align-items-center gap-2 flex-wrap justify-content-center">
                    <span class="d-inline-flex align-items-center gap-1">
                        <i class="bi bi-person-badge" style="color:#e48fa0;" aria-hidden="true"></i>
                        <span>Student ID: <strong class="mobile-id-strong">{{ $user->student_number ?? 'Not Assigned' }}</strong></span>
                    </span>
                    @if($user->student_number)
                        <button type="button" id="copyMobileStudentId" class="mobile-copy-id-btn" data-copy-id="{{ $user->student_number }}" aria-label="Copy student ID">
                            <i class="bi bi-copy" aria-hidden="true"></i>
                            <span>Copy</span>
                        </button>
                    @endif
                </span>
            @else
                {{ $user->employee_id ?? $user->email }}
            @endif
        </div>

        <div class="d-flex justify-content-center flex-wrap gap-2 mb-1">
            <span class="badge ppm-role-pill">
                @if($user->isAdmin())
                    <i class="bi bi-shield-fill text-danger me-1"></i> Admin
                @elseif($user->isTeacher())
                    <i class="bi bi-person-workspace text-warning me-1"></i> Teacher
                @elseif($user->isParent())
                    <i class="bi bi-people-fill text-info me-1"></i> Parent
                @else
                    <i class="bi bi-mortarboard-fill me-1" style="color:#ece4e7;" aria-hidden="true"></i> Student
                @endif
            </span>

            @if($user->course)
                <span class="badge ppm-course-pill">{{ $user->course }}</span>
            @endif

            @if($user->year_level)
                <span class="badge ppm-year-pill">Year {{ $user->year_level }}</span>
            @endif

            @if($user->section)
                <span class="badge ppm-year-pill">Section {{ $user->section }}</span>
            @endif
        </div>
    </div>

    <!-- Account Details Section -->
    <div class="mobile-section mb-4">
        <h3 class="mobile-section-title">ACCOUNT DETAILS</h3>
        <div class="mobile-card">
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-person-fill" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mobile-info-label">Full Name</div>
                    <div class="mobile-info-val">{{ $user->name }}</div>
                </div>
            </div>

            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-envelope-fill" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mobile-info-label">Email Address</div>
                    <div class="mobile-info-val">{{ $user->email }}</div>
                </div>
            </div>

            @if($user->isStudent() || $user->student_number)
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-card-text" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mobile-info-label">Student ID</div>
                    <div class="mobile-info-val fw-bold font-monospace">{{ $user->student_number ?? 'Not Assigned' }}</div>
                </div>
            </div>
            @endif

            @if($user->course || $user->year_level || $user->section)
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-mortarboard-fill" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="mobile-info-label">Academic Program</div>
                    <div class="mobile-info-val">
                        {{ collect([$user->course, $user->year_level ? 'Year ' . $user->year_level : null, $user->section ? 'Sec. ' . $user->section : null])->filter()->implode(' · ') }}
                    </div>
                </div>
            </div>
            @endif

            @if($user->department)
            <div class="mobile-info-row">
                <div class="mobile-info-icon"><i class="bi bi-building" aria-hidden="true"></i></div>
                <div class="flex-grow-1 min-w-0">
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
                <div class="mobile-menu-icon" style="background: rgba(138,36,57,0.34); color: #fcf9fa;">
                    <i class="bi bi-house-door-fill" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Home Dashboard</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            @if($user->isStudent())
            <a href="{{ route('mobile.scan') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(52,211,153,0.16); color: #34d399;">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Scan QR Code</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            <a href="{{ route('student.classes') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(228,143,160,0.16); color: #f8d5dd;">
                    <i class="bi bi-journal-bookmark-fill" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">My Classes &amp; COR</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            <a href="{{ route('student.schedule') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(251,191,36,0.15); color: #fbbf24;">
                    <i class="bi bi-calendar2-week-fill" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Weekly Schedule</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            <a href="{{ route('mobile.history') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(125,211,252,0.15); color: #7dd3fc;">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Attendance History</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            <a href="{{ route('excuses') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(255,107,122,0.16); color: #ff8e9b;">
                    <i class="bi bi-file-earmark-text-fill" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Excuses</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>

            <a href="{{ route('settings') }}" class="mobile-menu-item">
                <div class="mobile-menu-icon" style="background: rgba(196,181,253,0.15); color: #c4b5fd;">
                    <i class="bi bi-gear-fill" aria-hidden="true"></i>
                </div>
                <span class="flex-grow-1 fw-semibold">Settings &amp; Biometrics</span>
                <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
            </a>
            @endif

            <form action="{{ route('logout') }}" method="POST" class="m-0">
                @csrf
                <button type="submit" class="mobile-menu-item text-danger w-100 border-0 bg-transparent text-start">
                    <div class="mobile-menu-icon" style="background: rgba(239,68,68,0.15); color: #f87171;">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    </div>
                    <span class="flex-grow-1 fw-semibold">Sign Out</span>
                    <i class="bi bi-chevron-right mobile-link-chevron" aria-hidden="true"></i>
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
    color: #FCF8F2;
}
.mobile-user-sub {
    color: #D1C5B4;
}
.mobile-id-strong {
    color: #F3E7CD;
}
.mobile-copy-id-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 99px;
    border: 1px solid rgba(212, 175, 55, 0.35);
    background: rgba(212, 175, 55, 0.14);
    color: #F5DFA8;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
}
.mobile-copy-id-btn:active {
    transform: scale(0.95);
}
.mobile-link-chevron {
    color: #A39683;
    font-size: 0.9rem;
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
    overflow-wrap: anywhere;
}
.mobile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 52px;
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
    flex-shrink: 0;
}
</style>
@push('scripts')
<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const copyBtn = document.getElementById('copyMobileStudentId');
    if (!copyBtn) return;
    copyBtn.addEventListener('click', async function() {
        const val = copyBtn.getAttribute('data-copy-id');
        if (!val) return;
        try {
            await navigator.clipboard.writeText(val);
            const label = copyBtn.querySelector('span');
            if (label) {
                label.textContent = 'Copied';
                setTimeout(() => { label.textContent = 'Copy'; }, 1600);
            }
        } catch (e) {}
    });
});
</script>
@endpush
@endsection
