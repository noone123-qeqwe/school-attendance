@php
    $layout = (Auth::check() && Auth::user()->isAdmin()) ? 'admin.layout' : 'layouts.app';
@endphp
@extends($layout)

@section('page-title', 'Settings')
@section('role-sidebar')
    @include('layouts.sidebars.settings', ['settingsUser' => Auth::user()])
@endsection
@section('content')
@php
    $user = Auth::user();
    if ($user) {
        $user->loadMissing('deviceBinding');
    }
    $deviceBinding = $user?->deviceBinding;
    $totalRecords = 0;
    $totalPresent = 0;
    $totalLate = 0;
    $totalAbsent = 0;
    if ($user && $user->isStudent()) {
        $counts = $user->attendances()
            ->selectRaw("COUNT(*) as total_count, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count, SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count, SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent_count")
            ->first();
        $totalRecords = (int) ($counts->total_count ?? 0);
        $totalPresent = (int) ($counts->present_count ?? 0);
        $totalLate = (int) ($counts->late_count ?? 0);
        $totalAbsent = (int) ($counts->absent_count ?? 0);
    }
    $rate = $totalRecords > 0 ? round((($totalPresent + $totalLate) / $totalRecords) * 100) : 0;
@endphp

<link rel="stylesheet" href="{{ asset('css/settings-page.css') }}?v={{ filemtime(public_path('css/settings-page.css')) }}">
<style>
/* ── Student settings redesign ─────────────────────────────────────────── */
.student-settings {
    --student-panel: #14080C;
    --student-panel-strong: #14080C;
    --student-line: rgba(250, 247, 248, 0.11);
    --student-space: clamp(16px, 2.3vw, 28px);
    max-width: 1360px;
    padding: 24px var(--student-space) 64px;
}
.student-settings .settings-command-header {
    min-height: 128px;
    padding: 20px 24px;
    border-radius: 14px;
    border-color: var(--student-line);
    border-top: 2px solid #6B192B;
    background: #14080C;
    box-shadow: none;
}
.student-settings .settings-command-header::after { display: none; }
.student-settings .settings-top-left { align-self: stretch; }
.student-settings .settings-avatar-chip {
    width: 60px;
    height: 60px;
    border-radius: 14px;
    padding: 2px;
    background: rgba(250, 247, 248, 0.18);
}
.student-settings .settings-chip-avatar { border-radius: 12px; }
.student-settings .settings-chip-status { width: 14px; height: 14px; }
.student-settings .settings-breadcrumb-bar {
    padding: 3px 9px;
    margin-bottom: 6px;
    background: #1C0C11;
    border-color: rgba(250, 247, 248, 0.1);
}
.student-settings .pg-title {
    font-size: clamp(1.4rem, 1.9vw, 1.85rem);
    letter-spacing: -0.03em;
}
.student-settings .pg-sub { max-width: 560px; line-height: 1.5; }
.student-settings .settings-top-right {
    align-items: center;
    gap: 12px;
    flex: 0 1 600px;
}
.student-settings .settings-search-box {
    min-width: min(100%, 260px);
    flex: 1 1 250px;
    border-radius: 10px;
    background: #0B0406;
    border-color: rgba(250, 247, 248, 0.12);
}
.student-settings .settings-command-telemetry {
    display: grid;
    grid-template-columns: repeat(3, minmax(96px, 1fr));
    gap: 8px;
    width: 100%;
}
.student-settings .telemetry-pill {
    min-height: 50px;
    padding: 8px 11px;
    border-radius: 10px;
    background: #1C0C11;
    border: 1px solid rgba(250, 247, 248, 0.08);
}
.student-settings .telemetry-pill:hover { background: #261118; }
.student-settings .telemetry-lbl { font-size: 0.64rem; color: #9E8990; }
.student-settings .telemetry-val { font-size: 0.78rem; color: #FAF7F8; }

.student-settings .settings-layout-grid {
    display: block;
    margin-top: 2px;
}
.student-settings .settings-main-content { min-width: 0; }
.student-settings .spanel { animation: studentPanelIn .2s ease-out; }
@keyframes studentPanelIn { from { opacity: .45; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }
.student-settings .sc,
.student-settings .sec-health-hero,
.student-settings .sec-card,
.student-settings .sec-group-panel {
    border-color: var(--student-line);
    background: #14080C;
    box-shadow: none;
}
.student-settings .sc { border-radius: 14px; overflow: hidden; }
.student-settings .sc + .sc { margin-top: 16px; }
.student-settings .sc-head { padding: 18px 22px 15px; border-bottom-color: rgba(250, 247, 248, 0.08); }
.student-settings .sc-body { padding: 22px; }
.student-settings .sc-title, .student-settings .sec-group-title { font-size: 1rem; color: #FAF7F8; }
.student-settings .sc-sub, .student-settings .sec-group-desc { line-height: 1.48; color: #9E8990; }
.student-settings .profile-card-inner {
    gap: 18px;
    padding: 4px;
}
.student-settings .profile-details-col { gap: 8px; }
.student-settings .profile-user-name { font-size: 1.2rem; }
.student-settings .academic-spec-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
.student-settings .academic-spec-card {
    min-height: 86px;
    padding: 13px 14px;
    border-radius: 10px;
    background: #0B0406;
    border: 1px solid rgba(250, 247, 248, 0.08);
}
.student-settings .academic-spec-card:hover { border-color: rgba(232, 152, 168, 0.28); background: #1C0C11; }
.student-settings .si { min-height: 46px; border-radius: 10px; }
.student-settings .sbtn, .student-settings .sec-action-btn { min-height: 44px; border-radius: 10px; }
.student-settings .flash-ok, .student-settings .flash-err { border-radius: 12px; }

.student-settings .sec-health-hero {
    margin-bottom: 14px;
    padding: 20px 22px;
    border-radius: 14px;
}
.student-settings .sec-health-title { font-size: 1.1rem; }
.student-settings .sec-matrix-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
    gap: 8px;
    padding: 10px;
    border-radius: 12px;
    background: #0B0406;
    border: 1px solid rgba(250, 247, 248, 0.08);
}
.student-settings .sec-matrix-item {
    justify-content: flex-start;
    min-height: 42px;
    padding: 8px 10px;
    border-radius: 9px;
    background: #14080C;
}
.student-settings .sec-subnav-bar {
    position: sticky;
    top: 10px;
    z-index: 10;
    margin: 14px 0;
    padding: 6px;
    border-radius: 12px;
    background: rgba(11, 4, 6, 0.94);
    border: 1px solid var(--student-line);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}
.student-settings .sec-subnav-pill { min-height: 38px; border-radius: 8px; }
.student-settings .sec-group-panel { margin-bottom: 18px; border-radius: 14px; padding: 20px; }
.student-settings .sec-group-header { margin-bottom: 16px; }
.student-settings .sec-cards-grid { gap: 16px; }
.student-settings .sec-card { border-radius: 12px; padding: 18px 20px; }
.student-settings .student-account-shortcuts { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
.student-settings .student-account-shortcuts[hidden],
.student-settings .student-phone-shortcut[hidden] { display:none !important; }
.student-settings .student-account-shortcut,
.student-settings .student-phone-shortcut {
    display:flex; align-items:center; gap:14px; min-width:0; padding:16px;
    border:1px solid rgba(250,247,248,.1); border-radius:12px;
    background:#1C0C11;
}
.student-settings .student-account-shortcut-icon {
    display:grid; place-items:center; flex:0 0 42px; width:42px; height:42px;
    border:1px solid rgba(232,152,168,.26); border-radius:10px;
    background:rgba(107,25,43,.34); color:#E898A8; font-size:1.05rem;
}
.student-settings .student-account-shortcut-copy { flex:1; min-width:0; }
.student-settings .student-account-shortcut-copy strong,
.student-settings .student-account-shortcut-copy span { display:block; overflow-wrap:anywhere; }
.student-settings .student-account-shortcut-copy strong { color:#FAF7F8; font-size:.9rem; }
.student-settings .student-account-shortcut-copy span { margin-top:3px; color:#9E8990; font-size:.78rem; line-height:1.45; }
.student-settings .student-account-shortcut button,
.student-settings .student-phone-shortcut button {
    display:inline-flex; align-items:center; justify-content:center; gap:7px; min-height:42px;
    padding:9px 13px; border:1px solid rgba(250,247,248,.16); border-radius:9px;
    background:#261118; color:#FAF7F8; font:inherit; font-size:.8rem; font-weight:700;
}
.student-settings .student-account-shortcut button:hover,
.student-settings .student-phone-shortcut button:hover { background:#6B192B; border-color:rgba(232,152,168,.38); color:#FFFFFF; }
.student-settings .student-phone-shortcut { justify-content:space-between; margin-bottom:16px; }
.student-settings .student-account-edit-panel { max-width:760px; margin:0 auto; padding:0; border:0; background:transparent; box-shadow:none; }
.student-settings .student-account-edit-panel > .student-account-edit-intro {
    margin-bottom:14px; padding:15px 18px; border:1px solid rgba(250,247,248,.1);
    border-radius:12px; background:#14080C; color:#D4C6CA; line-height:1.5;
}
.student-settings .student-account-edit-card { border-radius:14px; }
.student-settings .account-edit-progress {
    display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px;
    margin:0 0 18px; padding:0; list-style:none;
}
.student-settings .account-edit-progress li {
    display:flex; align-items:center; gap:10px; min-width:0; min-height:46px;
    padding:9px 12px; border:1px solid rgba(250,247,248,.09); border-radius:10px;
    color:#9E8990; background:#0B0406; font-size:.8rem; font-weight:600;
}
.student-settings .account-edit-progress li > .account-step-number {
    display:grid; place-items:center; flex:0 0 26px; width:26px; height:26px;
    border-radius:7px; border:1px solid rgba(250,247,248,.16); color:#D4C6CA; font-size:.74rem;
}
.student-settings .account-edit-progress li[aria-current="step"] {
    border-color:rgba(232,152,168,.4); color:#FAF7F8; background:#1C0C11;
}
.student-settings .account-edit-progress li[aria-current="step"] > .account-step-number,
.student-settings .account-edit-progress li.is-complete > .account-step-number {
    border-color:#FAF7F8; color:#14080C; background:#FAF7F8;
}
.student-settings .account-edit-progress li.is-complete { color:#D4C6CA; }
.student-settings .account-edit-status {
    margin:0 0 14px; padding:11px 13px; border:1px solid rgba(232,152,168,.26);
    border-radius:10px; color:#FAF7F8; background:#1C0C11; font-size:.84rem; line-height:1.45;
}
.student-settings .account-edit-status[hidden] { display:none !important; }
.student-settings .account-edit-status[data-tone="error"] { border-color:rgba(251,113,133,.34); color:#FDA4AF; background:rgba(153,27,45,.18); }
.student-settings .account-edit-status[data-tone="success"] { border-color:rgba(52,211,153,.3); color:#6EE7B7; background:rgba(16,185,129,.1); }
.student-settings .account-edit-current-value {
    display:flex; align-items:center; gap:10px; min-width:0; margin:12px 0 16px;
    padding:12px 14px; border:1px solid rgba(250,247,248,.1); border-radius:10px;
    background:#0B0406; color:#D4C6CA; font-size:.85rem;
}
.student-settings .account-edit-current-value i { flex:0 0 auto; color:#E898A8; }
.student-settings .account-edit-current-value strong { color:#FAF7F8; overflow-wrap:anywhere; }
.student-settings .account-edit-help { margin:7px 0 16px; color:#9E8990; font-size:.8rem; line-height:1.5; }
.student-settings .account-otp-grid {
    display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:8px;
    max-width:390px; margin:8px 0 7px;
}
.student-settings .account-otp-input {
    width:100%; min-width:0; height:50px; padding:0; border:1px solid rgba(250,247,248,.16);
    border-radius:10px; color:#FAF7F8; background:#0B0406;
    font-size:1.15rem; font-weight:700; text-align:center; outline:none;
    transition:border-color .15s ease, box-shadow .15s ease;
}
.student-settings .account-otp-input:focus-visible {
    border-color:#E898A8; background:#14080C; box-shadow:0 0 0 3px rgba(232,152,168,.18);
}
.student-settings .account-form-actions { display:flex; align-items:center; gap:10px; margin-top:16px; }
.student-settings .account-form-actions > * { min-height:46px; }
.student-settings .account-form-actions .sec-action-btn { flex:1; }
.student-settings .student-account-edit-card .sec-card-content { min-width:0; }
.student-settings .student-account-edit-card .si { min-height:48px; }
.student-settings .student-account-edit-panel .student-phone-shortcut { margin-bottom:0; }
.student-settings .student-account-edit-panel .sec-card,
.student-settings .student-account-edit-panel .sc { margin:0; }
.student-settings .sec-card-top { margin-bottom: 14px; }
.student-settings .sec-card-name { font-size: .95rem; }
.student-settings .sec-card-subtitle { line-height: 1.4; }
.student-settings .sec-status-tile, .student-settings .pref-tile {
    border-radius: 14px;
    border-color: rgba(255,255,255,.08);
    background: rgba(255,255,255,.025);
}
.student-settings .pref-tile { min-height: 72px; padding: 14px; }
.student-settings .pref-tile:hover { background: rgba(207,164,111,.06); border-color: rgba(207,164,111,.18); }
.student-settings .device-item-card { border-radius: 13px; }

@media (max-width: 1100px) {
    .student-settings .settings-command-header { align-items: flex-start; }
    .student-settings .settings-top-right { flex: 1 1 100%; }
    .student-settings .settings-layout-grid { display: block; }
}
@media (max-width: 991.98px) {
    .student-settings { padding: 14px 14px calc(28px + env(safe-area-inset-bottom)); }
    .student-settings .settings-command-header {
        min-height: 0;
        padding: 18px;
        margin-bottom: 12px;
        border-radius: 19px;
    }
    .student-settings .settings-top-left { flex: 1 1 100%; gap: 13px; }
    .student-settings .settings-avatar-chip { width: 52px; height: 52px; border-radius: 17px; }
    .student-settings .settings-chip-avatar { border-radius: 14px; }
    .student-settings .settings-breadcrumb-bar { display: none; }
    .student-settings .pg-title { font-size: 1.35rem; }
    .student-settings .pg-sub { font-size: .78rem; }
    .student-settings .settings-top-right { gap: 9px; }
    .student-settings .settings-search-box { order: 2; flex-basis: 100%; min-width: 100%; }
    .student-settings .settings-command-telemetry { grid-template-columns: repeat(3, minmax(0, 1fr)); order: 1; }
    .student-settings .telemetry-pill { min-height: 48px; padding: 7px 8px; gap: 7px; }
    .student-settings .telemetry-pill i { font-size: .95rem; }
    .student-settings .telemetry-lbl { font-size: .56rem; }
    .student-settings .telemetry-val { font-size: .68rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .student-settings .stabs-wrapper {
        position: sticky;
        top: 0;
        z-index: 30;
        padding: 7px 0;
        margin: 0 -2px 14px;
        background: linear-gradient(180deg, rgba(18, 12, 9, .98) 76%, rgba(18, 12, 9, 0));
    }
    .student-settings .stabs {
        gap: 8px;
        padding: 0 40px 8px 2px;
        scrollbar-width: none;
        scroll-padding-inline: 8px;
    }
    .student-settings .stabs::-webkit-scrollbar { display: none; }
    .student-settings .stabs-wrapper::before,
    .student-settings .stabs-wrapper::after {
        content: "";
        position: absolute;
        z-index: 1;
        top: 1px;
        bottom: 1px;
        width: 42px;
        opacity: 0;
        pointer-events: none;
        transition: opacity .2s ease;
    }
    .student-settings .stabs-wrapper::before {
        left: 1px;
        background: linear-gradient(90deg, rgba(18, 14, 11, .98), rgba(18, 14, 11, 0));
    }
    .student-settings .stabs-wrapper::after {
        right: 1px;
        background: linear-gradient(270deg, rgba(18, 14, 11, .98), rgba(18, 14, 11, 0));
    }
    .student-settings .stabs-wrapper.has-scroll-left::before,
    .student-settings .stabs-wrapper.has-scroll-right::after { opacity: 1; }
    .student-settings .stabs-arrow {
        position: absolute;
        z-index: 3;
        top: 50%;
        width: 30px;
        height: 30px;
        transform: translateY(-50%);
        background: rgba(30, 24, 20, .98);
        border-color: rgba(207, 164, 111, .7);
        box-shadow: 0 2px 10px rgba(0, 0, 0, .5), 0 0 12px rgba(207, 164, 111, .16);
    }
    .student-settings .stabs-arrow-left { left: 7px; }
    .student-settings .stabs-arrow-right { right: 7px; }
    .student-settings .stabs-wrapper.has-scroll-left .stabs { padding-left: 40px; }
    .student-settings .stabs-wrapper.has-scroll-right .stabs-arrow-right i {
        animation: studentSettingsScrollNudge 1.1s ease-in-out infinite;
    }
    @keyframes studentSettingsScrollNudge {
        0%, 100% { transform: translateX(-2px); opacity: .72; }
        50% { transform: translateX(3px); opacity: 1; }
    }
    @media (prefers-reduced-motion: reduce) {
        .student-settings .stabs-wrapper.has-scroll-right .stabs-arrow-right i { animation: none; }
    }
    .student-settings .stab {
        flex: 0 0 auto;
        min-height: 42px;
        padding: 9px 13px;
        border-radius: 12px;
        font-size: .78rem;
        background: rgba(255,255,255,.045);
        border: 1px solid rgba(255,255,255,.08);
    }
    .student-settings .stab.active { background: linear-gradient(135deg, rgba(207,164,111,.25), rgba(143,39,31,.2)); border-color: rgba(207,164,111,.4); }
    .student-settings .stabs-floating-hint { display: none !important; }
    .student-settings .settings-layout-grid { display: block; margin-top: 0; }
    .student-settings .settings-sidebar { display: none; }
    .student-settings .sc { border-radius: 17px; }
    .student-settings .sc-head { padding: 20px 18px 16px; }
    .student-settings .sc-body { padding: 22px 18px; }
    .student-settings .sec-health-hero { padding: 22px 18px; border-radius: 17px; }
    .student-settings .sec-matrix-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .student-settings .sec-subnav-bar { top: 58px; overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    .student-settings .sec-subnav-pill { flex: 0 0 auto; white-space: nowrap; }
    .student-settings .sec-group-panel { padding: 20px 16px; border-radius: 16px; }
    .student-settings .sec-card { padding: 20px 16px; }
}
@media (max-width: 575.98px) {
    .student-settings { padding-inline: 10px; }
    .student-settings .settings-command-header { padding: 16px; border-radius: 17px; }
    .student-settings .settings-command-telemetry { grid-template-columns: 1fr; gap: 8px; }
    .student-settings .telemetry-pill { min-width: 0; min-height: 52px; padding: 10px 12px; }
    .student-settings .telemetry-pill:nth-child(n+4) { display: none; }
    .student-settings .profile-card-inner { align-items: flex-start; flex-direction: column; }
    .student-settings .profile-details-col { width: 100%; }
    .student-settings .academic-spec-grid { grid-template-columns: 1fr; gap: 9px; }
    .student-settings .academic-spec-card { min-height: 72px; }
    .student-settings .sec-matrix-bar { grid-template-columns: 1fr; gap: 6px; }
    .student-settings .sec-matrix-item { min-height: 38px; }
    .student-settings .sec-health-left { align-items: flex-start; }
    .student-settings .sec-health-icon { width: 42px; height: 42px; font-size: 1.05rem; }
    .student-settings .sec-health-title { font-size: 1rem; }
    .student-settings .sec-health-sub { font-size: .75rem; }
    .student-settings .sec-cards-grid { grid-template-columns: 1fr; }
    .student-settings .sc-head { padding: 20px 14px 16px; align-items: flex-start; }
    .student-settings .sc-body { padding: 22px 14px; }
    .student-settings .sec-health-hero { padding: 22px 14px; }
    .student-settings .sec-group-panel { padding: 18px 12px; }
    .student-settings .sec-card { padding: 20px 14px; }
    .student-settings .student-account-shortcuts { grid-template-columns:minmax(0,1fr); gap:10px; }
    .student-settings .student-account-shortcut { padding:14px; align-items:flex-start; }
    .student-settings .student-account-shortcut button { flex:0 0 auto; padding:9px 10px; }
    .student-settings .student-phone-shortcut { padding:14px; align-items:flex-start; }
    .student-settings .student-account-edit-panel > .student-account-edit-intro { padding:15px; }
    .student-settings .account-edit-progress { gap:7px; }
    .student-settings .account-edit-progress li { gap:7px; min-height:44px; padding:7px 9px; font-size:.74rem; }
    .student-settings .account-otp-grid { gap:6px; }
    .student-settings .account-otp-input { height:46px; border-radius:10px; }
    .student-settings .account-form-actions { flex-direction:column-reverse; align-items:stretch; }
    .student-settings .account-form-actions > * { width:100%; }
    .student-settings .bio-method-grid { grid-template-columns: 1fr; gap: 18px; }
    .student-settings .bio-method-card { padding: 22px 16px 20px; }
    .student-settings .bio-action-row { align-items: stretch; flex-direction: column; gap: 12px; }
    .student-settings .pref-tile { align-items: flex-start; flex-wrap: wrap; gap: 12px; padding: 18px 14px; }
    .student-settings .pref-tile > * { min-width: 0; }
    .student-settings .pref-tile .form-check { padding-top: 4px; }
    .student-settings .pref-tile select { width: 100% !important; min-width: 0 !important; margin-top: 8px; }
    .student-settings .sbtn, .student-settings .sec-action-btn { width: 100%; justify-content: center; }
    .student-settings #tabDeviceBoundTile > div:last-child,
    .student-settings #tabDeviceBoundTile div[style*="display:flex;gap:10px"] { flex-direction: column; }
}

/* Student settings: calmer hierarchy and easier category navigation. */
.student-settings .settings-command-header {
    background: linear-gradient(135deg, rgba(35, 25, 19, 0.98), rgba(20, 14, 11, 0.98));
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.24);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
.student-settings .settings-avatar-chip {
    box-shadow: none;
}
.student-settings .pg-title {
    background: none;
    color: #f3e7cd;
    -webkit-text-fill-color: currentColor;
}
.student-settings .pg-sub { max-width: 520px; }
.student-settings .settings-search-box { min-width: min(100%, 240px); }
.student-settings .settings-search-input {
    box-shadow: none;
    border-color: rgba(207, 164, 111, 0.22);
}
.student-settings .settings-command-telemetry { gap: 8px; }
.student-settings .telemetry-pill {
    backdrop-filter: none;
    transition: background-color 0.18s ease, border-color 0.18s ease;
}
.student-settings .telemetry-pill:hover { transform: none; }
.student-settings .settings-sidebar {
    background: rgba(23, 16, 12, 0.98);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.2);
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
.student-settings .snav-group-header { letter-spacing: 0.07em; }
.student-settings .snav-item {
    min-height: 62px;
    align-items: center;
    transition: background-color 0.18s ease, border-color 0.18s ease;
}
.student-settings .snav-item:hover { transform: none; }
.student-settings .snav-item-desc {
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    line-height: 1.35;
}
.student-settings .snav-item.active {
    background: rgba(207, 164, 111, 0.12);
    border-color: rgba(207, 164, 111, 0.3);
    box-shadow: inset 3px 0 var(--gold-primary);
}
.student-settings .snav-sub-item,
.student-settings .snav-sub-item.active {
    margin-left: 0 !important;
    padding: 9px 10px !important;
    border: 1px solid transparent !important;
    border-radius: 12px !important;
    background: transparent !important;
    opacity: 1;
}
.student-settings .snav-sub-item.active {
    background: rgba(207, 164, 111, 0.12) !important;
    border-color: rgba(207, 164, 111, 0.3) !important;
    box-shadow: inset 3px 0 var(--gold-primary);
}
.student-settings .snav-footer-card { box-shadow: none; }
.student-settings .sc,
.student-settings .sec-health-hero,
.student-settings .sec-card,
.student-settings .sec-group-panel {
    background: rgba(25, 18, 14, 0.96);
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.16);
}
.student-settings .sc { border-radius: 18px; }
.student-settings .sc-head { background: rgba(255, 255, 255, 0.015); }
.student-settings .sc + .sc { margin-top: 20px; }
.student-settings .sec-subnav-bar {
    box-shadow: none;
    backdrop-filter: none;
    -webkit-backdrop-filter: none;
}
.student-settings .sec-subnav-pill {
    min-height: 42px;
    transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
}
.student-settings .sec-subnav-pill:hover { transform: none; }
.student-settings :is(button, a, input, select, textarea):focus-visible {
    outline: 2px solid var(--gold-bright);
    outline-offset: 3px;
}

@media (max-width: 575.98px) {
    .student-settings { padding-inline: 14px; }
    .student-settings .settings-command-header {
        gap: 16px;
        padding: 18px;
        margin-bottom: 14px;
    }
    .student-settings .settings-top-left { gap: 12px; }
    .student-settings .settings-title-group { flex-basis: calc(100% - 64px); }
    .student-settings .pg-title { font-size: 1.3rem; }
    .student-settings .pg-sub { max-width: 36ch; line-height: 1.45; }
    .student-settings .settings-command-telemetry {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .student-settings .telemetry-pill {
        min-height: 54px;
        align-items: center;
        padding: 9px 10px;
    }
    .student-settings .telemetry-pill:nth-child(n+4) { display: inline-flex; }
    .student-settings .telemetry-val {
        overflow: visible;
        text-overflow: clip;
        white-space: normal;
        line-height: 1.25;
    }
    .student-settings .stabs-wrapper {
        position: sticky;
        top: 0;
        margin: 0 0 16px;
        padding: 8px;
        border-radius: 16px;
        background: rgba(18, 14, 11, 0.98);
        border-color: rgba(207, 164, 111, 0.2);
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
    }
    .student-settings .stabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 8px;
        overflow-x: auto;
        overflow-y: hidden;
        padding: 0 40px 8px 2px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
    }
    .student-settings .stab {
        width: max-content;
        min-width: max-content;
        flex: 0 0 auto;
        min-height: 46px;
        justify-content: center;
        padding: 10px 12px;
        border-radius: 11px;
        font-size: 0.78rem;
    }
    .student-settings .stab:hover { transform: none; }
    .student-settings .stab.active {
        color: #fffbeb !important;
        background: rgba(207, 164, 111, 0.16) !important;
        border-color: rgba(207, 164, 111, 0.38) !important;
        box-shadow: inset 0 -2px var(--gold-primary);
    }
    .student-settings .sec-subnav-bar {
        position: static;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        overflow: visible;
        margin: 12px 0 18px;
        padding: 8px;
    }
    .student-settings .sec-subnav-pill {
        min-width: 0;
        justify-content: flex-start;
        white-space: normal;
        text-align: left;
        padding: 9px 10px;
        line-height: 1.25;
    }
    .student-settings .sc-head { padding: 20px 18px 16px; }
    .student-settings .sc-body { padding: 22px 18px; }
    .student-settings .sec-group-panel { padding: 20px 16px; }
    .student-settings .sec-card { padding: 20px 16px; }
}

@media (max-width: 767.98px) {
    .student-settings { padding: 10px 12px calc(20px + env(safe-area-inset-bottom)) !important; }
    .student-settings .settings-command-header { gap: 12px; margin-bottom: 10px; padding: 14px; }
    .student-settings .stabs-wrapper { margin-bottom: 10px; }
    .student-settings .stabs-wrapper:not(.has-scroll-right) .stabs { padding-right: 2px !important; }
    .student-settings .stabs-wrapper.has-scroll-right .stabs { padding-right: 40px !important; }
    .student-settings .sc + .sc { margin-top: 14px; }
    .student-settings .sc-head { padding: 16px 14px 14px; }
    .student-settings .sc-body { padding: 18px 14px; }
    .student-settings .sec-health-hero { margin-bottom: 10px; padding: 18px 14px; }
    .student-settings .sec-subnav-bar { margin: 8px 0 12px; padding: 6px; }
    .student-settings .sec-group-panel { padding: 16px 12px; }
    .student-settings .sec-card { padding: 16px 12px; }
    .student-settings .pref-tile { gap: 10px; padding: 14px 12px; }
}

@media (prefers-reduced-motion: reduce) {
    .student-settings *,
    .student-settings *::before,
    .student-settings *::after {
        scroll-behavior: auto !important;
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>

<div class="sp {{ $user?->isStudent() ? 'student-settings' : '' }}" @if($user?->isStudent()) data-student-settings="{{ $user->id }}" data-student-view="profile" data-settings-feedback="{{ $errors->any() || session('success') ? 'true' : 'false' }}" data-settings-phone-error="{{ $errors->has('phone') ? 'true' : 'false' }}" data-settings-phone-error-message="{{ $errors->first('phone') }}" data-settings-email-error="{{ $errors->has('new_email') || $errors->has('otp') ? 'true' : 'false' }}" data-settings-email-address-error="{{ $errors->has('new_email') ? 'true' : 'false' }}" data-settings-email-error-message="{{ $errors->first('new_email') ?: $errors->first('otp') }}" data-settings-password-error="{{ $errors->has('password') || $errors->has('password_confirmation') || $errors->has('otp') ? 'true' : 'false' }}" data-settings-password-error-message="{{ $errors->first('password') ?: $errors->first('password_confirmation') ?: $errors->first('otp') }}" @endif>

    @if($user->isStudent())
    <header class="student-settings-heading">
        <div class="settings-heading-row">
            <div>
                <h1>Your settings</h1>
                <p>Manage your account and attendance access.</p>
            </div>
            <a href="{{ \App\Support\SettingsNavigation::dashboardUrl($user) }}" class="settings-mobile-back-chip d-md-none">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </header>
    @else
    <!-- Mobile Search Backdrop Overlay -->
    <div id="settingsSearchBackdrop" class="settings-search-backdrop" style="display:none;"></div>

    <!-- ── EXECUTIVE COMMAND HEADER & BREADCRUMBS ── -->
    <div class="settings-top-navbar settings-command-header">
        <div class="settings-top-left settings-command-left">
            <div class="settings-avatar-chip">
                <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="settings-chip-avatar">
                <span class="settings-chip-status" title="Account Active & Protected"></span>
            </div>
            <div class="settings-title-group">
                <div class="settings-breadcrumb-bar">
                    <span class="crumb-root"><i class="bi bi-sliders me-1"></i>Student settings</span>
                    <i class="bi bi-chevron-right crumb-sep"></i>
                    <span id="settingsBreadcrumbCategory" class="crumb-cat">My account</span>
                    <i class="bi bi-chevron-right crumb-sep"></i>
                    <span id="settingsBreadcrumbTab" class="crumb-active">Profile</span>
                </div>
                <div class="pg-title">Your settings</div>
                <div class="pg-sub">Manage your profile, attendance access, guardians, and notifications in one place.</div>
            </div>
        </div>

        <div class="settings-top-right">
            <!-- Search & Quick Navigation Input -->
            <div class="settings-search-box">
                <i class="bi bi-search settings-search-icon"></i>
                <input type="text"
                       id="settingsSearchInput"
                       class="settings-search-input"
                       placeholder="Search settings..."
                       autocomplete="off"
                       spellcheck="false"
                       aria-label="Search settings">
                <button type="button" id="settingsSearchClear" class="settings-search-clear-btn" aria-label="Clear search" style="display:none;">
                    <i class="bi bi-x-circle-fill"></i>
                </button>
                <kbd class="settings-search-kbd" title="Press / to search">/</kbd>
                <div id="settingsSearchResults" class="settings-search-dropdown" style="display:none;"></div>
            </div>

            <!-- Quick Telemetry Chips -->
            <div class="settings-command-telemetry">
                <div class="telemetry-pill" title="Role">
                    <i class="bi {{ Auth::user()->isAdmin() ? 'bi-shield-shaded' : (Auth::user()->isTeacher() ? 'bi-mortarboard-fill' : 'bi-person-badge-fill') }}" style="color:#cfa46f;"></i>
                    <div class="telemetry-pill-text">
                        <span class="telemetry-lbl">Account</span>
                        <span class="telemetry-val">Student</span>
                    </div>
                </div>
                <div class="telemetry-pill" title="Face enrollment">
                    <i class="bi bi-camera-video-fill" style="color:{{ !empty($hasCameraFaceEnrollment) ? '#4ade80' : '#fbbf24' }};"></i>
                    <div class="telemetry-pill-text">
                        <span class="telemetry-lbl">Face enrollment</span>
                        <span class="telemetry-val">{{ !empty($hasCameraFaceEnrollment) ? 'Registered' : 'Optional' }}</span>
                    </div>
                </div>
                <div class="telemetry-pill" title="Device Lock">
                    <i class="bi bi-phone" style="color:{{ $deviceBinding ? '#34d399' : '#fbbf24' }};"></i>
                    <div class="telemetry-pill-text">
                        <span class="telemetry-lbl">Device Lock</span>
                        <span class="telemetry-val">{{ $deviceBinding ? 'Bound' : 'Not Bound' }}</span>
                    </div>
                </div>
                @if(Auth::user()->isStudent() && $totalRecords > 0)
                <div class="telemetry-pill" title="Attendance Standing">
                    <i class="bi bi-graph-up-arrow" style="color:{{ $rate >= 75 ? '#4ade80' : '#f87171' }};"></i>
                    <div class="telemetry-pill-text">
                        <span class="telemetry-lbl">Attendance</span>
                        <span class="telemetry-val">{{ $rate }}% Standing</span>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @endif

    @if(session('success'))
    <div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- ── SINGLE-COLUMN WORKSPACE (Sidebar handled by layout role-sidebar section) ── -->
    <div class="settings-layout-grid">
        <!-- ── MAIN CONTENT WORKSPACE ── -->
        <section class="settings-main-content" id="studentSettingsContent" @if($user->isStudent()) aria-labelledby="studentSettingsPageTitle" @endif>
        @php
            $mobileNavGroups = \App\Support\SettingsNavigation::groups($user);
        @endphp
        <nav class="settings-mobile-nav d-md-none" aria-label="Settings sections">
            @foreach($mobileNavGroups as $groupItems)
                @foreach($groupItems as $item)
                    <button type="button"
                            class="settings-mobile-pill {{ $item['destination'] === 'profile' ? 'active' : '' }}"
                            data-settings-page="{{ $item['destination'] }}"
                            aria-controls="studentSettingsContent"
                            @if($item['destination'] === 'profile') aria-current="page" @endif>
                        <i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span>{{ $item['label'] }}</span>
                    </button>
                @endforeach
            @endforeach
        </nav>
        @if($user->isStudent())
        <header class="student-settings-detail-heading">
            <button type="button" class="student-settings-back" data-account-back hidden><i class="bi bi-arrow-left" aria-hidden="true"></i><span data-account-back-label>Back</span></button>
            <h2 id="studentSettingsPageTitle" tabindex="-1">Profile &amp; school details</h2>
            <p id="studentSettingsPageDescription">Manage your photo, contact number, and student information.</p>
        </header>
        @endif

    <!-- ── TAB: PROFILE ── -->
    <div id="tab-profile" class="spanel active">

        <!-- Avatar / Profile Photo Card -->
        <div class="sc student-profile-summary">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(207,164,111,0.14);color:#cfa46f;"><i class="bi bi-person-bounding-box"></i></div>
                <div>
                    <div class="sc-title">Profile Photo</div>
                    <div class="sc-sub">Manage your avatar and account appearance</div>
                </div>
            </div>
            <div class="sc-body">
                <div class="profile-card-inner">
                    <x-profile-photo-manager :user="Auth::user()" :size="96" avatar-id="settingsAvatarDisplay" />

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
            </div>
        </div>

        <!-- Personal Information (Editable) -->
        <div class="sc {{ $user->isStudent() ? 'student-account-edit-card' : '' }}" @if($user->isStudent()) data-account-edit-source="phone" @endif>
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(207,164,111,0.14);color:#cfa46f;"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <div class="sc-title">{{ $user->isStudent() ? 'Phone number' : 'Personal Information' }}</div>
                    <div class="sc-sub">{{ $user->isStudent() ? 'Add or update the phone number listed on your school account.' : 'Update your contact details and emergency recovery info' }}</div>
                </div>
            </div>
            <div class="sc-body">
                <form action="{{ route('settings.update') }}" method="POST" id="personalInfoForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="sl" for="phoneInput">Phone number</label>
                            <div class="input-icon-wrap">
                                <i class="bi bi-telephone-fill input-icon-prefix"></i>
                                <input type="tel"
                                       id="phoneInput"
                                       name="phone"
                                       class="si with-icon"
                                       style="padding-left: 44px !important;"
                                       value="{{ old('phone', Auth::user()->phone) }}"
                                       placeholder="+63 900 000 0000"
                                       inputmode="tel"
                                       autocomplete="tel"
                                       maxlength="20"
                                       pattern="[0-9+\s-]{0,20}"
                                       aria-describedby="phoneInputHelp">
                            </div>
                            <div id="phoneInputHelp" class="account-edit-help">
                                <i class="bi bi-shield-check text-warning"></i>
                                Include your country code if needed. Use digits, spaces, +, or hyphens; leave it blank to remove the saved number.
                            </div>
                        </div>
                    </div>
                    @if($user->isStudent())
                        <div id="phoneFormStatus" class="account-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>
                    @endif
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" id="savePhoneBtn" class="sbtn btn-gold" style="width:auto;padding:11px 28px;">
                            <i class="bi bi-save me-2"></i>{{ $user->isStudent() ? 'Save phone number' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($user->isStudent())
        <div class="sc student-phone-shortcut" id="studentPhoneShortcut" hidden>
            <div>
                <div class="sc-title">Phone number</div>
                <div class="sc-sub">{{ Auth::user()->phone ?: 'Add a number for school contact.' }}</div>
            </div>
            <button type="button" data-account-page="change-phone"><i class="bi bi-pencil-square" aria-hidden="true"></i>Change</button>
        </div>
        @endif

        <!-- Academic Info -->
        <div class="sc student-academic-details">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(34,197,94,0.14);color:#4ade80;border:1px solid rgba(34,197,94,0.25);"><i class="bi bi-mortarboard-fill"></i></div>
                <div>
                    <div class="sc-title">Academic Information</div>
                    <div class="sc-sub">Verified institutional enrollment details • Read-only</div>
                </div>
            </div>
            <div class="sc-body">
                <div class="academic-spec-grid">
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-person-fill"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Full Name</div>
                            <div class="info-val academic-spec-value">{{ Auth::user()->name }}</div>
                        </div>
                    </div>
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-card-text"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Student ID</div>
                            <div class="info-val academic-spec-value" style="display:flex;align-items:center;justify-content:space-between;gap:10px;">
                                <span>{{ Auth::user()->student_number ?: 'Not Assigned' }}</span>
                                @if(Auth::user()->student_number)
                                <button type="button" class="sec-copy-btn" onclick="navigator.clipboard.writeText('{{ Auth::user()->student_number }}');if(typeof showToast==='function')showToast('Student ID copied!','info');" title="Copy Student ID" aria-label="Copy Student ID">
                                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-book-fill"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Course / Program</div>
                            <div class="info-val academic-spec-value">{{ Auth::user()->course ?: '—' }}</div>
                        </div>
                    </div>
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-layers-fill"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Year Level</div>
                            <div class="info-val academic-spec-value">{{ Auth::user()->year_level ? Auth::user()->year_level . match((int)Auth::user()->year_level){1=>'st',2=>'nd',3=>'rd',default=>'th'} . ' Year' : '—' }}</div>
                        </div>
                    </div>
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-calendar3"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Semester</div>
                            <div class="info-val academic-spec-value">{{ Auth::user()->semester ? Auth::user()->semester . match((int)Auth::user()->semester){1=>'st',2=>'nd',3=>'rd',default=>'th'} . ' Semester' : '—' }}</div>
                        </div>
                    </div>
                    <div class="info-row academic-spec-card">
                        <div class="info-icon academic-spec-icon"><i class="bi bi-envelope-fill"></i></div>
                        <div class="academic-spec-info">
                            <div class="info-lbl academic-spec-label">Primary Email</div>
                            <div class="info-val academic-spec-value">{{ Auth::user()->email }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── TAB: SECURITY & ACCESS CONTROL ── -->
    <div id="tab-security" class="spanel">

        <!-- ── Security Hero Header ── -->
        <div class="sec-health-hero">
            <div class="sec-health-left">
                <div class="sec-health-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div>
                    <div class="sec-health-title">Security &amp; Access Control</div>
                    <div class="sec-health-sub">Manage credentials, biometric verification, authorized devices, and emergency vault</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <div class="sec-health-pill {{ $deviceBinding && $deviceBinding->isLocked() ? 'gold' : '' }}">
                    <span class="sec-pulse-dot" style="{{ $deviceBinding && $deviceBinding->isLocked() ? 'background:#ef4444;' : 'background:#4ade80;' }}"></span>
                    <span>{{ $deviceBinding && $deviceBinding->isLocked() ? 'Device Locked' : 'Account Protected' }}</span>
                </div>
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
                <span>FIDO2 / Biometrics</span>
            </div>
            <div class="sec-matrix-item">
                <i class="bi bi-phone-fill" style="color:{{ $deviceBinding ? '#34d399' : '#fbbf24' }};"></i>
                <span>{{ $deviceBinding ? 'Hardware Bound' : 'Device Unbound' }}</span>
            </div>
            <div class="sec-matrix-item">
                <i class="bi bi-safe-fill" style="color:#f5dfa8;"></i>
                <span>Recovery Vault</span>
            </div>
        </div>

        <!-- ── Security Section Interactive Sub-Navigation / Filter Bar ── -->
        <div class="sec-subnav-bar" id="secSubnavBar">
            <button type="button" class="sec-subnav-pill active" data-sec-sub="all" onclick="switchSecuritySub('all', this)">
                <i class="bi bi-grid-fill"></i>
                <span>All Controls</span>
            </button>
            <button type="button" class="sec-subnav-pill" data-sec-sub="credentials" onclick="switchSecuritySub('credentials', this)">
                <i class="bi bi-key-fill"></i>
                <span>Password &amp; Email</span>
            </button>
            <button type="button" class="sec-subnav-pill" data-sec-sub="biometrics" onclick="switchSecuritySub('biometrics', this)">
                <i class="bi bi-fingerprint"></i>
                <span>Biometrics Verification</span>
                <span class="sec-subnav-badge badge-emerald">FIDO2</span>
            </button>
            <button type="button" class="sec-subnav-pill" data-sec-sub="device" onclick="switchSecuritySub('device', this)">
                <i class="bi bi-phone-fill"></i>
                <span>Device Binding</span>
                <span class="sec-subnav-badge {{ $deviceBinding ? 'badge-emerald' : 'badge-amber' }}">{{ $deviceBinding ? 'Bound' : 'Action Req' }}</span>
            </button>
            <button type="button" class="sec-subnav-pill" data-sec-sub="recovery" onclick="switchSecuritySub('recovery', this)">
                <i class="bi bi-safe-fill"></i>
                <span>Emergency Vault</span>
            </button>
        </div>

        <!-- ── 1. ACCOUNT CREDENTIALS & PASSWORD SECTION ── -->
        <div class="sec-group-panel" id="sec-section-credentials">
            <div class="sec-group-header">
                <div class="sec-group-header-left">
                    <div class="sec-group-icon" style="background:rgba(207,164,111,0.12);color:#f5dfa8;border:1px solid rgba(207,164,111,0.25);">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <div class="sec-group-title">Password &amp; email</div>
                        <div class="sec-group-desc">Verify changes to your email address and password with a security code.</div>
                    </div>
                </div>
            </div>

            @if($user->isStudent())
            <div class="student-account-shortcuts" id="studentCredentialShortcuts" hidden>
                <article class="student-account-shortcut">
                    <span class="student-account-shortcut-icon"><i class="bi bi-envelope-at" aria-hidden="true"></i></span>
                    <span class="student-account-shortcut-copy"><strong>Email address</strong><span>{{ Auth::user()->email }}<br>Used for verification and account recovery.</span></span>
                    <button type="button" data-account-page="change-email">Change</button>
                </article>
                <article class="student-account-shortcut">
                    <span class="student-account-shortcut-icon"><i class="bi bi-key" aria-hidden="true"></i></span>
                    <span class="student-account-shortcut-copy"><strong>Password</strong><span>Change your password after verifying your email.</span></span>
                    <button type="button" data-account-page="change-password">Change</button>
                </article>
            </div>
            @endif

            <div class="sec-cards-grid" @if($user->isStudent()) id="studentCredentialFormGrid" @endif>
                <!-- Card 1: Email address Management -->
                <div class="sec-card {{ $user->isStudent() ? 'student-account-edit-card' : '' }}" @if($user->isStudent()) data-account-edit-source="email" @endif style="--card-accent: #3b82f6;">
                    <div class="sec-card-top">
                        <div class="sec-card-icon" style="background:rgba(59,130,246,0.12);color:#60a5fa;border:1px solid rgba(59,130,246,0.25);">
                            <i class="bi bi-envelope-check-fill"></i>
                        </div>
                        <div class="sec-card-meta">
                            <div class="sec-card-header-line">
                                <span class="sec-card-name">Email address</span>
                                <span class="sec-badge sec-badge-blue"><i class="bi bi-patch-check-fill"></i> Verified</span>
                            </div>
                            <div class="sec-card-subtitle">Used for account recovery, security codes, and school alerts.</div>
                        </div>
                    </div>

                    <div class="sec-card-content">
                        @if($user->isStudent())
                        <ol class="account-edit-progress" id="emailEditProgress" aria-label="Email change steps">
                            <li aria-current="step" data-account-step="1"><span class="account-step-number">1</span><span>New email</span></li>
                            <li data-account-step="2"><span class="account-step-number">2</span><span>Verify code</span></li>
                        </ol>
                        <div id="emailFormStatus" class="account-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>
                        @endif
                        <div id="emailStep1">
                            <div class="sec-input-display mb-2">
                                <i class="bi bi-envelope-at-fill text-primary me-2"></i>
                                <span class="sec-input-val" id="displayUserEmail">{{ Auth::user()->email }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ Auth::user()->email }}'); if(typeof showToast==='function') showToast('Email address copied!','info');" class="sec-copy-btn" title="Copy email">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div style="margin: 10px 0 12px;">
                                <label class="sl" for="inputNewEmail" style="font-size:0.82rem;margin-bottom:6px;color:#f3e7cd;display:block;">New email address</label>
                                <input type="email" id="inputNewEmail" class="si" placeholder="name@gmail.com" value="{{ old('new_email') }}" autocomplete="email" inputmode="email" maxlength="254" aria-describedby="emailAddressHelp" required style="padding:11px 13px;font-size:0.94rem;width:100%;">
                            </div>
                            <p class="account-edit-help sec-card-hint" id="emailAddressHelp">
                                Enter a Gmail address you can open. We’ll send a one-time code there to verify the change.
                            </p>
                            <button type="button" onclick="requestEmailOtp()" id="sendEmailOtpBtn" class="sec-action-btn sec-btn-blue">
                                <i class="bi bi-send-fill me-2" aria-hidden="true"></i>Send code
                            </button>
                        </div>

                        <div id="emailStep2" style="display:none;">
                            <div class="account-edit-current-value sec-status-tile" role="status">
                                <i class="bi bi-envelope-check-fill" style="font-size:1.05rem;"></i>
                                <span>Code sent to <strong id="emailSentDestination">{{ old('new_email', Auth::user()->email) }}</strong></span>
                            </div>
                            <form action="{{ route('otp.email.change') }}" method="POST">
                                @csrf
                                <label class="sl" for="ed1" style="font-size:0.82rem;margin-bottom:6px;">6-digit verification code</label>
                                <div class="account-otp-grid" role="group" aria-label="Email verification code">
                                    @for($j=1;$j<=6;$j++)
                                    <input type="text" class="email-otp-digit account-otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" id="ed{{$j}}" aria-label="Digit {{$j}} of 6" @if($j === 1) autocomplete="one-time-code" @endif required>
                                    @endfor
                                </div>
                                <p class="account-edit-help sec-card-hint" id="emailOtpHelp">Enter the code we sent. You can paste all six digits into the first box.</p>
                                <input type="hidden" name="otp" id="emailOtpHidden">
                                <label class="sl" for="confirmNewEmailInput" style="font-size:0.82rem;margin:14px 0 6px;">New email address</label>
                                <input type="email" name="new_email" id="confirmNewEmailInput" class="si" placeholder="name@gmail.com" value="{{ old('new_email') }}" readonly required style="margin-bottom:8px;padding:11px 13px;font-size:0.94rem;">
                                <div class="account-form-actions">
                                    <button type="button" onclick="cancelEmailOtp()" class="cancel-btn">Back</button>
                                    <button type="button" class="sec-action-btn sec-btn-blue" onclick="collectEmailOtp(this)"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Verify and update email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Password -->
                <div class="sec-card {{ $user->isStudent() ? 'student-account-edit-card' : '' }}" @if($user->isStudent()) data-account-edit-source="password" @endif style="--card-accent: #f59e0b;">
                    <div class="sec-card-top">
                        <div class="sec-card-icon" style="background:rgba(207,164,111,0.12);color:#f5dfa8;border:1px solid rgba(207,164,111,0.25);">
                            <i class="bi bi-key-fill"></i>
                        </div>
                        <div class="sec-card-meta">
                            <div class="sec-card-header-line">
                                <span class="sec-card-name">Password</span>
                                <span class="sec-badge sec-badge-amber"><i class="bi bi-shield-lock-fill"></i> Encrypted</span>
                            </div>
                            <div class="sec-card-subtitle">Use a strong, unique password to protect your account.</div>
                        </div>
                    </div>

                    <div class="sec-card-content">
                        @if($user->isStudent())
                        <ol class="account-edit-progress" id="passwordEditProgress" aria-label="Password change steps">
                            <li aria-current="step" data-account-step="1"><span class="account-step-number">1</span><span>Request code</span></li>
                            <li data-account-step="2"><span class="account-step-number">2</span><span>Verify &amp; update</span></li>
                        </ol>
                        <div id="passwordFormStatus" class="account-edit-status" role="status" aria-live="polite" aria-atomic="true" hidden></div>
                        @endif
                        <div id="otpStep1">
                            <div class="sec-status-tile">
                                <i class="bi bi-shield-check text-warning"></i>
                                <span>Your current password stays private</span>
                            </div>
                            <p class="account-edit-help">
                                We’ll send a one-time code to <strong>{{ Auth::user()->email }}</strong>. Verify it to choose a new password.
                            </p>
                            <button type="button" onclick="requestOtp()" id="sendOtpBtn" class="sec-action-btn sec-btn-amber">
                                <i class="bi bi-shield-lock-fill me-2" aria-hidden="true"></i>Send verification code
                            </button>
                        </div>

                        <div id="otpStep2" style="display:none;">
                            <div class="account-edit-current-value sec-status-tile" role="status">
                                <i class="bi bi-envelope-check-fill" aria-hidden="true"></i>
                                <span>Code sent to <strong>{{ Auth::user()->email }}</strong></span>
                            </div>
                            <form action="{{ route('otp.change') }}" method="POST">
                                @csrf
                                <label class="sl" for="sd1" style="font-size:0.82rem;margin-bottom:6px;">6-digit verification code</label>
                                <div class="account-otp-grid" role="group" aria-label="Password verification code">
                                    @for($i=1;$i<=6;$i++)
                                    <input type="text" class="otp-digit-s account-otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" id="sd{{$i}}" aria-label="Digit {{$i}} of 6" @if($i === 1) autocomplete="one-time-code" @endif required>
                                    @endfor
                                </div>
                                <p class="account-edit-help sec-card-hint">Enter the code we sent. You can paste all six digits into the first box.</p>
                                <input type="hidden" name="otp" id="settingsOtpHidden">
                                <label class="sl" for="spw1" style="font-size:0.82rem;margin-bottom:6px;">New password</label>
                                <div class="pw-wrap" style="margin-bottom:10px;">
                                    <input type="password" name="password" id="spw1" class="si" placeholder="Create a strong password" minlength="12" autocomplete="new-password" autocapitalize="none" autocorrect="off" spellcheck="false" aria-describedby="passwordRequirements" style="padding:11px 13px;font-size:0.94rem;" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('spw1',this,event)" data-toggle-password="spw1" aria-controls="spw1" aria-label="Show password" title="Show password" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <p class="account-edit-help sec-card-hint" id="passwordRequirements">Use at least 12 characters with uppercase and lowercase letters, a number, and a symbol.</p>
                                <label class="sl" for="spw2" style="font-size:0.82rem;margin-bottom:6px;">Confirm new password</label>
                                <div class="pw-wrap" style="margin-bottom:10px;">
                                    <input type="password" name="password_confirmation" id="spw2" class="si" placeholder="Enter the new password again" minlength="12" autocomplete="new-password" autocapitalize="none" autocorrect="off" spellcheck="false" style="padding:11px 13px;font-size:0.94rem;" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('spw2',this,event)" data-toggle-password="spw2" aria-controls="spw2" aria-label="Show password confirmation" title="Show password confirmation" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <div class="account-form-actions">
                                    <button type="button" onclick="cancelOtp()" class="cancel-btn">Back</button>
                                    <button type="button" class="sec-action-btn sec-btn-amber" onclick="collectOtp(this)"><i class="bi bi-check2-circle me-1" aria-hidden="true"></i>Update password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── 2. BIOMETRICS VERIFICATION SECTION ── -->
        <div class="sec-group-panel" id="sec-section-biometrics">
            <div class="sc mb-0" id="tab-fingerprint">
                <div class="sc-head">
                    <div class="sc-icon" style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);">
                        <i class="bi bi-shield-lock-fill"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                            <div class="sc-title">{{ $user->isStudent() ? 'Biometrics' : 'Device passkeys' }}</div>
                            <span class="sec-badge sec-badge-green"><i class="bi bi-patch-check-fill"></i> WebAuthn security</span>
                        </div>
                        <div class="sc-sub">{{ $user->isStudent() ? 'Register your face for attendance or register your fingerprint for sign-in and attendance.' : 'Set up a device passkey for sign-in.' }}</div>
                    </div>
                </div>
                <div class="sc-body">

                    @if($user->isStudent())
                    <div id="studentBiometricChoices" class="student-biometric-choices">
                        <a id="studentFaceRegistrationLink" href="{{ route('settings.biometrics.face') }}"><i class="bi bi-camera-video" aria-hidden="true"></i><span><strong>Face registration</strong><small>Use the camera to register your face for attendance checks.</small></span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                        <a id="studentFingerprintRegistrationLink" href="{{ route('settings.biometrics.fingerprint') }}"><i class="bi bi-fingerprint" aria-hidden="true"></i><span><strong>Fingerprint registration</strong><small>Register your device’s fingerprint or biometric verification for sign-in and attendance.</small></span><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
                    </div>
                    @else
                    <div id="studentPasskeySettings">
                    <div class="bio-flow-section">
                        <!-- Scanner Stage Container -->
                        <div class="bio-scanner-card" id="bioScannerStage">

                            <!-- State A: Idle / Ready Overview -->
                            <div class="bio-stage-view" id="bioIdleView">
                                <div class="bio-preview-container">
                                    <div class="bio-method-preview active">
                                        <div class="bio-sensor-pad" id="bioSensorPad" aria-hidden="true" style="margin:0 auto 14px;">
                                            <i class="bi bi-shield-lock" id="bioSensorPadIcon"></i>
                                        </div>
                                        <div class="bio-preview-title" id="bioCapabilityTitle">Add a device passkey</div>
                                        <div class="bio-preview-sub" id="bioCapabilityDesc">Use your operating system’s built-in biometric sensor or screen lock to register a WebAuthn passkey. Only a cryptographic public key is stored.</div>
                                    </div>
                                </div>
                                <div style="max-width:460px;margin:14px auto;text-align:left;">
                                    <label for="bioDeviceNameInput" style="display:block;font-size:.78rem;font-weight:700;color:#cbd5e1;margin-bottom:6px;">Device name (optional)</label>
                                    <input type="text" id="bioDeviceNameInput" class="si" maxlength="80" autocomplete="off" placeholder="e.g., Office Laptop or Phone" style="margin-bottom:12px;">
                                    <label for="bioConsentCheckbox" style="display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;border:1px solid rgba(255,255,255,0.12);background:rgba(15,23,42,0.45);font-size:.8rem;line-height:1.45;color:#cbd5e1;cursor:pointer;">
                                        <input type="checkbox" id="bioConsentCheckbox" style="margin-top:3px;width:16px;height:16px;flex-shrink:0;">
                                        <span>I consent to linking this device’s platform passkey to my account. My biometric data stays on my device and is never sent to the server.</span>
                                    </label>
                                </div>
                                <div class="bio-action-row">
                                    <button type="button" id="startBioBtn" class="sbtn btn-emerald bio-primary-cta" aria-describedby="devicePasskeyStatus" disabled>
                                        <i class="bi bi-shield-check me-2" id="startBioBtnIcon" aria-hidden="true"></i><span id="startBioBtnText">Register this device</span>
                                    </button>
                                </div>
                                <p id="devicePasskeyStatus" class="bio-preview-sub" role="status">Checking device compatibility…</p>
                            </div>
                            <!-- Device verification -->
                            <div class="bio-stage-view" id="bioScanningView" style="display:none;">
                                <div class="bio-scan-hud-container">
                                    <div class="bio-scanner-display active">
                                        <div class="bio-sensor-pad is-awaiting" id="devicePasskeyFrame" aria-hidden="true" style="margin:0 auto 14px;">
                                            <i class="bi bi-shield-lock"></i>
                                        </div>
                                        <div class="bio-scanning-title" id="fpScanningTitle">Preparing device prompt</div>
                                        <div class="bio-scanning-sub" id="fpStatusSub" role="status">Setting up the passkey request.</div>
                                    </div>
                                </div>
                                <div class="bio-cancel-row">
                                    <button type="button" id="confirmBioBtn" class="cancel-btn bio-cancel-btn" hidden>Continue to device prompt</button>
                                    <button type="button" id="cancelBioBtn" class="cancel-btn bio-cancel-btn">
                                        <i class="bi bi-x-circle me-1"></i>Cancel Registration
                                    </button>
                                </div>
                            </div>

                            <!-- State C: Success View -->
                            <div class="bio-stage-view" id="bioSuccessView" style="display:none;">
                                <div class="bio-success-wrap">
                                    <div class="bio-success-icon-ring">
                                        <svg class="bio-success-svg" viewBox="0 0 52 52">
                                            <circle class="bio-success-circle" cx="26" cy="26" r="25" fill="none"/>
                                            <path class="bio-success-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                                        </svg>
                                    </div>
                                    <div class="bio-success-title" id="bioSuccessTitle">Passkey registered</div>
                                    <div class="bio-success-desc">
                                        Your device passkey has been verified and linked to your account.
                                    </div>
                                    <div class="bio-success-actions">
                                        <button type="button" id="doneBioBtn" class="sbtn btn-emerald" style="padding:10px 24px;">
                                            <i class="bi bi-check-lg me-1"></i>Done
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- State D: Error View -->
                            <div class="bio-stage-view" id="bioErrorView" style="display:none;">
                                <div class="bio-error-wrap">
                                    <div class="bio-error-icon-box">
                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                    </div>
                                    <div class="bio-error-title" id="bioErrorTitle">Registration Failed</div>
                                    <div class="bio-error-desc" id="bioErrorDesc">
                                        Try registering your device again.
                                    </div>
                                    <div class="bio-error-actions">
                                        <button type="button" class="sbtn btn-emerald" id="retryBtn" style="padding:10px 24px;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Try Again
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Registered devices -->
                    <div style="margin-top:28px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;">
                            <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">Registered devices</div>
                            <span id="deviceCountBadge" style="font-size:.72rem;background:rgba(207,164,111,0.12);color:var(--gold,#cfa46f);padding:3px 10px;border-radius:99px;border:1px solid rgba(207,164,111,0.25);font-weight:700;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;">Loading...</span>
                        </div>
                        <div id="deviceList">
                            <div style="text-align:center;padding:32px 20px;color:#94a3b8;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:14px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                                <i class="bi bi-shield-lock" style="font-size:2.6rem;display:block;margin-bottom:10px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                                <div style="font-weight:700;color:#f8fafc;margin-bottom:4px;">No device passkeys registered yet</div>
                                <div style="font-size:.78rem;color:#94a3b8;">Use the registration button to add this device.</div>
                            </div>
                        </div>
                    </div>

                    </div> <!-- /studentPasskeySettings -->
                    @endif
                </div>
            </div>
        </div>

        <!-- ── 3. ATTENDANCE DEVICE BINDING & HARDWARE TRUST SECTION ── -->
        <div class="sec-group-panel" id="sec-section-device">
            <div id="tab-device">
                <div class="sec-group-header">
                    <div class="sec-group-header-left">
                        <div class="sec-group-icon" style="background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.3);">
                            <i class="bi bi-phone-fill"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                <div class="sec-group-title">Attendance device</div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="sec-health-pill" id="deviceTrustScorePill" style="background:rgba(59,130,246,0.12);border-color:rgba(59,130,246,0.3);color:#60a5fa;display:none;">
                                        <i class="bi bi-shield-check me-1"></i>
                                        <span id="deviceTrustScoreText">Trust Score: 85/100</span>
                                    </div>
                                    <div class="sec-health-pill {{ $deviceBinding ? ($deviceBinding->isLocked() ? 'gold' : 'emerald') : '' }}" id="deviceHeroPill">
                                        <span class="sec-pulse-dot" style="{{ $deviceBinding ? ($deviceBinding->isLocked() ? 'background:#ef4444;' : 'background:#34d399;') : 'background:#f59e0b;' }}"></span>
                                        <span id="deviceHeroPillText">
                                            @if(!$deviceBinding)
                                                Device Not Bound
                                            @elseif($deviceBinding->isLocked())
                                                Device Locked
                                            @else
                                                Device Bound
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="sec-group-desc">Manage the devices authorized to access the account and provide appropriate options for viewing, adding, or removing trusted devices</div>
                        </div>
                    </div>
                </div>

                <!-- Lock Warning Alert (Visible only when locked) -->
                <div id="deviceLockedAlert" style="{{ $deviceBinding && $deviceBinding->isLocked() ? 'display:flex;' : 'display:none;' }};align-items:center;justify-content:space-between;gap:16px;background:rgba(239,68,68,0.12);border:1px solid rgba(239,68,68,0.3);border-radius:12px;padding:16px;margin-bottom:20px;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <i class="bi bi-shield-slash-fill" style="font-size:1.8rem;color:#f87171;"></i>
                        <div>
                            <div style="font-weight:700;color:#fca5a5;font-size:0.95rem;">Attendance Frozen: Device Locked</div>
                            <div style="font-size:0.82rem;color:#fecaca;" id="deviceLockedReasonText">
                                Reason: {{ $deviceBinding && $deviceBinding->locked_reason ? $deviceBinding->locked_reason : 'Suspicious activity or manual anti-theft freeze' }}
                            </div>
                        </div>
                    </div>
                    <button type="button" onclick="handleUnlockDevice()" id="tabDeviceUnlockBtn" class="sec-action-btn" style="background:#ef4444;color:#fff;border:none;padding:8px 16px;font-size:0.85rem;white-space:nowrap;">
                        <i class="bi bi-unlock-fill me-1"></i> Unlock Device
                    </button>
                </div>

                <!-- 2-Column Responsive Device Grid -->
                <div class="sec-cards-grid">

                    <!-- Card 1: This Physical Device (Current Browser Telemetry) -->
                    <div class="sec-card" style="--card-accent: #3b82f6;">
                        <div class="sec-card-top">
                            <div class="sec-card-icon" style="background:rgba(59,130,246,0.12);color:#60a5fa;border:1px solid rgba(59,130,246,0.25);">
                                <i class="bi bi-laptop"></i>
                            </div>
                            <div class="sec-card-meta">
                                <div class="sec-card-header-line">
                                    <span class="sec-card-name">This device</span>
                                    <span class="sec-badge sec-badge-blue" id="currentDeviceMatchBadge" style="display:none;">
                                        <i class="bi bi-shield-check"></i> Current Device
                                    </span>
                                </div>
                                <div class="sec-card-subtitle">The phone or computer you are using now.</div>
                            </div>
                        </div>

                        <div class="sec-card-content">
                            <div class="info-row" style="padding:10px 0;">
                                <div class="info-icon"><i class="bi bi-cpu-fill"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="info-lbl">Detected Hardware Model</div>
                                    <div class="info-val" id="thisDeviceModelText">{{ request()->header('User-Agent') ? Str::limit(request()->header('User-Agent'), 45) : 'Client Device' }}</div>
                                </div>
                            </div>

                            @if($user->isStudent())<details class="student-device-details"><summary>Technical device details</summary>@endif
                            <div class="info-row" style="padding:10px 0;">
                                <div class="info-icon"><i class="bi bi-gpu-card"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="info-lbl">Graphics / GPU Engine</div>
                                    <div class="info-val" id="thisDeviceGpuText" style="font-size:0.82rem;color:#93c5fd;">Inspecting Silicon...</div>
                                </div>
                            </div>

                            <div class="info-row" style="padding:10px 0;">
                                <div class="info-icon"><i class="bi bi-display"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="info-lbl">Display &amp; Processing Cores</div>
                                    <div class="info-val" id="thisDeviceDisplayCores" style="font-size:0.82rem;color:#e2e8f0;">Reading Environment...</div>
                                </div>
                            </div>

                            <div class="info-row" style="padding:10px 0;">
                                <div class="info-icon"><i class="bi bi-globe2"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="info-lbl">Current Network &amp; IP</div>
                                    <div class="info-val" id="thisDeviceNetworkText" style="font-family:monospace;color:#60a5fa;">{{ request()->ip() }}</div>
                                </div>
                            </div>

                            <div class="info-row" style="padding:10px 0;border-bottom:none;">
                                <div class="info-icon"><i class="bi bi-fingerprint"></i></div>
                                <div style="flex:1;min-width:0;">
                                    <div class="info-lbl">Hardware Environment Protection</div>
                                    <div class="info-val" style="color:#4ade80;font-size:0.82rem;"><i class="bi bi-check2-circle me-1"></i> WebGL &amp; Canvas Silicon Hash Active</div>
                                </div>
                            </div>

                            @if($user->isStudent())</details>@endif
                            <div style="margin-top:16px;">
                                <button type="button" onclick="handleBindCurrentDevice()" id="tabDeviceBindBtn" class="sec-action-btn sec-btn-emerald" style="width:100%;padding:12px;font-size:0.9rem;">
                                    <i class="bi bi-link-45deg me-2"></i><span id="tabDeviceBindBtnText">{{ $deviceBinding ? 'Switch & Bind to This Device' : 'Bind to This Device' }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Authorized Account Device -->
                    <div class="sec-card" style="--card-accent: #10b981;">
                        <div class="sec-card-top">
                            <div class="sec-card-icon" style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);">
                                <i class="bi {{ $deviceBinding ? $deviceBinding->getDeviceIcon() : 'bi-phone' }}" id="deviceTabBoundIcon"></i>
                            </div>
                            <div class="sec-card-meta">
                                <div class="sec-card-header-line">
                                    <span class="sec-card-name">Bound Attendance Device</span>
                                    <span class="sec-badge {{ $deviceBinding ? ($deviceBinding->isLocked() ? 'sec-badge-danger' : 'sec-badge-emerald') : 'sec-badge-gold' }}" id="deviceTabAuthorizedBadge">
                                        <i class="bi {{ $deviceBinding ? ($deviceBinding->isLocked() ? 'bi-lock-fill' : 'bi-patch-check-fill') : 'bi-shield-exclamation' }}"></i>
                                        <span id="deviceTabAuthorizedBadgeText">
                                            @if(!$deviceBinding)
                                                Unregistered
                                            @elseif($deviceBinding->isLocked())
                                                Locked
                                            @else
                                                Authorized
                                            @endif
                                        </span>
                                    </span>
                                </div>
                                <div class="sec-card-subtitle">Device registered to record student attendance</div>
                            </div>
                        </div>

                        <div class="sec-card-content">
                            <div id="deviceTabBoundTile" style="{{ $deviceBinding ? 'display:block;' : 'display:none;' }}">
                                <div class="info-row" style="padding:10px 0;">
                                    <div class="info-icon"><i class="bi bi-phone"></i></div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="info-lbl">Device Model / Name</div>
                                        <div class="info-val" id="deviceTabBoundName">{{ $deviceBinding ? $deviceBinding->device_name : 'No device bound' }}</div>
                                    </div>
                                </div>

                                @if($user->isStudent())<details class="student-device-details"><summary>Registration details</summary>@endif
                            <div class="info-row" style="padding:10px 0;">
                                    <div class="info-icon"><i class="bi bi-shield-lock-fill"></i></div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="info-lbl">Trust Level &amp; Score</div>
                                        <div class="info-val" id="deviceTabBoundTrust" style="color:#34d399;font-weight:700;">
                                            {{ $deviceBinding ? ($deviceBinding->getTrustLevel() . ' (' . ($deviceBinding->trust_score ?? 85) . '/100)') : '—' }}
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row" style="padding:10px 0;">
                                    <div class="info-icon"><i class="bi bi-wifi"></i></div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="info-lbl">Registration IP &amp; Changes</div>
                                        <div class="info-val" style="font-family:monospace;color:#4ade80;">
                                            <span id="deviceTabBoundIp">{{ $deviceBinding ? ($deviceBinding->ip_address ?: 'Unknown') : '—' }}</span>
                                            <span style="color:#b39b82;font-size:0.75rem;margin-left:8px;" id="deviceTabBoundChanges">({{ $deviceBinding ? (int)$deviceBinding->change_count : 0 }} switches)</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-row" style="padding:10px 0;border-bottom:none;">
                                    <div class="info-icon"><i class="bi bi-clock-history"></i></div>
                                    <div style="flex:1;min-width:0;">
                                        <div class="info-lbl">Last Active Sync</div>
                                        <div class="info-val" id="deviceTabBoundTime">{{ $deviceBinding && $deviceBinding->last_seen_at ? $deviceBinding->last_seen_at->diffForHumans() : 'Recently' }}</div>
                                    </div>
                                </div>

                                @if($user->isStudent())</details>@endif
                            <div style="display:flex;gap:10px;margin-top:16px;">
                                    <button type="button" onclick="handleLockDevice()" id="tabDeviceLockBtn" class="sec-action-btn" style="flex:1;padding:10px;font-size:0.84rem;background:rgba(245,158,11,0.12);color:#fbbf24;border:1px solid rgba(245,158,11,0.3);">
                                        <i class="bi bi-lock-fill me-1"></i> Freeze / Lock
                                    </button>
                                    <button type="button" onclick="handleUnbindDevice()" id="tabDeviceUnbindBtn" class="sec-action-btn" style="flex:1;padding:10px;font-size:0.84rem;background:rgba(239,68,68,0.12);color:#f87171;border:1px solid rgba(239,68,68,0.3);">
                                        <i class="bi bi-trash3-fill me-1"></i> Unbind
                                    </button>
                                </div>
                            </div>

                            <div id="deviceTabUnboundTile" style="{{ $deviceBinding ? 'display:none;' : 'display:block;' }};text-align:center;padding:24px 16px;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);">
                                <i class="bi bi-phone" style="font-size:2.2rem;color:rgba(207,164,111,0.4);display:block;margin-bottom:8px;"></i>
                                <div style="font-weight:700;color:#f3e7cd;font-size:0.92rem;margin-bottom:4px;">No Device Bound Yet</div>
                                <p style="font-size:0.8rem;color:#b39b82;margin-bottom:0;">
                                    Click "Bind to This Device" to authorize your device for classroom attendance scanning.
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ── 4. EMERGENCY RECOVERY VAULT SECTION ── -->
        <div class="sec-group-panel" id="sec-section-recovery">
            <div class="sec-group-header">
                <div class="sec-group-header-left">
                    <div class="sec-group-icon" style="background:rgba(234,179,8,0.12);color:#fbbf24;border:1px solid rgba(234,179,8,0.25);">
                        <i class="bi bi-safe-fill"></i>
                    </div>
                    <div>
                        <div class="sec-group-title">Recovery codes</div>
                        <div class="sec-group-desc">One-time offline emergency keys to restore account access if credentials or devices are lost</div>
                    </div>
                </div>
            </div>

            <!-- Card: Emergency Recovery Codes Vault -->
            <div class="sec-card" style="--card-accent: #cfa46f;max-width:680px;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(234,179,8,0.12);color:#fbbf24;border:1px solid rgba(234,179,8,0.25);">
                        <i class="bi bi-safe-fill"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Backup recovery codes</span>
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



    @if(Auth::user()->isStudent())
    <!-- ── TAB: FAMILY / GUARDIAN ── -->
    <div id="tab-family" class="spanel">

        <!-- Hero Header -->
        <div class="sec-health-hero mb-4" style="background:#14080C;border-color:rgba(250,247,248,0.12);border-top:2px solid #6B192B;">
            <div class="sec-health-left">
                <div class="sec-health-icon" style="background:#1C0C11;color:#E898A8;border:1px solid rgba(232,152,168,0.28);">
                    <i class="bi bi-people-fill" aria-hidden="true"></i>
                </div>
                <div>
                    <div class="sec-health-title">Family &amp; Guardian Connections</div>
                    <div class="sec-health-sub">Authorize family members to monitor your attendance records and receive absence notices</div>
                </div>
            </div>
            <div class="sec-health-pill {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'emerald' : '' }}">
                <span class="sec-pulse-dot" style="{{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'background:#34d399;' : 'background:#fbbf24;' }}"></span>
                <span>{{ (isset($linkedParents) && $linkedParents->count() > 0) ? $linkedParents->count() . ' Guardian(s) Linked' : 'No Guardians Linked' }}</span>
            </div>
        </div>

        <div class="sec-cards-grid">
            <!-- Card 1: Instant Parent Link Code Generator -->
            <div class="sec-card" style="--card-accent: #E898A8;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:#1C0C11;color:#E898A8;border:1px solid rgba(232,152,168,0.28);">
                        <i class="bi bi-upc-scan" aria-hidden="true"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Instant Link Code</span>
                            <span class="sec-badge sec-badge-gold">
                                <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Zero Email Delay
                            </span>
                        </div>
                        <div class="sec-card-subtitle">Invite your guardian from the Parent Portal</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div class="student-link-code-copy" style="font-size:0.82rem;color:#D4C6CA;line-height:1.5;margin-bottom:14px;">
                        Generate a code and share it with your guardian. They can enter it under <strong>Link Student</strong>.
                    </div>

                    <!-- Code Display Card (Hidden until generated) -->
                    <div id="parentCodeDisplayCard" style="display:none;background:#0B0406;border:1px solid rgba(232,152,168,0.34);border-radius:12px;padding:18px 16px;margin-bottom:16px;text-align:center;">
                        <div style="font-size:0.72rem;font-weight:700;color:#E898A8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Your 6-Digit Linking Code</div>
                        <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin:8px 0;">
                            <div id="parentCodeValue" style="font-size:2.1rem;font-family:'JetBrains Mono',ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-weight:800;color:#FAF7F8;letter-spacing:8px;padding:6px 14px;background:#14080C;border-radius:10px;border:1px solid rgba(250,247,248,0.14);">
                                ------
                            </div>
                            <button type="button" class="sbtn btn-gold" onclick="copyParentLinkCode(this)" style="padding:10px 16px;font-size:0.8rem;width:auto;" title="Copy Code" aria-label="Copy linking code">
                                <i class="bi bi-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="parentCodeTimer" style="font-size:0.78rem;font-weight:700;color:#34d399;margin-bottom:14px;">
                            Valid for: 15:00
                        </div>

                        <!-- QR Code Box -->
                        <div style="display:inline-block;padding:12px;background:#ffffff;border-radius:12px;margin:0 auto 10px;">
                            <div id="parentCodeQrBox" style="width:170px;height:170px;display:flex;align-items:center;justify-content:center;"></div>
                        </div>
                        <div style="font-size:0.74rem;color:#9E8990;">
                            Parents can scan this QR code with their mobile camera to link instantly.
                        </div>
                    </div>

                    <button type="button" id="generateParentCodeBtn" onclick="generateParentLinkCode()" class="sec-action-btn sec-btn-gold" style="width:100%;padding:12px;font-size:0.88rem;">
                        <i class="bi bi-key-fill me-1" aria-hidden="true"></i> Generate Parent Link Code
                    </button>
                </div>
            </div>

            <!-- Card 2: Connected Guardians List -->
            <div class="sec-card" style="--card-accent: #10b981;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Authorized Guardians</span>
                            <span class="sec-badge {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'sec-badge-emerald' : 'sec-badge-amber' }}">
                                <i class="bi bi-patch-check-fill" aria-hidden="true"></i> {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'Active' : 'Unlinked' }}
                            </span>
                        </div>
                        <div class="sec-card-subtitle">Parents and guardians with view-only attendance access</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    @if(isset($linkedParents) && $linkedParents->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:12px;">
                            @foreach($linkedParents as $guardian)
                            <div class="info-row" id="guardian-item-{{ $guardian->id }}" style="padding:12px;background:#0B0406;border:1px solid rgba(250,247,248,0.1);border-radius:10px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                                    <img src="{{ $guardian->profile_photo_url }}" alt="{{ $guardian->name }}" style="width:40px;height:40px;border-radius:10px;object-fit:cover;border:1px solid rgba(250,247,248,0.2);">
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-weight:700;color:#FAF7F8;font-size:0.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $guardian->name }}</div>
                                        <div style="font-size:0.75rem;color:#9E8990;">
                                            <span>{{ $guardian->email }}</span>
                                            @if($guardian->phone)
                                                <span>• {{ $guardian->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="sec-badge sec-badge-green" style="font-size:0.74rem;">
                                    <i class="bi bi-shield-check" aria-hidden="true"></i> Linked
                                </span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="student-guardians-empty" style="text-align:center;padding:24px 16px;background:#0B0406;border-radius:10px;border:1px dashed rgba(250,247,248,0.14);margin-bottom:12px;">
                            <i class="bi bi-person-x" style="font-size:2rem;color:#E898A8;opacity:0.65;display:block;margin-bottom:6px;" aria-hidden="true"></i>
                            <div class="student-guardians-empty-copy">
                                <div style="font-weight:700;color:#FAF7F8;font-size:0.9rem;margin-bottom:4px;">No Guardians Connected</div>
                                <div style="font-size:0.78rem;color:#9E8990;">Generate a link code to invite a guardian.</div>
                            </div>
                        </div>
                    @endif

                    <div class="student-privacy-note" style="background:#1C0C11;border:1px solid rgba(250,247,248,0.1);border-radius:10px;padding:12px 14px;font-size:0.78rem;color:#D4C6CA;line-height:1.45;">
                        <i class="bi bi-shield-lock-fill me-1" style="color:#E898A8;" aria-hidden="true"></i>
                        <span class="student-privacy-note-text"><strong>Privacy safeguard:</strong> Guardians can view attendance and subject summaries. Students cannot remove a guardian link; a guardian or school administrator can manage it.</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
    @endif

    <!-- ── TAB: PREFERENCES ── -->
    <div id="tab-preferences" class="spanel">
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(59,130,246,0.14);color:#60a5fa;"><i class="bi bi-sliders"></i></div>
                <div>
                    <div class="sc-title">Notifications &amp; app</div>
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
                    <select class="si pref-lang-select" style="width:auto;min-width:160px;padding:9px 16px;font-size:0.85rem;border-radius:12px;cursor:pointer;flex-shrink:0;">
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
                                <label class="tlabel" for="preferenceInApp">In-App Notifications</label>
                                <div class="tsub">Receive instant badges and banners inside the portal</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" id="preferenceInApp" name="prefs[in_app]" value="1" {{ !empty($prefs['in_app']) ? 'checked' : '' }}>
                        </div>
                    </div>
                    
                    <!-- Email Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-envelope"></i></div>
                            <div style="flex:1;min-width:0;">
                                <label class="tlabel" for="preferenceEmail">Email Notifications</label>
                                <div class="tsub">Receive attendance summaries, excuse approvals, and security alerts via email</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" id="preferenceEmail" name="prefs[email]" value="1" {{ !empty($prefs['email']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <!-- SMS Notifications -->
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-chat-dots"></i></div>
                            <div style="flex:1;min-width:0;">
                                <label class="tlabel" for="preferenceSms">SMS Urgent Notifications</label>
                                <div class="tsub">Emergency announcements and absence warnings via text message</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" id="preferenceSms" name="prefs[sms]" value="1" {{ !empty($prefs['sms']) ? 'checked' : '' }}>
                        </div>
                    </div>

                    <!-- Display Interface -->
                    <div style="margin:20px 0 10px;font-size:.75rem;font-weight:700;color:#b39b82;text-transform:uppercase;letter-spacing:.5px;">Display & Layout</div>
                    
                    <div class="pref-tile">
                        <div style="display:flex;align-items:center;gap:14px;flex:1;min-width:0;">
                            <div class="pref-tile-icon"><i class="bi bi-layout-sidebar"></i></div>
                            <div style="flex:1;min-width:0;">
                                <label class="tlabel" for="compactToggle">Compact Sidebar</label>
                                <div class="tsub">Keep sidebar collapsed on desktop for extra dashboard space</div>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-0" style="flex-shrink:0;">
                            <input class="form-check-input" type="checkbox" id="compactToggle">
                        </div>
                    </div>

                    <div style="margin-top: 24px; text-align: right;">
                        <button type="submit" class="sbtn btn-gold" style="width:auto;padding:11px 28px;">
                            <i class="bi bi-save me-2"></i>Save Preferences
                        </button>
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
                                <span>App updates</span>
                                <span class="badge" style="background:rgba(207,164,111,0.15);color:var(--gold,#cfa46f);border:1px solid rgba(207,164,111,0.3);font-size:0.72rem;font-weight:700;flex-shrink:0;">v{{ app(\App\Services\VersionService::class)->getInstalledVersion() }}</span>
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

    @if($user->isStudent())
    <div id="tab-account-email" class="spanel student-account-edit-panel" aria-label="Change email address">
        <div class="student-account-edit-intro">Enter a Gmail address you can open. We’ll send a verification code there before changing your account email.</div>
        <div id="accountEmailFormSlot"></div>
    </div>
    <div id="tab-account-password" class="spanel student-account-edit-panel" aria-label="Change password">
        <div class="student-account-edit-intro">We’ll send a security code to your current email. Enter it and choose a strong new password.</div>
        <div id="accountPasswordFormSlot"></div>
    </div>
    <div id="tab-account-phone" class="spanel student-account-edit-panel" aria-label="Change phone number">
        <div class="student-account-edit-intro">Update the phone number listed on your school account.</div>
        <div id="accountPhoneFormSlot"></div>
    </div>
    @endif

        </section> <!-- /settings-main-content -->
    </div> <!-- /settings-layout-grid -->
</div>

@php
    $settingsJsConfig = [
        'installedVersion' => app(\App\Services\VersionService::class)->getInstalledVersion(),
        'csrfToken' => csrf_token(),
        'userEmail' => Auth::user()?->email ?? '',
        'isStudent' => (bool) $user?->isStudent(),
        'deviceBindingExists' => (bool) $deviceBinding,
        'hasErrors' => $errors->any(),
        'routes' => [
            'deviceBind' => route('device.bind'),
            'deviceLock' => route('device.lock'),
            'deviceUnlock' => route('device.unlock'),
            'deviceUnbind' => route('device.unbind'),
            'deviceStatus' => route('device.status'),
            'otpChangeSend' => route('otp.change.send'),
            'otpEmailSend' => route('otp.email.send'),
            'recoveryGenerate' => route('recovery.generate'),
            'parentLinkGenerateCode' => route('student.parent_link.generate_code'),
            'parentLinkForm' => route('parent.link.form'),
        ],
    ];
@endphp
<script @cspNonce>
window.__SETTINGS_CONFIG__ = @json($settingsJsConfig);
</script>
@unless($user->isStudent())
<script src="{{ asset('js/device-passkey-capabilities.js') }}?v={{ filemtime(public_path('js/device-passkey-capabilities.js')) }}" @cspNonce></script>
<script @cspNonce>
@include('partials.device-biometric-registration-script')
</script>
@endunless
<script @cspNonce src="{{ asset('js/settings-page.js') }}?v={{ filemtime(public_path('js/settings-page.js')) }}"></script>
@if(Auth::user()->isStudent())
<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script src="{{ asset('js/student-settings.js') }}?v={{ filemtime(public_path('js/student-settings.js')) }}"></script>
@endif
@endsection
