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
    padding: 8px 0 32px;
}
.mobile-profile-hero {
    background: #14080C;
    border: 1px solid rgba(250, 247, 248, 0.11);
    border-top: 2px solid #6B192B;
    border-radius: 14px;
    padding: 22px 16px;
}
.mobile-user-name {
    font-size: 1.24rem;
    letter-spacing: -0.02em;
    color: #FAF7F8;
}
.mobile-user-sub {
    color: #D4C6CA;
}
.mobile-id-strong {
    color: #FAF7F8;
}
.mobile-copy-id-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    min-height: 36px;
    padding: 4px 11px;
    border-radius: 8px;
    border: 1px solid rgba(232, 152, 168, 0.3);
    background: #1C0C11;
    color: #E898A8;
    font-size: 0.74rem;
    font-weight: 700;
    cursor: pointer;
}
.mobile-copy-id-btn:active {
    transform: scale(0.96);
}
.mobile-link-chevron {
    color: #9E8990;
    font-size: 0.9rem;
}
.ppm-role-pill {
    background: rgba(107, 25, 43, 0.36);
    color: #FAF7F8;
    border: 1px solid rgba(232, 152, 168, 0.28);
    font-weight: 600;
    font-size: 0.74rem;
    padding: 4px 10px;
    border-radius: 7px;
}
.ppm-course-pill, .ppm-year-pill {
    background: #1C0C11;
    color: #D4C6CA;
    border: 1px solid rgba(250, 247, 248, 0.1);
    font-weight: 600;
    font-size: 0.74rem;
    padding: 4px 10px;
    border-radius: 7px;
}
.mobile-section-title {
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    color: #9E8990;
    margin-bottom: 8px;
    padding-left: 2px;
}
.mobile-card {
    background: #14080C;
    border: 1px solid rgba(250, 247, 248, 0.1);
    border-radius: 14px;
    overflow: hidden;
}
.mobile-info-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 16px;
    border-bottom: 1px solid rgba(250, 247, 248, 0.075);
}
.mobile-info-row:last-child {
    border-bottom: none;
}
.mobile-info-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    background: #1C0C11;
    border: 1px solid rgba(250, 247, 248, 0.08);
    color: #E898A8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.96rem;
    flex-shrink: 0;
}
.mobile-info-label {
    font-size: 0.71rem;
    color: #9E8990;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.mobile-info-val {
    font-size: 0.9rem;
    font-weight: 600;
    color: #FAF7F8;
    overflow-wrap: anywhere;
}
.mobile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    min-height: 50px;
    padding: 11px 14px;
    border-radius: 10px;
    color: #FAF7F8;
    text-decoration: none;
    transition: background-color 0.15s ease;
}
.mobile-menu-item:hover, .mobile-menu-item:active {
    background: #1C0C11;
    color: #FFFFFF;
}
.mobile-menu-icon {
    width: 34px;
    height: 34px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.96rem;
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
