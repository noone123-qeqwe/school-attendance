<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} | @yield('portal-title', 'Student Portal')</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard-enterprise.css') }}?v={{ filemtime(public_path('css/dashboard-enterprise.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-enterprise.css') }}?v={{ filemtime(public_path('css/mobile-enterprise.css')) }}">

    <style>
    :root {
        --maroon: #3d1610;
        --maroon-dark: #190b08;
        --gold: #cfa46f;
        --gold-soft: #d4b77d;
        --surface: #1d1410;
        --surface-soft: rgba(255,245,225,0.09);
        --surface-border: rgba(255,225,150,0.16);
        --text-primary: #f2e8d5;
        --text-secondary: #b8a88d;
        --muted: #8f826f;
        --highlight: #cfa46f;
        --shadow: rgba(0,0,0,0.5);
        --bg: #0f0b08;
        --sidebar-width: 260px;
        --sidebar-mini: 76px;
        --header-height: 72px;
    }

    * { box-sizing: border-box; }
    body {
        font-family: 'Inter', sans-serif;
        background: radial-gradient(circle at top left, rgba(220,180,115,0.12), transparent 25%),
                    radial-gradient(circle at bottom right, rgba(205,160,90,0.08), transparent 30%),
                    linear-gradient(180deg, #100a07 0%, #0c0704 35%, #0e0805 100%);
        margin: 0;
        min-height: 100vh;
        color: var(--text-primary);
    }

    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #b89f7b; border-radius: 99px; }

    /* ── MOBILE OVERLAY ── */
    .sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1090;
        backdrop-filter: blur(2px);
    }
    .sidebar-overlay.show { display: block; }

    /* ── SIDEBAR ── */
    .sidebar {
        width: var(--sidebar-width);
        height: 100vh;
        position: fixed;
        left: 0; top: 0;
        background: rgba(40, 16, 12, 0.65);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-right: 1px solid rgba(255,225,145,0.08);
        z-index: 1100;
        color: var(--text-primary);
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 28px rgba(0,0,0,0.45);
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }

    /* Mobile: sidebar hidden off-screen by default */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); width: var(--sidebar-width) !important; }
        .sidebar.open { transform: translateX(0); }
        .sidebar.mobile-open { transform: translateX(0); }
        .main-content { padding-left: 0 !important; }
        .main-content.mini { padding-left: 0 !important; }
        .top-header { left: 0 !important; }
        /* Make content full width on mobile */
        .container-fluid { padding: 12px !important; }
        .p-4 { padding: 12px !important; }
    }

    /* Collapsed = mini rail (desktop only) */
    @media (min-width: 769px) {
        .sidebar.collapsed { width: var(--sidebar-mini); }
    }

    /* ── SIDEBAR HEADER (logo area) ── */
    .sidebar-head {
        padding: 20px 12px 16px;
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
    }

    .sidebar-logo {
        width: 38px; height: 38px;
        border-radius: 50%;
        border: 2px solid var(--highlight);
        padding: 2px; background: white;
        flex-shrink: 0;
        transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275), width 0.3s, height 0.3s;
    }
    .sidebar-logo:hover { transform: rotate(12deg) scale(1.1); }

    /* Expanded logo is bigger */
    .sidebar:not(.collapsed) .sidebar-logo {
        width: 64px; height: 64px;
        margin-bottom: 8px;
    }

    .sidebar-text {
        overflow: hidden;
        white-space: normal;
        transition: opacity 0.2s, max-height 0.3s;
        max-height: 80px;
        opacity: 1;
        text-align: center;
    }
    .sidebar.collapsed .sidebar-text { opacity: 0; max-height: 0; pointer-events: none; }

    .sidebar-brand { 
        font-size: 0.68rem; 
        font-weight: 800; 
        text-transform: uppercase; 
        line-height: 1.2; 
        letter-spacing: 0.3px;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }
    .sidebar-subtitle { font-size: 0.55rem; color: rgba(255,255,255,0.45); font-weight: 500; letter-spacing: 0.2px; margin-top: 3px; line-height: 1.3; word-wrap: break-word; overflow-wrap: break-word; }
    .sidebar-portal { font-size: 0.6rem; color: var(--highlight); font-weight: 600; letter-spacing: 1px; text-transform: uppercase; margin-top: 2px; }
    .sidebar-head { background: rgba(255,245,220,0.04); border-bottom: 1px solid rgba(255,225,145,0.12); }

    .sidebar-divider { height: 1px; background: rgba(255,255,255,0.1); margin: 0 10px 12px; flex-shrink: 0; }

    .sidebar-section-label {
        padding: 8px 12px;
        margin-top: 12px;
        margin-bottom: 6px;
        font-size: 0.72rem;
        font-weight: 800;
        color: rgba(255,255,255,0.55);
        text-transform: uppercase;
        letter-spacing: 0.12em;
    }

    /* ── NAV LINKS ── */
    .sidebar-nav { flex: 1; padding: 0 8px; overflow-y: auto; overflow-x: hidden; }
    
    /* Scrollbar styling for sidebar nav */
    .sidebar-nav::-webkit-scrollbar { width: 6px; }
    .sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    .sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 3px; }
    .sidebar-nav::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.25); }

    .sidebar .nav-link {
        color: rgba(255,255,255,0.65);
        font-size: 0.84rem; font-weight: 500;
        padding: 11px 12px;
        display: flex; align-items: center; gap: 11px;
        border-radius: 10px; margin-bottom: 4px;
        text-decoration: none;
        transition: all 0.2s ease;
        position: relative;
        white-space: nowrap;
    }
    .sidebar .nav-link:not(.active):hover {
        background: rgba(255,255,255,0.06);
        color: #f3e7cd;
        transform: translateX(4px);
    }
    .sidebar .nav-link.active {
        background: rgba(207,164,111,0.12);
        color: #f3e7cd; font-weight: 700;
    }
    .sidebar .nav-link.active::before {
        content: '';
        position: absolute; left: 0; top: 50%;
        transform: translateY(-50%);
        width: 3px; height: 60%;
        background: var(--highlight);
        border-radius: 0 4px 4px 0;
    }
    .sidebar .nav-link i {
        font-size: 1.05rem;
        flex-shrink: 0;
        width: 20px; text-align: center;
        transition: transform 0.2s;
    }
    .sidebar .nav-link:hover i { transform: scale(1.15); }

    /* Hide link text when collapsed */
    .nav-link-text {
        transition: opacity 0.15s, width 0.3s;
        opacity: 1; overflow: hidden;
    }
    .sidebar.collapsed .nav-link-text { opacity: 0; width: 0; }

    /* Tooltip on collapsed icons */
    .sidebar.collapsed .nav-link {
        justify-content: center;
        padding: 11px 0;
    }
    .sidebar.collapsed .nav-link::after {
        content: attr(data-title);
        position: absolute;
        left: calc(var(--sidebar-mini) + 8px);
        background: #2d0f12;
        color: white;
        font-size: 0.78rem; font-weight: 600;
        padding: 5px 10px;
        border-radius: 7px;
        white-space: nowrap;
        opacity: 0; pointer-events: none;
        transition: opacity 0.15s;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
    }
    .sidebar.collapsed .nav-link:hover::after { opacity: 1; }

    /* ── SIDEBAR FOOTER ── */
    .sidebar-footer {
        padding: 10px 8px;
        border-top: 1px solid rgba(255,255,255,0.1);
        flex-shrink: 0;
        overflow: hidden;
        transition: opacity 0.2s;
    }
    .sidebar-footer small { font-size: 0.62rem; color: rgba(255,255,255,0.3); white-space: nowrap; }
    .sidebar.collapsed .sidebar-footer { opacity: 0; }

    /* ── TOP HEADER ── */
    .top-header {
        height: var(--header-height);
        background: rgba(18, 9, 6, 0.65);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        position: fixed;
        top: 0; right: 0;
        left: var(--sidebar-width);
        z-index: 1000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        color: var(--text-primary);
        box-shadow: 0 1px 0 rgba(255,225,145,0.06), 0 2px 18px rgba(0,0,0,0.35);
        transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .top-header.mini { left: var(--sidebar-mini); }
    .top-header.guest { left: 0; justify-content: flex-start; }

    .header-left { display: flex; align-items: center; gap: 12px; }
    .header-right { display: flex; align-items: center; gap: 10px; }
    .header-right .dropdown-toggle,
    .header-right .dropdown-toggle * { color: #ffffff !important; }

    .header-page-title { font-size: 0.95rem; font-weight: 700; color: #ffffff; letter-spacing: -0.2px; }
    .header-page-sub { font-size: 0.72rem; color: rgba(255,255,255,0.9); margin-top: 1px; }

    /* Guest header */
    .guest-header { display: flex; align-items: center; gap: 12px; }
    .guest-header-icon-wrap {
        width: 36px; height: 36px; background: var(--maroon);
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
    }
    .guest-header-icon-wrap i { color: white; font-size: 1.1rem; }
    .guest-header-text { font-weight: 700; font-size: 0.9rem; color: #f8e7d3; text-transform: uppercase; letter-spacing: 0.3px; }

    /* ── BURGER ── */
    .burger-btn {
        width: 40px; height: 40px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.12);
        background: rgba(255,255,255,0.09);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; flex-shrink: 0;
        transition: all 0.2s ease;
    }
    .burger-btn:hover { background: rgba(255,255,255,0.16); border-color: rgba(255,255,255,0.18); }
    .burger-btn:hover .burger-line { background: white; }

    .burger-lines {
        width: 18px; height: 14px;
        display: flex; flex-direction: column;
        justify-content: space-between;
    }
    .burger-line {
        display: block; height: 2px; width: 100%;
        background: rgba(255,255,255,0.8); border-radius: 99px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: center;
    }
    /* Collapsed = X icon */
    .burger-btn.open .burger-line:nth-child(1) { transform: translateY(6px) rotate(45deg); background: var(--maroon); }
    .burger-btn.open .burger-line:nth-child(2) { opacity: 0; transform: scaleX(0); }
    .burger-btn.open .burger-line:nth-child(3) { transform: translateY(-6px) rotate(-45deg); background: var(--maroon); }

    /* Notif */
    .notif-btn {
        color: #f8e7d3; background: rgba(255,255,255,0.08);
        width: 40px; height: 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 10px; border: 1px solid rgba(255,255,255,0.12);
        transition: all 0.2s; cursor: pointer;
    }
    .notif-btn:hover { background: rgba(255,255,255,0.16); color: white; border-color: rgba(255,255,255,0.18); transform: scale(1.05); }

    .header-profile-img {
        width: 38px; height: 38px; border-radius: 50%;
        border: 2px solid rgba(255,225,170,0.9); object-fit: cover; transition: all 0.2s;
    }
    .header-profile-img:hover { border-color: var(--maroon); transform: scale(1.08); }
    .dropdown-toggle::after { display: none !important; }

    .fb-dropdown {
        width: 300px !important; border-radius: 14px !important;
        border: 1px solid rgba(207,164,111,0.24) !important;
        box-shadow: 0 20px 60px rgba(0,0,0,0.25) !important;
        padding: 10px !important;
        background: rgba(17, 9, 6, 0.96) !important;
    }
    .fb-profile-header {
        display: flex; align-items: center; gap: 12px;
        padding: 10px; border-radius: 10px;
        text-decoration: none; color: #f3e7cd !important; transition: background 0.15s;
    }
    .fb-profile-header:hover { background: rgba(255,235,190,0.08); }
    .fb-icon-circle {
        width: 36px; height: 36px; background: #43332a;
        border-radius: 50%; display: flex; align-items: center;
        justify-content: center; font-size: 1rem; color: #e7d7b4;
    }
    .fb-dropdown-item {
        display: flex; align-items: center; gap: 12px;
        padding: 10px; border-radius: 10px;
        color: #f3e7cd !important; font-weight: 500; font-size: 0.875rem;
        text-decoration: none; width: 100%; border: none; background: none;
        transition: background 0.15s;
    }
    .fb-dropdown-item:hover { background: rgba(255,235,190,0.08); }

    /* ── MAIN LAYOUT ── */
    .main-wrapper { 
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .main-content {
        flex: 1; display: flex; flex-direction: column;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        padding-top: var(--header-height);
        padding-left: var(--sidebar-width);
        transition: padding-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-x: visible;
        overflow-y: auto;
    }
    .main-content > div {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        box-sizing: border-box;
        display: flex;
        flex-direction: column;
    }
    .main-content .p-4 {
        width: 100%;
        min-width: 0;
        max-width: 100% !important;
        box-sizing: border-box;
        margin: 0 !important;
        padding: 1.5rem !important;
    }
    .main-content .container,
    .main-content .container-fluid {
        width: 100%;
        max-width: 100% !important;
    }
    .main-content > * {
        max-width: 100% !important;
    }
    .main-content.mini { padding-left: var(--sidebar-mini); }
    .main-content.guest-mode { padding-left: 0; }

    .auth-container {
        flex: 1; display: flex; align-items: center; justify-content: center;
        padding: 20px;
        background: linear-gradient(135deg, #1d110b 0%, #140a06 50%, #100704 100%);
        min-height: calc(100vh - var(--header-height));
    }

    /* ── DASHBOARD CARD STYLES ── */
    .tch-stats, .adm-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(180px, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }
    .tch-stat, .adm-stat {
        background: rgba(255,235,190,0.05);
        border: 1px solid rgba(255,215,145,0.12);
        border-radius: 22px;
        padding: 22px;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02);
        min-height: 130px;
    }
    .tch-stat-val, .adm-stat-val {
        font-size: 2rem;
        font-weight: 700;
        color: #f3e7cd;
        margin-bottom: 8px;
    }
    .tch-stat-lbl, .adm-stat-lbl {
        color: #b39b82;
        font-size: 0.82rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }
    .tch-card, .adm-card {
        background: rgba(255,235,190,0.04);
        border: 1px solid rgba(255,215,145,0.1);
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(0,0,0,0.18);
        margin-bottom: 24px;
    }
    .tch-card-head, .adm-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 22px;
        border-bottom: 1px solid rgba(255,215,145,0.08);
    }
    .tch-card-title, .adm-card-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 1rem;
        font-weight: 700;
        color: #f3e7cd;
    }
    .tch-card-icon, .adm-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: rgba(255,235,190,0.12);
        color: #cfa46f;
    }
    .tch-card-body, .adm-card-body { padding: 18px 22px; color: #e7dcc8; }
    .tch-btn, .adm-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 10px 16px;
        border-radius: 12px;
        border: 1px solid rgba(255,215,145,0.16);
        background: rgba(117,69,53,0.9);
        color: #f3e7cd;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .tch-btn:hover, .adm-btn:hover { background: rgba(135,87,58,0.95); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(117,69,53,0.3); }
    .adm-btn-primary { background: #7f432e; color: #f3e7cd; }
    .adm-btn-ghost { background: transparent; color: #b99b77; border-color: rgba(255,215,145,0.18); }
    .adm-table {
        width: 100%;
        border-collapse: collapse;
        color: #f5e6d3;
        table-layout: auto;
    }
    .adm-table thead th {
        color: #ffd98f;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,215,145,0.24);
        text-align: left;
        font-weight: 700;
        font-size: 0.82rem;
    }
    .adm-table tbody tr {
        background: rgba(255,235,190,0.05);
        border: 1px solid rgba(255,215,145,0.12);
        border-radius: 4px;
        display: table-row;
    }
    .adm-table tbody td {
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,215,145,0.1);
        color: #f5e6d3;
    }
    .adm-table tbody td:last-child { border-bottom: none; }
    .view-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid rgba(255,215,145,0.14);
        background: rgba(255,235,190,0.08);
        color: #f3e7cd;
        text-decoration: none;
        transition: background 0.18s ease;
    }
    .view-btn:hover { background: rgba(255,215,145,0.12); }
    .empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 12px;
        padding: 34px;
        color: #b39b82;
    }
    .empty-state i { font-size: 2rem; color: #7f5c45; }
    .badge-present { background: rgba(207,164,111,0.14); color: #d6b67b; padding: 4px 10px; border-radius: 999px; }
    .badge-late { background: rgba(207,164,111,0.1); color: #d4ab62; padding: 4px 10px; border-radius: 999px; }
    .badge-absent { background: rgba(188,120,90,0.14); color: #e7c4b6; padding: 4px 10px; border-radius: 999px; }
    .badge-course, .badge-year, .badge-yearsem { background: rgba(207,164,111,0.12); color: #d4b16e; padding: 3px 8px; border-radius: 999px; font-size: 0.78rem; }

    /* Student detail overrides (used by admin student view) */
    .student-back-link {
        display:inline-flex;align-items:center;gap:7px;font-size:.85rem;font-weight:600;color:#f5e6d3;text-decoration:none;padding:8px 14px;border:1.5px solid rgba(255,215,145,0.12);border-radius:9px;background:transparent;margin-bottom:20px;transition:all .2s;
    }
    .student-back-link:hover { color: #800000; border-color: #800000; }

    .student-name { font-size:1.2rem;font-weight:800;color:#f3e7cd; }
    .student-email { font-size:.82rem;color:#e7dcc8;margin-top:2px; }

    .student-stat-value { font-size:1.4rem;font-weight:800;color:#f3e7cd; }
    .student-stat-label { font-size:.68rem;font-weight:600;color:#b39b82;text-transform:uppercase; }

    .attendance-date { font-weight:600;color:#f3e7cd;font-size:.85rem; }
    .attendance-day { font-size:.72rem;color:#b39b82; }
    .attendance-subject { font-weight:600;color:#f3e7cd; }
    .attendance-time { color:#e7dcc8; }

    .modal-title-light { font-weight:700;color:#f3e7cd; }
    .modal-label-light { font-weight:600;color:#e7dcc8; }

    /* ── GLOBAL MOBILE RESPONSIVE ── */
    @media (max-width: 768px) {
        /* Header adjustments */
        .header-page-title { font-size: .82rem; }
        .header-page-sub { display: none; }
        .top-header { height: 56px; }
        --header-height: 56px;

        /* Content padding — extra bottom for bottom nav */
        .main-content > .p-4 { padding: 12px 12px 88px !important; }
        .container-fluid { padding-left: 12px !important; padding-right: 12px !important; }

        /* Cards stack properly */
        .row.g-3 > [class*="col-md"] { margin-bottom: 0; }

        /* Tables scroll horizontally */
        .table-responsive, [style*="overflow-x:auto"] { overflow-x: auto; -webkit-overflow-scrolling: touch; }

        /* Stat cards 2 per row */
        .stat-card { padding: 14px 16px; }
        .stat-value, .adm-stat-val { font-size: 1.4rem !important; }

        /* Hero banner */
        .hero-banner { padding: 20px !important; }
        .hero-title { font-size: 1.1rem !important; }
        .hero-sub { font-size: .78rem !important; }
        .hero-time { font-size: .75rem !important; }
        .hero-icon { display: none; }

        /* Bottom row stack */
        .col-lg-4, .col-lg-8 { width: 100% !important; }

        /* Notification dropdown */
        .fb-dropdown { width: 280px !important; }
        .dropdown-menu[style*="width:340px"] { width: 280px !important; }

        /* Profile meta */
        .profile-meta { padding-left: 0 !important; padding-top: 70px !important; }
        .avatar-wrap { left: 50% !important; transform: translateX(-50%) !important; }
        .profile-meta .d-flex { justify-content: center; }
        .profile-name { text-align: center; }

        /* Settings tabs scroll */
        .stabs { overflow-x: auto; flex-wrap: nowrap; }
        .stab { white-space: nowrap; }

        /* Admin stats grid */
        .adm-stats { grid-template-columns: repeat(2, 1fr) !important; }

        /* Admin table */
        .adm-table thead th { font-size: .65rem; padding: 8px 10px; }
        .adm-table tbody td { font-size: .8rem; padding: 10px 10px; }

        /* Buttons */
        .adm-btn { padding: 8px 14px; font-size: .8rem; }
    }

    @media (max-width: 480px) {
        .adm-stats { grid-template-columns: repeat(2, 1fr) !important; }
        .hero-title { font-size: 1rem !important; }
        .stat-value { font-size: 1.2rem !important; }
    }

    /* ── MOBILE: sidebar slides off-screen, content full width ── */
    @media (max-width: 768px) {
        .sidebar { transform: translateX(-100%); z-index: 1200; }
        .sidebar.open { transform: translateX(0); }
        .top-header { left: 0 !important; }
        .top-header.mini { left: 0 !important; }
        .main-content { padding-left: 0 !important; }
        .main-content.mini { padding-left: 0 !important; }
        .header-profile-img { width: 32px; height: 32px; }
        .fb-dropdown { width: 260px !important; }
        .dropdown-menu { max-width: 300px !important; }
        .sidebar-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1150;
            opacity: 0; visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s;
        }
        .sidebar-overlay.show { opacity: 1; visibility: visible; }

        /* Content pages — leave space for bottom nav */
        .main-content > .p-4 { padding: 14px 14px 88px !important; }

        /* Attendance records table → cards on mobile */
        .att-table { display: none !important; }
        .attendance-cards { display: block !important; }

        /* Settings */
        .stabs { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
        .stab { white-space: nowrap; flex-shrink: 0; }
        .stat-grid { grid-template-columns: repeat(2,1fr) !important; }

        /* Classes page */
        .class-grid { grid-template-columns: 1fr !important; }

        /* Profile page */
        .profile-cover { height: 120px !important; }
        .profile-meta { padding-left: 0 !important; padding-top: 60px !important; text-align: center; }
        .avatar-wrap { left: 50% !important; transform: translateX(-50%) !important; }
        .profile-name { text-align: center !important; }
        .profile-badges { justify-content: center !important; }

        /* Home page */
        .hero-banner { padding: 20px !important; border-radius: 14px !important; }
        .hero-title { font-size: 1.2rem !important; }
        .hero-icon { display: none !important; }
        .col-lg-4, .col-lg-8 { width: 100% !important; }
        .stat-card { padding: 16px !important; }
        .stat-value { font-size: 1.6rem !important; }

        /* Notification dropdown */
        .dropdown-menu[style*="width:340px"] { width: 280px !important; right: -60px !important; }
    }

    @media (max-width: 480px) {
        .main-content > .p-4 { padding: 10px 10px 88px !important; }
        .hero-title { font-size: 1rem !important; }
        .stat-value { font-size: 1.3rem !important; }
        .stat-card { padding: 12px !important; }

        /* Role links and small button utilities */
        .role-link { display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border-radius:9px;font-size:.8rem;font-weight:700;text-decoration:none;transition:all .15s;color:white; }
        .role-admin { background:#800000; }
        .role-link {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.65rem 0.9rem;
            border-radius: 10px;
            font-weight: 600;
            color: #ffffff;
            background: #5d2f21;
            text-decoration: none;
            transition: all 0.15s ease;
        }
        .role-link:hover {
            background: #7f432e;
            color: #ffffff;
            text-decoration: none;
        }
        .role-admin { background: #5d2f21; }
        .role-admin:hover { background: #7f432e; }
        .role-teacher { background: #5d2f21; }
        .role-teacher:hover { background: #7f432e; }

        .mark-all-read-btn { font-size:.75rem;font-weight:600;color:#800000;background:none;border:none;cursor:pointer;padding:0; }
        .mark-all-read-btn:hover { text-decoration:underline; }

        .notif-archive-btn { width:26px;height:26px;border-radius:6px;border:none;background:transparent;color:#e7dcc8;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.85rem;transition:all .15s;padding:0; }
        .notif-archive-btn:hover { background:#4d3225; color:#f7e1b5; }

        /* ── FORM INPUTS & BUTTONS ALIGNMENT ── */
        .adm-input, .tch-input {
            display: inline-block;
            padding: 10px 14px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-size: 0.875rem;
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #5c001d;
            transition: all 0.2s;
            outline: none;
            min-height: 36px;
            box-sizing: border-box;
        }
        .tch-input {
            background: rgba(255,235,190,0.08);
            border-color: rgba(255,215,145,0.18);
            color: #f3e7cd;
        }
        .adm-input:hover { border-color: #cbd5e1; background: white; }
        .adm-input:focus { border-color: #5c001d; background: white; box-shadow: 0 0 0 3px rgba(128,0,0,0.08); }
        .tch-input:hover { border-color: rgba(255,215,145,0.28); background: rgba(255,235,190,0.12); }
        .tch-input:focus { border-color: #d6b67b; background: rgba(255,235,190,0.15); box-shadow: 0 0 0 3px rgba(207,164,111,0.12); }

        /* Ensure buttons match input height */
        .adm-btn, .tch-btn {
            min-height: 36px;
            box-sizing: border-box;
        }

        /* Form wrapper to align items properly */
        form[style*="flex-wrap"] > div[style*="flex-direction"] {
            display: flex !important;
            flex-direction: column !important;
        }

        form[style*="flex-wrap"] > div[style*="flex-direction"] > label {
            margin-bottom: 6px !important;
        }
    }

    /* ═══════════════════════════════════════════════════════════
       MOBILE BOTTOM NAVIGATION BAR
       Native app-style tab bar — hidden on desktop, shown on mobile
    ═══════════════════════════════════════════════════════════ */
    .mobile-bottom-nav {
        display: none; /* hidden on desktop */
    }
    @media (max-width: 768px) {
        .mobile-bottom-nav {
            display: flex;
            position: fixed;
            bottom: 0; left: 0; right: 0;
            height: 68px;
            background: rgba(15, 8, 5, 0.96);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-top: 1px solid rgba(255, 225, 145, 0.12);
            z-index: 1300;
            padding: 0;
            align-items: stretch;
            box-shadow: 0 -8px 32px rgba(0,0,0,0.5);
        }
        .mobile-bottom-nav .mbn-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            text-decoration: none;
            color: rgba(255,255,255,0.45);
            font-size: 0.6rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            transition: all 0.2s ease;
            padding: 8px 4px;
            position: relative;
            border: none;
            background: none;
            cursor: pointer;
        }
        .mobile-bottom-nav .mbn-item:active {
            transform: scale(0.92);
        }
        .mobile-bottom-nav .mbn-item i {
            font-size: 1.25rem;
            transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.2s;
        }
        .mobile-bottom-nav .mbn-item.active {
            color: #cfa46f;
        }
        .mobile-bottom-nav .mbn-item.active i {
            transform: scale(1.12);
            color: #cfa46f;
        }
        .mobile-bottom-nav .mbn-item.active::before {
            content: '';
            position: absolute;
            top: 0; left: 20%; right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #cfa46f, transparent);
            border-radius: 0 0 4px 4px;
        }
        .mobile-bottom-nav .mbn-badge {
            position: absolute;
            top: 6px;
            right: calc(50% - 18px);
            width: 16px; height: 16px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            font-size: 0.55rem;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            border: 2px solid rgba(15,8,5,0.96);
        }

        /* Mobile-specific dashboard grid utility */
        .mobile-stat-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 10px !important;
        }
        .mobile-stat-card {
            background: rgba(255,235,190,0.05);
            border: 1px solid rgba(255,215,145,0.12);
            border-radius: 16px;
            padding: 14px;
        }
        .mobile-stat-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            background: rgba(207,164,111,0.15);
            color: #cfa46f;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }
        .mobile-stat-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #8f826f;
            margin-bottom: 4px;
        }
        .mobile-stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            line-height: 1;
            color: #f3e7cd;
        }
        .mobile-stat-diff {
            font-size: 0.72rem;
            margin-top: 5px;
            font-weight: 600;
        }

        /* Mobile dashboard header strip */
        .mobile-dash-header {
            background: linear-gradient(135deg, rgba(207,164,111,0.1) 0%, rgba(128,0,0,0.05) 100%);
            border: 1px solid rgba(207,164,111,0.15);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .mobile-dash-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #f3e7cd;
            line-height: 1.2;
        }
        .mobile-dash-subtitle {
            font-size: 0.72rem;
            color: #8f826f;
            margin-top: 3px;
        }
        .mobile-dash-date {
            text-align: right;
            font-size: 0.7rem;
            color: #b39b82;
            font-weight: 600;
        }

        /* Hide the heavy desktop-only dashboard headers */
        .desktop-dash-header { display: none !important; }

        /* Make all 2-col desktop grids stack properly */
        [style*="grid-template-columns:1fr 1fr"],
        [style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }

        /* Fix auto-fit grids on mobile */
        [style*="minmax(200px"] {
            grid-template-columns: repeat(2, 1fr) !important;
        }

        /* Quick action bars on mobile */
        .mobile-quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .mobile-quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 14px 10px;
            border-radius: 14px;
            background: rgba(255,235,190,0.05);
            border: 1px solid rgba(255,215,145,0.12);
            color: #f3e7cd;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s ease;
            text-align: center;
            line-height: 1.2;
        }
        .mobile-quick-action-btn i { font-size: 1.3rem; color: #cfa46f; }
        .mobile-quick-action-btn:active { transform: scale(0.96); }

        /* Table card mobile */
        .mobile-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
        }
    }
    </style>
    @stack('styles')
</head>

<body>

    @auth
    <!-- Mobile overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <div class="d-md-none" style="padding: 16px 16px 0 16px; display: flex; align-items: center;">
            <button onclick="closeSidebar()" class="btn btn-sm" style="color: #cfa46f; background: rgba(207,164,111,0.1); border: 1px solid rgba(207,164,111,0.2); border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="bi bi-chevron-left"></i> Back
            </button>
        </div>
        @yield('role-sidebar')
    </aside>
    @endauth

    <div class="main-wrapper">
        <header class="top-header {{ Auth::check() ? '' : 'guest' }}" id="topHeader">
            @auth
                <div class="header-left">
                    <!-- Burger -->
                    <button class="burger-btn" id="burgerBtn" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                        <i class="bi bi-layout-sidebar-inset"></i>
                    </button>
                    <div>
                        <div class="header-page-title">
                            @if(request()->routeIs('home')) Dashboard
                            @elseif(request()->routeIs('student.classes')) My Classes
                            @elseif(request()->routeIs('settings')) Settings
                            @elseif(request()->routeIs('teacher.*')) Teacher Portal
                            @elseif(request()->routeIs('admin.*')) Admin Portal
                            @else Portal
                            @endif
                        </div>
                        <div class="header-page-sub">{{ now()->format('l, F j, Y') }}</div>
                    </div>
                </div>

                <div class="header-right">
                    @php $unreadCount = \App\Models\Notification::where('user_id', Auth::id())->where('is_read', false)->count(); @endphp
                    <div class="dropdown">
                        <div class="notif-btn position-relative" data-bs-toggle="dropdown" style="cursor:pointer;">
                            <i class="bi bi-bell-fill" style="font-size:0.95rem;"></i>
                            @if($unreadCount > 0)
                            <span style="position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                            @endif
                        </div>
                        <div class="dropdown-menu dropdown-menu-end mt-2" style="width:340px;border-radius:14px;border:1px solid rgba(207,164,111,0.24);box-shadow:0 20px 60px rgba(0,0,0,0.28);padding:0;overflow:hidden;background:rgba(18,11,8,0.97);">
                            <div style="padding:14px 18px;border-bottom:1px solid rgba(255,215,145,0.12);display:flex;align-items:center;justify-content:space-between;">
                                <span style="font-size:.9rem;font-weight:700;color:#f3e7cd;">Notifications</span>
                                @if($unreadCount > 0)
                                <button onclick="markAllRead()" class="mark-all-read-btn">Mark all read</button>
                                @endif
                            </div>
                            @php
                                $notifications = \App\Models\Notification::where('user_id', Auth::id())
                                    ->active()
                                    ->orderBy('created_at','desc')->take(8)->get();
                            @endphp
                            <div style="max-height:320px;overflow-y:auto;">
                                @forelse($notifications as $notif)
                                <div id="notif-{{ $notif->id }}" style="padding:13px 18px;border-bottom:1px solid rgba(255,215,145,0.12);background:{{ $notif->is_read ? '#19100c' : '#2d1b13' }};transition:background .15s;">
                                    <div style="display:flex;gap:10px;align-items:flex-start;">
                                        <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;
                                            {{ $notif->type === 'warning_3' ? 'background:#3c1815;color:#dc2626;' : ($notif->type === 'custom' ? 'background:#3f2e20;color:#d8b06f;' : 'background:#36261c;color:#d97706;') }}">
                                            <i class="bi {{ $notif->type === 'warning_3' ? 'bi-exclamation-octagon-fill' : ($notif->type === 'custom' ? 'bi-info-circle-fill' : 'bi-exclamation-triangle-fill') }}" style="color:inherit;"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:.82rem;color:#e7dcc8;line-height:1.4;">{{ $notif->message }}</div>
                                            <div style="font-size:.72rem;color:#b39b82;margin-top:4px;">{{ $notif->created_at->diffForHumans() }}</div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                            @if(!$notif->is_read)
                                            <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
                                            @endif
                                            <button onclick="archiveNotif({{ $notif->id }}, this)" title="Archive" class="notif-archive-btn">
                                                <i class="bi bi-archive-fill"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @empty
                                <div style="text-align:center;padding:32px 20px;color:#b39b82;">
                                    <i class="bi bi-bell-slash" style="font-size:1.8rem;display:block;margin-bottom:8px;opacity:.3;color:#b39b82;"></i>
                                    <span style="font-size:.85rem;">No notifications yet</span>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="dropdown">
                        <a href="#" data-bs-toggle="dropdown" class="text-decoration-none d-flex align-items-center gap-2">
                            <img src="{{ Auth::user()->profile_image ? (str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&background=800000&color=fff&size=200' }}" class="header-profile-img">
                            <div class="d-none d-md-block text-start" style="line-height:1.2;">
                                <div style="font-size:0.8rem;font-weight:600;color:#ffffff;">{{ Auth::user()->name }}</div>
                            </div>
                            <i class="bi bi-chevron-down d-none d-md-block" style="font-size:0.7rem;color:rgba(255,255,255,0.75);"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end fb-dropdown mt-2">
                            <!-- Profile summary (non-clickable, just info) -->
                            <div class="fb-profile-header" style="cursor:default;">
                                <img src="{{ Auth::user()->profile_image ? (str_starts_with(Auth::user()->profile_image, 'http') ? Auth::user()->profile_image : asset('storage/'.Auth::user()->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode(Auth::user()->name).'&background=800000&color=fff&size=200' }}" style="width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,225,170,0.9);">
                                <div>
                                    <div class="fw-bold" style="font-size:0.9rem;">{{ Auth::user()->name }}</div>
                                    <div style="font-size:0.75rem;color:#b39b82;">
                                        @hasSection('profile-detail')
                                            @yield('profile-detail')
                                        @else
                                            @if(Auth::user()->isAdmin() || Auth::user()->isTeacher())
                                                {{ Auth::user()->email }}
                                            @else
                                                {{ Auth::user()->student_number }}
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <hr class="my-2" style="border-color:rgba(255,215,145,0.18);">
                            <a class="fb-dropdown-item" href="{{ route('settings') }}">
                                <div class="fb-icon-circle"><i class="bi bi-gear-fill"></i></div>
                                <span>Settings</span>
                            </a>
                            <form action="{{ route('logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="fb-dropdown-item" style="color:#dc2626 !important;">
                                    <div class="fb-icon-circle" style="background:#3a1d18;color:#dc2626;"><i class="bi bi-box-arrow-right"></i></div>
                                    <span>Log Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <div class="guest-header">
                    <div class="guest-header-icon-wrap"><i class="bi bi-laptop"></i></div>
                    <span class="guest-header-text">{{ config('app.name') }}</span>
                </div>
            @endauth
        </header>

        <main class="main-content {{ Auth::check() ? '' : 'guest-mode' }}" id="mainContent">
            <div class="{{ Auth::check() ? 'p-4' : '' }}">
                @yield('content')
            </div>
        </main>
    </div>

    @auth
    {{-- ═══ MOBILE BOTTOM NAVIGATION BAR ═══ --}}
    <nav class="mobile-bottom-nav" id="mobileBottomNav">
        @yield('mobile-bottom-nav')
    </nav>
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    @auth
    <script>
        const sidebar     = document.getElementById('sidebar');
        const burgerBtn   = document.getElementById('burgerBtn');
        const topHeader   = document.getElementById('topHeader');
        const mainContent = document.getElementById('mainContent');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Check if mobile
        const isMobile = window.innerWidth <= 768;

        // Restore saved state (only for desktop)
        if (!isMobile && localStorage.getItem('sidebarMini') === 'true') applyMini(true, false);

        function toggleSidebar() {
            if (isMobile) {
                const isOpen = sidebar.classList.contains('open');
                if (isOpen) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            } else {
                const isMini = sidebar.classList.contains('collapsed');
                applyMini(!isMini, true);
                localStorage.setItem('sidebarMini', !isMini);
            }
        }

        function openSidebar() {
            sidebar.classList.add('open');
            sidebarOverlay.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSidebar() {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }

        function applyMini(mini, animate) {
            if (!animate) {
                const noTrans = 'transition:none!important';
                sidebar.style.cssText     += noTrans;
                topHeader.style.cssText   += noTrans;
                mainContent.style.cssText += noTrans;
            }

            if (mini) {
                sidebar.classList.add('collapsed');
                topHeader.classList.add('mini');
                mainContent.classList.add('mini');
                burgerBtn.classList.add('open');
            } else {
                sidebar.classList.remove('collapsed');
                topHeader.classList.remove('mini');
                mainContent.classList.remove('mini');
                burgerBtn.classList.remove('open');
            }

            if (!animate) {
                requestAnimationFrame(() => {
                    sidebar.style.cssText     = '';
                    topHeader.style.cssText   = '';
                    mainContent.style.cssText = '';
                });
            }
        }

        function markAllRead() {
            fetch('{{ route("notifications.read") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).then(() => location.reload());
        }

        function archiveNotif(id, btn) {
            const row = document.getElementById('notif-' + id);
            // Animate out
            row.style.transition = 'opacity .2s, transform .2s';
            row.style.opacity = '0';
            row.style.transform = 'translateX(10px)';
            setTimeout(() => {
                fetch(`/notifications/${id}/archive`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                }).then(r => r.json()).then(data => {
                    if (data.success) {
                        row.remove();
                        // Update badge count
                        const badge = document.querySelector('.notif-btn span');
                        if (badge) {
                            const current = parseInt(badge.textContent) || 0;
                            if (current <= 1) badge.remove();
                            else badge.textContent = current - 1;
                        }
                    }
                });
            }, 200);
        }

        // ── WEBSOCKET: Real-time notifications + teacher dashboard updates via Laravel Echo + Reverb ──
        (function() {
            // Dynamically load Laravel Echo + Pusher-js (Reverb uses Pusher protocol)
            const script1 = document.createElement('script');
            script1.src = 'https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js';
            script1.onload = function() {
                const script2 = document.createElement('script');
                script2.src = 'https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js';
                script2.onload = function() {
                    window.teacherEcho = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env("REVERB_APP_KEY") }}',
                        wsHost: '{{ env("REVERB_HOST", "localhost") }}',
                        wsPort: {{ env("REVERB_PORT", 8080) }},
                        wssPort: {{ env("REVERB_PORT", 8080) }},
                        forceTLS: false,
                        enabledTransports: ['ws'],
                    });

                    @if(!Auth::user()->isAdmin() && !Auth::user()->isTeacher())
                    // Listen on private channel for real-time notifications (students only)
                    window.teacherEcho.private('notifications.{{ Auth::id() }}')
                        .listen('.notification.sent', (e) => {
                            // Bump badge count
                            const notifBtn = document.querySelector('.notif-btn');
                            let badge = notifBtn.querySelector('span');
                            if (badge) {
                                const count = parseInt(badge.textContent) || 0;
                                badge.textContent = count + 1 > 9 ? '9+' : count + 1;
                            } else {
                                badge = document.createElement('span');
                                badge.style.cssText = 'position:absolute;top:-4px;right:-4px;width:18px;height:18px;background:#dc2626;color:white;border-radius:50%;font-size:.65rem;font-weight:700;display:flex;align-items:center;justify-content:center;border:2px solid white;';
                                badge.textContent = '1';
                                notifBtn.appendChild(badge);
                            }

                            // Add notification to dropdown list
                            const list = document.querySelector('.dropdown-menu .overflow-auto, .dropdown-menu [style*="max-height"]');
                            if (list) {
                                const iconMap = { warning_3: 'bi-exclamation-octagon-fill', warning_2: 'bi-exclamation-triangle-fill', custom: 'bi-info-circle-fill' };
                                const colorMap = { warning_3: 'background:#3c1815;color:#dc2626;', warning_2: 'background:#2d1b13;color:#d97706;', custom: 'background:#3f2e20;color:#d8b06f;' };
                                const id = Date.now();
                                const div = document.createElement('div');
                                div.id = 'notif-' + id;
                                div.style.cssText = 'padding:13px 18px;border-bottom:1px solid rgba(255,215,145,0.12);background:#2d1b13;';
                                div.innerHTML = `
                                    <div style="display:flex;gap:10px;align-items:flex-start;">
                                        <div style="width:34px;height:34px;border-radius:9px;flex-shrink:0;display:flex;align-items:center;justify-content:center;${colorMap[e.type]||colorMap.custom}">
                                            <i class="bi ${iconMap[e.type]||'bi-bell-fill'}"></i>
                                        </div>
                                        <div style="flex:1;min-width:0;">
                                            <div style="font-size:.82rem;color:#e7dcc8;line-height:1.4;">${e.message}</div>
                                            <div style="font-size:.72rem;color:#b39b82;margin-top:4px;">Just now</div>
                                        </div>
                                        <div style="display:flex;align-items:center;gap:4px;flex-shrink:0;">
                                            <div style="width:8px;height:8px;border-radius:50%;background:#f59e0b;"></div>
                                        </div>
                                    </div>`;
                                list.prepend(div);
                            }
                        });

                    // Listen on private channel for LiveNotification (parents and students)
                    window.teacherEcho.private('user.{{ Auth::id() }}')
                        .listen('.LiveNotification', (e) => {
                            let bgColor = "#3f2e20"; // default info/custom
                            if (e.type === 'success') bgColor = "#103c15";
                            else if (e.type === 'warning') bgColor = "#2d1b13";
                            else if (e.type === 'error') bgColor = "#3c1815";
                            
                            Toastify({
                                text: e.title + "\\n" + e.message,
                                duration: 5000,
                                close: true,
                                gravity: "top",
                                position: "right",
                                style: {
                                    background: bgColor,
                                    color: "#f2e8d5",
                                    border: "1px solid rgba(255,215,145,0.2)",
                                    borderRadius: "8px",
                                    padding: "12px 16px",
                                    boxShadow: "0 10px 30px rgba(0,0,0,0.3)"
                                }
                            }).showToast();
                        });
                    @endif
                    // Echo is now available globally (window.teacherEcho) for all authenticated users.
                    // Teacher QR pages use it to subscribe to private teacher-dashboard channels.
                };
                document.head.appendChild(script2);
            };
            document.head.appendChild(script1);
            
            // Add Toastify JS
            const toastifyScript = document.createElement('script');
            toastifyScript.src = "https://cdn.jsdelivr.net/npm/toastify-js";
            document.head.appendChild(toastifyScript);
        })();
    </script>
    @endauth
    @yield('scripts')
</body>
</html>
