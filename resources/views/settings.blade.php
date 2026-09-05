@php
    $layout = 'layouts.app';
    if(Auth::check()) {
        if(Auth::user()->isAdmin()) $layout = 'admin.layout';
        elseif(Auth::user()->isTeacher()) $layout = 'teacher.layout';
        elseif(Auth::user()->isParent()) $layout = 'parent.layout';
    }
@endphp
@extends($layout)

@section('content')
@php
    $user = Auth::user();
    $allRecords   = $user->attendances ?? collect();
    $totalRecords = $allRecords->count();
    $totalPresent = $allRecords->where('status','Present')->count();
    $totalLate    = $allRecords->where('status','Late')->count();
    $totalAbsent  = $allRecords->where('status','Absent')->count();
    $rate = $totalRecords > 0 ? round((($totalPresent+$totalLate)/$totalRecords)*100) : 0;
@endphp

<style>
.sp{max-width:1100px;margin:0 auto;}
.pg-title{font-size:1.8rem;font-weight:800;color:#f3e7cd;letter-spacing:-.3px;}
.pg-sub{font-size:.875rem;color:#b39b82;margin-top:2px;}
.stabs-wrapper {
    position: relative;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    width: 100%;
}
.stabs {
    display: flex;
    gap: 0;
    width: 100%;
    border-bottom: 2px solid rgba(255,215,145,0.08);
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding-right: 48px;
    padding-left: 2px;
}
.stabs::-webkit-scrollbar {
    display: none;
}
.stab {
    white-space: nowrap;
    padding: 10px 20px;
    font-size: .875rem;
    font-weight: 600;
    color: #8f826f;
    cursor: pointer;
    border: none;
    background: none;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
}
.stab.active { color: #cfa46f; border-bottom-color: #cfa46f; }
.stab:hover { color: #f3e7cd; }

.stabs-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(30, 24, 20, 0.95);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    border: 1.5px solid rgba(207, 164, 111, 0.5);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.95rem;
    box-shadow: 0 4px 14px rgba(0,0,0,0.6), 0 0 10px rgba(207,164,111,0.3);
    transition: all 0.25s ease;
    padding: 0;
}
.stabs-arrow:hover {
    background: rgba(45, 36, 30, 0.98);
    border-color: #cfa46f;
    color: #f3e7cd;
    box-shadow: 0 6px 16px rgba(0,0,0,0.7), 0 0 14px rgba(207,164,111,0.45);
    transform: translateY(-50%) scale(1.08);
}
.stabs-arrow-left {
    left: 0;
    display: none;
}
.stabs-arrow-left.visible {
    display: flex;
}
.stabs-arrow-right {
    right: 0;
    display: flex;
    animation: stabsArrowPulse 2.5s infinite ease-in-out;
}
@keyframes stabsArrowPulse {
    0%, 100% {
        transform: translateY(-50%) scale(1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.6), 0 0 8px rgba(207,164,111,0.25);
    }
    50% {
        transform: translateY(-50%) scale(1.12);
        box-shadow: 0 4px 16px rgba(0,0,0,0.7), 0 0 16px rgba(207,164,111,0.5);
    }
}
.stabs-wrapper.has-scroll-right::after {
    content: '';
    position: absolute;
    right: 0;
    top: 0;
    bottom: 0;
    width: 52px;
    background: linear-gradient(to right, transparent, rgba(17, 14, 12, 0.9));
    pointer-events: none;
    z-index: 5;
}
.stabs-wrapper.has-scroll-left::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 52px;
    background: linear-gradient(to left, transparent, rgba(17, 14, 12, 0.9));
    pointer-events: none;
    z-index: 5;
}
.spanel{display:none;}.spanel.active{display:block;}
.sc{background:rgba(255,235,190,0.02);border-radius:16px;border:1px solid rgba(255,215,145,0.08);box-shadow:0 4px 15px rgba(0,0,0,.2);overflow:hidden;margin-bottom:20px;transition:all .25s;}
.sc:hover{box-shadow:0 8px 25px rgba(0,0,0,.3);border-color:rgba(255,215,145,0.15);}
.sc-head{padding:20px 22px;border-bottom:1px solid rgba(255,215,145,0.06);display:flex;align-items:center;gap:16px;}
.sc-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;background:rgba(207,164,111,0.12)!important;color:#cfa46f!important;flex-shrink:0;}
.sc-title{font-size:1.05rem;font-weight:700;color:#f3e7cd;}
.sc-sub{font-size:.82rem;color:#b39b82;margin-top:3px;}
.sc-body{padding:18px 18px 20px;}
@media(max-width:640px){.sc-head{padding:16px 16px;}.sc-body{padding:14px 14px 18px;}}
.sl{font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;display:block;}
.si{width:100%;padding:11px 14px;border-radius:10px;border:1.5px solid rgba(255,215,145,0.12);font-size:.875rem;font-family:'Inter',sans-serif;background:rgba(255,235,190,0.05);color:#f3e7cd;transition:all .2s;outline:none;}
.si:hover{border-color:rgba(255,215,145,0.25);background:rgba(255,235,190,0.08);}
.si:focus{border-color:#cfa46f;background:rgba(255,235,190,0.08);box-shadow:0 0 0 3px rgba(207,164,111,.15);}
.si option {background:#1a1d24;color:#f3e7cd;}
.pw-wrap{position:relative;}
.pw-wrap .si{padding-right:44px;}
.eye-btn{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:#b39b82;font-size:1rem;cursor:pointer;background:none;border:none;padding:0;transition:color .2s;line-height:1;}
.eye-btn:hover{color:#cfa46f;}
.sbtn{padding:11px 28px;background:rgba(117,69,53,0.9)!important;color:#f3e7cd;font-weight:700;font-size:.875rem;border:1px solid rgba(255,215,145,0.16);border-radius:10px;cursor:pointer;box-shadow:0 4px 14px rgba(0,0,0,.25)!important;transition:all .25s;}
.sbtn:hover{background:rgba(135,87,58,0.95)!important;transform:translateY(-2px);box-shadow:0 8px 22px rgba(0,0,0,.35)!important;}
.sbtn:active{transform:translateY(0);}
.cancel-btn {
    padding:11px 20px; background:rgba(255,235,190,0.05)!important; color:#b39b82!important; border:1px solid rgba(255,215,145,0.15)!important; border-radius:10px; font-weight:600; font-size:.875rem; cursor:pointer; transition:all 0.2s;
}
.cancel-btn:hover { background:rgba(255,235,190,0.1)!important; color:#f3e7cd!important; }
.trow{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid rgba(255,215,145,0.06);}
.trow:last-child{border-bottom:none;padding-bottom:0;}
.tlabel{font-size:.875rem;font-weight:600;color:#f3e7cd;}
.tsub{font-size:.78rem;color:#b39b82;margin-top:2px;}
.form-check-input{background-color:rgba(255,235,190,0.1);border-color:rgba(255,215,145,0.2);}
.form-check-input:checked{background-color:#cfa46f!important;border-color:#cfa46f!important;}
.form-check-input{width:2.4em!important;height:1.3em!important;cursor:pointer;}
.flash-ok{background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.2);color:#4ade80;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.flash-err{background:rgba(248,113,113,0.1);border:1px solid rgba(248,113,113,0.2);color:#f87171;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.stat-box{background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:14px 16px;text-align:center;transition:transform .2s,box-shadow .2s;}
.stat-box:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.15);border-color:rgba(255,215,145,0.15);}
.stat-val{font-size:1.6rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:.68rem;font-weight:600;color:#b39b82;text-transform:uppercase;letter-spacing:.4px;margin-top:4px;}
.prog-bar{height:8px;background:rgba(255,215,145,0.1);border-radius:99px;overflow:hidden;margin-top:6px;}
.prog-fill{height:100%;border-radius:99px;transition:width 1s ease;}
.info-row{display:flex;align-items:center;gap:14px;padding:12px 0;border-bottom:1px solid rgba(255,215,145,0.06);}
.info-row:last-child{border-bottom:none;}
.info-icon{width:34px;height:34px;border-radius:9px;background:rgba(207,164,111,0.12);border:1px solid rgba(255,215,145,0.1);display:flex;align-items:center;justify-content:center;color:#cfa46f;font-size:.9rem;flex-shrink:0;}
.info-lbl{font-size:.7rem;font-weight:600;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;}
.info-val{font-size:.9rem;font-weight:600;color:#f3e7cd;}
.act-row{display:flex;align-items:center;gap:14px;padding:14px 24px;border-bottom:1px solid rgba(255,215,145,0.06);transition:background .15s;}
.act-row:hover{background:rgba(255,235,190,0.04);}
.act-row:last-child{border-bottom:none;}

/* Form overrides specific for Dark Theme */
.email-otp-digit, .otp-digit-s {
    color: #f3e7cd !important;
    background: rgba(255,235,190,0.05) !important;
    border-color: rgba(255,215,145,0.12) !important;
}
.email-otp-digit:focus, .otp-digit-s:focus {
    border-color: #cfa46f !important;
    box-shadow: 0 0 0 3px rgba(207,164,111,.15) !important;
}

/* ── Sleek Profile Identity Card ── */
.profile-card-inner {
    display: flex;
    align-items: center;
    gap: 22px;
}
.profile-avatar-holder {
    position: relative;
    width: 88px;
    height: 88px;
    flex-shrink: 0;
    cursor: pointer;
    display: block;
    margin: 0;
}
.profile-avatar-img-wrap {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    overflow: hidden;
    border: 2.5px solid rgba(207,164,111,0.65);
    box-shadow: 0 8px 24px -4px rgba(0,0,0,0.6), 0 0 16px rgba(207,164,111,0.2);
    position: relative;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), border-color 0.25s ease;
    background: #181412;
}
.profile-avatar-holder:hover .profile-avatar-img-wrap {
    transform: scale(1.04);
    border-color: #f5dfa8;
}
.profile-avatar-holder:active .profile-avatar-img-wrap {
    transform: scale(0.97);
}
.profile-avatar-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.profile-avatar-badge {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: radial-gradient(circle at 35% 30%, #fff7db 0%, #e5c07b 50%, #b88638 100%);
    color: #140703;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.78rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.6), inset 0 1px 1px rgba(255,255,255,0.8);
    border: 2.5px solid #181412;
    transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    pointer-events: none;
}
.profile-avatar-holder:hover .profile-avatar-badge {
    transform: scale(1.15);
}
.profile-details-col {
    flex: 1;
    min-width: 0;
}
.profile-user-name {
    font-size: 1.25rem;
    font-weight: 800;
    color: #f3e7cd;
    letter-spacing: -0.3px;
    line-height: 1.25;
    margin-bottom: 4px;
    word-break: break-word;
}
.profile-user-id {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.82rem;
    color: #b39b82;
    font-weight: 600;
    margin-bottom: 12px;
}
.profile-actions-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.profile-btn-choose {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 16px;
    background: linear-gradient(135deg, #cfa46f 0%, #9a733e 100%) !important;
    color: #140703 !important;
    font-weight: 800 !important;
    font-size: 0.78rem !important;
    border-radius: 99px !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(207,164,111,0.3) !important;
    cursor: pointer !important;
    transition: all 0.22s ease !important;
    line-height: 1 !important;
    text-decoration: none !important;
}
.profile-btn-choose:hover {
    background: linear-gradient(135deg, #dfb885 0%, #b88648 100%) !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(207,164,111,0.45) !important;
}
.profile-btn-choose:active {
    transform: scale(0.96);
}
.profile-badge-course {
    background: rgba(128, 0, 0, 0.4);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
    font-size: 0.72rem;
    font-weight: 800;
    padding: 5px 12px;
    border-radius: 99px;
    letter-spacing: 0.3px;
    white-space: nowrap;
    flex-shrink: 0;
}
.profile-badge-year {
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(207, 164, 111, 0.3);
    color: #f5dfa8;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 99px;
    letter-spacing: 0.3px;
    white-space: nowrap;
    flex-shrink: 0;
}

/* ── Modern Security Dashboard Design System ── */
.sec-health-hero {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.08) 0%, rgba(30, 22, 18, 0.65) 100%);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 16px;
    padding: 16px 20px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    flex-wrap: wrap;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.35);
}
.sec-health-left {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
}
.sec-health-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(207, 164, 111, 0.15);
    border: 1px solid rgba(207, 164, 111, 0.3);
    color: #f5dfa8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}
.sec-health-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #f3e7cd;
    letter-spacing: -0.2px;
    line-height: 1.25;
}
.sec-health-sub {
    font-size: 0.78rem;
    color: #b39b82;
    margin-top: 2px;
}
.sec-health-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
    font-size: 0.74rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 99px;
    white-space: nowrap;
}
.sec-pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #4ade80;
    box-shadow: 0 0 8px #4ade80;
    animation: secPulse 2s infinite ease-in-out;
}
@keyframes secPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.6; }
}

.sec-matrix-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 10px;
    margin-bottom: 18px;
}
@media (max-width: 768px) {
    .sec-matrix-bar {
        grid-template-columns: repeat(2, 1fr);
    }
}
.sec-matrix-item {
    background: rgba(255, 235, 190, 0.03);
    border: 1px solid rgba(255, 215, 145, 0.09);
    border-radius: 12px;
    padding: 10px 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.76rem;
    font-weight: 700;
    color: #f3e7cd;
    transition: all 0.2s ease;
}
.sec-matrix-item:hover {
    border-color: rgba(207, 164, 111, 0.25);
    background: rgba(255, 235, 190, 0.06);
}

.sec-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 16px;
}

.sec-card {
    background: rgba(24, 17, 15, 0.88) !important;
    border: 1px solid rgba(255, 215, 145, 0.12) !important;
    border-left: 3.5px solid var(--card-accent, #cfa46f) !important;
    border-radius: 16px !important;
    padding: 18px !important;
    margin-bottom: 0 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    position: relative !important;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.4) !important;
    backdrop-filter: blur(16px) !important;
    -webkit-backdrop-filter: blur(16px) !important;
    transition: all 0.25s ease !important;
}
.sec-card:hover {
    border-color: rgba(207, 164, 111, 0.3) !important;
    border-left-color: var(--card-accent, #cfa46f) !important;
    background: rgba(30, 21, 18, 0.94) !important;
    box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.55), 0 0 16px rgba(207, 164, 111, 0.1) !important;
    transform: translateY(-2px) !important;
}

.sec-card-top {
    display: flex !important;
    align-items: flex-start !important;
    gap: 12px !important;
    margin-bottom: 14px !important;
}
.sec-card-icon {
    width: 40px !important;
    height: 40px !important;
    border-radius: 11px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.15rem !important;
    flex-shrink: 0 !important;
    box-shadow: inset 0 1px 1px rgba(255, 255, 255, 0.15), 0 3px 8px rgba(0, 0, 0, 0.25) !important;
}
.sec-card-meta {
    flex: 1 !important;
    min-width: 0 !important;
}
.sec-card-header-line {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 8px !important;
    margin-bottom: 2px !important;
}
.sec-card-name {
    font-size: 0.94rem !important;
    font-weight: 800 !important;
    color: #f3e7cd !important;
    line-height: 1.25 !important;
    letter-spacing: -0.2px !important;
}
.sec-card-subtitle {
    font-size: 0.74rem !important;
    color: #b39b82 !important;
    line-height: 1.35 !important;
}

.sec-badge {
    font-size: 0.68rem !important;
    font-weight: 700 !important;
    padding: 3px 8px !important;
    border-radius: 99px !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 4px !important;
    flex-shrink: 0 !important;
    letter-spacing: 0.2px !important;
}
.sec-badge-blue {
    background: rgba(59, 130, 246, 0.12) !important;
    color: #60a5fa !important;
    border: 1px solid rgba(59, 130, 246, 0.3) !important;
}
.sec-badge-amber {
    background: rgba(245, 158, 11, 0.12) !important;
    color: #fbbf24 !important;
    border: 1px solid rgba(245, 158, 11, 0.3) !important;
}
.sec-badge-green {
    background: rgba(34, 197, 94, 0.12) !important;
    color: #4ade80 !important;
    border: 1px solid rgba(34, 197, 94, 0.3) !important;
}
.sec-badge-gold {
    background: rgba(207, 164, 111, 0.14) !important;
    color: #f5dfa8 !important;
    border: 1px solid rgba(207, 164, 111, 0.3) !important;
}

.sec-card-content {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-end !important;
    margin-top: 10px !important;
    padding-top: 12px !important;
    border-top: 1px solid rgba(255, 215, 145, 0.07) !important;
}
.sec-card-hint {
    font-size: 0.78rem !important;
    color: #b39b82 !important;
    line-height: 1.45 !important;
    margin-bottom: 14px !important;
}

.sec-input-display {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 8px !important;
    padding: 9px 12px !important;
    background: rgba(59, 130, 246, 0.07) !important;
    border: 1px solid rgba(59, 130, 246, 0.22) !important;
    border-radius: 10px !important;
    margin-bottom: 12px !important;
}
.sec-input-val {
    font-size: 0.85rem !important;
    font-weight: 700 !important;
    color: #f3e7cd !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    flex: 1 !important;
}
.sec-copy-btn {
    background: transparent !important;
    border: none !important;
    color: #8f826f !important;
    cursor: pointer !important;
    padding: 3px 6px !important;
    border-radius: 6px !important;
    transition: all 0.2s ease !important;
    flex-shrink: 0 !important;
}
.sec-copy-btn:hover {
    color: #f3e7cd !important;
}

.sec-status-tile {
    display: flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 8px 12px !important;
    background: rgba(255, 235, 190, 0.03) !important;
    border: 1px solid rgba(255, 215, 145, 0.08) !important;
    border-radius: 10px !important;
    font-size: 0.75rem !important;
    font-weight: 600 !important;
    color: #f3e7cd !important;
    margin-bottom: 12px !important;
}

.sec-feature-chips {
    display: flex !important;
    gap: 6px !important;
    flex-wrap: wrap !important;
    margin-bottom: 10px !important;
}
.sec-feature-chips span {
    font-size: 0.68rem !important;
    font-weight: 600 !important;
    padding: 3px 8px !important;
    border-radius: 6px !important;
    background: rgba(34, 197, 94, 0.06) !important;
    border: 1px solid rgba(34, 197, 94, 0.18) !important;
    color: #86efac !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 3px !important;
}

.sec-action-btn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    width: 100% !important;
    padding: 10px 16px !important;
    border-radius: 11px !important;
    font-size: 0.82rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.2px !important;
    cursor: pointer !important;
    border: none !important;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    text-decoration: none !important;
}
.sec-action-btn:hover {
    transform: translateY(-1px) !important;
    filter: brightness(1.08) !important;
}
.sec-action-btn:active {
    transform: translateY(0) scale(0.98) !important;
}
.sec-btn-blue {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(37,99,235,0.35) !important;
}
.sec-btn-amber {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(217,119,6,0.35) !important;
}
.sec-btn-emerald {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(22,163,74,0.35) !important;
}
.sec-btn-gold {
    background: linear-gradient(135deg, #cfa46f 0%, #a67c43 100%) !important;
    color: #140703 !important;
    font-weight: 800 !important;
    box-shadow: 0 4px 14px rgba(207,164,111,0.35) !important;
}
.sec-card-title {
    font-size: 0.96rem !important;
    font-weight: 800 !important;
    color: #f3e7cd !important;
    line-height: 1.3 !important;
    letter-spacing: -0.2px !important;
    margin-bottom: 3px !important;
}
.sec-card-sub {
    font-size: 0.76rem !important;
    color: #b39b82 !important;
    line-height: 1.4 !important;
}

.sec-card-body {
    flex: 1 !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: flex-end !important;
    margin-top: 14px !important;
    padding-top: 14px !important;
    border-top: 1px solid rgba(255, 215, 145, 0.08) !important;
}
.sec-card-desc {
    font-size: 0.8rem !important;
    color: #b39b82 !important;
    line-height: 1.5 !important;
    margin-bottom: 14px !important;
}

.sec-email-pill {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 10px 14px;
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.25);
    border-radius: 12px;
    margin-bottom: 14px;
    overflow: hidden;
    min-width: 0;
}
.sec-email-val {
    font-size: 0.88rem;
    font-weight: 700;
    color: #f3e7cd;
    letter-spacing: 0.2px;
    word-break: break-all;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sbtn { 
    display: inline-flex !important; 
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    width: 100% !important; 
    padding: 11px 20px !important;
    border-radius: 12px !important;
    font-size: 0.84rem !important;
    font-weight: 800 !important;
    letter-spacing: 0.2px !important;
    cursor: pointer !important;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    text-decoration: none !important;
}
.sbtn:hover {
    transform: translateY(-2px) !important;
    filter: brightness(1.08) !important;
}
.sbtn:active {
    transform: translateY(0) scale(0.98) !important;
}

/* ── Biometric Pulse Scanner ── */
.fp-radar-wrap {
    position: relative;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle, rgba(34, 197, 94, 0.25) 0%, rgba(34, 197, 94, 0.05) 70%, transparent 100%);
    border: 1.5px solid rgba(74, 222, 128, 0.45);
    box-shadow: 0 0 24px rgba(34, 197, 94, 0.25);
    flex-shrink: 0;
}
.fp-radar-wrap::before {
    content: '';
    position: absolute;
    inset: -5px;
    border-radius: 50%;
    border: 1.5px dashed rgba(74, 222, 128, 0.35);
    animation: fpRadarSpin 12s linear infinite;
}
@keyframes fpRadarSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* ── Device Credential Cards ── */
.device-item-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 14px 16px;
    border-radius: 14px;
    background: rgba(255, 235, 190, 0.03);
    border: 1px solid rgba(255, 215, 145, 0.08);
    margin-bottom: 12px;
    transition: all 0.2s ease;
}
.device-item-card:hover {
    border-color: rgba(255, 215, 145, 0.2);
    background: rgba(255, 235, 190, 0.06);
}
.device-item-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 0;
}
.device-item-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: rgba(22, 163, 74, 0.15);
    border: 1px solid rgba(22, 163, 74, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #4ade80;
    font-size: 1.2rem;
    flex-shrink: 0;
}
.device-item-info {
    flex: 1;
    min-width: 0;
}
.device-item-name {
    font-size: 0.9rem;
    font-weight: 700;
    color: #f3e7cd;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.device-item-meta {
    font-size: 0.74rem;
    color: #b39b82;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 3px;
    flex-wrap: wrap;
}
.device-meta-verified {
    color: #4ade80;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}
.device-meta-dot {
    color: #8f826f;
}
.device-meta-date {
    color: #b39b82;
    font-weight: 500;
}
.device-remove-btn {
    flex-shrink: 0;
    padding: 7px 14px;
    border-radius: 10px;
    background: rgba(248, 113, 113, 0.1);
    color: #f87171;
    border: 1px solid rgba(248, 113, 113, 0.25);
    font-size: 0.76rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}
.device-remove-btn:hover {
    background: rgba(248, 113, 113, 0.22);
    color: #fca5a5;
    border-color: rgba(248, 113, 113, 0.4);
}

/* ── Attendance KPI Stat Cards ── */
.att-stat-grid {
    display: grid !important;
    grid-template-columns: repeat(4, 1fr) !important;
    gap: 12px !important;
    margin-bottom: 18px !important;
}
.att-stat-card {
    background: rgba(255, 235, 190, 0.035) !important;
    border: 1px solid rgba(255, 215, 145, 0.1) !important;
    border-radius: 14px !important;
    padding: 16px 12px !important;
    text-align: center !important;
    position: relative !important;
    overflow: hidden !important;
    transition: all 0.25s ease !important;
}
.att-stat-card:hover {
    transform: translateY(-2px);
    border-color: rgba(255, 215, 145, 0.22) !important;
    background: rgba(255, 235, 190, 0.055) !important;
    box-shadow: 0 8px 20px -5px rgba(0,0,0,0.5) !important;
}
.att-stat-val {
    font-size: 1.75rem !important;
    font-weight: 800 !important;
    line-height: 1.1 !important;
    margin-bottom: 3px !important;
    font-family: 'Outfit', 'Inter', sans-serif !important;
}
.att-stat-label {
    font-size: 0.7rem !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #b39b82 !important;
}

/* ── Attendance Gauge Card ── */
.att-gauge-card {
    background: rgba(255, 235, 190, 0.03) !important;
    border: 1px solid rgba(255, 215, 145, 0.1) !important;
    border-radius: 16px !important;
    padding: 18px 20px !important;
    margin-bottom: 18px !important;
}
.att-gauge-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    margin-bottom: 8px !important;
}
.att-segmented-bar {
    display: flex !important;
    height: 10px !important;
    border-radius: 99px !important;
    overflow: hidden !important;
    background: rgba(255, 255, 255, 0.08) !important;
    gap: 2px !important;
    margin-top: 10px !important;
    margin-bottom: 12px !important;
}
.att-seg-present { background: linear-gradient(90deg, #16a34a, #22c55e) !important; }
.att-seg-late { background: linear-gradient(90deg, #d97706, #f59e0b) !important; }
.att-seg-absent { background: linear-gradient(90deg, #dc2626, #ef4444) !important; }

/* ── Preferences Toggle Rows ── */
.pref-tile {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    padding: 16px 18px !important;
    background: rgba(255, 235, 190, 0.025) !important;
    border: 1px solid rgba(255, 215, 145, 0.08) !important;
    border-radius: 14px !important;
    margin-bottom: 10px !important;
    gap: 14px !important;
    transition: all 0.2s ease !important;
}
.pref-tile:hover {
    background: rgba(255, 235, 190, 0.045) !important;
    border-color: rgba(255, 215, 145, 0.18) !important;
}
.pref-tile-icon {
    width: 38px !important;
    height: 38px !important;
    border-radius: 10px !important;
    background: rgba(207,164,111,0.12) !important;
    color: #f5dfa8 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.1rem !important;
    flex-shrink: 0 !important;
}

/* ── Luxury Buttons ── */
.btn-gold {
    background: linear-gradient(135deg, #cfa46f 0%, #a67c43 100%) !important;
    color: #140703 !important;
    font-weight: 800 !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(207,164,111,0.3) !important;
}
.btn-gold:hover {
    background: linear-gradient(135deg, #dfb885 0%, #b88648 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(207,164,111,0.45) !important;
}

.btn-emerald {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(22,163,74,0.3) !important;
}
.btn-emerald:hover {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(22,163,74,0.45) !important;
}

.btn-amber {
    background: linear-gradient(135deg, #d97706 0%, #b45309 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(217,119,6,0.3) !important;
}
.btn-amber:hover {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(217,119,6,0.45) !important;
}

.btn-blue {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
    color: #ffffff !important;
    font-weight: 700 !important;
    border: none !important;
    box-shadow: 0 4px 14px rgba(37,99,235,0.3) !important;
}
.btn-blue:hover {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%) !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(37,99,235,0.45) !important;
}

@media (max-width: 768px) {
    .att-stat-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 10px !important;
    }
    .sec-card {
        padding: 14px 14px 14px 16px !important;
    }
    .pref-tile {
        padding: 14px 14px !important;
    }
}

@media (max-width: 640px) {
    .sec-card-header {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
    }
    .sec-card-header .sec-card-title-wrap {
        width: 100% !important;
    }
    .sec-card-header .sbtn,
    .sec-card-header .sec-card-action-btn {
        width: 100% !important;
        display: inline-flex !important;
        justify-content: center !important;
        align-items: center !important;
        margin-top: 4px !important;
    }
    .pref-tile.pref-tile-update {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 14px !important;
    }
    .pref-tile.pref-tile-update .pref-tile-btn-wrap,
    .pref-tile.pref-tile-update #checkUpdateBtn {
        width: 100% !important;
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
    }
}

@media (max-width: 576px) {
    .profile-card-inner {
        flex-direction: column !important;
        text-align: center !important;
        align-items: center !important;
        gap: 16px !important;
    }
    .profile-details-col {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
    }
    .profile-actions-row {
        justify-content: center !important;
    }
    .profile-user-id {
        justify-content: center !important;
    }
}

@media (max-width: 480px) {
    .device-item-card {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
        padding: 14px !important;
    }
    .device-item-left {
        width: 100% !important;
    }
    .device-remove-btn {
        width: 100% !important;
        justify-content: center !important;
        padding: 8px 14px !important;
    }
}

    /* â”€â”€ MOBILE RESPONSIVENESS â”€â”€ */
    @media (max-width: 768px) {
        .sp {
            padding-left: 15px !important;
            padding-right: 15px !important;
        }

        .pg-title { font-size: 1.2rem; }
        .pg-sub { font-size: 0.8rem; }

        .stabs-wrapper {
            margin-bottom: 20px;
        }
        .stabs {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            gap: 8px;
            padding: 4px 44px 4px 4px;
            margin-bottom: 0;
        }
        .stabs::-webkit-scrollbar {
            display: none;
        }
        .stab {
            white-space: nowrap;
            padding: 8px 16px;
            font-size: 0.85rem;
        }

        .sc-head {
            padding: 16px 20px;
        }
        .sc-icon {
            width: 32px; height: 32px;
            font-size: 0.9rem;
        }
        .sc-title { font-size: 0.9rem; }
        .sc-sub { font-size: 0.75rem; }
        .sc-body { padding: 20px; }

        .sl { font-size: 0.7rem; }
        .si { font-size: 0.85rem; padding: 10px 12px; }

        .sbtn { padding: 10px 20px; font-size: 0.85rem; }

        .trow {
            flex-direction: column;
            align-items: flex-start;
            gap: 4px;
            padding: 12px 0;
        }
        .tlabel { font-size: 0.85rem; }
        .tsub { font-size: 0.75rem; }

        .stat-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }
        .stat-box {
            padding: 12px 14px;
        }
        .stat-val { font-size: 1.4rem; }
        .stat-lbl { font-size: 0.65rem; }

        .info-row {
            gap: 10px;
            padding: 10px 0;
        }
        .info-icon {
            width: 30px; height: 30px;
            font-size: 0.8rem;
        }
        .info-lbl { font-size: 0.68rem; }
        .info-val { font-size: 0.85rem; }

        .act-row {
            padding: 12px 20px;
            gap: 10px;
        }
    }
</style>

<div class="sp">

    <div style="margin-bottom:24px;">
        <div class="pg-title">Settings</div>
        <div class="pg-sub">Manage your account, security, and preferences</div>
    </div>

    @if(session('success'))
    <div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- TABS -->
    <div class="stabs-wrapper">
        <button type="button" class="stabs-arrow stabs-arrow-left" id="stabsArrowLeft" onclick="scrollStabs('left')" aria-label="Scroll left">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="stabs" id="stabsNav">
            <button class="stab active" data-tab="profile" onclick="switchTab('profile',this)"><i class="bi bi-person-circle me-1"></i> Profile</button>
            <button class="stab" data-tab="security" onclick="switchTab('security',this)"><i class="bi bi-shield-lock-fill me-1"></i> Security</button>
            <button class="stab" data-tab="fingerprint" onclick="switchTab('fingerprint',this)"><i class="bi bi-fingerprint me-1"></i> Fingerprint</button>
            <button class="stab" data-tab="attendance" onclick="switchTab('attendance',this)"><i class="bi bi-bar-chart-fill me-1"></i> Attendance</button>
            <button class="stab" data-tab="preferences" data-tab-id="preferences" onclick="switchTab('preferences',this)"><i class="bi bi-sliders me-1"></i> Preferences</button>
        </div>
        <button type="button" class="stabs-arrow stabs-arrow-right" id="stabsArrowRight" onclick="scrollStabs('right')" aria-label="Scroll right">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- ── TAB: PROFILE ── -->
    <div id="tab-profile" class="spanel active">

        <!-- Avatar / Profile Photo Card -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(207,164,111,0.14);color:#cfa46f;"><i class="bi bi-person-bounding-box"></i></div>
                <div>
                    <div class="sc-title">Profile Photo</div>
                    <div class="sc-sub">Manage your avatar and account appearance</div>
                </div>
            </div>
            <div class="sc-body">
                <form action="{{ route('profile.image.update') }}" method="POST" enctype="multipart/form-data" id="settingsProfileImageForm">
                    @csrf
                    <input type="file" name="profile_image" id="imgInput" class="d-none" accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,image/heic,image/heif,image/*" onchange="handleSettingsAvatarUpload(this)">
                    
                    <div class="profile-card-inner">
                        <!-- Avatar clickable area with floating camera badge -->
                        <label for="imgInput" class="profile-avatar-holder" title="Tap to change profile picture">
                            <div class="profile-avatar-img-wrap">
                                @if(Auth::user()->profile_image)
                                    <img id="settingsAvatarDisplay" class="profile-avatar-img" src="{{ str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image) }}"
                                         onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200'">
                                @else
                                    <img id="settingsAvatarDisplay" class="profile-avatar-img" src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&background=800000&color=fff&size=200">
                                @endif
                                <div id="settingsAvatarOverlay" style="position:absolute;inset:0;background:rgba(0,0,0,.6);display:flex;flex-direction:column;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;border-radius:50%;color:white;">
                                    <i class="bi bi-camera-fill" style="font-size:1.3rem;"></i>
                                    <span style="font-size:0.6rem;font-weight:700;">Change</span>
                                </div>
                            </div>
                            <div class="profile-avatar-badge" aria-hidden="true">
                                <i class="bi bi-camera-fill"></i>
                            </div>
                        </label>

                        <!-- User details column -->
                        <div class="profile-details-col">
                            <div class="profile-user-name">{{ Auth::user()->name }}</div>
                            <div class="profile-user-id">
                                <i class="bi bi-person-badge"></i>
                                <span>{{ Auth::user()->student_number ?? Auth::user()->email }}</span>
                            </div>
                            <div class="profile-actions-row">
                                @if(Auth::user()->course)
                                    <span class="profile-badge-course">{{ Auth::user()->course }}</span>
                                @endif
                                @if(Auth::user()->year_level)
                                    <span class="profile-badge-year">Year {{ Auth::user()->year_level }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Academic Info -->
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-mortarboard-fill"></i></div>
                <div><div class="sc-title">Academic Information</div><div class="sc-sub">Contact admin to update enrollment details</div></div>
            </div>
            <div class="sc-body">
                <div class="info-row"><div class="info-icon"><i class="bi bi-person-fill"></i></div><div><div class="info-lbl">Full Name</div><div class="info-val">{{ Auth::user()->name }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-card-text"></i></div><div><div class="info-lbl">Student ID</div><div class="info-val">{{ Auth::user()->student_number }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-book-fill"></i></div><div><div class="info-lbl">Course</div><div class="info-val">{{ Auth::user()->course }}</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-layers-fill"></i></div><div><div class="info-lbl">Year Level</div><div class="info-val">{{ Auth::user()->year_level }}{{ match((int)Auth::user()->year_level){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Year</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-calendar3"></i></div><div><div class="info-lbl">Semester</div><div class="info-val">{{ Auth::user()->semester }}{{ match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} }} Semester</div></div></div>
                <div class="info-row"><div class="info-icon"><i class="bi bi-envelope-fill"></i></div><div><div class="info-lbl">Email</div><div class="info-val">{{ Auth::user()->email }}</div></div></div>
            </div>
        </div>
    </div>

    <!-- ── TAB: SECURITY ── -->
    <div id="tab-security" class="spanel">

        <!-- ── Security Hero Header ── -->
        <div class="sec-health-hero">
            <div class="sec-health-left">
                <div class="sec-health-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <div class="sec-health-title">Security & Access Control</div>
                    <div class="sec-health-sub">Manage credentials, biometric authentication, and emergency vault</div>
                </div>
            </div>
            <div class="sec-health-pill">
                <span class="sec-pulse-dot"></span>
                <span>Account Protected</span>
            </div>
        </div>

        <!-- ── Real-Time Security Matrix Strip ── -->
        <div class="sec-matrix-bar">
            <div class="sec-matrix-item">
                <i class="bi bi-envelope-check-fill text-primary"></i>
                <span>Email Verified</span>
            </div>
            <div class="sec-matrix-item">
                <i class="bi bi-key-fill text-warning"></i>
                <span>bcrypt-12 Hashed</span>
            </div>
            <div class="sec-matrix-item">
                <i class="bi bi-fingerprint text-success"></i>
                <span>FIDO2 Ready</span>
            </div>
            <div class="sec-matrix-item">
                <i class="bi bi-safe-fill" style="color:#f5dfa8;"></i>
                <span>Recovery Vault</span>
            </div>
        </div>

        <!-- ── 2-Column Responsive Security Cards Grid ── -->
        <div class="sec-cards-grid">

            <!-- ── Card 1: Email Address Management ── -->
            <div class="sec-card" style="--card-accent: #3b82f6;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(59,130,246,0.12);color:#60a5fa;border:1px solid rgba(59,130,246,0.25);">
                        <i class="bi bi-envelope-check-fill"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Primary Email Address</span>
                            <span class="sec-badge sec-badge-blue"><i class="bi bi-patch-check-fill"></i> Verified</span>
                        </div>
                        <div class="sec-card-subtitle">Primary channel for portal alerts & OTP security codes</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div id="emailStep1">
                        <div class="sec-input-display">
                            <i class="bi bi-envelope-at-fill text-primary me-2"></i>
                            <span class="sec-input-val" id="displayUserEmail">{{ Auth::user()->email }}</span>
                            <button type="button" onclick="navigator.clipboard.writeText('{{ Auth::user()->email }}'); if(typeof showToast==='function') showToast('Email address copied!','info');" class="sec-copy-btn" title="Copy email">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                        <p class="sec-card-hint">
                            A 6-digit security code will be sent to your current email before updating.
                        </p>
                        <button type="button" onclick="requestEmailOtp()" id="sendEmailOtpBtn" class="sec-action-btn sec-btn-blue">
                            <i class="bi bi-send-fill me-2"></i>Send Verification OTP
                        </button>
                    </div>

                    <div id="emailStep2" style="display:none;">
                        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.25);color:#4ade80;border-radius:10px;padding:10px 12px;font-size:0.78rem;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-envelope-check-fill" style="font-size:1.05rem;"></i>
                            <span>Code sent to <strong>{{ Auth::user()->email }}</strong></span>
                        </div>
                        <form action="{{ route('otp.email.change') }}" method="POST">
                            @csrf
                            <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">Enter 6-Digit Code</label>
                            <div style="display:flex;gap:6px;margin-bottom:12px;justify-content:space-between;">
                                @for($j=1;$j<=6;$j++)
                                <input type="text" class="email-otp-digit" maxlength="1" inputmode="numeric" id="ed{{$j}}" style="flex:1;min-width:0;max-width:44px;height:42px;border-radius:8px;border:1.5px solid rgba(255,215,145,0.15);font-size:1.15rem;font-weight:800;text-align:center;color:#f3e7cd;background:rgba(255,235,190,0.06);outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="emailOtpHidden">
                            <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">New Email Address</label>
                            <input type="email" name="new_email" class="si" placeholder="name@example.com" style="margin-bottom:12px;padding:9px 12px;font-size:0.84rem;" required>
                            <div style="display:flex;gap:8px;">
                                <button type="button" onclick="cancelEmailOtp()" class="cancel-btn" style="flex:0 0 auto;padding:8px 14px;font-size:0.8rem;">Cancel</button>
                                <button type="button" class="sec-action-btn sec-btn-blue" style="flex:1;" onclick="collectEmailOtp(this)"><i class="bi bi-check2-circle me-1"></i>Confirm Email</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Card 2: Password Authentication ── -->
            <div class="sec-card" style="--card-accent: #f59e0b;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(207,164,111,0.12);color:#f5dfa8;border:1px solid rgba(207,164,111,0.25);">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Password Authentication</span>
                            <span class="sec-badge sec-badge-amber"><i class="bi bi-shield-lock-fill"></i> Encrypted</span>
                        </div>
                        <div class="sec-card-subtitle">Protected by salted bcrypt-12 hashing & rate limits</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div id="otpStep1">
                        <div class="sec-status-tile">
                            <i class="bi bi-shield-check text-warning"></i>
                            <span>Active • Encrypted with salted bcrypt-12</span>
                        </div>
                        <p class="sec-card-hint">
                            A verification code is required before creating a new password.
                        </p>
                        <button type="button" onclick="requestOtp()" id="sendOtpBtn" class="sec-action-btn sec-btn-amber">
                            <i class="bi bi-shield-lock-fill me-2"></i>Request Password Reset OTP
                        </button>
                    </div>

                    <div id="otpStep2" style="display:none;">
                        <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.25);color:#4ade80;border-radius:10px;padding:10px 12px;font-size:0.78rem;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-check-circle-fill" style="font-size:1.05rem;"></i>
                            <span>OTP sent to <strong>{{ Auth::user()->email }}</strong></span>
                        </div>
                        <form action="{{ route('otp.change') }}" method="POST">
                            @csrf
                            <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">Enter 6-Digit Code</label>
                            <div style="display:flex;gap:6px;margin-bottom:12px;justify-content:space-between;">
                                @for($i=1;$i<=6;$i++)
                                <input type="text" class="otp-digit-s" maxlength="1" inputmode="numeric" id="sd{{$i}}" style="flex:1;min-width:0;max-width:44px;height:42px;border-radius:8px;border:1.5px solid rgba(255,215,145,0.15);font-size:1.15rem;font-weight:800;text-align:center;color:#f3e7cd;background:rgba(255,235,190,0.06);outline:none;transition:all .2s;">
                                @endfor
                            </div>
                            <input type="hidden" name="otp" id="settingsOtpHidden">
                            <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">New Password</label>
                            <div class="pw-wrap" style="margin-bottom:10px;">
                                <input type="password" name="password" id="spw1" class="si" placeholder="Minimum 8 characters" style="padding:9px 12px;font-size:0.84rem;" required>
                                <button type="button" class="eye-btn" onclick="togglePw('spw1',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                            <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">Confirm Password</label>
                            <div class="pw-wrap" style="margin-bottom:14px;">
                                <input type="password" name="password_confirmation" id="spw2" class="si" placeholder="Repeat new password" style="padding:9px 12px;font-size:0.84rem;" required>
                                <button type="button" class="eye-btn" onclick="togglePw('spw2',this)" tabindex="-1"><i class="bi bi-eye-slash"></i></button>
                            </div>
                            <div style="display:flex;gap:8px;">
                                <button type="button" onclick="cancelOtp()" class="cancel-btn" style="flex:0 0 auto;padding:8px 14px;font-size:0.8rem;">Cancel</button>
                                <button type="button" class="sec-action-btn sec-btn-amber" style="flex:1;" onclick="collectOtp(this)"><i class="bi bi-check2-circle me-1"></i>Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── Card 3: Biometric & Fingerprint Login ── -->
            <div class="sec-card" style="--card-accent: #22c55e;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(34,197,94,0.12);color:#4ade80;border:1px solid rgba(34,197,94,0.25);">
                        <i class="bi bi-fingerprint"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Biometric & Fingerprint Login</span>
                            <span class="sec-badge sec-badge-green"><i class="bi bi-patch-check-fill"></i> FIDO2 Ready</span>
                        </div>
                        <div class="sec-card-subtitle">Hardware-grade passwordless biometric authentication</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div class="sec-feature-chips">
                        <span><i class="bi bi-check2"></i> Touch ID</span>
                        <span><i class="bi bi-check2"></i> Face ID</span>
                        <span><i class="bi bi-check2"></i> Windows Hello</span>
                        <span><i class="bi bi-qr-code"></i> Fast QR Clock-In</span>
                    </div>
                    <p class="sec-card-hint">
                        Sign in instantly and verify classroom attendance QR scans without typing passwords.
                    </p>
                    <button type="button" onclick="switchTab('fingerprint')" class="sec-action-btn sec-btn-emerald">
                        <i class="bi bi-fingerprint me-2"></i>Manage Biometrics & Devices
                    </button>
                </div>
            </div>

            <!-- ── Card 4: Emergency Recovery Codes Vault ── -->
            <div class="sec-card" style="--card-accent: #cfa46f;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(234,179,8,0.12);color:#fbbf24;border:1px solid rgba(234,179,8,0.25);">
                        <i class="bi bi-safe-fill"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Emergency Recovery Vault</span>
                            <span class="sec-badge sec-badge-gold"><i class="bi bi-key-fill"></i> Backup Keys</span>
                        </div>
                        <div class="sec-card-subtitle">One-time offline emergency keys to restore account access</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div class="sec-status-tile">
                        <i class="bi bi-info-circle-fill text-warning"></i>
                        <span>Generating new keys invalidates all previous codes</span>
                    </div>
                    <p class="sec-card-hint">
                        Store emergency codes safely in a secure password manager or offline notes.
                    </p>
                    <button type="button" onclick="generateRecoveryCodes()" id="generateCodesBtn" class="sec-action-btn sec-btn-gold">
                        <i class="bi bi-key-fill me-2"></i>Generate Recovery Codes
                    </button>
                    
                    <div id="recoveryCodesList" style="display:none;margin-top:14px;background:rgba(18,12,10,0.95);border:1px solid rgba(207,164,111,0.25);border-radius:12px;padding:14px;box-shadow:0 8px 24px rgba(0,0,0,0.55);">
                        <div style="font-size:.76rem;font-weight:700;color:#f87171;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-exclamation-triangle-fill"></i> Store these codes safely. Only shown once!
                        </div>
                        <div id="codesContainer" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(105px, 1fr));gap:6px;margin-bottom:10px;">
                            <!-- Codes injected here -->
                        </div>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button type="button" onclick="copyAllRecoveryCodes(this)" class="sec-action-btn sec-btn-gold" style="flex:1;padding:7px 10px;font-size:0.76rem;">
                                <i class="bi bi-clipboard-check me-1"></i>Copy All
                            </button>
                            <button type="button" onclick="downloadRecoveryCodes()" class="cancel-btn" style="padding:7px 10px;font-size:0.76rem;border-radius:10px;">
                                <i class="bi bi-download me-1"></i>TXT
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- ── TAB: FINGERPRINT ── -->
    <div id="tab-fingerprint" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(34,197,94,0.14);color:#4ade80;"><i class="bi bi-fingerprint"></i></div>
                <div>
                    <div class="sc-title">Biometric & Fingerprint Security</div>
                    <div class="sc-sub">Hardware-grade FIDO2 / WebAuthn biometric authentication on this device</div>
                </div>
            </div>
            <div class="sc-body">

                <!-- In-app browser alert -->
                <div id="webauthnUnsupported" style="display:none;background:rgba(248,113,113,0.08);border:1px solid rgba(248,113,113,0.25);color:#f87171;border-radius:14px;padding:16px 20px;font-size:.85rem;margin-bottom:20px;">
                    <div style="display:flex;align-items:flex-start;gap:12px;">
                        <i class="bi bi-exclamation-triangle" style="font-size:1.2rem;flex-shrink:0;margin-top:2px;"></i>
                        <div>
                            <div style="font-weight:700;margin-bottom:4px;">Biometric login not available on this browser</div>
                            <div id="webauthnUnsupportedMsg" style="font-size:.8rem;opacity:.85;line-height:1.5;">
                                You're using an in-app browser that doesn't support fingerprint/biometric login. Please open this page in <strong>Chrome</strong> or <strong>Safari</strong> to register your fingerprint.
                            </div>
                            <a id="openInBrowserBtn" href="#" onclick="openInSystemBrowser()" style="display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:8px 16px;background:rgba(248,113,113,0.15);border:1px solid rgba(248,113,113,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;">
                                <i class="bi bi-box-arrow-up-right"></i> Open in Browser
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Hero Biometric Card -->
                <div style="display:flex;align-items:center;gap:20px;background:linear-gradient(135deg,rgba(34,197,94,0.08) 0%,rgba(207,164,111,0.04) 100%);border:1px solid rgba(34,197,94,0.2);border-radius:16px;padding:22px;margin-bottom:24px;flex-wrap:wrap;">
                    <div class="fp-radar-wrap">
                        <i class="bi bi-fingerprint" style="font-size:2rem;color:#4ade80;"></i>
                    </div>
                    <div style="flex:1;min-width:200px;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                            <span style="font-size:1.05rem;font-weight:800;color:#f3e7cd;">FIDO2 / WebAuthn Hardware Security</span>
                            <span style="font-size:0.68rem;font-weight:700;background:rgba(34,197,94,0.18);color:#4ade80;padding:2px 8px;border-radius:99px;border:1px solid rgba(34,197,94,0.3);">Certified</span>
                        </div>
                        <p style="font-size:0.8rem;color:#b39b82;margin:0;line-height:1.5;">
                            Biometric signatures never leave your local hardware device. Register once to log in and sign attendance without typing passwords.
                        </p>
                    </div>
                </div>

                <!-- Feature Highlights -->
                <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));gap:12px;margin-bottom:24px;">
                    <div style="background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:16px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(207,164,111,0.15);color:var(--gold,#cfa46f);display:flex;align-items:center;justify-content:center;font-size:.9rem;"><i class="bi bi-shield-lock"></i></div>
                            <span style="font-size:.85rem;font-weight:700;color:#f3e7cd;">Instant Sign-In</span>
                        </div>
                        <p style="font-size:.76rem;color:#b39b82;margin:0;line-height:1.4;">Unlock your account in milliseconds using your fingerprint sensor or Face ID.</p>
                    </div>
                    <div style="background:rgba(255,235,190,0.03);border:1px solid rgba(255,215,145,0.08);border-radius:12px;padding:16px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(34,197,94,0.15);color:#4ade80;display:flex;align-items:center;justify-content:center;font-size:.9rem;"><i class="bi bi-qr-code-scan"></i></div>
                            <span style="font-size:.85rem;font-weight:700;color:#f3e7cd;">QR Attendance Clock-In</span>
                        </div>
                        <p style="font-size:.76rem;color:#b39b82;margin:0;line-height:1.4;">Verify your identity securely when scanning teacher classroom attendance QR codes.</p>
                    </div>
                </div>

                <!-- Registered Devices List -->
                <div style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;">
                        <div style="font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;flex:1;min-width:0;">Registered Hardware Credentials</div>
                        <span id="deviceCountBadge" style="font-size:.72rem;background:rgba(207,164,111,0.12);color:var(--gold,#cfa46f);padding:3px 10px;border-radius:99px;border:1px solid rgba(207,164,111,0.25);font-weight:700;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;">Loading...</span>
                    </div>
                    <div id="deviceList">
                        <div style="text-align:center;padding:32px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:14px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                            <i class="bi bi-fingerprint" style="font-size:2.6rem;display:block;margin-bottom:10px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                            <div style="font-weight:700;color:#f3e7cd;margin-bottom:4px;">No biometric credentials registered yet</div>
                            <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in and QR clock-in.</div>
                        </div>
                    </div>
                </div>

                <!-- Register action -->
                <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                    <button type="button" onclick="registerFingerprint()" id="registerFpBtn" class="sbtn btn-emerald" style="padding:10px 22px;font-size:0.85rem;">
                        <i class="bi bi-fingerprint me-2"></i>Register This Device
                    </button>
                    <span style="font-size:.78rem;color:#b39b82;"><i class="bi bi-shield-check me-1 text-success"></i>FIDO2 / WebAuthn standard security</span>
                </div>
                <div id="fpMessage" style="margin-top:14px;font-size:.82rem;display:none;"></div>
            </div>
        </div>
    </div>

    <!-- ── TAB: ATTENDANCE ── -->
    <div id="tab-attendance" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(34,197,94,0.12);color:#4ade80;"><i class="bi bi-bar-chart-fill"></i></div>
                <div>
                    <div class="sc-title">Attendance Overview & Analytics</div>
                    <div class="sc-sub">Your complete academic standing and attendance performance summary</div>
                </div>
            </div>
            <div class="sc-body">

                <!-- 4 KPI Stat Grid -->
                <div class="att-stat-grid">
                    <div class="att-stat-card">
                        <div class="att-stat-val" style="color:#f3e7cd;">{{ $totalRecords }}</div>
                        <div class="att-stat-label">Total Classes</div>
                    </div>
                    <div class="att-stat-card" style="border-color:rgba(34,197,94,0.25)!important;background:rgba(34,197,94,0.06)!important;">
                        <div class="att-stat-val" style="color:#4ade80;">{{ $totalPresent }}</div>
                        <div class="att-stat-label" style="color:#86efac;">Present</div>
                    </div>
                    <div class="att-stat-card" style="border-color:rgba(234,179,8,0.25)!important;background:rgba(234,179,8,0.06)!important;">
                        <div class="att-stat-val" style="color:#fbbf24;">{{ $totalLate }}</div>
                        <div class="att-stat-label" style="color:#fde68a;">Late</div>
                    </div>
                    <div class="att-stat-card" style="border-color:rgba(239,68,68,0.25)!important;background:rgba(239,68,68,0.06)!important;">
                        <div class="att-stat-val" style="color:#f87171;">{{ $totalAbsent }}</div>
                        <div class="att-stat-label" style="color:#fca5a5;">Absent</div>
                    </div>
                </div>

                <!-- Overall Standing Gauge -->
                <div class="att-gauge-card">
                    <div class="att-gauge-header">
                        <div>
                            <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;color:#b39b82;">Overall Attendance Rate</div>
                            <div style="font-size:1.3rem;font-weight:800;color:{{ $rate >= 75 ? '#4ade80' : '#f87171' }};margin-top:2px;">
                                {{ $rate }}%
                                <span style="font-size:0.75rem;font-weight:700;margin-left:8px;padding:3px 10px;border-radius:99px;background:{{ $rate >= 90 ? 'rgba(34,197,94,0.15)' : ($rate >= 75 ? 'rgba(234,179,8,0.15)' : 'rgba(239,68,68,0.15)') }};color:{{ $rate >= 90 ? '#4ade80' : ($rate >= 75 ? '#fbbf24' : '#f87171') }};border:1px solid currentColor;">
                                    {{ $rate >= 90 ? 'Excellent Standing' : ($rate >= 75 ? 'Good Standing' : 'Attention Needed') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    @php
                        $presPct = $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0;
                        $latePct = $totalRecords > 0 ? round(($totalLate / $totalRecords) * 100, 1) : 0;
                        $absPct  = $totalRecords > 0 ? round(($totalAbsent / $totalRecords) * 100, 1) : 0;
                    @endphp

                    <!-- Segmented Multi-Color Distribution Bar -->
                    <div class="att-segmented-bar">
                        @if($totalRecords > 0)
                            <div class="att-seg-present" style="width:{{ $presPct }}%;" title="Present: {{ $presPct }}%"></div>
                            <div class="att-seg-late" style="width:{{ $latePct }}%;" title="Late: {{ $latePct }}%"></div>
                            <div class="att-seg-absent" style="width:{{ $absPct }}%;" title="Absent: {{ $absPct }}%"></div>
                        @else
                            <div style="width:100%;background:rgba(255,255,255,0.08);"></div>
                        @endif
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;font-size:0.75rem;color:#b39b82;flex-wrap:wrap;gap:8px;">
                        <div style="display:flex;gap:14px;align-items:center;">
                            <span><i class="bi bi-circle-fill me-1" style="color:#22c55e;font-size:0.6rem;"></i>Present ({{ $presPct }}%)</span>
                            <span><i class="bi bi-circle-fill me-1" style="color:#f59e0b;font-size:0.6rem;"></i>Late ({{ $latePct }}%)</span>
                            <span><i class="bi bi-circle-fill me-1" style="color:#ef4444;font-size:0.6rem;"></i>Absent ({{ $absPct }}%)</span>
                        </div>
                        <div>
                            {{ $rate >= 75 ? 'Maintaining compliant attendance standing.' : 'Attendance is below 75%. Please submit excuse slips if applicable.' }}
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div style="border-top:1px solid rgba(255,215,145,0.08);padding-top:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <div style="font-size:0.8rem;font-weight:700;color:#f3e7cd;text-transform:uppercase;letter-spacing:0.5px;">Recent Class Attendance</div>
                        <a href="{{ route('attendance.records') }}" style="font-size:0.78rem;font-weight:700;color:#cfa46f;text-decoration:none;">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    @php $recent = Auth::user()->attendances()->with('subject')->latest('date')->take(5)->get(); @endphp
                    <div style="display:flex;flex-direction:column;gap:8px;">
                        @forelse($recent as $r)
                        <div class="act-row" style="background:rgba(255,235,190,0.02);border:1px solid rgba(255,215,145,0.06);border-radius:12px;padding:12px 16px;">
                            <div style="width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;
                                {{ $r->status=='Present'?'background:rgba(34,197,94,0.15);color:#4ade80;':($r->status=='Late'?'background:rgba(234,179,8,0.15);color:#fbbf24;':'background:rgba(239,68,68,0.15);color:#f87171;') }}">
                                <i class="bi {{ $r->status=='Present'?'bi-check2-circle':($r->status=='Late'?'bi-clock':'bi-x-circle') }}" style="font-size:1.1rem;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:.875rem;font-weight:700;color:#f3e7cd;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r->subject->name ?? $r->subject_code }}</div>
                                <div style="font-size:.74rem;color:#b39b82;">{{ \Carbon\Carbon::parse($r->date)->format('M d, Y') }} • {{ $r->time_in ? \Carbon\Carbon::parse($r->time_in)->format('h:i A') : 'Recorded' }}</div>
                            </div>
                            <span style="font-size:.72rem;font-weight:800;padding:4px 12px;border-radius:99px;
                                {{ $r->status=='Present'?'background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.3);':($r->status=='Late'?'background:rgba(234,179,8,0.15);color:#fbbf24;border:1px solid rgba(234,179,8,0.3);':'background:rgba(239,68,68,0.15);color:#f87171;border:1px solid rgba(239,68,68,0.3);') }}">
                                {{ $r->status }}
                            </span>
                        </div>
                        @empty
                        <div style="text-align:center;padding:32px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.15);">
                            No attendance recorded yet.
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- ── TAB: PREFERENCES ── -->
    <div id="tab-preferences" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(59,130,246,0.14);color:#60a5fa;"><i class="bi bi-sliders"></i></div>
                <div>
                    <div class="sc-title">System Preferences & Portal Settings</div>
                    <div class="sc-sub">Customize alerts, language, interface layout, and offline updates</div>
                </div>
            </div>
            <div class="sc-body">

                <!-- Language Selection -->
                <div class="pref-tile">
                    <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                        <div class="pref-tile-icon"><i class="bi bi-translate"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="tlabel">System Display Language</div>
                            <div class="tsub">Choose your portal language</div>
                        </div>
                    </div>
                    <select class="si" style="width:auto;padding:7px 14px;font-size:0.82rem;border-radius:10px;flex-shrink:0;">
                        <option>English (US)</option>
                        <option>Filipino</option>
                        <option>Bikolano</option>
                    </select>
                </div>

                <form action="{{ route('settings.preferences.update') }}" method="POST">
                    @csrf
                    <div style="margin:20px 0 10px;font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Notification Alerts</div>
                    
                    @php
                        $prefs = Auth::user()->notification_preferences ?? ['in_app' => true, 'email' => true];
                    @endphp

                    <!-- In-App Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-app-indicator"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="tlabel">In-App Notifications</div>
                                <div class="tsub">Receive instant badges and banners inside the portal</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" name="prefs[in_app]" value="1" {{ !empty($prefs['in_app']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    
                    <!-- Email Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-envelope"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="tlabel">Email Notifications</div>
                                <div class="tsub">Receive attendance summaries, excuse approvals, and security alerts via email</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" name="prefs[email]" value="1" {{ !empty($prefs['email']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <!-- Web Push Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:flex-start;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon" style="margin-top:2px;"><i class="bi bi-bell-fill"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="tlabel" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                    <span>Web Push Notifications</span>
                                    <span class="push-status-badge badge-inactive" style="font-size:0.7rem;padding:2px 8px;border-radius:999px;background:rgba(207,164,111,0.15);color:#cfa46f;border:1px solid rgba(207,164,111,0.3);font-weight:700;flex-shrink:0;">Checking...</span>
                                </div>
                                <div class="tsub" style="margin-top:2px;">Receive background push notifications even when the browser tab is closed.</div>
                                <div class="mt-2">
                                    <button type="button" onclick="WebPushManager.sendTest()" class="push-test-btn sbtn btn-emerald" style="display:none;padding:4px 12px;font-size:0.72rem;">
                                        <i class="bi bi-send-check me-1"></i> Send Test Push Alert
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input push-toggle-input" type="checkbox" onchange="toggleWebPush(this)">
                        </div>
                    </div>

                    <!-- SMS Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-chat-dots"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="tlabel">SMS Urgent Notifications</div>
                                <div class="tsub">Emergency announcements and absence warnings via text message</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" name="prefs[sms]" value="1" {{ !empty($prefs['sms']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <!-- Display Interface -->
                    <div style="margin:20px 0 10px;font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Display & Layout</div>
                    
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-layout-sidebar"></i></div>
                            <div style="flex:1;min-width:0;">
                                <div class="tlabel">Compact Sidebar</div>
                                <div class="tsub">Keep sidebar collapsed on desktop for extra dashboard space</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" id="compactToggle">
                        </div>
                    </div>

                    <div style="margin-top: 20px; text-align: right;">
                        <button type="submit" class="sbtn btn-gold"><i class="bi bi-save me-2"></i>Save Preferences</button>
                    </div>
                </form>

                <hr style="border:0; border-top:1px solid rgba(255,255,255,0.08); margin: 26px 0 20px;">

                <!-- App & System Software Updates -->
                <div style="margin-bottom:10px;font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">App & System Updates</div>
                <div class="pref-tile pref-tile-update" style="gap: 16px;">
                    <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                        <div class="pref-tile-icon"><i class="bi bi-arrow-repeat"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="tlabel" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span>Software Updates & PWA Assets</span>
                                <span class="badge" style="background:rgba(207,164,111,0.15);color:var(--gold,#cfa46f);border:1px solid rgba(207,164,111,0.3);font-size:0.72rem;font-weight:700;flex-shrink:0;">v{{ config('changelog.default_version', '1.4.3') }}</span>
                            </div>
                            <div class="tsub" id="updateStatusText">Check for latest software features, security patches, and offline assets.</div>
                        </div>
                    </div>
                    <div class="pref-tile-btn-wrap">
                        <button type="button" id="checkUpdateBtn" onclick="checkForAppUpdates()" class="sbtn btn-gold" style="padding:8px 18px;font-size:0.82rem;white-space:nowrap;">
                            <i class="bi bi-cloud-arrow-down me-1"></i> Check Updates
                        </button>
                    </div>
                </div>
                <div id="updateFeedbackArea" style="display:none;margin-top:12px;padding:14px 18px;border-radius:12px;font-size:0.85rem;line-height:1.5;"></div>
            </div>
        </div>
    </div>

</div>

<script nonce="{{ csp_nonce() }}">
async function checkForAppUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const feedback = document.getElementById('updateFeedbackArea');
    const statusText = document.getElementById('updateStatusText');

    if (!btn || !feedback) return;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" style="width:14px;height:14px;"></span> Checking...';
    feedback.style.display = 'block';
    feedback.style.background = 'rgba(59, 130, 246, 0.1)';
    feedback.style.border = '1px solid rgba(59, 130, 246, 0.3)';
    feedback.style.color = '#93c5fd';
    feedback.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Connecting to server and checking for updates...';

    if (!navigator.onLine) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-wifi-off me-2"></i>You are currently offline. Please connect to the internet to check for updates.';
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
        return;
    }

    try {
        if (typeof checkServerVersion === 'function') {
            await checkServerVersion(true);
        } else if ('serviceWorker' in navigator) {
            const reg = await navigator.serviceWorker.getRegistration();
            if (reg) {
                await reg.update();
                if (reg.waiting && typeof showAppUpdatePopup === 'function') {
                    showAppUpdatePopup(null, true);
                }
            }
        }

        await new Promise(r => setTimeout(r, 600));

        const popup = document.getElementById('pwaSystemUpdatePopup');
        const popupVisible = popup && window.getComputedStyle(popup).display !== 'none';

        if (popupVisible) {
            feedback.style.background = 'rgba(207, 164, 111, 0.12)';
            feedback.style.border = '1px solid rgba(207, 164, 111, 0.35)';
            feedback.style.color = '#f3e7cd';
            feedback.innerHTML = '<i class="bi bi-stars me-2"></i>A new software update is available! Tap "Restart & Update" on the notification to install.';
        } else {
            feedback.style.background = 'rgba(16, 185, 129, 0.1)';
            feedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            feedback.style.color = '#6ee7b7';
            feedback.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Your application is up to date (v{{ config('changelog.default_version', '1.4.3') }}). You have the latest version installed.';
        }
        if (statusText) statusText.textContent = 'Last checked: Just now';
    } catch (e) {
        feedback.style.background = 'rgba(239, 68, 68, 0.1)';
        feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
        feedback.style.color = '#fca5a5';
        feedback.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Unable to complete update check: ' + (e.message || 'Network error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-cloud-arrow-down me-1"></i> Check for Updates';
    }
}

function applySwUpdate() {
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistration().then(reg => {
            if (reg && reg.waiting) {
                reg.waiting.postMessage({ action: 'skipWaiting' });
            } else {
                window.location.reload();
            }
        });
    } else {
        window.location.reload();
    }
}

function updateStabsScrollArrows() {
    const nav = document.getElementById('stabsNav');
    const leftBtn = document.getElementById('stabsArrowLeft');
    const rightBtn = document.getElementById('stabsArrowRight');
    const wrapper = nav?.closest('.stabs-wrapper');
    if (!nav || !leftBtn || !rightBtn) return;

    const maxScrollLeft = nav.scrollWidth - nav.clientWidth;

    if (maxScrollLeft <= 8) {
        leftBtn.classList.remove('visible');
        rightBtn.style.display = 'none';
        wrapper?.classList.remove('has-scroll-left', 'has-scroll-right');
        return;
    }

    rightBtn.style.display = '';

    // Toggle left button visibility when scrolled right
    if (nav.scrollLeft > 10) {
        leftBtn.classList.add('visible');
        wrapper?.classList.add('has-scroll-left');
    } else {
        leftBtn.classList.remove('visible');
        wrapper?.classList.remove('has-scroll-left');
    }

    // Always keep right arrow clearly visible on mobile & desktop
    if (nav.scrollLeft >= maxScrollLeft - 8) {
        rightBtn.innerHTML = '<i class="bi bi-arrow-repeat"></i>';
        rightBtn.setAttribute('title', 'Scroll to start');
        wrapper?.classList.remove('has-scroll-right');
    } else {
        rightBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        rightBtn.setAttribute('title', 'Scroll tabs');
        wrapper?.classList.add('has-scroll-right');
    }
}

window.scrollStabs = function(direction) {
    const nav = document.getElementById('stabsNav');
    if (!nav) return;
    const maxScrollLeft = nav.scrollWidth - nav.clientWidth;

    if (direction === 'right' && maxScrollLeft > 10 && nav.scrollLeft >= maxScrollLeft - 5) {
        nav.scrollTo({ left: 0, behavior: 'smooth' });
    } else {
        const distance = 180;
        nav.scrollBy({
            left: direction === 'right' ? distance : -distance,
            behavior: 'smooth'
        });
    }
    setTimeout(updateStabsScrollArrows, 250);
};

window.switchTab = function(id, btn) {
    if (!id) return;
    document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById('tab-' + id);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }

    let targetBtn = btn;
    if (!targetBtn || !targetBtn.classList || !targetBtn.classList.contains('stab')) {
        targetBtn = document.querySelector(`.stab[data-tab="${id}"]`) || Array.from(document.querySelectorAll('.stab')).find(b => b.textContent.toLowerCase().includes(id));
    }
    if (targetBtn) {
        targetBtn.classList.add('active');
        targetBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    if (id === 'fingerprint') {
        if (typeof loadDevices === 'function') loadDevices();
        if (typeof prefetchWebAuthn === 'function') prefetchWebAuthn();
    }
    if (window.triggerHaptic) window.triggerHaptic('light');
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, '#tab-' + id);
    }
    setTimeout(updateStabsScrollArrows, 300);
};

async function toggleWebPush(input) {
    if (input.checked) {
        const ok = await WebPushManager.subscribe();
        if (!ok) input.checked = false;
    } else {
        await WebPushManager.unsubscribe();
    }
}
function togglePw(id, btn) {
    const i = document.getElementById(id);
    const ic = btn.querySelector('i');
    if (i.type === 'password') { i.type = 'text'; ic.className = 'bi bi-eye'; btn.style.color = '#800000'; }
    else { i.type = 'password'; ic.className = 'bi bi-eye-slash'; btn.style.color = ''; }
}
// Compact sidebar toggle
const ct = document.getElementById('compactToggle');
if (ct) {
    ct.checked = localStorage.getItem('sidebarMini') === 'true';
    ct.addEventListener('change', function() {
        localStorage.setItem('sidebarMini', this.checked);
        location.reload();
    });
}
// Auto-open security tab on validation errors
@if($errors->any()) switchTab('security', document.querySelectorAll('.stab')[1]); @endif

// ── In-app browser detection ──
function isInAppBrowser() {
    var ua = navigator.userAgent || '';
    // Detect Facebook, Messenger, Instagram, LINE, Twitter, Snapchat, etc.
    return /FBAN|FBAV|FB_IAB|FBIOS|Instagram|Line\/|Twitter|Snapchat|MicroMessenger|KAKAOTALK/i.test(ua);
}

function openInSystemBrowser() {
    var url = window.location.href;
    // Android: use intent to open in Chrome
    if (/android/i.test(navigator.userAgent)) {
        window.location.href = 'intent://' + url.replace(/^https?:\/\//, '') + '#Intent;scheme=https;package=com.android.chrome;end';
        // Fallback after a short delay (if intent doesn't work)
        setTimeout(function() { window.open(url, '_system'); }, 500);
    } else {
        window.open(url, '_system');
    }
}

// ── WebAuthn Fingerprint Registration ──
async function loadDevices() {
    var inApp = isInAppBrowser();
    const list = document.getElementById('deviceList');
    const badge = document.getElementById('deviceCountBadge');
    const regBtn = document.getElementById('registerFpBtn');
    const unsupported = document.getElementById('webauthnUnsupported');

    if (!window.PublicKeyCredential) {
        if (badge) { badge.textContent = !window.isSecureContext ? 'Requires HTTPS' : 'Unsupported'; badge.style.color = '#f87171'; }
        if (unsupported) {
            unsupported.style.display = 'block';
            var msgEl = document.getElementById('webauthnUnsupportedMsg');
            var openBtn = document.getElementById('openInBrowserBtn');
            if (!window.isSecureContext) {
                msgEl.innerHTML = 'Biometric WebAuthn requires a <strong>secure connection (HTTPS or localhost)</strong>. If testing on mobile over Wi-Fi, please open this app using an HTTPS URL.';
                if (openBtn) openBtn.style.display = 'none';
            } else if (inApp) {
                msgEl.innerHTML = 'You\'re using an in-app browser (like Messenger or Facebook) that doesn\'t support fingerprint login. Tap the button below to open this page in <strong>Chrome</strong> or <strong>Safari</strong>.';
                if (openBtn) openBtn.style.display = 'inline-flex';
            } else {
                msgEl.innerHTML = 'Your browser or device doesn\'t support biometric login. Please try using <strong>Chrome</strong> or <strong>Safari</strong> on a device with a fingerprint sensor or Face ID.';
                if (openBtn) openBtn.style.display = 'none';
            }
        }
        if (regBtn) {
            regBtn.style.display = 'inline-flex';
            regBtn.onclick = function() {
                if (!window.isSecureContext) {
                    alert('Biometric registration requires a secure HTTPS connection. Please access via HTTPS or localhost.');
                } else {
                    alert('Biometric APIs are not supported by your current browser or device.');
                }
            };
        }
    }

    try {
        const res = await fetch('{{ route("webauthn.devices") }}');
        const devices = await res.json();
        if (!list) return;

        list.innerHTML = '';

        if (devices && devices.length > 0) {
            if (badge) {
                badge.textContent = `${devices.length} Registered`;
                badge.style.color = '#4ade80';
                badge.style.borderColor = 'rgba(74,222,128,0.3)';
                badge.style.background = 'rgba(74,222,128,0.1)';
                badge.style.whiteSpace = 'nowrap';
                badge.style.flexShrink = '0';
                badge.style.display = 'inline-flex';
                badge.style.alignItems = 'center';
            }

            const registeredMsg = document.createElement('div');
            registeredMsg.style.cssText = 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            registeredMsg.innerHTML = '<i class="bi bi-check-circle-fill" style="font-size:1.2rem;color:#22c55e;"></i> <span>Biometric authentication is <strong>active</strong> on your account.</span>';
            list.appendChild(registeredMsg);

            devices.forEach(d => {
                const div = document.createElement('div');
                div.className = 'device-item-card';
                div.innerHTML = `
                    <div class="device-item-left">
                        <div class="device-item-icon">
                            <i class="bi bi-fingerprint"></i>
                        </div>
                        <div class="device-item-info">
                            <div class="device-item-name">${d.name || d.device_name || "Registered Device"}</div>
                            <div class="device-item-meta">
                                <span class="device-meta-verified"><i class="bi bi-shield-check me-1"></i>Verified</span>
                                <span class="device-meta-dot">•</span>
                                <span class="device-meta-date">${new Date(d.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })}</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="removeDevice('${d.credential_id}', this)" class="device-remove-btn" type="button" title="Remove device credential">
                        <i class="bi bi-trash3 me-1"></i>Remove
                    </button>`;
                list.appendChild(div);
            });
        } else {
            if (badge) {
                badge.textContent = '0 Registered';
                badge.style.color = '#b39b82';
                badge.style.borderColor = 'rgba(207,164,111,0.2)';
                badge.style.background = 'rgba(207,164,111,0.12)';
                badge.style.whiteSpace = 'nowrap';
                badge.style.flexShrink = '0';
                badge.style.display = 'inline-flex';
                badge.style.alignItems = 'center';
            }
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:28px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);margin-bottom:16px;';
            emptyDiv.innerHTML = `
                <i class="bi bi-fingerprint" style="font-size:2.4rem;display:block;margin-bottom:8px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                <div style="font-weight:600;color:#f3e7cd;margin-bottom:4px;">No fingerprint registered yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Register this device to enable fast fingerprint sign-in and QR clock-in.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {
        console.error('Failed to load devices', e);
    }
}

function normalizeBase64(base64) {
    base64 = (base64 || '').replace(/-/g, '+').replace(/_/g, '/');
    var padding = base64.length % 4;
    if (padding) base64 += '===='.slice(padding);
    return base64;
}

function base64ToUint8Array(base64) {
    base64 = normalizeBase64(base64);
    var binary = atob(base64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

function bufferToBase64Url(buffer) {
    var bytes = new Uint8Array(buffer);
    var binary = '';
    for (var i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

let prefetchOptions = null;
let isFetchingOptions = false;

async function prefetchWebAuthn() {
    if (isFetchingOptions || prefetchOptions) return;
    isFetchingOptions = true;
    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        prefetchOptions = await optRes.json();
    } catch(e) {
        console.error(e);
    }
    isFetchingOptions = false;
}

async function registerFingerprint() {
    const btn = document.getElementById('registerFpBtn');
    const msg = document.getElementById('fpMessage');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Waiting for biometric prompt...';
    msg.style.display = 'none';

    try {
        // Step 1: Always fetch fresh registration challenge directly from server
        const optRes = await fetch('{{ route("webauthn.register.options") }}', {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            }
        });
        const opts = await optRes.json();

        // Decode challenge and userId from base64
        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);

        // Sanitize RP configuration (IP addresses must not be sent as rp.id per WebAuthn spec)
        const hostname = window.location.hostname;
        const isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        const rp = { name: opts.rp?.name || 'School Attendance' };
        if (opts.rp?.id && !isIp) {
            rp.id = opts.rp.id;
        }

        const excludeCredentials = (opts.excludeCredentials || []).map(function(c) {
            return { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
        });

        const timeoutPromise = new Promise((_, reject) => {
            const err = new Error('Biometric prompt timed out. Please try again.');
            err.name = 'TimeoutError';
            setTimeout(() => reject(err), 60000);
        });

        const createPromise = navigator.credentials.create({
            publicKey: {
                challenge: challenge,
                rp: rp,
                user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
                pubKeyCredParams: opts.pubKeyCredParams || [
                    { type: 'public-key', alg: -7 },
                    { type: 'public-key', alg: -257 }
                ],
                authenticatorSelection: opts.authenticatorSelection || {
                    authenticatorAttachment: 'platform',
                    userVerification: 'preferred',
                    requireResidentKey: false
                },
                timeout: opts.timeout || 60000,
                attestation: opts.attestation || 'none',
                excludeCredentials: excludeCredentials
            }
        });

        const credential = await Promise.race([createPromise, timeoutPromise]);

        // Step 3: encode credential id and attestation object
        var credentialId = bufferToBase64Url(credential.rawId);
        var attestationObject = bufferToBase64Url(credential.response.attestationObject);
        var clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        // Detect device name
        var ua = navigator.userAgent;
        var deviceName = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Mobile' :
                         ua.indexOf('Windows') !== -1 ? 'Windows PC' :
                         ua.indexOf('Mac') !== -1 ? 'Mac Device' : 'Mobile Device';

        // Step 4: save to server
        const saveRes = await fetch('{{ route("webauthn.register") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            body: JSON.stringify({
                credential_id: credentialId,
                credential: {
                    id: credential.id,
                    type: credential.type,
                    response: {
                        attestationObject: attestationObject,
                        clientDataJSON: clientDataJSON
                    }
                },
                device_name: deviceName
            })
        });
        const result = await saveRes.json();

        msg.style.display = 'block';
        if (result.success) {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + (result.message || 'Fingerprint registered successfully!');
            btn.innerHTML = '<i class="bi bi-check2 me-2"></i>Registered';
            loadDevices();
            setTimeout(function() { location.reload(); }, 1500);
        } else {
            msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + (result.message || 'Registration failed.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        }
    } catch(err) {
        msg.style.display = 'block';
        msg.style.cssText = 'margin-top:12px;font-size:.82rem;display:block;background:#fef2f2;border:1px solid #fecaca;color:#dc2626;padding:10px 14px;border-radius:10px;';
        if (err.name === 'NotAllowedError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Biometric prompt was cancelled.';
        } else if (err.name === 'InvalidStateError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>This device is already registered.';
        } else if (err.name === 'NotReadableError') {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>Device biometric sensor is unavailable or locked.';
        } else {
            msg.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i>' + (err.message || 'Registration failed.');
        }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Register This Device';
        prefetchWebAuthn();
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Remove this fingerprint device?')) return;
    await fetch('{{ route("webauthn.remove") }}', {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential_id: credentialId })
    });
    loadDevices();
}

// OTP digit handling in settings
const sDigits = document.querySelectorAll('.otp-digit-s');
sDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < sDigits.length - 1) sDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) sDigits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch, i) => { if (sDigits[i]) sDigits[i].value = ch; });
        e.preventDefault();
    });
});

function collectOtp(btn) {
    document.getElementById('settingsOtpHidden').value = Array.from(sDigits).map(d => d.value).join('');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.closest('form').submit();
    }
}

function requestOtp() {
    const btn = document.getElementById('sendOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.change.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => {
        if (!r.ok) {
            return r.text().then(text => { throw new Error('HTTP ' + r.status + ': ' + text.substring(0, 300)); });
        }
        return r.json();
    }).then(data => {
        if (data.success) {
            document.getElementById('otpStep1').style.display = 'none';
            document.getElementById('otpStep2').style.display = 'block';
            if (data.dev_otp && sDigits) {
                const str = String(data.dev_otp).trim();
                sDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                const hiddenInput = document.getElementById('settingsOtpHidden');
                if (hiddenInput) hiddenInput.value = data.dev_otp;
            }
            if (sDigits[0]) sDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send OTP to Email';
        console.error('OTP fetch error:', err.message);
        alert('Error: ' + err.message);
    });
}

function cancelOtp() {
    document.getElementById('otpStep1').style.display = 'block';
    document.getElementById('otpStep2').style.display = 'none';
    sDigits.forEach(d => d.value = '');
}

// ── Email change via SMS/Email OTP ──
const eDigits = document.querySelectorAll('.email-otp-digit');
eDigits.forEach((input, idx) => {
    input.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '');
        if (e.target.value && idx < eDigits.length - 1) eDigits[idx + 1].focus();
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !e.target.value && idx > 0) eDigits[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
        paste.split('').forEach((ch, i) => { if (eDigits[i]) eDigits[i].value = ch; });
        e.preventDefault();
    });
});

function collectEmailOtp(btn) {
    document.getElementById('emailOtpHidden').value = Array.from(eDigits).map(d => d.value).join('');
    if(btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';
        btn.closest('form').submit();
    }
}

function requestEmailOtp() {
    const btn = document.getElementById('sendEmailOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.email.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' }
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('emailStep1').style.display = 'none';
            document.getElementById('emailStep2').style.display = 'block';
            if (data.dev_otp && eDigits) {
                const str = String(data.dev_otp).trim();
                eDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                const hiddenInput = document.getElementById('emailOtpHidden');
                if (hiddenInput) hiddenInput.value = data.dev_otp;
            }
            if (eDigits[0]) eDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-envelope-fill me-2"></i>Send OTP to Current Email';
        alert('Network error. Please try again.');
    });
}

function cancelEmailOtp() {
    document.getElementById('emailStep1').style.display = 'block';
    document.getElementById('emailStep2').style.display = 'none';
    eDigits.forEach(d => d.value = '');
}

function generateRecoveryCodes() {
    if(!confirm("Are you sure? Generating new recovery codes will invalidate all your old codes.")) return;
    
    const btn = document.getElementById('generateCodesBtn');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating...';
    
    fetch('{{ route("recovery.generate") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if(data.success) {
            const container = document.getElementById('codesContainer');
            container.innerHTML = '';
            data.codes.forEach(code => {
                const codeEl = document.createElement('div');
                codeEl.className = 'recovery-code-chip';
                codeEl.style.cssText = 'background:rgba(255,235,190,0.06);border:1px solid rgba(207,164,111,0.2);padding:8px 10px;border-radius:8px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:0.95rem;color:#f3e7cd;letter-spacing:1px;font-weight:700;text-align:center;user-select:all;box-shadow:inset 0 1px 2px rgba(0,0,0,0.3);';
                codeEl.textContent = code;
                container.appendChild(codeEl);
            });
            document.getElementById('recoveryCodesList').style.display = 'block';
            if (typeof showToast === 'function') {
                showToast('New recovery codes generated! Please save them now.', 'success');
            } else {
                alert('New recovery codes generated successfully. Please save them now.');
            }
        } else {
            alert(data.message || 'Failed to generate recovery codes.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('Network error. Please try again.');
    });
}

function copyAllRecoveryCodes(btn) {
    const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
    if (!chips.length) return;
    const codes = Array.from(chips).map(el => el.textContent.trim()).join('\n');
    navigator.clipboard.writeText(codes).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
        if (typeof showToast === 'function') showToast('Recovery codes copied to clipboard!', 'success');
        if (window.triggerHaptic) window.triggerHaptic('success');
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    }).catch(() => {
        alert('Failed to copy to clipboard.');
    });
}

function downloadRecoveryCodes() {
    const chips = document.querySelectorAll('#codesContainer .recovery-code-chip');
    if (!chips.length) return;
    const codes = Array.from(chips).map(el => el.textContent.trim()).join('\n');
    const content = "SMART ATTENDANCE - EMERGENCY RECOVERY CODES\nGenerated: " + new Date().toLocaleString() + "\nAccount: {{ Auth::user()->email }}\n\n" + codes + "\n\nStore these keys in a safe, offline location.";
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'attendance-recovery-codes-' + Date.now() + '.txt';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    if (typeof showToast === 'function') showToast('Recovery codes downloaded!', 'info');
}

async function handleSettingsAvatarUpload(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 10 * 1024 * 1024) {
        alert('The selected image is too large (' + (file.size / (1024 * 1024)).toFixed(1) + 'MB). Please choose an image under 10MB.');
        input.value = '';
        return;
    }

    // Instant Local Preview
    const reader = new FileReader();
    reader.onload = function(e) {
        const avatarImg = document.getElementById('settingsAvatarDisplay');
        if (avatarImg) avatarImg.src = e.target.result;
    };
    reader.readAsDataURL(file);

    // Overlay spinner
    const overlay = document.getElementById('settingsAvatarOverlay');
    if (overlay) {
        overlay.innerHTML = '<div class="spinner-border spinner-border-sm text-warning" role="status" style="width:1.3rem;height:1.3rem;"></div><span style="font-size:0.6rem;color:#ffd700;font-weight:700;margin-top:2px;">Saving...</span>';
        overlay.style.opacity = '1';
    }

    const form = document.getElementById('settingsProfileImageForm');
    const formData = new FormData(form);
    formData.set('profile_image', file);
    formData.set('_token', '{{ csrf_token() }}');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (response.ok && data.success) {
            if (overlay) {
                overlay.innerHTML = '<i class="bi bi-check-circle-fill text-success" style="font-size:1.4rem;"></i>';
                setTimeout(() => { 
                    overlay.style.opacity = '0'; 
                    overlay.innerHTML = '<i class="bi bi-camera-fill" style="font-size:1.3rem;"></i><span style="font-size:0.6rem;font-weight:700;">Change</span>'; 
                }, 1800);
            }
            if (data.image_url) {
                const freshUrl = data.image_url + (data.image_url.includes('?') ? '&' : '?') + 't=' + Date.now();
                document.querySelectorAll('.top-nav-avatar, .user-avatar-img, .header-user-avatar, .mobile-user-avatar, .header-profile-img, #settingsAvatarDisplay, #studentAvatarDisplay').forEach(img => {
                    img.src = freshUrl;
                });
            }
            if (window.triggerHaptic) window.triggerHaptic('success');
            if (typeof showToast === 'function') {
                showToast('Profile photo updated successfully!', 'success');
            }
        } else {
            throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('\n') : 'Upload failed'));
        }
    } catch (err) {
        console.warn('AJAX upload fallback to form submit:', err);
        form.submit();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof loadDevices === 'function') loadDevices();
    if (typeof prefetchWebAuthn === 'function') prefetchWebAuthn();
    if (typeof updateStabsScrollArrows === 'function') updateStabsScrollArrows();

    // Attach direct click event listeners to all stab buttons for guaranteed response
    document.querySelectorAll('.stab').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.getAttribute('data-tab') || this.dataset.tab;
            if (tab && window.switchTab) {
                window.switchTab(tab, this);
            }
        });
    });

    const nav = document.getElementById('stabsNav');
    if (nav) {
        nav.addEventListener('scroll', updateStabsScrollArrows, { passive: true });
    }
    window.addEventListener('resize', updateStabsScrollArrows, { passive: true });

    // Check if hash or localStorage requested a specific tab (e.g., #tab-fingerprint or #fingerprint)
    const rawHash = window.location.hash.replace('#tab-', '').replace('#', '');
    const storedTab = localStorage.getItem('active_settings_tab');
    const targetTab = rawHash || storedTab;
    if (targetTab && window.switchTab) {
        localStorage.removeItem('active_settings_tab');
        window.switchTab(targetTab);
    }
});
</script>
@endsection