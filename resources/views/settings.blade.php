@php
    $layout = (Auth::check() && Auth::user()->isAdmin()) ? 'admin.layout' : 'layouts.app';
@endphp
@extends($layout)

@section('content')
@php
    $user = Auth::user();
    if ($user) {
        $user->loadMissing('deviceBinding');
    }
    $deviceBinding = $user?->deviceBinding;
    $allRecords   = $user->attendances ?? collect();
    $totalRecords = $allRecords->count();
    $totalPresent = $allRecords->where('status','Present')->count();
    $totalLate    = $allRecords->where('status','Late')->count();
    $totalAbsent  = $allRecords->where('status','Absent')->count();
    $rate = $totalRecords > 0 ? round((($totalPresent+$totalLate)/$totalRecords)*100) : 0;
@endphp

<style>
:root {
    --gold-primary: #cfa46f;
    --gold-bright: #f5dfa8;
    --gold-dark: #9a733e;
    --gold-glow: rgba(207, 164, 111, 0.28);
    --gold-subtle: rgba(207, 164, 111, 0.12);
    --gold-border: rgba(207, 164, 111, 0.18);
    --surface-dark: #120e0b;
    --surface-card: rgba(24, 18, 14, 0.88);
    --surface-card-hover: rgba(32, 23, 18, 0.95);
    --surface-input: rgba(14, 11, 9, 0.75);
    --text-pure: #fcfbf9;
    --text-primary: #f3e7cd;
    --text-muted: #b39b82;
    --text-dim: #7d6e5d;
    --emerald-primary: #22c55e;
    --emerald-glow: rgba(34, 197, 94, 0.25);
    --cyan-primary: #06b6d4;
    --amber-primary: #f59e0b;
    --rose-primary: #ef4444;
}

.sp {
    max-width: 1220px;
    margin: 0 auto;
    padding-bottom: 40px;
}

/* ── Executive Command Header & Top Navbar ── */
.settings-top-navbar,
.settings-command-header {
    background: linear-gradient(135deg, rgba(32, 23, 17, 0.94) 0%, rgba(18, 13, 10, 0.98) 100%);
    border: 1px solid rgba(207, 164, 111, 0.22);
    border-radius: 20px;
    padding: 20px 26px;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
    position: relative;
    z-index: 100;
    overflow: visible;
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}
.settings-top-navbar:focus-within,
.settings-command-header:focus-within,
.settings-top-navbar.search-active,
.settings-command-header.search-active {
    z-index: 1000 !important;
}
.settings-top-navbar::after,
.settings-command-header::after {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 320px;
    height: 100%;
    background: radial-gradient(circle at 80% 30%, rgba(207, 164, 111, 0.12) 0%, transparent 70%);
    pointer-events: none;
    border-radius: 20px;
}
.settings-top-left,
.settings-command-left {
    display: flex;
    align-items: center;
    gap: 18px;
    min-width: 0;
    flex: 1 1 360px;
}
.settings-avatar-chip {
    position: relative;
    width: 54px;
    height: 54px;
    border-radius: 16px;
    padding: 2.5px;
    background: linear-gradient(135deg, #f5dfa8 0%, #cfa46f 50%, #754535 100%);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.5), 0 0 16px rgba(207, 164, 111, 0.3);
    flex-shrink: 0;
}
.settings-chip-avatar {
    width: 100%;
    height: 100%;
    border-radius: 14px;
    object-fit: cover;
    display: block;
    background: #140e0b;
}
.settings-chip-status {
    position: absolute;
    bottom: -2px;
    right: -2px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    background: #22c55e;
    border: 2.5px solid #140e0b;
    box-shadow: 0 0 8px #22c55e;
}
.settings-title-group {
    min-width: 0;
    flex: 1;
}
.settings-breadcrumb-bar {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    font-size: 0.74rem;
    font-weight: 700;
    margin-bottom: 4px;
    color: #a89885;
    background: rgba(207, 164, 111, 0.08);
    border: 1px solid rgba(207, 164, 111, 0.16);
    padding: 3px 10px;
    border-radius: 99px;
}
.crumb-root {
    color: #cfa46f;
    display: inline-flex;
    align-items: center;
}
.crumb-sep {
    font-size: 0.65rem;
    color: #7d6e5d;
}
.crumb-cat {
    color: #f3e7cd;
}
.crumb-active {
    color: #ffd700;
    font-weight: 800;
}
.settings-header-badge-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}
.settings-system-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #cfa46f;
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(207, 164, 111, 0.28);
    padding: 3px 10px;
    border-radius: 99px;
}
.settings-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    color: #86efac;
    background: rgba(34, 197, 94, 0.12);
    border: 1px solid rgba(34, 197, 94, 0.28);
    padding: 3px 10px;
    border-radius: 99px;
}
.pulse-beacon {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #22c55e;
    box-shadow: 0 0 8px #22c55e;
    animation: beaconPulse 1.8s infinite ease-in-out;
}
@keyframes beaconPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.5; }
}
.pg-title {
    font-size: 1.55rem;
    font-weight: 800;
    background: linear-gradient(135deg, #ffffff 0%, #fef3c7 45%, #cfa46f 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    letter-spacing: -0.4px;
    line-height: 1.2;
    margin: 0;
}
.pg-sub {
    font-size: 0.82rem;
    color: #b39b82;
    margin-top: 3px;
    line-height: 1.35;
}

/* ── Top Navbar Right & Search Filter ── */
.settings-top-right {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    justify-content: flex-end;
}
.settings-search-box {
    position: relative;
    width: 290px;
    z-index: 1001;
}
.settings-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #cfa46f;
    font-size: 0.92rem;
    pointer-events: none;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    transition: color 0.2s ease, transform 0.2s ease;
}
.settings-search-box:focus-within .settings-search-icon {
    color: #f59e0b;
    transform: translateY(-50%) scale(1.08);
}
.settings-search-input {
    width: 100%;
    min-height: 44px;
    background: rgba(14, 11, 9, 0.8);
    border: 1.5px solid rgba(207, 164, 111, 0.25);
    border-radius: 12px;
    padding: 10px 42px 10px 44px !important;
    color: #fcfbf9;
    font-size: 0.85rem;
    font-weight: 500;
    outline: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.35);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.settings-search-input:hover {
    border-color: rgba(207, 164, 111, 0.42);
    background: rgba(18, 14, 11, 0.9);
}
.settings-search-input:focus {
    border-color: #cfa46f;
    background: rgba(22, 17, 13, 0.98);
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.2), 0 8px 20px rgba(0,0,0,0.5), inset 0 1px 2px rgba(0,0,0,0.2);
}
.settings-search-input::placeholder {
    color: #9e8e7c;
    font-size: 0.82rem;
    font-weight: 400;
}
.settings-search-clear-btn {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    color: #a89885;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 1.05rem;
    line-height: 1;
    z-index: 3;
    border-radius: 50%;
    transition: color 0.18s ease, transform 0.18s ease;
}
.settings-search-clear-btn:hover {
    color: #f59e0b;
    transform: translateY(-50%) scale(1.15);
}
.settings-search-kbd {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    padding: 2px 7px;
    font-size: 0.7rem;
    font-weight: 700;
    font-family: inherit;
    color: #cfa46f;
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(207, 164, 111, 0.3);
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.3);
    pointer-events: none;
    user-select: none;
    line-height: 1.2;
    transition: opacity 0.2s ease, border-color 0.2s ease, color 0.2s ease;
}
.settings-search-box:focus-within .settings-search-kbd {
    border-color: rgba(207, 164, 111, 0.55);
    color: #f59e0b;
}
.settings-search-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    z-index: 950;
    pointer-events: auto;
    transition: opacity 0.2s ease;
}
.settings-search-dropdown {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    right: 0;
    min-width: 320px;
    background: #140d0a !important;
    border: 1.5px solid rgba(207, 164, 111, 0.45) !important;
    border-radius: 14px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.95), 0 0 24px rgba(207, 164, 111, 0.22) !important;
    z-index: 99999 !important;
    padding: 8px;
    max-height: min(340px, calc(100dvh - 250px));
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}
.search-item-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.18s ease;
    border: 1px solid rgba(255, 255, 255, 0.04);
    background: rgba(255, 255, 255, 0.02);
    margin-bottom: 4px;
}
.search-item-row:last-child {
    margin-bottom: 0;
}
.search-item-row:hover, .search-item-row.selected {
    background: rgba(207, 164, 111, 0.18);
    border-color: rgba(207, 164, 111, 0.42);
}
.search-item-icon {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    background: rgba(207, 164, 111, 0.14);
    color: #f5dfa8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.search-item-body {
    flex: 1;
    min-width: 0;
}
.search-item-title {
    display: block;
    font-size: 0.88rem;
    font-weight: 700;
    color: #fffbeb;
    line-height: 1.3;
}
.search-item-sub {
    display: block;
    font-size: 0.74rem;
    color: #b3a18f;
    margin-top: 2px;
    line-height: 1.35;
    white-space: normal;
    word-break: break-word;
}
.search-item-cat {
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #cfa46f;
    margin-left: auto;
    padding: 3px 8px;
    background: rgba(207, 164, 111, 0.12);
    border-radius: 6px;
    flex-shrink: 0;
}
.search-empty-state {
    padding: 20px 14px;
    text-align: center;
    font-size: 0.82rem;
    color: #a89885;
}

.settings-command-telemetry {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.telemetry-pill {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: rgba(20, 15, 12, 0.65);
    border: 1px solid rgba(207, 164, 111, 0.18);
    border-radius: 12px;
    padding: 7px 12px;
    backdrop-filter: blur(10px);
    transition: all 0.2s ease;
}
.telemetry-pill:hover {
    border-color: rgba(207, 164, 111, 0.35);
    background: rgba(30, 22, 18, 0.8);
    transform: translateY(-1px);
}
.telemetry-pill i {
    font-size: 1.1rem;
}
.telemetry-pill-text {
    display: flex;
    flex-direction: column;
}
.telemetry-lbl {
    font-size: 0.62rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #8f826f;
    line-height: 1;
}
.telemetry-val {
    font-size: 0.78rem;
    font-weight: 800;
    color: #f3e7cd;
    line-height: 1.25;
    margin-top: 2px;
}

/* ── Master-Detail Layout Grid & Sidebar Navigation ── */
.settings-layout-grid {
    display: grid;
    grid-template-columns: 290px 1fr;
    gap: 26px;
    align-items: start;
    position: relative;
    z-index: 1 !important;
}
.settings-sidebar {
    background: linear-gradient(145deg, rgba(26, 20, 16, 0.92) 0%, rgba(16, 13, 11, 0.96) 100%);
    border: 1px solid rgba(207, 164, 111, 0.18);
    border-radius: 18px;
    padding: 16px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.04);
    position: sticky;
    top: 24px;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}
.snav-group {
    margin-bottom: 16px;
}
.snav-group:last-of-type {
    margin-bottom: 8px;
}
.snav-group-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: #8c7d6d;
    padding: 6px 10px;
    margin-bottom: 4px;
}
.snav-group-header i {
    color: #cfa46f;
    font-size: 0.8rem;
}
.snav-item {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    text-align: left;
    margin-bottom: 3px;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}
.snav-item:hover {
    background: rgba(207, 164, 111, 0.08);
    border-color: rgba(207, 164, 111, 0.22);
    transform: translateX(2px);
}
.snav-item.active {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.22) 0%, rgba(166, 124, 67, 0.32) 100%) !important;
    border: 1px solid rgba(207, 164, 111, 0.42) !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.12) !important;
}
.snav-item-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(207, 164, 111, 0.1);
    color: #cfa46f;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.snav-item:hover .snav-item-icon {
    color: #f5dfa8;
    background: rgba(207, 164, 111, 0.18);
}
.snav-item.active .snav-item-icon {
    background: linear-gradient(135deg, #cfa46f 0%, #a67c43 100%);
    color: #140703;
    box-shadow: 0 2px 10px rgba(207, 164, 111, 0.4);
}
.snav-item-body {
    flex: 1;
    min-width: 0;
}
.snav-item-title {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: #f3e7cd;
    line-height: 1.25;
}
.snav-item.active .snav-item-title {
    color: #fffbeb;
}
.snav-item-desc {
    display: block;
    font-size: 0.7rem;
    color: #8f826f;
    margin-top: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.snav-item.active .snav-item-desc {
    color: #dfceb7;
}
.snav-badge {
    font-size: 0.63rem;
    font-weight: 800;
    padding: 2px 7px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    flex-shrink: 0;
}
.snav-badge-emerald {
    background: rgba(34, 197, 94, 0.15);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.3);
}
.snav-badge-amber {
    background: rgba(245, 158, 11, 0.15);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.snav-badge-rose {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}
.snav-sub-item {
    margin-left: 14px !important;
    padding: 7px 12px !important;
    border-left: 2px solid rgba(207, 164, 111, 0.2) !important;
    border-radius: 0 10px 10px 0 !important;
    background: rgba(255, 235, 190, 0.02) !important;
    opacity: 0.88;
}
.snav-sub-item:hover {
    opacity: 1 !important;
    border-left-color: rgba(207, 164, 111, 0.55) !important;
    background: rgba(207, 164, 111, 0.08) !important;
}
.snav-sub-item.active {
    border-left-color: #cfa46f !important;
    background: rgba(207, 164, 111, 0.16) !important;
    opacity: 1 !important;
}
.snav-sub-item .snav-item-icon {
    width: 26px !important;
    height: 26px !important;
    font-size: 0.84rem !important;
    border-radius: 8px !important;
}
.snav-sub-item .snav-item-title {
    font-size: 0.8rem !important;
}
.snav-sub-item .snav-item-desc {
    font-size: 0.65rem !important;
}
.snav-footer-card {
    margin-top: 14px;
    padding: 12px 14px;
    border-radius: 12px;
    background: rgba(207, 164, 111, 0.05);
    border: 1px solid rgba(207, 164, 111, 0.12);
}
.snav-footer-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.snav-footer-title {
    font-size: 0.7rem;
    font-weight: 800;
    color: #f3e7cd;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.snav-footer-sub {
    font-size: 0.68rem;
    color: #8c7d6d;
    line-height: 1.35;
}

.settings-main-content {
    min-width: 0;
    flex: 1;
}

/* Quick jump highlight animation */
@keyframes searchHighlightGlow {
    0% { outline: 2px solid rgba(207, 164, 111, 0.95); box-shadow: 0 0 25px rgba(207, 164, 111, 0.6); }
    100% { outline: 2px solid transparent; box-shadow: none; }
}
.search-highlight-pulse {
    animation: searchHighlightGlow 2.5s ease-out;
}

/* Desktop and Tablet visibility rules */
@media (min-width: 992px) {
    .stabs-wrapper {
        display: none !important;
    }
    .settings-sidebar {
        display: block !important;
    }
}
@media (max-width: 991.98px) {
    .settings-layout-grid {
        display: block !important;
    }
    .settings-sidebar {
        display: none !important;
    }
    .stabs-wrapper {
        display: flex !important;
        margin-bottom: 22px !important;
    }
}

/* ── Modern Segmented Pill Track ── */
.stabs-wrapper {
    position: relative;
    z-index: 10 !important;
    margin-bottom: 26px;
    display: flex;
    align-items: center;
    gap: 6px;
    width: 100%;
    background: rgba(18, 14, 11, 0.75);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(207, 164, 111, 0.18);
    border-radius: 16px;
    padding: 6px 8px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.stabs {
    flex: 1 1 0%;
    min-width: 0;
    display: flex;
    gap: 6px;
    border-bottom: none;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding: 2px 2px;
}
.stabs::-webkit-scrollbar {
    display: none;
}
.stab {
    white-space: nowrap;
    padding: 10px 18px;
    font-size: 0.84rem;
    font-weight: 700;
    color: #9d8e7d;
    cursor: pointer;
    border: 1px solid transparent;
    border-radius: 11px;
    background: transparent;
    margin-bottom: 0;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    user-select: none;
    line-height: 1.2;
}
.stab i {
    font-size: 0.95rem;
    transition: transform 0.2s ease;
}
.stab:hover {
    color: #fef3c7;
    background: rgba(207, 164, 111, 0.08);
    border-color: rgba(207, 164, 111, 0.2);
    transform: translateY(-1px);
}
.stab:hover i {
    transform: scale(1.1);
}
.stab.active {
    color: #fffbeb !important;
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.28) 0%, rgba(166, 124, 67, 0.38) 100%) !important;
    border: 1px solid rgba(207, 164, 111, 0.45) !important;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.4), 0 0 14px rgba(207, 164, 111, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.15) !important;
}
.stab.active i {
    color: #fde68a !important;
}

.stabs-arrow {
    flex-shrink: 0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(30, 24, 20, 0.92);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(207, 164, 111, 0.35);
    color: #f5dfa8;
    display: none;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.84rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.4);
    transition: all 0.2s ease;
    padding: 0;
    z-index: 2;
}
.stabs-arrow:hover {
    background: rgba(45, 36, 30, 0.98);
    border-color: #cfa46f;
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.6), 0 0 10px rgba(207,164,111,0.4);
    transform: scale(1.08);
}
.stabs-arrow:active {
    transform: scale(0.96);
}

.spanel { display: none; }
.spanel.active { display: block; animation: spanelFadeIn 0.28s cubic-bezier(0.16, 1, 0.3, 1); }
@keyframes spanelFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

/* ── Cyber-Luxury Cards ── */
.sc {
    background: linear-gradient(145deg, rgba(26, 20, 16, 0.88) 0%, rgba(16, 13, 11, 0.95) 100%);
    border-radius: 18px;
    border: 1px solid rgba(207, 164, 111, 0.15);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    overflow: hidden;
    margin-bottom: 24px;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}
.sc::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(207, 164, 111, 0.35), transparent);
    pointer-events: none;
}
.sc:hover {
    border-color: rgba(207, 164, 111, 0.28);
    box-shadow: 0 12px 38px rgba(0, 0, 0, 0.55), 0 0 22px rgba(207, 164, 111, 0.1);
    transform: translateY(-2px);
}
.sc-head {
    padding: 20px 24px;
    border-bottom: 1px solid rgba(207, 164, 111, 0.08);
    display: flex;
    align-items: center;
    gap: 16px;
    background: rgba(255, 215, 145, 0.015);
}
.sc-icon {
    width: 46px;
    height: 46px;
    border-radius: 13px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    background: rgba(207, 164, 111, 0.14) !important;
    color: #cfa46f !important;
    border: 1px solid rgba(207, 164, 111, 0.25);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
    flex-shrink: 0;
}
.sc-title {
    font-size: 1.1rem;
    font-weight: 800;
    color: #f3e7cd;
    letter-spacing: -0.2px;
}
.sc-sub {
    font-size: 0.8rem;
    color: #b39b82;
    margin-top: 2px;
}
.sc-body {
    padding: 24px;
}
@media(max-width: 640px) {
    .sc-head { padding: 16px 18px; }
    .sc-body { padding: 18px 16px; }
}

/* ── Refined Form Controls ── */
.sl {
    font-size: 0.74rem;
    font-weight: 800;
    color: #cfa46f;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 8px;
    display: block;
}
.si {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    border: 1.5px solid rgba(255, 215, 145, 0.14);
    font-size: 0.9rem;
    font-family: inherit;
    background: rgba(14, 11, 9, 0.7);
    color: #fef3c7;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}
.si:hover {
    border-color: rgba(207, 164, 111, 0.3);
    background: rgba(18, 14, 12, 0.8);
}
.si:focus {
    border-color: #cfa46f;
    background: rgba(22, 17, 14, 0.9);
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.2), 0 0 16px rgba(207, 164, 111, 0.12), inset 0 2px 4px rgba(0, 0, 0, 0.3);
}
.si option {
    background: #181411;
    color: #fef3c7;
}
.input-icon-wrap {
    position: relative;
    display: flex;
    align-items: center;
}
.input-icon-prefix {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #cfa46f;
    font-size: 1rem;
    pointer-events: none;
    z-index: 2;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
}
.si.with-icon,
.input-icon-wrap .si {
    padding-left: 44px !important;
}
.pw-wrap { position: relative; }
.pw-wrap .si { padding-right: 48px !important; }
.eye-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #b39b82;
    font-size: 1.1rem;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    transition: color 0.2s, transform 0.2s;
    line-height: 1;
}
.eye-btn:hover {
    color: #cfa46f;
    transform: translateY(-50%) scale(1.1);
}

.sbtn {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 8px !important;
    padding: 12px 26px !important;
    background: linear-gradient(135deg, #cfa46f 0%, #a67c43 100%) !important;
    color: #140703 !important;
    font-weight: 800 !important;
    font-size: 0.88rem !important;
    border: none !important;
    border-radius: 12px !important;
    cursor: pointer !important;
    box-shadow: 0 4px 16px rgba(207,164,111,0.3) !important;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
    letter-spacing: 0.2px !important;
    text-decoration: none !important;
}
.sbtn:hover {
    background: linear-gradient(135deg, #dfb885 0%, #b88648 100%) !important;
    transform: translateY(-2px) !important;
    box-shadow: 0 8px 24px rgba(207,164,111,0.45) !important;
    filter: brightness(1.05);
}
.sbtn:active {
    transform: translateY(0) scale(0.98) !important;
}
.cancel-btn {
    padding: 11px 20px !important;
    background: rgba(20, 15, 12, 0.7) !important;
    color: #b39b82 !important;
    border: 1px solid rgba(207, 164, 111, 0.18) !important;
    border-radius: 12px !important;
    font-weight: 700 !important;
    font-size: 0.85rem !important;
    cursor: pointer !important;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
    text-decoration: none !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.cancel-btn:hover {
    background: rgba(30, 22, 18, 0.9) !important;
    border-color: rgba(207, 164, 111, 0.35) !important;
    color: #fef3c7 !important;
    transform: translateY(-1px) !important;
}

.trow {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 0;
    border-bottom: 1px solid rgba(255,215,145,0.06);
}
.trow:last-child { border-bottom: none; padding-bottom: 0; }
.tlabel { font-size: 0.875rem; font-weight: 700; color: #f3e7cd; }
.tsub { font-size: 0.78rem; color: #b39b82; margin-top: 2px; }

.form-check-input {
    background-color: rgba(255,235,190,0.1);
    border-color: rgba(255,215,145,0.2);
    width: 2.4em !important;
    height: 1.3em !important;
    cursor: pointer;
    transition: all 0.2s ease;
}
.form-check-input:checked {
    background-color: #cfa46f !important;
    border-color: #cfa46f !important;
    box-shadow: 0 0 10px rgba(207,164,111,0.4);
}

.flash-ok {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.14) 0%, rgba(20, 83, 45, 0.25) 100%);
    border: 1px solid rgba(34, 197, 94, 0.35);
    color: #86efac;
    border-radius: 14px;
    padding: 14px 18px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3), 0 0 16px rgba(34, 197, 94, 0.15);
    backdrop-filter: blur(10px);
}
.flash-err {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.14) 0%, rgba(127, 29, 29, 0.25) 100%);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #fca5a5;
    border-radius: 14px;
    padding: 14px 18px;
    font-size: 0.88rem;
    font-weight: 600;
    margin-bottom: 22px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.3), 0 0 16px rgba(239, 68, 68, 0.15);
    backdrop-filter: blur(10px);
}

/* ── Academic Specification Grid ── */
.academic-spec-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 14px;
}
.academic-spec-card {
    background: rgba(20, 15, 12, 0.6);
    border: 1px solid rgba(207, 164, 111, 0.12);
    border-radius: 14px;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.22s ease;
}
.academic-spec-card:hover {
    background: rgba(30, 22, 18, 0.75);
    border-color: rgba(207, 164, 111, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
}
.academic-spec-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(207, 164, 111, 0.22);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cfa46f;
    font-size: 1rem;
    flex-shrink: 0;
}
.academic-spec-info {
    flex: 1;
    min-width: 0;
}
.academic-spec-label {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #9d8e7d;
}
.academic-spec-value {
    font-size: 0.92rem;
    font-weight: 700;
    color: #fef3c7;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}
.stat-box {
    background: rgba(255, 235, 190, 0.03);
    border: 1px solid rgba(255, 215, 145, 0.08);
    border-radius: 12px;
    padding: 14px 16px;
    text-align: center;
    transition: transform .2s, box-shadow .2s;
}
.stat-box:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0,0,0,.15);
    border-color: rgba(255, 215, 145, 0.15);
}
.stat-val { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.stat-lbl {
    font-size: 0.68rem;
    font-weight: 600;
    color: #b39b82;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    margin-top: 4px;
}
.prog-bar {
    height: 8px;
    background: rgba(255, 215, 145, 0.1);
    border-radius: 99px;
    overflow: hidden;
    margin-top: 6px;
}
.prog-fill { height: 100%; border-radius: 99px; transition: width 1s ease; }

.info-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 0;
    border-bottom: 1px solid rgba(255, 215, 145, 0.06);
}
.info-row:last-child { border-bottom: none; }
.info-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(255, 215, 145, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #cfa46f;
    font-size: 0.95rem;
    flex-shrink: 0;
}
.info-lbl {
    font-size: 0.7rem;
    font-weight: 600;
    color: #b39b82;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.info-val { font-size: 0.9rem; font-weight: 600; color: #f3e7cd; }

.act-row {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255, 215, 145, 0.06);
    transition: background .15s;
}
.act-row:hover { background: rgba(255, 235, 190, 0.04); }
.act-row:last-child { border-bottom: none; }

/* Form overrides specific for Dark Theme */
.email-otp-digit, .otp-digit-s {
    color: #f3e7cd !important;
    background: rgba(255, 235, 190, 0.05) !important;
    border-color: rgba(255, 215, 145, 0.15) !important;
}
.email-otp-digit:focus, .otp-digit-s:focus {
    border-color: #cfa46f !important;
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.2), 0 0 12px rgba(207, 164, 111, 0.15) !important;
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

/* ── Security Sub-Navigation Segmented Pill Bar ── */
.sec-subnav-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(18, 14, 11, 0.85);
    border: 1px solid rgba(207, 164, 111, 0.2);
    border-radius: 14px;
    padding: 6px 8px;
    margin-bottom: 20px;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.04);
}
.sec-subnav-bar::-webkit-scrollbar {
    display: none;
}
.sec-subnav-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border-radius: 10px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #a89885;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    user-select: none;
    line-height: 1.2;
}
.sec-subnav-pill:hover {
    color: #fef3c7;
    background: rgba(207, 164, 111, 0.08);
    border-color: rgba(207, 164, 111, 0.18);
    transform: translateY(-1px);
}
.sec-subnav-pill:active {
    transform: scale(0.97);
}
.sec-subnav-pill.active {
    color: #fffbeb !important;
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.25) 0%, rgba(166, 124, 67, 0.35) 100%) !important;
    border-color: rgba(207, 164, 111, 0.45) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35), 0 0 12px rgba(207, 164, 111, 0.2) !important;
}
.sec-subnav-pill.active i {
    color: #fde68a !important;
}
.sec-subnav-badge {
    font-size: 0.62rem;
    font-weight: 800;
    padding: 2px 6px;
    border-radius: 6px;
    letter-spacing: 0.3px;
    text-transform: uppercase;
}
.sec-subnav-badge.badge-emerald {
    background: rgba(34, 197, 94, 0.18);
    color: #4ade80;
    border: 1px solid rgba(34, 197, 94, 0.35);
}
.sec-subnav-badge.badge-amber {
    background: rgba(245, 158, 11, 0.18);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

/* ── Security Group Panels & Headers ── */
.sec-group-panel {
    margin-bottom: 24px;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
}
.sec-group-panel:last-child {
    margin-bottom: 0;
}
.sec-group-header {
    background: linear-gradient(135deg, rgba(28, 21, 16, 0.88) 0%, rgba(18, 13, 10, 0.96) 100%);
    border: 1px solid rgba(207, 164, 111, 0.18);
    border-radius: 14px;
    padding: 13px 18px;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
}
.sec-group-header-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}
.sec-group-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}
.sec-group-title {
    font-size: 0.96rem;
    font-weight: 800;
    color: #f3e7cd;
    letter-spacing: -0.2px;
    line-height: 1.25;
}
.sec-group-desc {
    font-size: 0.74rem;
    color: #b39b82;
    margin-top: 1px;
    line-height: 1.35;
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

/* ── Biometric Registration Suite (Enterprise Design System) ── */
.bio-flow-section {
    margin-bottom: 26px;
}
.bio-section-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}
.bio-step-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(207, 164, 111, 0.15);
    color: var(--gold, #cfa46f);
    border: 1px solid rgba(207, 164, 111, 0.32);
    font-size: 0.68rem;
    font-weight: 800;
    padding: 3px 10px;
    border-radius: 99px;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.bio-step-title {
    font-size: 1rem;
    font-weight: 700;
    color: #f8fafc;
    letter-spacing: -0.2px;
}
.bio-step-subtitle {
    font-size: 0.8rem;
    color: #94a3b8;
    margin-top: 2px;
}

/* Method Selection Grid */
.bio-method-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
    margin-bottom: 8px;
}
.bio-method-card {
    position: relative;
    background: linear-gradient(165deg, rgba(30, 41, 59, 0.45) 0%, rgba(15, 23, 42, 0.75) 100%);
    border: 1.5px solid rgba(148, 163, 184, 0.18);
    border-top: 1.5px solid rgba(255, 255, 255, 0.12);
    border-radius: 18px;
    padding: 22px 20px 20px 20px;
    cursor: pointer;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    outline: none;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    user-select: none;
}
.bio-method-card:hover {
    border-color: rgba(207, 164, 111, 0.45);
    background: linear-gradient(165deg, rgba(30, 41, 59, 0.65) 0%, rgba(15, 23, 42, 0.88) 100%);
    transform: translateY(-2px);
    box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.5), 0 0 20px rgba(207, 164, 111, 0.08);
}
.bio-method-card:focus-visible {
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.4), 0 8px 24px rgba(0, 0, 0, 0.4);
}
.bio-method-card.selected#methodCardFingerprint,
.bio-method-card.selected#methodCardFp {
    border-color: #10b981;
    background: linear-gradient(165deg, rgba(16, 185, 129, 0.12) 0%, rgba(15, 23, 42, 0.88) 100%);
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.3), 0 16px 36px -10px rgba(16, 185, 129, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}
.bio-method-card.selected#methodCardFace {
    border-color: #0ea5e9;
    background: linear-gradient(165deg, rgba(14, 165, 233, 0.12) 0%, rgba(15, 23, 42, 0.88) 100%);
    box-shadow: 0 0 0 1px rgba(14, 165, 233, 0.3), 0 16px 36px -10px rgba(14, 165, 233, 0.28), inset 0 1px 0 rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
}

.bio-card-radio {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 1.5px solid rgba(148, 163, 184, 0.3);
    background: rgba(15, 23, 42, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.bio-radio-inner {
    width: 14px;
    height: 14px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.72rem;
    color: #ffffff;
    opacity: 0;
    transform: scale(0.4);
    transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
.bio-method-card.selected#methodCardFingerprint .bio-card-radio,
.bio-method-card.selected#methodCardFp .bio-card-radio {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.2);
    box-shadow: 0 0 10px rgba(16, 185, 129, 0.35);
}
.bio-method-card.selected#methodCardFingerprint .bio-radio-inner,
.bio-method-card.selected#methodCardFp .bio-radio-inner {
    background: #10b981;
    opacity: 1;
    transform: scale(1);
}
.bio-method-card.selected#methodCardFace .bio-card-radio {
    border-color: #0ea5e9;
    background: rgba(14, 165, 233, 0.2);
    box-shadow: 0 0 10px rgba(14, 165, 233, 0.35);
}
.bio-method-card.selected#methodCardFace .bio-radio-inner {
    background: #0ea5e9;
    opacity: 1;
    transform: scale(1);
}

.bio-method-icon-wrap {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    margin-bottom: 14px;
    transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
}
.bio-fp-icon {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.16) 0%, rgba(5, 150, 105, 0.08) 100%);
    border: 1.5px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
}
.bio-face-icon {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.16) 0%, rgba(2, 132, 199, 0.08) 100%);
    border: 1.5px solid rgba(14, 165, 233, 0.35);
    color: #38bdf8;
}
.bio-method-card.selected .bio-method-icon-wrap {
    transform: scale(1.08);
}
.bio-method-card.selected#methodCardFingerprint .bio-fp-icon,
.bio-method-card.selected#methodCardFp .bio-fp-icon {
    box-shadow: 0 0 24px rgba(16, 185, 129, 0.45);
    border-color: #34d399;
}
.bio-method-card.selected#methodCardFace .bio-face-icon {
    box-shadow: 0 0 24px rgba(14, 165, 233, 0.45);
    border-color: #38bdf8;
}

.bio-method-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 6px;
}
.bio-method-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #f8fafc;
    letter-spacing: -0.2px;
}
.bio-status-pill {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 99px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    transition: all 0.3s ease;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.bio-status-pill.not-reg {
    background: rgba(245, 158, 11, 0.12);
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}
.bio-status-pill.registered {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
.bio-status-pill.registering {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(14, 165, 233, 0.35);
}
.bio-status-pill.failed {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.bio-method-desc {
    font-size: 0.8rem;
    color: #cbd5e1;
    line-height: 1.5;
    margin-bottom: 14px;
}
.bio-method-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
    font-size: 0.72rem;
    color: #94a3b8;
}
.bio-spec-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(148, 163, 184, 0.1);
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 6px;
    padding: 2px 7px;
    font-size: 0.68rem;
    font-weight: 600;
    color: #cbd5e1;
}

/* Scanner Stage Card (Enterprise Central Enclave) */
.bio-scanner-card {
    background: linear-gradient(165deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.96) 100%);
    border: 1.5px solid rgba(148, 163, 184, 0.18);
    border-top: 1.5px solid rgba(255, 255, 255, 0.12);
    border-radius: 22px;
    padding: 32px 24px 28px;
    text-align: center;
    position: relative;
    overflow: hidden;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 18px 45px -12px rgba(0, 0, 0, 0.65), inset 0 1px 0 rgba(255, 255, 255, 0.08);
}
.bio-scanner-card.bio-detected {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 1px rgba(16, 185, 129, 0.4), 0 20px 50px -10px rgba(16, 185, 129, 0.45) !important;
    animation: bioDetectFlash 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes bioDetectFlash {
    0% { transform: scale(1); }
    50% { transform: scale(1.012); box-shadow: 0 0 60px rgba(16, 185, 129, 0.6); }
    100% { transform: scale(1); }
}

.bio-stage-view {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.bio-method-preview {
    display: none;
    opacity: 0;
    transform: translateY(6px);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.bio-method-preview.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}
.bio-scanner-display {
    display: none;
    opacity: 0;
    transform: translateY(6px);
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}
.bio-scanner-display.active {
    display: block;
    opacity: 1;
    transform: translateY(0);
}

/* Fingerprint Idle Graphic */
.bio-idle-sensor-ring {
    position: relative;
    width: 96px;
    height: 96px;
    border-radius: 50%;
    margin: 0 auto 18px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, rgba(16, 185, 129, 0.05) 65%, transparent 100%);
    border: 2px solid rgba(52, 211, 153, 0.4);
    box-shadow: 0 0 30px rgba(16, 185, 129, 0.25), inset 0 0 20px rgba(16, 185, 129, 0.15);
}
.bio-radar-ring {
    position: absolute;
    inset: -6px;
    border-radius: 50%;
    border: 1.5px dashed rgba(52, 211, 153, 0.35);
    animation: fpRadarSpin 16s linear infinite;
}
.bio-radar-ring.delay-1 {
    inset: -14px;
    border: 1px solid rgba(52, 211, 153, 0.18);
    animation: fpRadarPulse 3s ease-out infinite;
}
.bio-idle-sensor-icon {
    font-size: 2.8rem;
    color: #34d399;
    filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.4));
}

/* Face Idle Graphic */
.bio-idle-face-box {
    position: relative;
    width: 140px;
    height: 160px;
    margin: 0 auto 18px auto;
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.16) 0%, rgba(14, 165, 233, 0.04) 70%, transparent 100%);
    border: 1.5px dashed rgba(56, 189, 248, 0.35);
    box-shadow: 0 0 30px rgba(14, 165, 233, 0.2), inset 0 0 20px rgba(14, 165, 233, 0.1);
}
.hud-corner {
    position: absolute;
    width: 22px;
    height: 22px;
    border-color: #38bdf8;
    border-style: solid;
    pointer-events: none;
    z-index: 10;
}
.hud-tl { top: -2px; left: -2px; border-width: 3px 0 0 3px; border-top-left-radius: 10px; }
.hud-tr { top: -2px; right: -2px; border-width: 3px 3px 0 0; border-top-right-radius: 10px; }
.hud-bl { bottom: -2px; left: -2px; border-width: 0 0 3px 3px; border-bottom-left-radius: 10px; }
.hud-br { bottom: -2px; right: -2px; border-width: 0 3px 3px 0; border-bottom-right-radius: 10px; }
.hud-face-reticle {
    display: flex;
    align-items: center;
    justify-content: center;
}
.bio-idle-face-icon {
    font-size: 3.6rem;
    color: #38bdf8;
    filter: drop-shadow(0 0 12px rgba(14, 165, 233, 0.45));
}

.bio-preview-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 6px;
    letter-spacing: -0.2px;
}
.bio-preview-sub {
    font-size: 0.84rem;
    color: #cbd5e1;
    max-width: 480px;
    margin: 0 auto 20px auto;
    line-height: 1.55;
}
.bio-action-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    flex-wrap: wrap;
}
.bio-primary-cta {
    padding: 13px 32px;
    font-size: 0.94rem;
    font-weight: 700;
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.35);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.bio-primary-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(16, 185, 129, 0.48);
}
.bio-hardware-note {
    font-size: 0.78rem;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
}

/* Fingerprint Live Active Scanner (Realistic Optical Enclave) */
.fp-scan-frame {
    position: relative;
    width: 140px;
    height: 170px;
    margin: 0 auto 18px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: radial-gradient(circle at 50% 50%, rgba(16, 185, 129, 0.2) 0%, rgba(15, 23, 42, 0.95) 85%);
    border-radius: 26px;
    border: 2px solid rgba(52, 211, 153, 0.45);
    box-shadow: 0 0 35px rgba(16, 185, 129, 0.28), inset 0 0 25px rgba(16, 185, 129, 0.15);
    overflow: hidden;
    transition: all 0.3s ease;
}
.fp-svg {
    width: 96px;
    height: 122px;
    stroke: rgba(207, 164, 111, 0.55);
    fill: none;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    transition: all 0.3s ease;
}
.fp-ridge {
    transition: stroke 0.3s ease, filter 0.3s ease;
}
.bio-scanner-card.bio-detected .fp-svg,
.fp-scan-frame.bio-detected .fp-svg {
    stroke: #34d399 !important;
    filter: drop-shadow(0 0 14px #10b981);
    transform: scale(1.05);
}
.fp-scan-frame.bio-detected {
    border-color: #10b981 !important;
    box-shadow: 0 0 45px rgba(16, 185, 129, 0.5) !important;
}
.fp-laser-line {
    position: absolute;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, #10b981 25%, #34d399 50%, #10b981 75%, transparent 100%);
    box-shadow: 0 0 18px #34d399, 0 0 8px #10b981;
    z-index: 5;
    top: 5%;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.fp-scan-frame.bio-detected .fp-laser-line,
.bio-scanner-card.bio-detected .fp-laser-line {
    opacity: 1;
    animation: fpLaserSweepSuccess 0.85s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
@keyframes fpLaserSweepSuccess {
    0%   { top: 5%; opacity: 1; }
    50%  { top: 92%; opacity: 1; }
    100% { top: 50%; opacity: 0; }
}
.fp-pulse-wave {
    position: absolute;
    width: 48px;
    height: 48px;
    border-radius: 50%;
    border: 1.5px solid rgba(52, 211, 153, 0.4);
    animation: fpTouchWaitPulse 2.8s ease-in-out infinite;
}
@keyframes fpTouchWaitPulse {
    0%, 100% { transform: scale(0.9); opacity: 0.25; }
    50%      { transform: scale(1.18); opacity: 0.8; }
}
.fp-scan-frame.bio-detected .fp-pulse-wave,
.bio-scanner-card.bio-detected .fp-pulse-wave {
    animation: fpPulseDetected 0.7s ease-out forwards;
}
@keyframes fpPulseDetected {
    0%   { transform: scale(0.9); opacity: 1; border-color: #34d399; }
    100% { transform: scale(3.8); opacity: 0; border-color: #10b981; }
}

/* Face Recognition Live Active Scanner (Aerospace-Grade Biometric HUD) */
.face-scan-frame {
    position: relative;
    width: 100%;
    max-width: 360px;
    height: 360px;
    aspect-ratio: 1 / 1;
    margin: 0 auto 18px auto;
    border-radius: 26px;
    background: #020617;
    border: 2px solid rgba(14, 165, 233, 0.55);
    box-shadow: 0 0 40px rgba(14, 165, 233, 0.28), inset 0 0 30px rgba(14, 165, 233, 0.12);
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: box-shadow 0.25s ease, border-color 0.25s ease;
}
@media (max-width: 480px) {
    .face-scan-frame {
        max-width: min(330px, 84vw);
        height: min(330px, 84vw);
        border-radius: 22px;
    }
}
/* Face Flash / Fill Light Controls */
.face-hud-flash-btn {
    position: absolute;
    top: 14px;
    right: 14px;
    z-index: 25;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    background: rgba(15, 23, 42, 0.85);
    border: 1.5px solid rgba(255, 255, 255, 0.25);
    color: #f1f5f9;
    font-size: 0.78rem;
    font-weight: 600;
    border-radius: 20px;
    cursor: pointer;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.5);
}
.face-hud-flash-btn:hover {
    background: rgba(30, 41, 59, 0.95);
    border-color: #facc15;
    color: #fef08a;
    transform: scale(1.05);
    box-shadow: 0 0 18px rgba(250, 204, 21, 0.6);
}
.face-hud-flash-btn.active-flash {
    background: linear-gradient(135deg, #facc15, #f59e0b) !important;
    border-color: #ffffff !important;
    color: #0f172a !important;
    box-shadow: 0 0 24px rgba(250, 204, 21, 0.95), 0 0 10px #ffffff !important;
    font-weight: 700;
    transform: scale(1.04);
}
.face-hud-flash-btn.active-flash i {
    color: #0f172a !important;
    text-shadow: 0 0 4px rgba(255, 255, 255, 0.8);
}
.face-hud-flash-btn.flash-suggest-pulse {
    animation: flashBtnSuggestPulse 1.4s ease-in-out infinite;
}
@keyframes flashBtnSuggestPulse {
    0%, 100% { border-color: rgba(250, 204, 21, 0.4); box-shadow: 0 0 0 rgba(250, 204, 21, 0); }
    50% { border-color: #facc15; box-shadow: 0 0 18px rgba(250, 204, 21, 0.75); color: #facc15; }
}

/* Face Screen Flash Overlay (Selfie Fill Light) */
.face-screen-flash-overlay {
    position: absolute;
    inset: 0;
    pointer-events: none;
    z-index: 12;
    opacity: 0;
    transition: opacity 0.25s ease, box-shadow 0.25s ease;
    border-radius: inherit;
    box-shadow: inset 0 0 70px 25px rgba(255, 255, 255, 0.95), 0 0 90px 30px rgba(255, 255, 255, 0.9);
    border: 4px solid #ffffff;
}
.face-screen-flash-overlay.active {
    opacity: 1;
}

.face-scan-frame.flash-on,
.bio-login-camera-box.flash-on {
    border-color: #ffffff !important;
    box-shadow: 0 0 0 12px #ffffff, 0 0 90px 35px rgba(255, 255, 255, 0.95), 0 0 150px 70px rgba(255, 255, 255, 0.85) !important;
}

/* Card-wide screen fill light when flash is active */
.bio-scanner-card.flash-on {
    background: #ffffff !important;
    box-shadow: 0 0 150px 70px rgba(255, 255, 255, 0.95), 0 0 0 9999px rgba(255, 255, 255, 0.5) !important;
    color: #0f172a !important;
    border-color: #ffffff !important;
}
.bio-scanner-card.flash-on .bio-scanning-title {
    color: #0f172a !important;
}
.bio-scanner-card.flash-on .bio-scanning-sub {
    color: #334155 !important;
}
.bio-scanner-card.flash-on .bio-progress-state,
.bio-scanner-card.flash-on .bio-progress-pct {
    color: #0f172a !important;
}

@keyframes faceCaptureBurst {
    0% { opacity: 0; }
    20% { opacity: 1; background: #ffffff; }
    100% { opacity: 0; }
}
.face-scan-frame.face-flash-burst::after,
.bio-login-camera-box.face-flash-burst::after {
    content: '';
    position: absolute;
    inset: 0;
    background: #ffffff;
    z-index: 30;
    pointer-events: none;
    border-radius: inherit;
    animation: faceCaptureBurst 0.38s ease-out forwards;
}
.face-laser-bar {
    position: absolute;
    left: 4%;
    width: 92%;
    height: 3px;
    background: linear-gradient(90deg, transparent 0%, #0ea5e9 25%, #38bdf8 50%, #0ea5e9 75%, transparent 100%);
    box-shadow: 0 0 18px #38bdf8, 0 0 8px #0ea5e9;
    z-index: 5;
    top: 0;
    animation: faceLaserSweep 2.4s ease-in-out infinite;
}
@keyframes faceLaserSweep {
    0%   { top: 6%; opacity: 0.75; }
    50%  { top: 90%; opacity: 1; }
    100% { top: 6%; opacity: 0.75; }
}
.face-camera-feed {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transform: scaleX(-1);
    display: block;
}
.face-holo-mesh {
    position: relative;
    width: 230px;
    height: 270px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.face-oval-target {
    width: 190px;
    height: 240px;
    border-radius: 50% / 60% 60% 40% 40%;
    border: 2px dashed rgba(56, 189, 248, 0.5);
    position: relative;
    box-shadow: inset 0 0 25px rgba(14, 165, 233, 0.15), 0 0 25px rgba(14, 165, 233, 0.12);
    animation: faceOvalPulse 2.2s ease-in-out infinite;
}
@keyframes faceOvalPulse {
    0%, 100% { border-color: rgba(56, 189, 248, 0.45); transform: scale(1); }
    50% { border-color: rgba(56, 189, 248, 0.85); transform: scale(1.02); }
}
.face-mesh-node {
    position: absolute;
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #38bdf8;
    box-shadow: 0 0 10px #0ea5e9;
    animation: meshNodePulse 1.8s infinite ease-in-out;
}
.n-forehead { top: 35px; left: 50%; transform: translateX(-50%); }
.n-eye-l    { top: 80px; left: 35px; animation-delay: 0.2s; }
.n-eye-r    { top: 80px; right: 35px; animation-delay: 0.3s; }
.n-nose     { top: 120px; left: 50%; transform: translateX(-50%); animation-delay: 0.5s; }
.n-mouth    { top: 165px; left: 50%; transform: translateX(-50%); animation-delay: 0.7s; }
.n-jaw-l    { top: 200px; left: 45px; animation-delay: 0.9s; }
.n-jaw-r    { top: 200px; right: 45px; animation-delay: 1s; }
@keyframes meshNodePulse {
    0%, 100% { opacity: 0.4; transform: scale(0.8); }
    50% { opacity: 1; transform: scale(1.3); }
}

.bio-scanning-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #f8fafc;
    margin-bottom: 4px;
    letter-spacing: -0.2px;
}
.bio-scanning-sub {
    font-size: 0.84rem;
    color: #cbd5e1;
    margin-bottom: 18px;
    line-height: 1.5;
}

/* Progressive Scan Feedback */
.bio-progress-container {
    width: 100%;
    max-width: 380px;
    margin: 0 auto 16px auto;
}
.bio-progress-track {
    width: 100%;
    height: 7px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 99px;
    overflow: hidden;
    margin-bottom: 8px;
    border: 1px solid rgba(255, 255, 255, 0.06);
}
.bio-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #059669, #34d399);
    border-radius: 99px;
    transition: width 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 0 12px rgba(52, 211, 153, 0.5);
}
.bio-progress-fill.cyan-fill {
    background: linear-gradient(90deg, #0284c7, #38bdf8);
    box-shadow: 0 0 12px rgba(56, 189, 248, 0.5);
}
.bio-progress-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.76rem;
    color: #94a3b8;
    line-height: 1.4;
}
.bio-progress-pct {
    font-weight: 700;
    color: #f8fafc;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}

.bio-cancel-row {
    margin-top: 10px;
}
.bio-cancel-btn {
    padding: 8px 20px;
    font-size: 0.82rem;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.14);
    color: #cbd5e1;
    cursor: pointer;
    transition: all 0.2s ease;
}
.bio-cancel-btn:hover {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.25);
}

/* Success View */
.bio-success-wrap {
    padding: 12px 0;
}
.bio-success-icon-ring {
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(16, 185, 129, 0.06) 70%, transparent 100%);
    border: 2px solid rgba(52, 211, 153, 0.5);
    box-shadow: 0 0 35px rgba(16, 185, 129, 0.35);
    margin: 0 auto 18px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: successPop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
@keyframes successPop {
    0% { transform: scale(0.6); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.bio-success-svg {
    width: 52px;
    height: 52px;
}
.bio-success-circle {
    stroke: #10b981;
    stroke-width: 3;
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: circleStroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.bio-success-check {
    stroke: #34d399;
    stroke-width: 3.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: checkStroke 0.4s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
}
@keyframes circleStroke {
    100% { stroke-dashoffset: 0; }
}
@keyframes checkStroke {
    100% { stroke-dashoffset: 0; }
}

.bio-success-title {
    font-size: 1.3rem;
    font-weight: 800;
    color: #34d399;
    margin-bottom: 6px;
    letter-spacing: -0.2px;
}
.bio-success-desc {
    font-size: 0.86rem;
    color: #cbd5e1;
    max-width: 460px;
    margin: 0 auto 22px auto;
    line-height: 1.55;
}
.bio-success-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Error View */
.bio-error-wrap {
    padding: 12px 0;
    animation: bioShake 0.5s ease-in-out;
}
@keyframes bioShake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-8px); }
    40%, 80% { transform: translateX(8px); }
}
.bio-error-icon-box {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(239, 68, 68, 0.2) 0%, rgba(239, 68, 68, 0.05) 70%, transparent 100%);
    border: 2px solid rgba(239, 68, 68, 0.45);
    box-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
    margin: 0 auto 16px auto;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f87171;
    font-size: 2.2rem;
}
.bio-error-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #f87171;
    margin-bottom: 6px;
    letter-spacing: -0.2px;
}
.bio-error-desc {
    font-size: 0.84rem;
    color: #cbd5e1;
    max-width: 440px;
    margin: 0 auto 22px auto;
    line-height: 1.55;
}
.bio-error-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Device Item Cards (Registered Credentials) */
.device-item-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 16px 20px;
    border-radius: 16px;
    background: linear-gradient(165deg, rgba(30, 41, 59, 0.4) 0%, rgba(15, 23, 42, 0.6) 100%);
    border: 1px solid rgba(148, 163, 184, 0.14);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 12px;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.device-item-card:hover {
    border-color: rgba(207, 164, 111, 0.35);
    background: linear-gradient(165deg, rgba(30, 41, 59, 0.6) 0%, rgba(15, 23, 42, 0.75) 100%);
    transform: translateY(-1px);
    box-shadow: 0 8px 24px -6px rgba(0, 0, 0, 0.4);
}
.device-item-left {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 0;
}
.device-item-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
.device-item-icon.fp-type {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.35);
    color: #34d399;
}
.device-item-icon.face-type {
    background: rgba(14, 165, 233, 0.15);
    border-color: rgba(14, 165, 233, 0.35);
    color: #38bdf8;
}
.device-item-info {
    flex: 1;
    min-width: 0;
}
.device-item-name-row {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.device-item-name {
    font-size: 0.94rem;
    font-weight: 700;
    color: #f8fafc;
}
.device-type-badge {
    font-size: 0.65rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.device-type-badge.fp {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}
.device-type-badge.face {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(14, 165, 233, 0.3);
}
.bio-device-type-pill {
    font-size: 0.66rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    letter-spacing: 0.4px;
    text-transform: uppercase;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
}
.bio-device-type-pill.pill-fp {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
.bio-device-type-pill.pill-face {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(14, 165, 233, 0.35);
}
.device-item-meta {
    font-size: 0.74rem;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
    flex-wrap: wrap;
}
.device-meta-verified {
    color: #34d399;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
}
.device-meta-dot {
    color: #64748b;
}
.device-meta-date {
    color: #94a3b8;
    font-weight: 500;
}
.device-remove-btn {
    flex-shrink: 0;
    padding: 7px 15px;
    border-radius: 10px;
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.25);
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
}
.device-remove-btn:hover {
    background: rgba(239, 68, 68, 0.22);
    color: #fca5a5;
    border-color: rgba(239, 68, 68, 0.45);
}

@keyframes fpRadarSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
@keyframes fpRadarPulse {
    0% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.15); opacity: 0.1; }
    100% { transform: scale(1); opacity: 0.6; }
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

    /* ── Responsive Mobile & Tablet Optimization ── */
    @media (max-width: 768px) {
        .sp {
            padding-left: 14px !important;
            padding-right: 14px !important;
        }

        .settings-top-navbar,
        .settings-command-header {
            padding: 16px 18px !important;
            border-radius: 16px !important;
            gap: 14px !important;
        }
        .settings-top-left,
        .settings-command-left {
            gap: 14px !important;
        }
        .settings-avatar-chip {
            width: 48px !important;
            height: 48px !important;
            border-radius: 12px !important;
        }
        .settings-chip-avatar {
            border-radius: 10px !important;
        }
        .settings-top-navbar .pg-title,
        .settings-command-header .pg-title {
            font-size: 1.3rem !important;
        }
        .settings-top-navbar .pg-sub,
        .settings-command-header .pg-sub {
            font-size: 0.76rem !important;
        }
        .settings-search-box {
            width: 100% !important;
        }
        .settings-search-input {
            padding-left: 44px !important;
            padding-right: 42px !important;
            font-size: 0.85rem !important;
        }
        .settings-search-kbd {
            display: none !important;
        }
        .settings-search-dropdown {
            position: absolute !important;
            top: calc(100% + 6px) !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            min-width: 0 !important;
            max-height: min(280px, calc(100dvh - 260px)) !important;
            border-radius: 12px !important;
        }
        .settings-top-right {
            width: 100% !important;
            justify-content: stretch !important;
        }
        .settings-command-telemetry {
            width: 100% !important;
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
        .telemetry-pill {
            width: 100% !important;
            padding: 7px 10px !important;
            gap: 8px !important;
        }
        .telemetry-pill i {
            font-size: 1rem !important;
        }
        .telemetry-lbl {
            font-size: 0.6rem !important;
        }
        .telemetry-val {
            font-size: 0.75rem !important;
        }

        .stabs-wrapper {
            position: relative !important;
            z-index: 10 !important;
            margin-top: 6px !important;
            margin-bottom: 20px !important;
            padding: 4px 6px !important;
            border-radius: 14px !important;
            gap: 4px !important;
        }
        .stabs {
            gap: 5px !important;
            padding: 2px !important;
        }
        .stab {
            padding: 8px 13px !important;
            font-size: 0.82rem !important;
            border-radius: 9px !important;
        }
        .stabs-arrow {
            width: 28px !important;
            height: 28px !important;
            font-size: 0.78rem !important;
        }

        .sc {
            border-radius: 16px !important;
            margin-bottom: 18px !important;
        }
        .sc-head {
            padding: 16px 18px !important;
            gap: 12px !important;
        }
        .sc-icon {
            width: 38px !important;
            height: 38px !important;
            font-size: 1.05rem !important;
            border-radius: 10px !important;
        }
        .sc-title { font-size: 0.98rem !important; }
        .sc-sub { font-size: 0.75rem !important; }
        .sc-body { padding: 18px 16px !important; }

        .sl { font-size: 0.72rem !important; }
        .si { font-size: 0.85rem !important; padding: 10px 14px !important; }
        .si.with-icon,
        .input-icon-wrap .si { padding-left: 44px !important; }
        .pw-wrap .si { padding-right: 48px !important; }

        .sbtn { padding: 11px 20px !important; font-size: 0.85rem !important; }

        .academic-spec-grid {
            grid-template-columns: 1fr !important;
            gap: 10px !important;
        }

        .trow {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 6px !important;
            padding: 12px 0 !important;
        }
        .tlabel { font-size: 0.85rem !important; }
        .tsub { font-size: 0.75rem !important; }

        .stat-grid {
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 8px !important;
        }
        .stat-box {
            padding: 12px 14px !important;
        }
        .stat-val { font-size: 1.4rem !important; }
        .stat-lbl { font-size: 0.65rem !important; }

        .act-row {
            padding: 12px 14px !important;
            gap: 10px !important;
        }
    }
/* ── Student settings redesign ─────────────────────────────────────────── */
.student-settings {
    --student-panel: rgba(28, 19, 14, 0.92);
    --student-panel-strong: rgba(20, 13, 10, 0.98);
    --student-line: rgba(207, 164, 111, 0.2);
    --student-space: clamp(16px, 2.3vw, 30px);
    max-width: 1320px;
    padding: 22px var(--student-space) 48px;
}
.student-settings .settings-command-header {
    min-height: 142px;
    padding: 24px 28px;
    border-radius: 24px;
    border-color: var(--student-line);
    background:
        radial-gradient(circle at 84% 15%, rgba(207, 164, 111, 0.17), transparent 24rem),
        linear-gradient(135deg, rgba(42, 26, 18, 0.97), rgba(18, 12, 9, 0.98));
    box-shadow: 0 18px 42px rgba(0, 0, 0, 0.34), inset 0 1px rgba(255, 255, 255, 0.08);
}
.student-settings .settings-command-header::after { display: none; }
.student-settings .settings-top-left { align-self: stretch; }
.student-settings .settings-avatar-chip {
    width: 68px;
    height: 68px;
    border-radius: 22px;
    padding: 3px;
    background: linear-gradient(145deg, var(--gold-bright), var(--gold-primary) 48%, #7f2b24);
}
.student-settings .settings-chip-avatar { border-radius: 19px; }
.student-settings .settings-chip-status { width: 16px; height: 16px; }
.student-settings .settings-breadcrumb-bar {
    padding: 4px 10px;
    margin-bottom: 7px;
    background: rgba(207, 164, 111, 0.1);
}
.student-settings .pg-title {
    font-size: clamp(1.55rem, 2vw, 2.15rem);
    letter-spacing: -0.045em;
}
.student-settings .pg-sub { max-width: 560px; line-height: 1.5; }
.student-settings .settings-top-right {
    align-items: center;
    gap: 12px;
    flex: 0 1 610px;
}
.student-settings .settings-search-box {
    min-width: min(100%, 260px);
    flex: 1 1 250px;
    border-radius: 14px;
    background: rgba(9, 6, 5, 0.56);
}
.student-settings .settings-command-telemetry {
    display: grid;
    grid-template-columns: repeat(3, minmax(96px, 1fr));
    gap: 8px;
    width: 100%;
}
.student-settings .telemetry-pill {
    min-height: 54px;
    padding: 9px 11px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.045);
    border: 1px solid rgba(255, 255, 255, 0.08);
}
.student-settings .telemetry-pill:hover { transform: translateY(-1px); background: rgba(255, 255, 255, 0.075); }
.student-settings .telemetry-lbl { font-size: 0.64rem; }
.student-settings .telemetry-val { font-size: 0.78rem; }

.student-settings .settings-layout-grid {
    display: grid;
    grid-template-columns: minmax(220px, 250px) minmax(0, 1fr);
    gap: 24px;
    align-items: start;
    margin-top: 2px;
}
.student-settings .settings-sidebar {
    position: sticky;
    top: 18px;
    padding: 10px;
    border: 1px solid var(--student-line);
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(29, 19, 14, 0.94), rgba(15, 10, 8, 0.94));
    box-shadow: 0 12px 28px rgba(0,0,0,.24);
}
.student-settings .settings-side-nav { gap: 8px; }
.student-settings .snav-group { padding: 7px 0; }
.student-settings .snav-group + .snav-group { border-top: 1px solid rgba(207, 164, 111, 0.12); }
.student-settings .snav-group-header {
    padding: 7px 9px 8px;
    color: var(--gold-primary);
    font-size: .67rem;
    letter-spacing: .09em;
}
.student-settings .snav-item {
    min-height: 58px;
    margin: 2px 0;
    padding: 9px 10px;
    border-radius: 13px;
    border: 1px solid transparent;
}
.student-settings .snav-item:hover { background: rgba(207, 164, 111, .08); border-color: rgba(207, 164, 111, .12); }
.student-settings .snav-item.active {
    background: linear-gradient(135deg, rgba(207, 164, 111, .18), rgba(143, 39, 31, .18));
    border-color: rgba(207, 164, 111, .34);
    box-shadow: inset 3px 0 var(--gold-primary);
}
.student-settings .snav-item-icon { width: 34px; height: 34px; border-radius: 10px; }
.student-settings .snav-item-title { font-size: .84rem; }
.student-settings .snav-item-desc { font-size: .68rem; line-height: 1.25; }
.student-settings .snav-footer-card {
    margin: 8px 2px 2px;
    padding: 14px;
    border-radius: 14px;
    background: rgba(207, 164, 111, .08);
    border: 1px solid rgba(207, 164, 111, .16);
}
.student-settings .settings-main-content { min-width: 0; }
.student-settings .spanel { animation: studentPanelIn .25s ease-out; }
@keyframes studentPanelIn { from { opacity: .35; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
.student-settings .sc,
.student-settings .sec-health-hero,
.student-settings .sec-card,
.student-settings .sec-group-panel {
    border-color: var(--student-line);
    background: linear-gradient(145deg, var(--student-panel), var(--student-panel-strong));
    box-shadow: 0 12px 28px rgba(0,0,0,.18), inset 0 1px rgba(255,255,255,.04);
}
.student-settings .sc { border-radius: 20px; overflow: hidden; }
.student-settings .sc + .sc { margin-top: 18px; }
.student-settings .sc-head { padding: 20px 22px 15px; border-bottom-color: rgba(207,164,111,.12); }
.student-settings .sc-body { padding: 22px; }
.student-settings .sc-title, .student-settings .sec-group-title { font-size: 1.03rem; }
.student-settings .sc-sub, .student-settings .sec-group-desc { line-height: 1.48; }
.student-settings .profile-card-inner {
    gap: 20px;
    padding: 4px;
}
.student-settings .profile-details-col { gap: 8px; }
.student-settings .profile-user-name { font-size: 1.25rem; }
.student-settings .academic-spec-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
.student-settings .academic-spec-card {
    min-height: 92px;
    padding: 14px;
    border-radius: 14px;
    background: rgba(255,255,255,.035);
    border: 1px solid rgba(255,255,255,.07);
}
.student-settings .academic-spec-card:hover { border-color: rgba(207,164,111,.26); background: rgba(207,164,111,.055); }
.student-settings .si { min-height: 46px; border-radius: 12px; }
.student-settings .sbtn, .student-settings .sec-action-btn { min-height: 44px; border-radius: 12px; }
.student-settings .flash-ok, .student-settings .flash-err { border-radius: 14px; }

.student-settings .sec-health-hero {
    margin-bottom: 14px;
    padding: 22px;
    border-radius: 20px;
}
.student-settings .sec-health-title { font-size: 1.15rem; }
.student-settings .sec-matrix-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(135px, 1fr));
    gap: 8px;
    padding: 10px;
    border-radius: 16px;
    background: rgba(0,0,0,.2);
    border: 1px solid rgba(207,164,111,.13);
}
.student-settings .sec-matrix-item {
    justify-content: flex-start;
    min-height: 42px;
    padding: 8px 10px;
    border-radius: 10px;
    background: rgba(255,255,255,.025);
}
.student-settings .sec-subnav-bar {
    position: sticky;
    top: 10px;
    z-index: 10;
    margin: 16px 0;
    padding: 7px;
    border-radius: 14px;
    background: rgba(18, 12, 9, .92);
    border: 1px solid var(--student-line);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
}
.student-settings .sec-subnav-pill { min-height: 39px; border-radius: 10px; }
.student-settings .sec-group-panel { margin-bottom: 18px; border-radius: 18px; padding: 20px; }
.student-settings .sec-group-header { margin-bottom: 18px; }
.student-settings .sec-cards-grid { gap: 14px; }
.student-settings .sec-card { border-radius: 16px; padding: 18px; }
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
.student-settings .cameraFaceEnrollment,
.student-settings #cameraFaceEnrollment { border-radius: 16px !important; }
.student-settings #cameraFacePreviewWrap { border-radius: 18px !important; }
.student-settings .device-item-card { border-radius: 13px; }

@media (max-width: 1100px) {
    .student-settings .settings-command-header { align-items: flex-start; }
    .student-settings .settings-top-right { flex: 1 1 100%; }
    .student-settings .settings-layout-grid { grid-template-columns: 210px minmax(0, 1fr); gap: 16px; }
    .student-settings .settings-sidebar { top: 10px; }
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
    .student-settings .stabs { gap: 8px; padding: 0 2px 8px; scrollbar-width: none; }
    .student-settings .stabs::-webkit-scrollbar { display: none; }
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
    .student-settings .stabs-arrow, .student-settings .stabs-floating-hint { display: none !important; }
    .student-settings .settings-layout-grid { display: block; margin-top: 0; }
    .student-settings .settings-sidebar { display: none; }
    .student-settings .sc { border-radius: 17px; }
    .student-settings .sc-head { padding: 17px 17px 13px; }
    .student-settings .sc-body { padding: 17px; }
    .student-settings .sec-health-hero { padding: 17px; border-radius: 17px; }
    .student-settings .sec-matrix-bar { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .student-settings .sec-subnav-bar { top: 58px; overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    .student-settings .sec-subnav-pill { flex: 0 0 auto; white-space: nowrap; }
    .student-settings .sec-group-panel { padding: 15px; border-radius: 16px; }
    .student-settings .sec-card { padding: 15px; }
}
@media (max-width: 575.98px) {
    .student-settings { padding-inline: 12px; }
    .student-settings .settings-command-header { padding: 16px; border-radius: 17px; }
    .student-settings .settings-command-telemetry { gap: 6px; }
    .student-settings .telemetry-pill { min-width: 0; }
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
    .student-settings .pref-tile { align-items: flex-start; gap: 12px; }
    .student-settings .pref-tile .form-check { padding-top: 4px; }
    .student-settings .pref-tile select { width: 100% !important; min-width: 0 !important; margin-top: 8px; }
    .student-settings .sbtn, .student-settings .sec-action-btn { width: 100%; justify-content: center; }
    .student-settings #cameraFacePreviewWrap { width: 100% !important; }
    .student-settings #tabDeviceBoundTile > div:last-child,
    .student-settings #tabDeviceBoundTile div[style*="display:flex;gap:10px"] { flex-direction: column; }
}
</style>

<div class="sp {{ $user?->isStudent() ? 'student-settings' : '' }}">

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

    @if(session('success'))
    <div class="flash-ok"><i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if($errors->any())
    <div class="flash-err"><i class="bi bi-exclamation-circle-fill fs-5"></i><span>{{ $errors->first() }}</span></div>
    @endif

    <!-- ── MOBILE / COMPACT HORIZONTAL TAB BAR (<992px) ── -->
    <div class="stabs-wrapper">
        <span id="stabsFloatingHint" class="stabs-floating-hint d-none" aria-hidden="true">Scrollable</span>
        <button type="button" class="stabs-arrow stabs-arrow-left" id="stabsArrowLeft" onclick="scrollStabs('left')" aria-label="Scroll left" style="display:none;">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="stabs" id="stabsNav">
            <button class="stab active" data-tab="profile" onclick="switchTab('profile',this)"><i class="bi bi-person-circle me-1"></i> Account</button>
            <button class="stab" data-tab="security" onclick="switchTab('security',this)"><i class="bi bi-shield-lock-fill me-1"></i> Security</button>
            @if(Auth::user()->isStudent())
            <button class="stab" data-tab="family" onclick="switchTab('family',this)"><i class="bi bi-people-fill me-1"></i> Guardians</button>
            @endif
            <button class="stab" data-tab="preferences" data-tab-id="preferences" onclick="switchTab('preferences',this)"><i class="bi bi-sliders me-1"></i> Preferences</button>
        </div>
        <button type="button" class="stabs-arrow stabs-arrow-right" id="stabsArrowRight" onclick="scrollStabs('right')" aria-label="Scroll right">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>

    <!-- ── MASTER-DETAIL LAYOUT GRID ── -->
    <div class="settings-layout-grid">
        <!-- ── MASTER SIDEBAR NAVIGATION (Desktop ≥992px) ── -->
        <aside class="settings-sidebar">
            <nav class="settings-side-nav" aria-label="Settings Categories">
                <!-- Group 1: Account -->
                <div class="snav-group">
                    <div class="snav-group-header">
                        <i class="bi bi-person-badge"></i>
                        <span>Account</span>
                    </div>
                    <button type="button" class="snav-item active" data-tab="profile" onclick="switchTab('profile', this)">
                        <span class="snav-item-icon"><i class="bi bi-person-circle"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Profile & identity</span>
                            <span class="snav-item-desc">Photo, contact number, school details</span>
                        </div>
                    </button>
                    @if(Auth::user()->isStudent())
                    <button type="button" class="snav-item" data-tab="family" onclick="switchTab('family', this)">
                        <span class="snav-item-icon"><i class="bi bi-people-fill"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Guardians</span>
                            <span class="snav-item-desc">Parent attendance access</span>
                        </div>
                    </button>
                    @endif
                </div>

                <!-- Group 2: Security & Authentication Controls -->
                <div class="snav-group">
                    <div class="snav-group-header">
                        <i class="bi bi-shield-lock"></i>
                        <span>Security &amp; Access</span>
                    </div>
                    <button type="button" class="snav-item" data-tab="security" onclick="switchTab('security', this)">
                        <span class="snav-item-icon"><i class="bi bi-shield-lock-fill"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Account security</span>
                            <span class="snav-item-desc">Password, email, and recovery</span>
                        </div>
                    </button>
                    <button type="button" class="snav-item snav-sub-item" data-tab="fingerprint" data-sec-sub="biometrics" onclick="switchTab('fingerprint', this)">
                        <span class="snav-item-icon"><i class="bi bi-fingerprint"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Face &amp; passkeys</span>
                            <span class="snav-item-desc">Camera enrollment and device sign-in</span>
                        </div>
                        <span class="snav-badge snav-badge-emerald">FIDO2</span>
                    </button>
                    <button type="button" class="snav-item snav-sub-item" data-tab="device" data-sec-sub="device" onclick="switchTab('device', this)">
                        <span class="snav-item-icon"><i class="bi bi-phone-fill"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Attendance device</span>
                            <span class="snav-item-desc">Device used to scan attendance</span>
                        </div>
                        <span class="snav-badge {{ $deviceBinding ? 'snav-badge-emerald' : 'snav-badge-amber' }}">{{ $deviceBinding ? 'Bound' : 'Action Req' }}</span>
                    </button>
                </div>

                <!-- Group 3: System Preferences -->
                <div class="snav-group">
                    <div class="snav-group-header">
                        <i class="bi bi-sliders"></i>
                        <span>System Preferences</span>
                    </div>
                    <button type="button" class="snav-item" data-tab="preferences" onclick="switchTab('preferences', this)">
                        <span class="snav-item-icon"><i class="bi bi-sliders"></i></span>
                        <div class="snav-item-body">
                            <span class="snav-item-title">Notifications &amp; app</span>
                            <span class="snav-item-desc">Alerts, display, and updates</span>
                        </div>
                    </button>
                </div>
            </nav>

            <!-- Sidebar Trust & Quick Status Footer Card -->
            <div class="snav-footer-card">
                <div class="snav-footer-header">
                    <span class="pulse-beacon"></span>
                    <span class="snav-footer-title">Account Security</span>
                </div>
                <div class="snav-footer-sub">Your password, device credentials, and attendance access are protected.</div>
            </div>
        </aside>

        <!-- ── MAIN CONTENT WORKSPACE ── -->
        <main class="settings-main-content">

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
        <div class="sc">
            <div class="sc-head">
                <div class="sc-icon" style="background:rgba(207,164,111,0.14);color:#cfa46f;"><i class="bi bi-person-lines-fill"></i></div>
                <div>
                    <div class="sc-title">Personal Information</div>
                    <div class="sc-sub">Update your contact details and emergency recovery info</div>
                </div>
            </div>
            <div class="sc-body">
                <form action="{{ route('settings.update') }}" method="POST" id="personalInfoForm">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="sl" for="phoneInput">Phone Number</label>
                            <div class="input-icon-wrap">
                                <i class="bi bi-telephone-fill input-icon-prefix"></i>
                                <input type="text"
                                       id="phoneInput"
                                       name="phone"
                                       class="si with-icon"
                                       style="padding-left: 44px !important;"
                                       value="{{ old('phone', Auth::user()->phone) }}"
                                       placeholder="+63 900 000 0000"
                                       maxlength="20">
                            </div>
                            <div class="mt-2 d-flex align-items-center gap-1" style="font-size:0.75rem;color:#b39b82;">
                                <i class="bi bi-shield-check text-warning"></i>
                                <span>Used for emergency guardian contact and two-factor account recovery.</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="submit" class="sbtn btn-gold" style="width:auto;padding:11px 28px;">
                            <i class="bi bi-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Academic Info -->
        <div class="sc">
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
                            <div class="info-val academic-spec-value" style="display:flex;align-items:center;justify-content:space-between;">
                                <span>{{ Auth::user()->student_number ?: 'Not Assigned' }}</span>
                                @if(Auth::user()->student_number)
                                <button type="button" class="sec-copy-btn" onclick="navigator.clipboard.writeText('{{ Auth::user()->student_number }}');if(typeof showToast==='function')showToast('Student ID copied!','info');" title="Copy Student ID">
                                    <i class="bi bi-clipboard"></i>
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
                        <div class="sec-group-title">Account Credentials &amp; Authentication</div>
                        <div class="sec-group-desc">Manage primary email for security OTP codes and salted bcrypt-12 encrypted password</div>
                    </div>
                </div>
            </div>

            <div class="sec-cards-grid">
                <!-- Card 1: Primary Email Address Management -->
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
                            <div class="sec-card-subtitle">Primary channel for portal alerts &amp; OTP security codes</div>
                        </div>
                    </div>

                    <div class="sec-card-content">
                        <div id="emailStep1">
                            <div class="sec-input-display mb-2">
                                <i class="bi bi-envelope-at-fill text-primary me-2"></i>
                                <span class="sec-input-val" id="displayUserEmail">{{ Auth::user()->email }}</span>
                                <button type="button" onclick="navigator.clipboard.writeText('{{ Auth::user()->email }}'); if(typeof showToast==='function') showToast('Email address copied!','info');" class="sec-copy-btn" title="Copy email">
                                    <i class="bi bi-clipboard"></i>
                                </button>
                            </div>
                            <div style="margin: 10px 0 12px;">
                                <label class="sl" style="font-size:0.75rem;margin-bottom:4px;color:#f3e7cd;display:block;">New Email Address (where OTP will be sent)</label>
                                <input type="email" id="inputNewEmail" class="si" placeholder="Enter new email address (e.g. name@gmail.com)" style="padding:9px 12px;font-size:0.84rem;width:100%;">
                            </div>
                            <p class="sec-card-hint">
                                A 6-digit security code will be sent to this email address to verify ownership before updating.
                            </p>
                            <button type="button" onclick="requestEmailOtp()" id="sendEmailOtpBtn" class="sec-action-btn sec-btn-blue">
                                <i class="bi bi-send-fill me-2"></i>Send Verification OTP
                            </button>
                        </div>

                        <div id="emailStep2" style="display:none;">
                            <div style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.25);color:#4ade80;border-radius:10px;padding:10px 12px;font-size:0.78rem;margin-bottom:12px;display:flex;align-items:center;gap:8px;">
                                <i class="bi bi-envelope-check-fill" style="font-size:1.05rem;"></i>
                                <span>Code sent to <strong id="emailSentDestination">{{ Auth::user()->email }}</strong></span>
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
                                <input type="email" name="new_email" id="confirmNewEmailInput" class="si" placeholder="name@example.com" style="margin-bottom:12px;padding:9px 12px;font-size:0.84rem;" required>
                                <div style="display:flex;gap:8px;">
                                    <button type="button" onclick="cancelEmailOtp()" class="cancel-btn" style="flex:0 0 auto;padding:8px 14px;font-size:0.8rem;">Cancel</button>
                                    <button type="button" class="sec-action-btn sec-btn-blue" style="flex:1;" onclick="collectEmailOtp(this)"><i class="bi bi-check2-circle me-1"></i>Confirm Email</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Password Authentication -->
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
                            <div class="sec-card-subtitle">Protected by salted bcrypt-12 hashing &amp; rate limits</div>
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
                                    <input type="password" name="password" id="spw1" class="si" placeholder="12+ chars, mixed case, number, symbol" minlength="12" style="padding:9px 12px;font-size:0.84rem;" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('spw1',this,event)" data-toggle-password="spw1" aria-controls="spw1" aria-label="Show password" title="Show password" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <label class="sl" style="font-size:0.72rem;margin-bottom:4px;">Confirm Password</label>
                                <div class="pw-wrap" style="margin-bottom:14px;">
                                    <input type="password" name="password_confirmation" id="spw2" class="si" placeholder="Repeat new password" minlength="12" style="padding:9px 12px;font-size:0.84rem;" required>
                                    <button type="button" class="eye-btn" onclick="togglePw('spw2',this,event)" data-toggle-password="spw2" aria-controls="spw2" aria-label="Show password confirmation" title="Show password confirmation" aria-pressed="false"><i class="bi bi-eye-slash"></i></button>
                                </div>
                                <div style="display:flex;gap:8px;">
                                    <button type="button" onclick="cancelOtp()" class="cancel-btn" style="flex:0 0 auto;padding:8px 14px;font-size:0.8rem;">Cancel</button>
                                    <button type="button" class="sec-action-btn sec-btn-amber" style="flex:1;" onclick="collectOtp(this)"><i class="bi bi-check2-circle me-1"></i>Update Password</button>
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
                            <div class="sc-title">Biometrics Verification</div>
                            <span class="sec-badge sec-badge-green"><i class="bi bi-patch-check-fill"></i> FIDO2 / WebAuthn Active</span>
                        </div>
                        <div class="sc-sub">Manage enterprise biometric authentication such as hardware fingerprint or facial recognition for instant passwordless login and verified clock-in</div>
                    </div>
                </div>
                <div class="sc-body">

                    @if(Auth::user()->isStudent())
                    <section id="cameraFaceEnrollment" aria-labelledby="cameraFaceEnrollmentTitle" style="margin:0 0 26px;padding:20px;border:1px solid rgba(14,165,233,.28);border-radius:16px;background:rgba(14,165,233,.045);">
                        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:14px;">
                            <i class="bi bi-camera-video" aria-hidden="true" style="font-size:1.35rem;color:#38bdf8;"></i>
                            <div>
                                <h3 id="cameraFaceEnrollmentTitle" style="font-size:1rem;font-weight:750;color:#f8fafc;margin:0 0 4px;">Register face for attendance</h3>
                                <p style="font-size:.82rem;color:#cbd5e1;margin:0;line-height:1.5;">Use your camera to capture one clear face. The image is checked for quality and stored encrypted for attendance matching; it is not saved as a public profile photo.</p>
                            </div>
                        </div>
                        <div id="cameraFacePreviewWrap" style="display:none;position:relative;overflow:hidden;width:min(100%,440px);aspect-ratio:4/3;margin:0 auto 14px;border-radius:14px;background:#080b12;border:1px solid rgba(56,189,248,.3);">
                            <video id="cameraFaceVideo" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;transform:scaleX(-1);"></video>
                            <div aria-hidden="true" style="position:absolute;inset:10% 25%;border:2px solid rgba(56,189,248,.9);border-radius:50%;box-shadow:0 0 0 999px rgba(0,0,0,.24);pointer-events:none;"></div>
                            <canvas id="cameraFaceCanvas" style="display:none;"></canvas>
                        </div>
                        <div id="cameraFaceStatus" role="status" aria-live="polite" style="min-height:1.3em;margin:0 0 10px;color:{{ $hasCameraFaceEnrollment ? '#34d399' : '#94a3b8' }};font-size:.82rem;">{{ $hasCameraFaceEnrollment ? 'A face profile is enrolled for attendance matching. Register a new capture to replace it.' : '' }}</div>
                        <div style="display:flex;gap:9px;flex-wrap:wrap;">
                            <button type="button" id="cameraFaceStartBtn" class="sbtn bio-primary-cta" style="background:linear-gradient(135deg,#0284c7,#0ea5e9);color:#fff;"><i class="bi bi-camera-video me-2"></i>Open camera</button>
                            <button type="button" id="cameraFaceCaptureBtn" class="sbtn bio-primary-cta" style="display:none;background:linear-gradient(135deg,#0284c7,#0ea5e9);color:#fff;"><i class="bi bi-camera me-2"></i>Capture face</button>
                            <button type="button" id="cameraFaceRetakeBtn" class="cancel-btn" style="display:none;"><i class="bi bi-arrow-repeat me-1"></i>Retake</button>
                            <button type="button" id="cameraFaceSaveBtn" class="sbtn bio-primary-cta" style="display:none;background:linear-gradient(135deg,#0284c7,#0ea5e9);color:#fff;"><i class="bi bi-shield-check me-2"></i>Register face</button>
                            <button type="button" id="cameraFaceCancelBtn" class="cancel-btn" style="display:none;">Cancel</button>
                        </div>
                    </section>
                    @endif

                    <!-- In-app browser & Insecure Context Alert -->
                    <div id="webauthnUnsupported" style="display:none;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);color:#f87171;border-radius:14px;padding:16px 20px;font-size:.85rem;margin-bottom:20px;">
                        <div style="display:flex;align-items:flex-start;gap:12px;">
                            <i class="bi bi-exclamation-triangle" style="font-size:1.2rem;flex-shrink:0;margin-top:2px;"></i>
                            <div>
                                <div style="font-weight:700;margin-bottom:4px;" id="unsupportedTitle">Biometric sensor not available on this browser</div>
                                <div id="webauthnUnsupportedMsg" style="font-size:.8rem;opacity:.9;line-height:1.5;">
                                    Your current browser or connection does not support hardware biometric sign-in.
                                </div>
                                <a id="openInBrowserBtn" href="#" onclick="openInSystemBrowser()" style="display:none;align-items:center;gap:6px;margin-top:10px;padding:8px 16px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);border-radius:8px;color:#fca5a5;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .2s;">
                                    <i class="bi bi-box-arrow-up-right"></i> Open in External Browser
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- ── Step 1: Method Selection ── -->
                    <div class="bio-flow-section">
                        <div class="bio-section-title-wrap">
                            <span class="bio-step-badge">Step 1</span>
                            <div>
                                <div class="bio-step-title">Choose Biometric Authentication Method</div>
                                <div class="bio-step-subtitle">Select ONE preferred biometric method to register for passwordless login and QR clock-in:</div>
                            </div>
                        </div>

                        <div class="bio-method-grid">
                            <!-- Option 1: Fingerprint -->
                            <div class="bio-method-card selected" id="methodCardFp" data-method="fingerprint" tabindex="0" role="button" aria-pressed="true" onclick="selectBiometricMethod('fingerprint')" onkeydown="if(event.key==='Enter'||event.key===' ')selectBiometricMethod('fingerprint')">
                                <div class="bio-card-radio">
                                    <div class="bio-radio-inner">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                </div>
                                <div class="bio-method-icon-wrap bio-fp-icon">
                                    <i class="bi bi-fingerprint"></i>
                                </div>
                                <div class="bio-method-info">
                                    <div class="bio-method-header">
                                        <span class="bio-method-name">Fingerprint</span>
                                        <span class="bio-status-pill not-reg" id="statusBadgeFingerprint">Checking...</span>
                                    </div>
                                    <p class="bio-method-desc">
                                        Use your built-in fingerprint sensor when configured. Your device may also ask for a PIN.
                                    </p>
                                    <div class="bio-method-meta">
                                        <span class="bio-spec-chip"><i class="bi bi-shield-check" style="color:#34d399;"></i> WebAuthn</span>
                                        <span class="bio-spec-chip"><i class="bi bi-cpu" style="color:#cfa46f;"></i> Device-protected key</span>
                                        <span class="bio-spec-chip"><i class="bi bi-lightning-charge" style="color:#34d399;"></i> User verified</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Option 2: Device face sign-in -->
                            <div class="bio-method-card" id="methodCardFace" data-method="face" tabindex="0" role="button" aria-pressed="false" onclick="selectBiometricMethod('face')" onkeydown="if(event.key==='Enter'||event.key===' ')selectBiometricMethod('face')">
                                <div class="bio-card-radio">
                                    <div class="bio-radio-inner">
                                        <i class="bi bi-check-lg"></i>
                                    </div>
                                </div>
                                <div class="bio-method-icon-wrap bio-face-icon">
                                    <i class="bi bi-person-bounding-box"></i>
                                </div>
                                <div class="bio-method-info">
                                    <div class="bio-method-header">
                                        <span class="bio-method-name">Device passkey (Face ID / Windows Hello)</span>
                                        <span class="bio-status-pill not-reg" id="statusBadgeFace">Checking...</span>
                                    </div>
                                    <p class="bio-method-desc">
                                        This enrolls a device passkey and uses whatever verification your device supports. For camera-based attendance face registration, use the camera section above.
                                    </p>
                                    <div class="bio-method-meta">
                                        <span class="bio-spec-chip"><i class="bi bi-eye" style="color:#38bdf8;"></i> On-device</span>
                                        <span class="bio-spec-chip"><i class="bi bi-patch-check" style="color:#cfa46f;"></i> Verified passkey</span>
                                        <span class="bio-spec-chip"><i class="bi bi-lock" style="color:#38bdf8;"></i> No face upload</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Step 2: Registration & Scanning Stage ── -->
                    <div class="bio-flow-section" style="margin-top:26px;">
                        <div class="bio-section-title-wrap">
                            <span class="bio-step-badge">Step 2</span>
                            <div>
                                <div class="bio-step-title" id="bioStep2Title">Biometric Sensor Capture</div>
                                <div class="bio-step-subtitle" id="bioStep2Subtitle">Selected: <strong id="selectedMethodDisplay" style="color:#34d399;">Fingerprint</strong> — Ready to register this device</div>
                            </div>
                        </div>

                        <!-- Scanner Stage Container -->
                        <div class="bio-scanner-card" id="bioScannerStage">

                            <!-- State A: Idle / Ready Overview -->
                            <div class="bio-stage-view" id="bioIdleView">
                                <div class="bio-preview-container">
                                    <!-- Fingerprint Idle Graphic -->
                                    <div id="fpIdlePreview" class="bio-method-preview active">
                                        <div class="bio-idle-sensor-ring">
                                            <div class="bio-radar-ring"></div>
                                            <div class="bio-radar-ring delay-1"></div>
                                            <i class="bi bi-fingerprint bio-idle-sensor-icon"></i>
                                        </div>
                                        <div class="bio-preview-title">Register Fingerprint Authentication</div>
                                        <div class="bio-preview-sub">Click the button below to start the hardware fingerprint enrollment for this account.</div>
                                    </div>

                                    <!-- Face Idle Graphic -->
                                    <div id="faceIdlePreview" class="bio-method-preview">
                                        <div class="bio-idle-face-box">
                                            <span class="hud-corner hud-tl"></span>
                                            <span class="hud-corner hud-tr"></span>
                                            <span class="hud-corner hud-bl"></span>
                                            <span class="hud-corner hud-br"></span>
                                            <div class="hud-face-reticle">
                                                <i class="bi bi-person-bounding-box bio-idle-face-icon"></i>
                                            </div>
                                        </div>
                                        <div class="bio-preview-title">Register a device passkey</div>
                                        <div class="bio-preview-sub">Confirm in your device prompt. Verification stays on the device; your device may offer a PIN.</div>
                                    </div>
                                </div>

                                <div class="bio-action-row">
                                    <button type="button" id="startBioBtn" class="sbtn btn-emerald bio-primary-cta">
                                        <i class="bi bi-fingerprint me-2"></i>Continue to Register Fingerprint
                                    </button>
                                    <span class="bio-hardware-note">
                                        <i class="bi bi-shield-check text-success me-1"></i>FIDO2 / WebAuthn Device Passkey Security
                                    </span>
                                </div>
                            </div>

                            <!-- State B: Active Live Scanning Stage -->
                            <div class="bio-stage-view" id="bioScanningView" style="display:none;">
                                <div class="bio-scan-hud-container">

                                    <!-- Fingerprint Realistic Scanner -->
                                    <div id="fpActiveScanner" class="bio-scanner-display active">
                                        <div class="fp-scan-frame">
                                            <div class="fp-pulse-wave"></div>
                                            <div class="fp-laser-line" id="fpLaserLine"></div>
                                            <!-- Concentric Authentic Dermal Ridge Vectors -->
                                            <svg class="fp-svg" viewBox="0 0 120 140" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path class="fp-ridge" d="M60 46 C53 46 48 52 48 62 C48 72 54 80 60 88 C66 80 72 72 72 62 C72 52 67 46 60 46" />
                                                <path class="fp-ridge" d="M60 36 C46 36 38 46 38 62 C38 82 48 98 56 110" />
                                                <path class="fp-ridge" d="M60 36 C74 36 82 46 82 62 C82 82 72 98 64 110" />
                                                <path class="fp-ridge" d="M60 26 C40 26 30 38 30 62 C30 92 42 110 50 122" />
                                                <path class="fp-ridge" d="M60 26 C80 26 90 38 90 62 C90 92 78 110 70 122" />
                                                <path class="fp-ridge" d="M60 16 C34 16 22 32 22 62 C22 98 36 120 46 132" />
                                                <path class="fp-ridge" d="M60 16 C86 16 98 32 98 62 C98 98 84 120 74 132" />
                                                <path class="fp-ridge" d="M60 7 C28 7 14 26 14 62 C14 104 30 128 42 138" />
                                                <path class="fp-ridge" d="M60 7 C92 7 106 26 106 62 C106 104 90 128 78 138" />
                                            </svg>
                                        </div>
                                        <div class="bio-scanning-title" id="fpScanningTitle">Touch Fingerprint Sensor</div>
                                        <div class="bio-scanning-sub" id="fpStatusSub">Place your finger on your device sensor or confirm the prompt</div>

                                        <!-- Progressive Scan Feedback -->
                                        <div class="bio-progress-container">
                                            <div class="bio-progress-track">
                                                <div class="bio-progress-fill" id="fpProgressFill" style="width: 25%;"></div>
                                            </div>
                                            <div class="bio-progress-labels">
                                                <span class="bio-progress-state" id="fpStateLabel">Waiting for sensor touch...</span>
                                                <span class="bio-progress-pct" id="fpPctLabel">Ready</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Device verification prompt; no camera capture occurs here. -->
                                    <div id="faceActiveScanner" class="bio-scanner-display">
                                        <div class="face-scan-frame" id="faceScanFrame">
                                            <span class="hud-corner hud-tl"></span>
                                            <span class="hud-corner hud-tr"></span>
                                            <span class="hud-corner hud-bl"></span>
                                            <span class="hud-corner hud-br"></span>
                                            <div class="face-holo-mesh"><i class="bi bi-person-check" aria-hidden="true"></i></div>
                                        </div>
                                        <div class="bio-scanning-title" id="faceScanningTitle">Waiting for Device Verification</div>
                                        <div class="bio-scanning-sub" id="faceStatusSub">Complete the protected prompt on your device. A PIN may be available.</div>
                                        <div class="bio-progress-container">
                                            <div class="bio-progress-track"><div class="bio-progress-fill cyan-fill" id="faceProgressFill" style="width: 0%;"></div></div>
                                            <div class="bio-progress-labels">
                                                <span class="bio-progress-state" id="faceStateLabel">Waiting for device prompt...</span>
                                                <span class="bio-progress-pct" id="facePctLabel">Ready</span>
                                            </div>
                                        </div>
                                    </div></div>

                                <div class="bio-cancel-row">
                                    <button type="button" onclick="cancelBiometricRegistration()" class="cancel-btn bio-cancel-btn">
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
                                    <div class="bio-success-title" id="bioSuccessTitle">Biometric Registered Successfully!</div>
                                    <div class="bio-success-desc" id="bioSuccessDesc">
                                        Your device passkey has been verified and linked to your account.
                                    </div>
                                    <div class="bio-success-actions">
                                        <button type="button" onclick="resetToSelectionStage()" class="sbtn btn-emerald" style="padding:10px 24px;">
                                            <i class="bi bi-check-lg me-1"></i>Done
                                        </button>
                                        <button type="button" onclick="switchOrRegisterOtherMethod()" class="cancel-btn" id="registerOtherBtn" style="padding:10px 20px;">
                                            <i class="bi bi-plus-circle me-1"></i>Register Other Method
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
                                        The biometric prompt was cancelled or timed out.
                                    </div>
                                    <div class="bio-error-actions">
                                        <button type="button" onclick="retryBiometricRegistration()" class="sbtn btn-emerald" id="retryBtn" style="padding:10px 24px;">
                                            <i class="bi bi-arrow-repeat me-1"></i>Try Again
                                        </button>
                                        <button type="button" onclick="switchBiometricMethodFallback()" class="cancel-btn" id="fallbackSwitchBtn" style="padding:10px 20px;">
                                            <i class="bi bi-arrow-left-right me-1"></i>Switch Method
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- ── Step 3: Registered Hardware Credentials List ── -->
                    <div style="margin-top:28px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:12px;">
                            <div style="font-size:.78rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;">Registered Hardware Credentials</div>
                            <span id="deviceCountBadge" style="font-size:.72rem;background:rgba(207,164,111,0.12);color:var(--gold,#cfa46f);padding:3px 10px;border-radius:99px;border:1px solid rgba(207,164,111,0.25);font-weight:700;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;">Loading...</span>
                        </div>
                        <div id="deviceList">
                            <div style="text-align:center;padding:32px 20px;color:#94a3b8;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:14px;border:1px dashed rgba(207,164,111,0.2);" id="noDevices">
                                <i class="bi bi-shield-lock" style="font-size:2.6rem;display:block;margin-bottom:10px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                                <div style="font-weight:700;color:#f8fafc;margin-bottom:4px;">No biometric credentials registered yet</div>
                                <div style="font-size:.78rem;color:#94a3b8;">Choose a device sign-in method above to register this device.</div>
                            </div>
                        </div>
                    </div>

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
                                <div class="sec-group-title">Attendance Device Binding &amp; Silicon Telemetry</div>
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
                                    <span class="sec-card-name">This Device (Current Hardware)</span>
                                    <span class="sec-badge sec-badge-blue" id="currentDeviceMatchBadge" style="display:none;">
                                        <i class="bi bi-shield-check"></i> Current Device
                                    </span>
                                </div>
                                <div class="sec-card-subtitle">Active browser and real-time silicon environment identity</div>
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
                        <div class="sec-group-title">Emergency Recovery Vault</div>
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
                            <span class="sec-card-name">Account Recovery Vault</span>
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
        <div class="sec-health-hero mb-4" style="background:linear-gradient(135deg, rgba(34,20,14,0.95) 0%, rgba(20,12,8,0.95) 100%);border-color:rgba(207,164,111,0.3);">
            <div class="sec-health-left">
                <div class="sec-health-icon" style="background:rgba(207,164,111,0.15);color:#f5dfa8;border:1px solid rgba(207,164,111,0.3);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <div class="sec-health-title">Family &amp; Guardian Connections</div>
                    <div class="sec-health-sub">Authorize family members to monitor your attendance records and receive absence notices</div>
                </div>
            </div>
            <div class="sec-health-pill {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'emerald' : '' }}">
                <span class="sec-pulse-dot" style="{{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'background:#34d399;' : 'background:#f59e0b;' }}"></span>
                <span>{{ (isset($linkedParents) && $linkedParents->count() > 0) ? $linkedParents->count() . ' Guardian(s) Linked' : 'No Guardians Linked' }}</span>
            </div>
        </div>

        <div class="sec-cards-grid">
            <!-- Card 1: Instant Parent Link Code Generator -->
            <div class="sec-card" style="--card-accent: #cfa46f;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(207,164,111,0.15);color:#f5dfa8;border:1px solid rgba(207,164,111,0.3);">
                        <i class="bi bi-upc-scan"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Instant Link Code</span>
                            <span class="sec-badge sec-badge-gold">
                                <i class="bi bi-lightning-charge-fill"></i> Zero Email Delay
                            </span>
                        </div>
                        <div class="sec-card-subtitle">Generate a 6-digit code or QR for your parent to scan</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    <div style="font-size:0.82rem;color:#b39b82;line-height:1.5;margin-bottom:14px;">
                        Generate a secure 6-digit code. Your parent can enter it in their Parent Portal under <strong>Link Student</strong> to instantly bind with zero waiting.
                    </div>

                    <!-- Code Display Card (Hidden until generated) -->
                    <div id="parentCodeDisplayCard" style="display:none;background:rgba(14,8,5,0.7);border:1.5px solid rgba(207,164,111,0.4);border-radius:16px;padding:20px 16px;margin-bottom:16px;text-align:center;">
                        <div style="font-size:0.75rem;font-weight:700;color:#cfa46f;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px;">Your 6-Digit Linking Code</div>
                        <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin:8px 0;">
                            <div id="parentCodeValue" style="font-size:2.2rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-weight:800;color:#f3e7cd;letter-spacing:8px;padding:6px 14px;background:rgba(255,255,255,0.05);border-radius:10px;border:1px solid rgba(207,164,111,0.25);">
                                ------
                            </div>
                            <button type="button" class="sbtn btn-gold" onclick="copyParentLinkCode(this)" style="padding:10px 16px;font-size:0.8rem;width:auto;" title="Copy Code">
                                <i class="bi bi-copy"></i>
                            </button>
                        </div>
                        <div id="parentCodeTimer" style="font-size:0.78rem;font-weight:700;color:#34d399;margin-bottom:14px;">
                            Valid for: 15:00
                        </div>

                        <!-- QR Code Box -->
                        <div style="display:inline-block;padding:12px;background:#ffffff;border-radius:14px;box-shadow:0 8px 24px rgba(0,0,0,0.5);margin:0 auto 10px;">
                            <div id="parentCodeQrBox" style="width:170px;height:170px;display:flex;align-items:center;justify-content:center;"></div>
                        </div>
                        <div style="font-size:0.74rem;color:#b39b82;">
                            Parents can scan this QR code with their mobile camera to link instantly.
                        </div>
                    </div>

                    <button type="button" id="generateParentCodeBtn" onclick="generateParentLinkCode()" class="sec-action-btn sec-btn-gold" style="width:100%;padding:12px;font-size:0.9rem;">
                        <i class="bi bi-key-fill me-1"></i> Generate Parent Link Code
                    </button>
                </div>
            </div>

            <!-- Card 2: Connected Guardians List -->
            <div class="sec-card" style="--card-accent: #10b981;">
                <div class="sec-card-top">
                    <div class="sec-card-icon" style="background:rgba(16,185,129,0.12);color:#34d399;border:1px solid rgba(16,185,129,0.25);">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="sec-card-meta">
                        <div class="sec-card-header-line">
                            <span class="sec-card-name">Authorized Guardians</span>
                            <span class="sec-badge {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'sec-badge-emerald' : 'sec-badge-amber' }}">
                                <i class="bi bi-patch-check-fill"></i> {{ (isset($linkedParents) && $linkedParents->count() > 0) ? 'Active' : 'Unlinked' }}
                            </span>
                        </div>
                        <div class="sec-card-subtitle">Parents and guardians with view-only attendance access</div>
                    </div>
                </div>

                <div class="sec-card-content">
                    @if(isset($linkedParents) && $linkedParents->isNotEmpty())
                        <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:12px;">
                            @foreach($linkedParents as $guardian)
                            <div class="info-row" id="guardian-item-{{ $guardian->id }}" style="padding:12px;background:rgba(255,255,255,0.02);border:1px solid rgba(207,164,111,0.15);border-radius:12px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
                                <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                                    <img src="{{ $guardian->profile_photo_url }}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1.5px solid rgba(207,164,111,0.4);">
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-weight:700;color:#f3e7cd;font-size:0.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $guardian->name }}</div>
                                        <div style="font-size:0.75rem;color:#b39b82;">
                                            <span>{{ $guardian->email }}</span>
                                            @if($guardian->phone)
                                                <span>• {{ $guardian->phone }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <span class="sec-badge sec-badge-green" style="font-size:0.74rem;">
                                    <i class="bi bi-shield-check"></i> Linked
                                </span>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div style="text-align:center;padding:26px 16px;background:rgba(255,255,255,0.02);border-radius:12px;border:1px dashed rgba(207,164,111,0.2);margin-bottom:12px;">
                            <i class="bi bi-person-x" style="font-size:2.2rem;color:rgba(207,164,111,0.35);display:block;margin-bottom:6px;"></i>
                            <div style="font-weight:700;color:#f3e7cd;font-size:0.9rem;margin-bottom:4px;">No Guardians Connected</div>
                            <div style="font-size:0.78rem;color:#b39b82;">Click "Generate Parent Link Code" to invite your guardian.</div>
                        </div>
                    @endif

                    <div style="background:rgba(207,164,111,0.06);border:1px solid rgba(207,164,111,0.15);border-radius:12px;padding:12px 14px;font-size:0.78rem;color:#e6dbce;line-height:1.45;">
                        <i class="bi bi-shield-lock-fill text-gold me-1"></i>
                        <strong>Privacy Safeguard:</strong> Guardians have view-only access to your attendance logs, late marks, and subject summaries. To protect attendance integrity, guardian links cannot be unlinked by students and can only be managed by your guardian or school administrator.
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
                                <span>Software Updates & PWA Assets</span>
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

        </main> <!-- /settings-main-content -->
    </div> <!-- /settings-layout-grid -->
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
        let result = null;
        if (typeof checkServerVersion === 'function') {
            result = await checkServerVersion(true, true);
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

        if (result && result.checkFailed) {
            feedback.style.background = 'rgba(239, 68, 68, 0.1)';
            feedback.style.border = '1px solid rgba(239, 68, 68, 0.3)';
            feedback.style.color = '#fca5a5';
            feedback.textContent = 'Unable to check for updates right now. Please try again when connected.';
        } else if (popupVisible || (result && result.updateAvailable)) {
            feedback.style.background = 'rgba(207, 164, 111, 0.12)';
            feedback.style.border = '1px solid rgba(207, 164, 111, 0.35)';
            feedback.style.color = '#f3e7cd';
            feedback.innerHTML = '<i class="bi bi-stars me-2"></i>A new software update is ready! Tap "Update Now" on the notification to use it.';
        } else {
            feedback.style.background = 'rgba(16, 185, 129, 0.1)';
            feedback.style.border = '1px solid rgba(16, 185, 129, 0.3)';
            feedback.style.color = '#6ee7b7';
            feedback.innerHTML = '<div style="display:flex; align-items:flex-start; gap:8px;"><i class="bi bi-check-circle-fill me-1" style="font-size:1.1rem; color:#22c55e;"></i><div><strong>You’re up to date ✓</strong><div style="font-size:0.85em; opacity:0.9; margin-top:2px;">Your system is running the deployed version (v{{ app(\App\Services\VersionService::class)->getInstalledVersion() }}).</div></div></div>';
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
    if (!nav || !leftBtn || !rightBtn) return;

    const maxScrollLeft = nav.scrollWidth - nav.clientWidth;

    if (maxScrollLeft <= 4) {
        leftBtn.style.display = 'none';
        rightBtn.style.display = 'none';
        return;
    }

    // Show left arrow only when scrolled away from start
    if (nav.scrollLeft > 6) {
        leftBtn.style.display = 'flex';
    } else {
        leftBtn.style.display = 'none';
    }

    // Show right arrow only when there is more content to scroll to on the right
    if (nav.scrollLeft < maxScrollLeft - 6) {
        rightBtn.style.display = 'flex';
        rightBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
        rightBtn.setAttribute('title', 'Scroll right');
    } else {
        rightBtn.style.display = 'none';
    }
}

window.scrollStabs = function(direction) {
    const nav = document.getElementById('stabsNav');
    if (!nav) return;
    const distance = 160;
    nav.scrollBy({
        left: direction === 'right' ? distance : -distance,
        behavior: 'smooth'
    });
    setTimeout(updateStabsScrollArrows, 200);
};

window.switchSecuritySub = function(subKey, btn) {
    if (!subKey) subKey = 'all';
    if (subKey === 'fingerprint') subKey = 'biometrics';

    // 1. Ensure parent tab-security panel is active
    const secPanel = document.getElementById('tab-security');
    if (secPanel && !secPanel.classList.contains('active')) {
        document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
        secPanel.classList.add('active');
        document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
        const secStab = document.querySelector('.stab[data-tab="security"]');
        if (secStab) secStab.classList.add('active');
    }

    // 2. Update subnav filter pills
    document.querySelectorAll('.sec-subnav-pill').forEach(pill => {
        if (pill.getAttribute('data-sec-sub') === subKey) {
            pill.classList.add('active');
        } else {
            pill.classList.remove('active');
        }
    });

    // 3. Update sidebar active status
    document.querySelectorAll('.snav-item').forEach(b => b.classList.remove('active'));
    if (subKey === 'biometrics') {
        const item = document.querySelector('.snav-item[data-tab="fingerprint"]') || document.querySelector('.snav-item[data-sec-sub="biometrics"]');
        if (item) item.classList.add('active');
    } else if (subKey === 'device') {
        const item = document.querySelector('.snav-item[data-tab="device"]') || document.querySelector('.snav-item[data-sec-sub="device"]');
        if (item) item.classList.add('active');
    } else {
        const secNav = document.querySelector('.snav-item[data-tab="security"]');
        if (secNav) secNav.classList.add('active');
    }

    // 4. Show/hide security group panels
    const sections = {
        credentials: document.getElementById('sec-section-credentials'),
        biometrics: document.getElementById('sec-section-biometrics'),
        device: document.getElementById('sec-section-device'),
        recovery: document.getElementById('sec-section-recovery')
    };

    if (subKey === 'all') {
        Object.values(sections).forEach(s => { if (s) s.style.display = 'block'; });
    } else {
        Object.entries(sections).forEach(([key, section]) => {
            if (!section) return;
            if (key === subKey) {
                section.style.display = 'block';
            } else {
                section.style.display = 'none';
            }
        });
    }

    // 5. Special handlers for biometrics or device sub-sections
    if (subKey === 'biometrics' || subKey === 'all') {
        if (typeof loadDevices === 'function') loadDevices();
        if (typeof prefetchWebAuthn === 'function') prefetchWebAuthn();
    }
    if (subKey === 'device' || subKey === 'all') {
        if (typeof checkDeviceBindingStatus === 'function') checkDeviceBindingStatus();
    }

    // 6. Update breadcrumbs
    if (typeof window.updateSettingsBreadcrumbs === 'function') {
        if (subKey === 'biometrics') window.updateSettingsBreadcrumbs('fingerprint');
        else if (subKey === 'device') window.updateSettingsBreadcrumbs('device');
        else window.updateSettingsBreadcrumbs('security', subKey);
    }

    // 7. Update URL hash
    if (window.history && window.history.replaceState) {
        let hashTarget = 'security';
        if (subKey === 'biometrics') hashTarget = 'fingerprint';
        else if (subKey === 'device') hashTarget = 'device';
        else if (subKey !== 'all') hashTarget = 'security-' + subKey;
        window.history.replaceState(null, null, '#tab-' + hashTarget);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');

    // 8. If switching to a specific sub-section, scroll smoothly to it
    if (subKey !== 'all' && sections[subKey]) {
        try {
            sections[subKey].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } catch (e) {}
    }
};

window.switchTab = function(id, btn) {
    if (!id) return;
    if (id === 'biometrics') id = 'fingerprint';

    // If target is biometric sensors or device binding, route into unified Security section
    if (id === 'fingerprint' || id === 'device') {
        document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
        const secPanel = document.getElementById('tab-security');
        if (secPanel) secPanel.classList.add('active');

        document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
        const secStab = document.querySelector('.stab[data-tab="security"]');
        if (secStab) {
            secStab.classList.add('active');
            if (typeof secStab.scrollIntoView === 'function') {
                secStab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
        }

        const subKey = (id === 'fingerprint') ? 'biometrics' : 'device';
        window.switchSecuritySub(subKey, btn);
        setTimeout(updateStabsScrollArrows, 300);
        return;
    }

    // Standard primary tab switching (profile, security, family, preferences)
    document.querySelectorAll('.spanel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.snav-item').forEach(b => b.classList.remove('active'));

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
        if (typeof targetBtn.scrollIntoView === 'function') {
            targetBtn.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }
    }

    let targetNavItem = (btn && btn.classList && btn.classList.contains('snav-item')) ? btn : document.querySelector(`.snav-item[data-tab="${id}"]`);
    if (targetNavItem) {
        targetNavItem.classList.add('active');
    }

    if (id === 'security') {
        window.switchSecuritySub('all');
    }

    if (typeof window.updateSettingsBreadcrumbs === 'function') {
        window.updateSettingsBreadcrumbs(id);
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
    if (window.history && window.history.replaceState) {
        window.history.replaceState(null, null, '#tab-' + id);
    }
    setTimeout(updateStabsScrollArrows, 300);
};

window.updateSettingsBreadcrumbs = function(tabId, secSub) {
    const meta = {
        'profile': { cat: 'Account', name: 'Profile & Identity' },
        'family': { cat: 'Account', name: 'Family & Guardian' },
        'security': { cat: 'Security & Access', name: 'Security & Authentication Controls' },
        'fingerprint': { cat: 'Security & Access', name: 'Biometrics Verification (FIDO2)' },
        'device': { cat: 'Security & Access', name: 'Device Binding & Telemetry' },
        'preferences': { cat: 'System & Academics', name: 'System Preferences' }
    };
    let info = meta[tabId] || { cat: 'Settings', name: tabId.charAt(0).toUpperCase() + tabId.slice(1) };
    if (tabId === 'security' && secSub && secSub !== 'all') {
        if (secSub === 'credentials') info = { cat: 'Security & Access', name: 'Password & Email Credentials' };
        else if (secSub === 'biometrics') info = { cat: 'Security & Access', name: 'Biometrics Verification (FIDO2)' };
        else if (secSub === 'device') info = { cat: 'Security & Access', name: 'Device Binding & Telemetry' };
        else if (secSub === 'recovery') info = { cat: 'Security & Access', name: 'Emergency Recovery Vault' };
    }
    const catEl = document.getElementById('settingsBreadcrumbCategory');
    const tabEl = document.getElementById('settingsBreadcrumbTab');
    if (catEl) catEl.textContent = info.cat;
    if (tabEl) tabEl.textContent = info.name;
};

// ── Attendance Device Binding API Handlers ──
window.deviceBindingExists = @json((bool) $deviceBinding);
window.handleBindCurrentDevice = async function(stepUpPassword = null) {
    if (window.deviceBindingExists && !stepUpPassword) {
        stepUpPassword = prompt('Enter your account password to switch or re-verify this device:');
        if (!stepUpPassword) return;
    }

    const bindBtns = document.querySelectorAll('#tabDeviceBindBtn, #secQuickBindBtn');
    bindBtns.forEach(b => {
        b.disabled = true;
        b._origHtml = b.innerHTML;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Authorizing...';
    });

    try {
        let devKey = (typeof window.getOrCreateDeviceKey === 'function')
            ? window.getOrCreateDeviceKey()
            : (localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid') || '');
        let devFp = localStorage.getItem('saas_device_fp') || '';
        let devModel = '';
        let devMeta = (typeof window.getDeviceTelemetry === 'function') ? window.getDeviceTelemetry() : {};

        if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceId === 'function') {
            devKey = window.AndroidDeviceBridge.getNativeDeviceId();
        } else if (window.AndroidBridge && typeof window.AndroidBridge.getNativeDeviceId === 'function') {
            devKey = window.AndroidBridge.getNativeDeviceId();
        }
        if (window.AndroidBridge && typeof window.AndroidBridge.getNativeDeviceModel === 'function') {
            devModel = window.AndroidBridge.getNativeDeviceModel();
        }
        if (!devFp && typeof window.getDeviceFingerprint === 'function') {
            devFp = window.getDeviceFingerprint();
        }
        if (!devModel && typeof window.getDeviceModel === 'function') {
            devModel = window.getDeviceModel();
        }

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        const resp = await fetch('{{ route("device.bind") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Device-Key': devKey,
                'X-Device-Fingerprint': devFp,
                'X-Device-Model': devModel,
                'X-Device-Metadata': JSON.stringify(devMeta),
            },
            body: JSON.stringify({
                device_key: devKey,
                device_fingerprint: devFp,
                device_model: devModel,
                device_metadata: devMeta,
                password: stepUpPassword,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            updateDeviceBindingUI(data);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Device bound successfully!', 'success');
            } else {
                alert(data.message || 'Device bound successfully!');
            }
            if (window.triggerHaptic) window.triggerHaptic('success');
        } else {
            const err = data.message || 'Failed to bind device. Please try again.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Device bind error:', err);
        if (typeof showToast === 'function') showToast('Network or server error while binding device.', 'error');
        else alert('Network error while binding device.');
    } finally {
        bindBtns.forEach(b => {
            b.disabled = false;
            if (b._origHtml) b.innerHTML = b._origHtml;
        });
    }
};

window.handleLockDevice = async function() {
    if (!confirm('Freeze attendance for this device? Attendance clock-ins will be locked until you enter your password to unlock it.')) {
        return;
    }

    const lockBtn = document.getElementById('tabDeviceLockBtn');
    if (lockBtn) {
        lockBtn.disabled = true;
        lockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Locking...';
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const resp = await fetch('{{ route("device.lock") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                reason: 'Student emergency anti-theft freeze',
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            else alert(data.message);
            window.checkDeviceBindingStatus();
        } else {
            const err = data.message || 'Failed to lock device.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Lock error:', err);
        if (typeof showToast === 'function') showToast('Network error while locking device.', 'error');
    } finally {
        if (lockBtn) {
            lockBtn.disabled = false;
            lockBtn.innerHTML = '<i class="bi bi-lock-fill me-1"></i> Freeze / Lock';
        }
    }
};

window.handleUnlockDevice = async function() {
    const pw = prompt('Enter your account password to unlock attendance for this device:');
    if (!pw) return;

    const unlockBtn = document.getElementById('tabDeviceUnlockBtn');
    if (unlockBtn) {
        unlockBtn.disabled = true;
        unlockBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Unlocking...';
    }

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const resp = await fetch('{{ route("device.unlock") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                password: pw,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            if (typeof showToast === 'function') showToast(data.message, 'success');
            else alert(data.message);
            window.checkDeviceBindingStatus();
        } else {
            const err = data.message || 'Incorrect password or failed to unlock.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Unlock error:', err);
        if (typeof showToast === 'function') showToast('Network error while unlocking device.', 'error');
    } finally {
        if (unlockBtn) {
            unlockBtn.disabled = false;
            unlockBtn.innerHTML = '<i class="bi bi-unlock-fill me-1"></i> Unlock Device';
        }
    }
};

window.handleUnbindDevice = async function() {
    if (!confirm('Are you sure you want to unbind this device? You will need to re-bind a device to record classroom attendance.')) {
        return;
    }

    const password = prompt('Enter your account password to unbind the device:');
    if (!password) return;

    const unbindBtns = document.querySelectorAll('#tabDeviceUnbindBtn, #secQuickUnbindBtn');
    unbindBtns.forEach(b => {
        b.disabled = true;
        b._origHtml = b.innerHTML;
        b.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Unbinding...';
    });

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

        const resp = await fetch('{{ route("device.unbind") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({
                password: password,
                _token: token,
            }),
        });

        const data = await resp.json();
        if (resp.ok && data.success) {
            updateDeviceBindingUI(data);
            if (typeof showToast === 'function') {
                showToast(data.message || 'Device unbound successfully.', 'success');
            } else {
                alert(data.message || 'Device unbound successfully.');
            }
            if (window.triggerHaptic) window.triggerHaptic('medium');
        } else {
            const err = data.message || 'Failed to unbind device. Please try again.';
            if (typeof showToast === 'function') showToast(err, 'error');
            else alert(err);
        }
    } catch (err) {
        console.error('Device unbind error:', err);
        if (typeof showToast === 'function') showToast('Network or server error while unbinding device.', 'error');
        else alert('Network error while unbinding device.');
    } finally {
        unbindBtns.forEach(b => {
            b.disabled = false;
            if (b._origHtml) b.innerHTML = b._origHtml;
        });
    }
};

function updateDeviceBindingUI(data) {
    const isBound = !!data.is_bound;
    window.deviceBindingExists = isBound;
    const isCurrent = !!data.is_current_device;
    const binding = data.binding;

    // 1. Update Security Tab Quick Card
    const secBadge = document.getElementById('secCardDeviceBadge');
    const secBadgeText = document.getElementById('secCardDeviceBadgeText');
    const secName = document.getElementById('secCardDeviceName');
    const secIcon = document.getElementById('secCardDeviceIcon');
    const secHint = document.getElementById('secCardDeviceHint');
    const secBindBtnText = document.getElementById('secQuickBindBtnText');
    const secUnbindBtn = document.getElementById('secQuickUnbindBtn');

    if (isBound && binding) {
        if (secBadge) {
            secBadge.className = 'sec-badge ' + (binding.is_locked ? 'sec-badge-danger' : (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold'));
        }
        if (secBadgeText) {
            secBadgeText.textContent = binding.is_locked ? 'Device Locked' : (isCurrent ? 'Bound to This Device' : 'Bound to Other Device');
        }
        if (secName) secName.textContent = binding.device_name || 'Registered Device';
        if (secIcon) secIcon.className = 'bi ' + (binding.device_icon || 'bi-phone') + (binding.is_locked ? ' text-danger me-2' : ' text-success me-2');
        if (secHint) {
            secHint.innerHTML = `Bound device IP: <strong style="color:#f3e7cd;">${binding.ip_address || 'Unknown'}</strong> • Active ${binding.last_seen_human || 'Recently'}.`;
        }
        if (secBindBtnText) secBindBtnText.textContent = isCurrent ? 'Re-verify This Device' : 'Switch / Re-bind to This Device';
        if (secUnbindBtn) secUnbindBtn.style.display = 'inline-flex';
    } else {
        if (secBadge) secBadge.className = 'sec-badge';
        if (secBadgeText) secBadgeText.textContent = 'No Device Bound';
        if (secName) secName.textContent = 'No device currently registered';
        if (secIcon) secIcon.className = 'bi bi-phone text-muted me-2';
        if (secHint) secHint.textContent = 'Bind your smartphone, tablet, or laptop to unlock anti-proxy verified QR attendance clock-ins.';
        if (secBindBtnText) secBindBtnText.textContent = 'Bind to This Device';
        if (secUnbindBtn) secUnbindBtn.style.display = 'none';
    }

    // 2. Update Device Tab Full Page
    const heroPill = document.getElementById('deviceHeroPill');
    const heroPillText = document.getElementById('deviceHeroPillText');
    const trustScorePill = document.getElementById('deviceTrustScorePill');
    const trustScoreText = document.getElementById('deviceTrustScoreText');
    const lockedAlert = document.getElementById('deviceLockedAlert');
    const lockedReasonText = document.getElementById('deviceLockedReasonText');

    const boundStatusTile = document.getElementById('deviceTabBoundTile');
    const unboundStatusTile = document.getElementById('deviceTabUnboundTile');
    const boundDeviceName = document.getElementById('deviceTabBoundName');
    const boundDeviceIp = document.getElementById('deviceTabBoundIp');
    const boundDeviceChanges = document.getElementById('deviceTabBoundChanges');
    const boundDeviceTime = document.getElementById('deviceTabBoundTime');
    const boundDeviceIcon = document.getElementById('deviceTabBoundIcon');
    const boundDeviceTrust = document.getElementById('deviceTabBoundTrust');
    const tabBindBtnText = document.getElementById('tabDeviceBindBtnText');
    const tabUnbindBtn = document.getElementById('tabDeviceUnbindBtn');
    const tabLockBtn = document.getElementById('tabDeviceLockBtn');
    const currentDeviceMatchBadge = document.getElementById('currentDeviceMatchBadge');
    const deviceTabAuthorizedBadge = document.getElementById('deviceTabAuthorizedBadge');
    const deviceTabAuthorizedBadgeText = document.getElementById('deviceTabAuthorizedBadgeText');

    if (isBound && binding) {
        const isLocked = !!binding.is_locked;
        if (heroPill) {
            heroPill.className = 'sec-health-pill ' + (isLocked ? 'gold' : (isCurrent ? 'emerald' : 'gold'));
        }
        if (heroPillText) {
            heroPillText.textContent = isLocked ? 'Device Locked' : (isCurrent ? 'Bound to This Device' : 'Bound to Other Hardware');
        }

        if (trustScorePill) {
            trustScorePill.style.display = 'inline-flex';
            if (trustScoreText) {
                trustScoreText.textContent = `Trust Score: ${binding.trust_score || 85}/100 • ${binding.trust_level || 'VERIFIED'}`;
            }
        }

        if (lockedAlert) {
            lockedAlert.style.display = isLocked ? 'flex' : 'none';
            if (lockedReasonText && binding.locked_reason) {
                lockedReasonText.textContent = `Reason: ${binding.locked_reason}`;
            }
        }

        if (boundStatusTile) boundStatusTile.style.display = 'block';
        if (unboundStatusTile) unboundStatusTile.style.display = 'none';
        if (boundDeviceName) boundDeviceName.textContent = binding.device_name || 'Registered Attendance Device';
        if (boundDeviceIp) boundDeviceIp.textContent = binding.ip_address || 'Unknown IP';
        if (boundDeviceChanges) boundDeviceChanges.textContent = `(${binding.change_count || 0} switches)`;
        if (boundDeviceTime) boundDeviceTime.textContent = binding.last_seen_human || 'Just now';
        if (boundDeviceIcon) boundDeviceIcon.className = 'bi ' + (binding.device_icon || 'bi-phone');
        if (boundDeviceTrust) {
            boundDeviceTrust.textContent = `${binding.trust_level || 'VERIFIED'} (${binding.trust_score || 85}/100)`;
            boundDeviceTrust.style.color = isLocked ? '#f87171' : ((binding.trust_score || 85) >= 80 ? '#34d399' : '#fbbf24');
        }

        if (deviceTabAuthorizedBadge) {
            deviceTabAuthorizedBadge.className = 'sec-badge ' + (isLocked ? 'sec-badge-danger' : (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold'));
        }
        if (deviceTabAuthorizedBadgeText) {
            deviceTabAuthorizedBadgeText.textContent = isLocked ? 'Locked' : (isCurrent ? 'Authorized (This Device)' : 'Authorized (Other Device)');
        }

        if (tabBindBtnText) tabBindBtnText.textContent = isCurrent ? 'Re-verify Current Device' : 'Switch & Bind This Device';
        if (tabUnbindBtn) tabUnbindBtn.style.display = 'inline-flex';
        if (tabLockBtn) tabLockBtn.style.display = isLocked ? 'none' : 'inline-flex';

        if (currentDeviceMatchBadge) {
            currentDeviceMatchBadge.style.display = 'inline-flex';
            currentDeviceMatchBadge.className = 'sec-badge ' + (isCurrent ? 'sec-badge-emerald' : 'sec-badge-gold');
            currentDeviceMatchBadge.innerHTML = isCurrent 
                ? '<i class="bi bi-check-circle-fill me-1"></i> Authorized Device' 
                : '<i class="bi bi-exclamation-triangle-fill me-1"></i> Unmatched Device';
        }
    } else {
        if (heroPill) heroPill.className = 'sec-health-pill';
        if (heroPillText) heroPillText.textContent = 'Device Not Bound';
        if (trustScorePill) trustScorePill.style.display = 'none';
        if (lockedAlert) lockedAlert.style.display = 'none';
        if (boundStatusTile) boundStatusTile.style.display = 'none';
        if (unboundStatusTile) unboundStatusTile.style.display = 'block';
        if (deviceTabAuthorizedBadge) deviceTabAuthorizedBadge.className = 'sec-badge';
        if (deviceTabAuthorizedBadgeText) deviceTabAuthorizedBadgeText.textContent = 'Unregistered';
        if (tabBindBtnText) tabBindBtnText.textContent = 'Bind to This Device';
        if (tabUnbindBtn) tabUnbindBtn.style.display = 'none';
        if (currentDeviceMatchBadge) {
            currentDeviceMatchBadge.style.display = 'none';
        }
    }
}

window.checkDeviceBindingStatus = async function() {
    try {
        if (typeof window.getDeviceTelemetry === 'function') {
            const telemetry = window.getDeviceTelemetry();
            const gpuEl = document.getElementById('thisDeviceGpuText');
            if (gpuEl && telemetry.gpu_renderer) {
                let cleaned = telemetry.gpu_renderer.replace(/^ANGLE \([^,]+,\s*/i, '').replace(/\s+Direct3D.+$/i, '');
                gpuEl.textContent = cleaned || telemetry.gpu_renderer;
            } else if (gpuEl) {
                gpuEl.textContent = 'Standard Web Graphics Engine';
            }

            const dispEl = document.getElementById('thisDeviceDisplayCores');
            if (dispEl) {
                dispEl.textContent = `${telemetry.screen_res || '1080p'} @ ${telemetry.pixel_ratio || 1}x • ${telemetry.cores || 4} Logical Cores`;
            }

            const netEl = document.getElementById('thisDeviceNetworkText');
            if (netEl && telemetry.connection_type && telemetry.connection_type !== 'unknown') {
                netEl.textContent += ` (${telemetry.connection_type.toUpperCase()})`;
            }
        }

        if (typeof window.getDeviceModel === 'function') {
            const dm = window.getDeviceModel();
            const el = document.getElementById('thisDeviceModelText');
            if (el && dm) el.textContent = dm;
        }

        const resp = await fetch('{{ route("device.status") }}', {
            headers: { 'Accept': 'application/json' }
        });
        if (resp.ok) {
            const data = await resp.json();
            if (data.success) {
                updateDeviceBindingUI(data);
            }
        }
    } catch (e) {
        console.warn('Device status check error:', e);
    }
};


async function toggleWebPush(input) {
    if (input.checked) {
        const ok = await WebPushManager.subscribe();
        if (!ok) input.checked = false;
    } else {
        await WebPushManager.unsubscribe();
    }
}
function togglePw(id, btn, e) {
    if (e) {
        if (e._pwToggled) return;
        e._pwToggled = true;
        if (e.preventDefault) e.preventDefault();
        if (e.stopPropagation) e.stopPropagation();
    }
    if (window.togglePassword) {
        window.togglePassword(id, btn, e);
        return;
    }
    const i = typeof id === 'string' ? document.getElementById(id) : id;
    if (!i) return;

    let start = null;
    let end = null;
    try {
        start = i.selectionStart;
        end = i.selectionEnd;
    } catch (err) {}

    const isPw = i.type === 'password';
    i.type = isPw ? 'text' : 'password';
    const ic = btn ? btn.querySelector('i') : null;
    if (ic) ic.className = isPw ? 'bi bi-eye' : 'bi bi-eye-slash';
    if (btn) {
        btn.style.color = isPw ? '#800000' : '';
        const isConf = i.name === 'password_confirmation' || i.id === 'spw2';
        const label = isPw 
            ? (isConf ? 'Hide password confirmation' : 'Hide password')
            : (isConf ? 'Show password confirmation' : 'Show password');
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        btn.setAttribute('aria-pressed', isPw ? 'true' : 'false');
    }

    try {
        i.focus();
        if (start !== null && end !== null) {
            i.setSelectionRange(start, end);
        }
    } catch (err) {}
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

// ── WebAuthn Device Biometrics (Fingerprint & Face ID / Windows Hello) ──
let selectedBioMethod = 'fingerprint'; // 'fingerprint' | 'face'
let bioAbortController = null;
let bioScanProgressTimer = null;

function selectBiometricMethod(method) {
    if (method !== 'fingerprint' && method !== 'face') return;
    selectedBioMethod = method;

    // 1. Toggle option card active styling & radio state
    const cardFp = document.getElementById('methodCardFp') || document.getElementById('methodCardFingerprint');
    const cardFace = document.getElementById('methodCardFace');
    if (cardFp && cardFace) {
        if (method === 'fingerprint') {
            cardFp.classList.add('selected');
            cardFp.setAttribute('aria-pressed', 'true');
            cardFace.classList.remove('selected');
            cardFace.setAttribute('aria-pressed', 'false');
        } else {
            cardFace.classList.add('selected');
            cardFace.setAttribute('aria-pressed', 'true');
            cardFp.classList.remove('selected');
            cardFp.setAttribute('aria-pressed', 'false');
        }
    }

    // 2. Smoothly switch idle preview graphic
    const fpPrev = document.getElementById('fpIdlePreview');
    const facePrev = document.getElementById('faceIdlePreview');
    if (fpPrev && facePrev) {
        if (method === 'fingerprint') {
            fpPrev.classList.add('active');
            facePrev.classList.remove('active');
        } else {
            facePrev.classList.add('active');
            fpPrev.classList.remove('active');
        }
    }

    // 3. Switch active scanner views
    const fpScan = document.getElementById('fpActiveScanner');
    const faceScan = document.getElementById('faceActiveScanner');
    if (fpScan && faceScan) {
        if (method === 'fingerprint') {
            fpScan.classList.add('active');
            faceScan.classList.remove('active');
        } else {
            faceScan.classList.add('active');
            fpScan.classList.remove('active');
        }
    }

    // 4. Update Step 2 title & CTA button text
    const displaySpan = document.getElementById('selectedMethodDisplay');
    const startBtn = document.getElementById('startBioBtn');
    if (displaySpan) {
        if (method === 'fingerprint') {
            displaySpan.textContent = 'Fingerprint';
            displaySpan.style.color = '#4ade80';
        } else {
            displaySpan.textContent = 'Face ID / Windows Hello';
            displaySpan.style.color = '#38bdf8';
        }
    }
    if (startBtn) {
        if (method === 'fingerprint') {
            startBtn.innerHTML = '<i class="bi bi-fingerprint me-2"></i>Continue to Register Fingerprint';
            startBtn.className = 'sbtn btn-emerald bio-primary-cta';
            startBtn.style.background = '';
            startBtn.style.color = '';
        } else {
            startBtn.innerHTML = '<i class="bi bi-person-bounding-box me-2"></i>Register Device Passkey';
            startBtn.className = 'sbtn bio-primary-cta';
            startBtn.style.background = 'linear-gradient(135deg, #0284c7, #0ea5e9)';
            startBtn.style.color = '#ffffff';
        }
    }

    // 5. Reset scanner stage back to idle view if error or success was shown
    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    if (idleView && (successView?.style.display !== 'none' || errorView?.style.display !== 'none')) {
        idleView.style.display = 'block';
        if (scanningView) scanningView.style.display = 'none';
        if (successView) successView.style.display = 'none';
        if (errorView) errorView.style.display = 'none';
    }

    if (window.triggerHaptic) window.triggerHaptic('light');
}

function updateProgressiveFeedback(pct, stateText) {
    if (selectedBioMethod === 'fingerprint') {
        const fill = document.getElementById('fpProgressFill');
        const pctEl = document.getElementById('fpPctLabel');
        const stateEl = document.getElementById('fpStateLabel');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (stateEl && stateText) stateEl.textContent = stateText;
    } else {
        const fill = document.getElementById('faceProgressFill');
        const pctEl = document.getElementById('facePctLabel');
        const stateEl = document.getElementById('faceStateLabel');
        if (fill) fill.style.width = pct + '%';
        if (pctEl) pctEl.textContent = pct + '%';
        if (stateEl && stateText) stateEl.textContent = stateText;
    }
}

function startScanProgressAnimation() {
    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    if (selectedBioMethod === 'fingerprint') {
        updateProgressiveFeedback(25, 'Sensor ready — place finger on device sensor...');
        const pctEl = document.getElementById('fpPctLabel');
        if (pctEl) pctEl.textContent = 'Ready';
        const titleEl = document.getElementById('fpScanningTitle');
        const subEl = document.getElementById('fpStatusSub');
        if (titleEl) titleEl.textContent = 'Touch Fingerprint Sensor';
        if (subEl) subEl.textContent = 'Place your finger on your device sensor or confirm the prompt';
    } else {
        updateProgressiveFeedback(25, 'Waiting for Face ID or Windows Hello...');
    }
}

function triggerBiometricDetected() {
    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    updateProgressiveFeedback(100, selectedBioMethod === 'fingerprint' ? 'Device verified! Finalizing...' : 'Device face sign-in verified! Finalizing...');
    
    const frame = selectedBioMethod === 'fingerprint'
        ? document.querySelector('.fp-scan-frame')
        : document.getElementById('faceScanFrame');
    if (frame) frame.classList.add('bio-detected');

    if (window.triggerHaptic) window.triggerHaptic('success');
}

function cancelBiometricRegistration() {

    if (bioAbortController) {
        try { bioAbortController.abort(); } catch(e){}
        bioAbortController = null;
    }

    clearInterval(bioScanProgressTimer);
    bioScanProgressTimer = null;
    isBioRegistrationRunning = false;

    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    if (idleView) idleView.style.display = 'block';
    if (scanningView) scanningView.style.display = 'none';
    if (successView) successView.style.display = 'none';
    if (errorView) errorView.style.display = 'none';

    // Reset styles
    const faceScanFrame = document.getElementById('faceScanFrame');
    if (faceScanFrame) {
        faceScanFrame.classList.remove('bio-detected');
        faceScanFrame.style.borderColor = '';
    }
    const faceLaserBar = document.getElementById('faceLaserBar');
    if (faceLaserBar) faceLaserBar.style.background = '';
    document.querySelectorAll('.fp-scan-frame, .face-scan-frame').forEach(el => el.classList.remove('bio-detected'));
}

function resetToSelectionStage() {
    cancelBiometricRegistration();
}

function switchOrRegisterOtherMethod() {
    const other = selectedBioMethod === 'fingerprint' ? 'face' : 'fingerprint';
    selectBiometricMethod(other);
    resetToSelectionStage();
}

function retryBiometricRegistration() {
    const errorView = document.getElementById('bioErrorView');
    if (errorView) errorView.style.display = 'none';
    beginSelectedBiometricRegistration();
}

function switchBiometricMethodFallback() {
    const other = selectedBioMethod === 'fingerprint' ? 'face' : 'fingerprint';
    selectBiometricMethod(other);
    resetToSelectionStage();
}

async function loadDevices() {
    const list = document.getElementById('deviceList');
    const badge = document.getElementById('deviceCountBadge');
    const badgeFp = document.getElementById('statusBadgeFingerprint');
    const badgeFace = document.getElementById('statusBadgeFace');

    if (!window.PublicKeyCredential) {
        if (badge) { badge.textContent = !window.isSecureContext ? 'Requires HTTPS' : 'Unsupported'; badge.style.color = '#f87171'; }
        if (badgeFp) { badgeFp.textContent = 'Unavailable'; badgeFp.className = 'bio-status-pill not-reg'; }
        if (badgeFace) { badgeFace.textContent = 'Unavailable'; badgeFace.className = 'bio-status-pill not-reg'; }
    }

    try {
        const res = await fetch('{{ route("webauthn.devices") }}');
        const devices = await res.json();
        if (!list) return;

        list.innerHTML = '';

        const hasFp = Array.isArray(devices) && devices.some(d => (d.biometric_type || 'fingerprint') === 'fingerprint');
        const hasFace = Array.isArray(devices) && devices.some(d => d.biometric_type === 'face' && d.is_secure_face_credential);
        const hasLegacyFace = Array.isArray(devices) && devices.some(d => d.biometric_type === 'face' && !d.is_secure_face_credential);

        if (badgeFp) {
            badgeFp.textContent = hasFp ? 'Active' : 'Not Registered';
            badgeFp.className = 'bio-status-pill ' + (hasFp ? 'reg-active' : 'not-reg');
        }
        if (badgeFace) {
            badgeFace.textContent = hasFace ? 'Active' : (hasLegacyFace ? 'Re-enroll' : 'Not Registered');
            badgeFace.className = 'bio-status-pill ' + (hasFace ? 'reg-active' : 'not-reg');
        }

        if (devices && devices.length > 0) {
            if (badge) {
                badge.textContent = `${devices.length} Registered`;
                badge.style.color = '#4ade80';
                badge.style.borderColor = 'rgba(74,222,128,0.3)';
                badge.style.background = 'rgba(74,222,128,0.1)';
            }

            const activeSummary = document.createElement('div');
            const hasUsableCredential = hasFp || hasFace;
            const enrolledMethods = [
                ...(hasFp ? ['Fingerprint'] : []),
                ...(hasFace ? ['Face ID / Windows Hello'] : [])
            ];
            activeSummary.style.cssText = hasUsableCredential
                ? 'padding:14px 18px;color:#4ade80;font-size:.875rem;background:rgba(22,163,74,0.12);border-radius:12px;border:1px solid rgba(22,163,74,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;'
                : 'padding:14px 18px;color:#fbbf24;font-size:.875rem;background:rgba(245,158,11,0.12);border-radius:12px;border:1px solid rgba(245,158,11,0.25);margin-bottom:16px;font-weight:600;display:flex;align-items:center;gap:10px;';
            activeSummary.innerHTML = `
                <i class="bi ${hasUsableCredential ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'}" style="font-size:1.2rem;color:${hasUsableCredential ? '#22c55e' : '#f59e0b'};"></i>
                <div>
                    <div>${hasUsableCredential ? 'Device sign-in is <strong>active</strong> on your account.' : 'A camera-only face profile <strong>cannot sign in</strong>. Register a device passkey below.'}</div>
                    <div style="font-size:.76rem;color:${hasUsableCredential ? '#86efac' : '#fcd34d'};font-weight:400;margin-top:2px;">
                        ${hasUsableCredential ? `Enrolled: ${enrolledMethods.join(' and ')}` : 'Remove the old profile after registering a passkey.'}
                    </div>
                </div>`;
            list.appendChild(activeSummary);

            devices.forEach(d => {
                const isFace = d.biometric_type === 'face';
                const isLegacyFace = isFace && !d.is_secure_face_credential;
                const div = document.createElement('div');
                div.className = 'device-item-card';
                div.innerHTML = `
                    <div class="device-item-left">
                        <div class="device-item-icon" style="background:${isFace ? 'rgba(56,189,248,0.12)' : 'rgba(74,222,128,0.12)'};color:${isFace ? '#38bdf8' : '#4ade80'};">
                            <i class="bi ${isFace ? 'bi-person-bounding-box' : 'bi-fingerprint'}"></i>
                        </div>
                        <div class="device-item-info">
                            <div class="device-item-name" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                                <span>${d.name || d.device_name || (isFace ? 'Face Recognition Credential' : 'Fingerprint Credential')}</span>
                                <span class="bio-device-type-pill ${isFace ? 'pill-face' : 'pill-fp'}">
                                    <i class="bi ${isFace ? 'bi-person-bounding-box' : 'bi-fingerprint'} me-1"></i>${isFace ? 'Face' : 'Fingerprint'}
                                </span>
                            </div>
                            <div class="device-item-meta">
                                <span class="device-meta-verified"><i class="bi ${isLegacyFace ? 'bi-exclamation-triangle' : 'bi-shield-check'} me-1"></i>${isLegacyFace ? 'Camera-only legacy profile · re-enroll' : 'Device-protected credential'}</span>
                                <span class="device-meta-dot">•</span>
                                <span class="device-meta-date">${d.created_at ? new Date(d.created_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) : 'Registered'}</span>
                            </div>
                        </div>
                    </div>
                    <button onclick="removeDevice('${d.credential_id}', this)" class="device-remove-btn" type="button" title="Remove biometric credential">
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
            }
            const emptyDiv = document.createElement('div');
            emptyDiv.id = 'noDevices';
            emptyDiv.style.cssText = 'text-align:center;padding:32px 20px;color:#b39b82;font-size:.85rem;background:rgba(255,255,255,0.02);border-radius:14px;border:1px dashed rgba(207,164,111,0.2);';
            emptyDiv.innerHTML = `
                <i class="bi bi-shield-lock" style="font-size:2.6rem;display:block;margin-bottom:10px;opacity:.35;color:var(--gold,#CFA46F);"></i>
                <div style="font-weight:700;color:#f3e7cd;margin-bottom:4px;">No biometric credentials registered yet</div>
                <div style="font-size:.78rem;color:#b39b82;">Select a device sign-in method above and continue to register.</div>
            `;
            list.appendChild(emptyDiv);
        }
    } catch(e) {
        console.error('Failed to load biometric devices', e);
    }
}

async function removeDevice(credentialId, btn) {
    if (!confirm('Remove this biometric credential from your account?')) return;
    try {
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Removing...';
        }
        const res = await fetch('{{ route("webauthn.remove") }}', {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ credential_id: credentialId })
        });
        const data = await res.json();
        await loadDevices();
        if (typeof showToast === 'function') {
            showToast(data.message || 'Biometric credential removed.', 'info');
        }
    } catch(err) {
        console.error('Failed to remove biometric device', err);
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-trash3 me-1"></i>Remove';
        }
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

// ── Camera-based face enrollment for attendance ──
let cameraFaceStream = null;
let cameraFaceBlob = null;

function stopCameraFaceStream() {
    if (cameraFaceStream) cameraFaceStream.getTracks().forEach(track => track.stop());
    cameraFaceStream = null;
}

function initCameraFaceEnrollment() {
    const start = document.getElementById('cameraFaceStartBtn');
    if (!start) return;
    const video = document.getElementById('cameraFaceVideo');
    const canvas = document.getElementById('cameraFaceCanvas');
    const wrap = document.getElementById('cameraFacePreviewWrap');
    const capture = document.getElementById('cameraFaceCaptureBtn');
    const save = document.getElementById('cameraFaceSaveBtn');
    const retake = document.getElementById('cameraFaceRetakeBtn');
    const cancel = document.getElementById('cameraFaceCancelBtn');
    const status = document.getElementById('cameraFaceStatus');

    const setStatus = (message, color = '#94a3b8') => {
        if (status) { status.textContent = message; status.style.color = color; }
    };
    const reset = () => {
        stopCameraFaceStream();
        cameraFaceBlob = null;
        if (wrap) wrap.style.display = 'none';
        if (video) video.style.display = 'block';
        if (canvas) canvas.style.display = 'none';
        if (capture) capture.style.display = 'none';
        if (retake) retake.style.display = 'none';
        if (save) { save.style.display = 'none'; save.disabled = false; }
        if (cancel) cancel.style.display = 'none';
        if (start) { start.style.display = ''; start.disabled = false; }
    };

    start.addEventListener('click', async () => {
        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Camera access requires a supported browser and a secure HTTPS connection.', '#f87171');
            return;
        }
        start.disabled = true;
        setStatus('Requesting camera access…');
        try {
            stopCameraFaceStream();
            cameraFaceStream = await navigator.mediaDevices.getUserMedia({
                audio: false,
                video: { facingMode: { ideal: 'user' }, width: { ideal: 1280 }, height: { ideal: 960 } }
            });
            video.srcObject = cameraFaceStream;
            await video.play();
            wrap.style.display = 'block';
            video.style.display = 'block';
            canvas.style.display = 'none';
            start.style.display = 'none';
            capture.style.display = '';
            cancel.style.display = '';
            setStatus('Center your face inside the frame, face the camera, and use even lighting.');
        } catch (error) {
            stopCameraFaceStream();
            start.disabled = false;
            setStatus(error?.name === 'NotAllowedError'
                ? 'Camera permission was denied. Allow camera access in your browser settings and retry.'
                : 'The camera could not be opened. Check that it is available, then retry.', '#f87171');
        }
    });

    capture.addEventListener('click', () => {
        if (!video.videoWidth || !video.videoHeight) {
            setStatus('The camera is not ready yet. Wait a moment and retry.', '#f87171');
            return;
        }
        const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d', { alpha: false }).drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(blob => {
            if (!blob) {
                setStatus('The camera frame could not be captured. Please try again.', '#f87171');
                return;
            }
            cameraFaceBlob = blob;
            stopCameraFaceStream();
            video.srcObject = null;
            video.style.display = 'none';
            canvas.style.cssText = 'display:block;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);';
            capture.style.display = 'none';
            retake.style.display = '';
            save.style.display = '';
            setStatus('Capture ready. Register it for secure face matching, or cancel to retake.');
        }, 'image/jpeg', 0.88);
    });

    save.addEventListener('click', async () => {
        if (!cameraFaceBlob) return;
        save.disabled = true;
        setStatus('Checking face quality and saving securely…');
        const form = new FormData();
        form.append('face_image', cameraFaceBlob, 'face-enrollment.jpg');
        try {
            const response = await fetch('{{ route("profile.face.enroll") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: form,
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.success) {
                save.disabled = false;
                retake.style.display = '';
                setStatus(result.message || 'Face registration could not be completed. Please try again.', '#f87171');
                return;
            }
            reset();
            setStatus(result.message || 'Face registered securely for attendance verification.', '#34d399');
        } catch (error) {
            save.disabled = false;
            retake.style.display = '';
            setStatus('Could not reach the server. Your capture was not saved; please retry.', '#f87171');
        }
    });

    retake.addEventListener('click', () => {
        reset();
        start.click();
    });

    cancel.addEventListener('click', () => {
        reset();
        setStatus('Capture canceled.');
    });
    window.addEventListener('pagehide', stopCameraFaceStream, { once: true });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCameraFaceEnrollment, { once: true });
} else {
    initCameraFaceEnrollment();
}

// ── Device Passkey Registration ──
let isBioRegistrationRunning = false;

async function beginFaceRegistration() {
    // Face verification stays on the device; the server stores only a verified passkey public key.
    selectedBioMethod = 'face';
    return beginFingerprintRegistration();
}
// ── WebAuthn Device Credential Registration ──
async function beginFingerprintRegistration() {
    const registrationType = selectedBioMethod === 'face' ? 'face' : 'fingerprint';
    const idleView = document.getElementById('bioIdleView');
    const scanningView = document.getElementById('bioScanningView');
    const successView = document.getElementById('bioSuccessView');
    const errorView = document.getElementById('bioErrorView');
    const errorTitle = document.getElementById('bioErrorTitle');
    const errorDesc = document.getElementById('bioErrorDesc');
    const successTitle = document.getElementById('bioSuccessTitle');
    const successDesc = document.getElementById('bioSuccessDesc');

    // 1. WebAuthn requires a named domain like localhost or HTTPS (not raw 127.0.0.1 IP)
    if (window.location.hostname === '127.0.0.1') {
        const targetUrl = window.location.href.replace('//127.0.0.1', '//localhost');
        window.location.replace(targetUrl);
        return;
    }

    if (!window.isSecureContext) {
        if (idleView) idleView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (errorTitle) errorTitle.textContent = 'HTTPS Connection Required';
            if (errorDesc) errorDesc.textContent = 'Device biometric sign-in requires HTTPS or localhost. On mobile Wi-Fi, open the app through its secure HTTPS address.';
        }
        return;
    }

    if (!window.PublicKeyCredential) {
        if (idleView) idleView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (errorTitle) errorTitle.textContent = 'Browser Unsupported';
            if (errorDesc) errorDesc.innerHTML = 'Your current browser does not support WebAuthn biometric credentials. Please use <strong>Google Chrome</strong>, <strong>Microsoft Edge</strong>, or <strong>Apple Safari</strong>.';
        }
        return;
    }

    if (idleView) idleView.style.display = 'none';
    if (successView) successView.style.display = 'none';
    if (errorView) errorView.style.display = 'none';
    if (scanningView) scanningView.style.display = 'block';

    document.querySelectorAll('.fp-scan-frame, .face-scan-frame').forEach(el => el.classList.remove('bio-detected'));

    const fpScan = document.getElementById('fpActiveScanner');
    const faceScan = document.getElementById('faceActiveScanner');
    if (faceScan) faceScan.classList.toggle('active', registrationType === 'face');
    if (fpScan) fpScan.classList.toggle('active', registrationType !== 'face');

    const fpScanFrame = document.querySelector(registrationType === 'face' ? '.face-scan-frame' : '.fp-scan-frame');
    if (fpScanFrame) {
        fpScanFrame.classList.remove('bio-detected');
    }
    const fpScanningTitle = document.getElementById(registrationType === 'face' ? 'faceScanningTitle' : 'fpScanningTitle');
    const fpStatusSub = document.getElementById(registrationType === 'face' ? 'faceStatusSub' : 'fpStatusSub');
    if (fpScanningTitle) fpScanningTitle.textContent = registrationType === 'face' ? 'Waiting for Face ID / Windows Hello' : 'Touch Fingerprint Sensor';
    if (fpStatusSub) fpStatusSub.textContent = registrationType === 'face'
        ? 'Complete the protected device prompt. Your device may offer a PIN instead of face verification.'
        : 'Place your finger on the sensor or confirm the device prompt.';
    updateProgressiveFeedback(25, registrationType === 'face' ? 'Waiting for device face sign-in prompt...' : 'Sensor ready — waiting for biometric prompt...');
    const fpPctLabel = document.getElementById(registrationType === 'face' ? 'facePctLabel' : 'fpPctLabel');
    if (fpPctLabel) fpPctLabel.textContent = 'Ready';

    bioAbortController = new AbortController();

    try {
        const optRes = await fetch('{{ route("webauthn.register.options") }}?biometric_type=' + registrationType, {
            headers: { 
                'X-CSRF-TOKEN': '{{ csrf_token() }}', 
                'Accept': 'application/json',
                'ngrok-skip-browser-warning': 'true'
            },
            signal: bioAbortController.signal
        });
        const opts = await optRes.json();

        if (!optRes.ok || !opts.challenge || !opts.user) {
            throw new Error(opts.message || 'Failed to initialize biometric registration options from server.');
        }

        const challenge = base64ToUint8Array(opts.challenge);
        const userId    = base64ToUint8Array(opts.user.id);

        const hostname = window.location.hostname;
        const isIp = /^(\d{1,3}\.){3}\d{1,3}$/.test(hostname) || hostname.includes(':');
        const rp = { name: opts.rp?.name || 'School Attendance' };
        if (!isIp) {
            const sRp = (opts.rp?.id || '').toLowerCase().trim();
            const h = hostname.toLowerCase().trim();
            rp.id = (sRp && (h === sRp || h.endsWith('.' + sRp))) ? sRp : hostname;
        }

        const excludeCredentials = (opts.excludeCredentials || []).map(function(c) {
            return { type: c.type || 'public-key', id: base64ToUint8Array(c.id) };
        });

        // Detect if platform authenticator (Windows Hello, Touch ID, Android fingerprint) is available
        let hasPlatformAuth = false;
        let platformAvailabilityKnown = false;
        if (typeof PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                hasPlatformAuth = await PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
                platformAvailabilityKnown = true;
            } catch(e) {
                hasPlatformAuth = false;
            }
        }
        if (platformAvailabilityKnown && !hasPlatformAuth) {
            throw new Error('No on-device biometric sign-in is available. Set up fingerprint, Face ID, or Windows Hello in your device settings, then try again.');
        }

        const basePublicKey = {
            challenge: challenge,
            rp: rp,
            user: { id: userId, name: opts.user.name, displayName: opts.user.displayName },
            pubKeyCredParams: opts.pubKeyCredParams || [
                { type: 'public-key', alg: -7 },
                { type: 'public-key', alg: -257 }
            ],
            timeout: opts.timeout || 120000,
            attestation: opts.attestation || 'none',
            excludeCredentials: excludeCredentials,
            hints: ['client-device']
        };

        // ── Direct Platform Biometric Registration ──────────────────────────
        // Enforce on-device platform biometric attachment so Android Chrome,
        // iOS Touch ID/Face ID, and Windows Hello invoke the native sensor directly
        // without prompting with the external security key chooser (NFC/USB).
        const primarySelection = {
            authenticatorAttachment: 'platform',
            userVerification: 'required',
            residentKey: 'preferred',
            requireResidentKey: false
        };

        let credential = null;
        try {
            credential = await navigator.credentials.create({
                publicKey: Object.assign({}, basePublicKey, {
                    authenticatorSelection: primarySelection
                }),
                signal: bioAbortController.signal
            });
        } catch (firstErr) {
            // If the first attempt was aborted/cancelled by the user, propagate immediately.
            if (firstErr.name === 'AbortError' || firstErr.name === 'NotAllowedError') {
                throw firstErr;
            }
            // Fallback attempt: still strictly platform-bound, but with residentKey: 'discouraged'
            console.warn('Primary platform registration failed, retrying with platform discouraged fallback:', firstErr);
            if (fpStatusSub) fpStatusSub.textContent = 'Retrying with device sensor...';
            try {
                credential = await navigator.credentials.create({
                    publicKey: Object.assign({}, basePublicKey, {
                        authenticatorSelection: {
                            authenticatorAttachment: 'platform',
                            userVerification: 'required',
                            residentKey: 'discouraged',
                            requireResidentKey: false
                        }
                    }),
                    signal: bioAbortController.signal
                });
            } catch (retryErr) {
                throw retryErr;
            }
        }

        if (!credential) {
            throw new Error('Biometric registration was cancelled.');
        }

        if (fpScanFrame) {
            fpScanFrame.classList.add('bio-detected');
        }
        if (fpScanningTitle) fpScanningTitle.textContent = registrationType === 'face' ? 'Device Verified ✓' : 'Fingerprint Verified ✓';
        if (fpStatusSub) fpStatusSub.textContent = 'Device verification complete. Registering credential...';
        triggerBiometricDetected();

        const credentialId = bufferToBase64Url(credential.rawId);
        const attestationObject = bufferToBase64Url(credential.response.attestationObject);
        const clientDataJSON = bufferToBase64Url(credential.response.clientDataJSON);

        const ua = navigator.userAgent;
        let deviceType = ua.indexOf('iPhone') !== -1 ? 'iPhone' :
                         ua.indexOf('iPad') !== -1 ? 'iPad' :
                         ua.indexOf('Android') !== -1 ? 'Android Device' :
                         ua.indexOf('Windows') !== -1 ? 'Windows PC' :
                         ua.indexOf('Mac') !== -1 ? 'Mac' : 'Biometric Device';
        const deviceName = `${deviceType} (${registrationType === 'face' ? 'Secure Face ID / Passkey' : 'Fingerprint'})`;

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
                biometric_type: registrationType,
                device_name: deviceName
            })
        });

        const result = await saveRes.json();

        if (saveRes.status === 409 || (result && result.error === 'duplicate')) {
            if (scanningView) scanningView.style.display = 'none';
            if (errorView) {
                errorView.style.display = 'block';
                if (errorTitle) errorTitle.textContent = 'Biometric Already Registered';
                if (errorDesc) errorDesc.textContent = result.message || 'This device credential is already registered on your account.';
            }
            return;
        }

        if (result.success) {
            if (scanningView) scanningView.style.display = 'none';
            if (successView) {
                successView.style.display = 'block';
                if (successTitle) successTitle.textContent = registrationType === 'face' ? 'Face Sign-in Registered!' : 'Fingerprint Registered Successfully!';
                if (successDesc) successDesc.textContent = result.message || 'Your device credential is linked to your account.';
            }
            await loadDevices();
        } else {
            if (scanningView) scanningView.style.display = 'none';
            if (errorView) {
                errorView.style.display = 'block';
                if (errorTitle) errorTitle.textContent = 'Registration Incomplete';
                if (errorDesc) errorDesc.textContent = result.message || 'The server could not verify and save the biometric enrollment.';
            }
        }
    } catch(err) {
        clearInterval(bioScanProgressTimer);
        bioScanProgressTimer = null;
        if (fpScanFrame) fpScanFrame.classList.remove('bio-detected');

        if (err.name === 'AbortError') {
            if (scanningView) scanningView.style.display = 'none';
            if (idleView) idleView.style.display = 'block';
            return;
        }

        if (scanningView) scanningView.style.display = 'none';
        if (errorView) {
            errorView.style.display = 'block';
            if (err.name === 'NotAllowedError') {
                if (errorTitle) errorTitle.textContent = 'Biometric Prompt Dismissed';
                if (errorDesc) errorDesc.textContent = 'The device verification prompt was cancelled or timed out. Try again or use another sign-in method.';
            } else if (err.name === 'InvalidStateError') {
                if (errorTitle) errorTitle.textContent = 'Already Registered';
                if (errorDesc) errorDesc.textContent = 'This device credential is already registered for your account.';
            } else if (err.name === 'NotSupportedError') {
                if (errorTitle) errorTitle.textContent = 'Device Authentication Unsupported';
                if (errorDesc) errorDesc.textContent = 'Set up Face ID, Windows Hello, or another device sign-in method and try again.';
            } else {
                if (errorTitle) errorTitle.textContent = 'Registration Failed';
                if (errorDesc) errorDesc.textContent = err.message || 'Device verification could not be completed. Check your device sign-in settings and try again.';
            }
        }
        prefetchWebAuthn();
    }
}

// ── Unified Biometric Registration Dispatcher ──
async function beginSelectedBiometricRegistration() {
    if (isBioRegistrationRunning) return;
    isBioRegistrationRunning = true;

    // Determine the active method from both the JS state and the DOM aria-pressed
    // attribute, so we are never dependent on a single source of truth.
    // This prevents device verification from triggering when face is selected.
    let method = selectedBioMethod;
    const faceCard = document.getElementById('methodCardFace');
    const fpCard   = document.getElementById('methodCardFp') || document.getElementById('methodCardFingerprint');
    if (faceCard && faceCard.getAttribute('aria-pressed') === 'true') {
        method = 'face';
    } else if (fpCard && fpCard.getAttribute('aria-pressed') === 'true') {
        method = 'fingerprint';
    }
    // Also read from the visible button label as a final fallback
    const startBtn = document.getElementById('startBioBtn');
    if (startBtn && startBtn.textContent && startBtn.textContent.toLowerCase().includes('face')) {
        method = 'face';
    }

    // Sync the JS variable to what we determined from the DOM
    selectedBioMethod = method;

    try {
        if (method === 'face') {
            await beginFaceRegistration();
        } else {
            await beginFingerprintRegistration();
        }
    } finally {
        isBioRegistrationRunning = false;
    }
}

// Backward compatibility alias
window.registerFingerprint = beginSelectedBiometricRegistration;

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
    const newEmailInput = document.getElementById('inputNewEmail');
    const newEmail = newEmailInput ? newEmailInput.value.trim() : '';
    if (newEmailInput && !newEmail) {
        alert('Please enter your new email address.');
        newEmailInput.focus();
        return;
    }
    const btn = document.getElementById('sendEmailOtpBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending...';
    fetch('{{ route("otp.email.send") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json' },
        body: JSON.stringify({ new_email: newEmail })
    }).then(r => r.json()).then(data => {
        if (data.success) {
            document.getElementById('emailStep1').style.display = 'none';
            document.getElementById('emailStep2').style.display = 'block';
            const destEl = document.getElementById('emailSentDestination');
            if (destEl) destEl.textContent = data.target_email || newEmail || '{{ Auth::user()->email }}';
            const confirmInput = document.getElementById('confirmNewEmailInput');
            if (confirmInput && newEmail) confirmInput.value = newEmail;
            if (data.dev_otp && eDigits) {
                const str = String(data.dev_otp).trim();
                eDigits.forEach((d, i) => { if (str[i]) d.value = str[i]; });
                const hiddenInput = document.getElementById('emailOtpHidden');
                if (hiddenInput) hiddenInput.value = data.dev_otp;
            }
            if (eDigits[0]) eDigits[0].focus();
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send Verification OTP';
            alert(data.message || 'Failed to send OTP.');
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send-fill me-2"></i>Send Verification OTP';
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



const SETTINGS_SEARCH_INDEX = [
    { title: 'Profile Photo & Avatar', desc: 'Change avatar, upload photo, preview appearance', cat: 'Account', tab: 'profile', icon: 'bi-person-bounding-box', elId: 'tab-profile' },
    { title: 'Personal Information', desc: 'Name, email address, and role identity', cat: 'Account', tab: 'profile', icon: 'bi-person-vcard', elId: 'name' },
    { title: 'Student ID Number', desc: '7-character institutional student ID', cat: 'Account', tab: 'profile', icon: 'bi-card-text', elId: 'studentId' },
    { title: 'Change Password', desc: 'Update account password and security credentials', cat: 'Security & Access', tab: 'security', icon: 'bi-key-fill', elId: 'current_password' },
    { title: 'Active Sessions & Audit Trail', desc: 'Review login history and active device sessions', cat: 'Security & Access', tab: 'security', icon: 'bi-shield-check', elId: 'tab-security' },
    { title: 'Biometrics Sensors', desc: 'Manage hardware biometric credentials', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-fingerprint', elId: 'tab-fingerprint' },
    { title: 'Fingerprint Registration', desc: 'Register hardware fingerprint sensor via WebAuthn', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-fingerprint', elId: 'methodCardFp' },
    { title: 'Device Passkey', desc: 'Enroll a passkey using your device security prompt', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-key', elId: 'methodCardFace' },
    @if(Auth::user()->isStudent())
    { title: 'Camera Face Registration', desc: 'Capture and securely enroll a face for attendance verification', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-camera-video', elId: 'cameraFaceEnrollment' },
    @endif
    { title: 'Registered Hardware Credentials', desc: 'View, test, or remove registered biometric tokens', cat: 'Security & Access', tab: 'fingerprint', icon: 'bi-usb-drive', elId: 'deviceList' },
    { title: 'Device Binding & Trust', desc: 'Hardware device binding and attendance authorization', cat: 'Security & Access', tab: 'device', icon: 'bi-phone', elId: 'tab-device' },
    { title: 'Bind Current Device', desc: 'Cryptographically bind this phone or computer to your attendance', cat: 'Security & Access', tab: 'device', icon: 'bi-phone-fill', elId: 'tabDeviceBindBtn' },
    { title: 'Emergency Device Lock', desc: 'Lock attendance check-ins exclusively to your authorized device', cat: 'Security & Access', tab: 'device', icon: 'bi-shield-lock-fill', elId: 'tab-device' },
    { title: 'System Display Language', desc: 'Select English (US), Filipino, or Bikolano', cat: 'System & Academics', tab: 'preferences', icon: 'bi-translate', elId: 'tab-preferences' },
    { title: 'Notification Alerts', desc: 'In-app notifications and email alert preferences', cat: 'System & Academics', tab: 'preferences', icon: 'bi-bell-fill', elId: 'tab-preferences' },
    { title: 'Software Updates & PWA Assets', desc: 'Check latest system updates, security patches and offline assets', cat: 'System & Academics', tab: 'preferences', icon: 'bi-cloud-arrow-down-fill', elId: 'checkUpdateBtn' },
    { title: 'Family & Guardian Link', desc: 'Connect parent accounts with QR code or link code', cat: 'Account', tab: 'family', icon: 'bi-people-fill', elId: 'tab-family' },
    { title: 'Linked Guardians List', desc: 'View authorized guardians with view-only attendance access', cat: 'Account', tab: 'family', icon: 'bi-person-lines-fill', elId: 'tab-family' }
];

function initSettingsSearch() {
    const input = document.getElementById('settingsSearchInput');
    const dropdown = document.getElementById('settingsSearchResults');
    const clearBtn = document.getElementById('settingsSearchClear');
    const backdrop = document.getElementById('settingsSearchBackdrop');
    const header = document.querySelector('.settings-command-header') || document.querySelector('.settings-top-navbar');
    const kbd = document.querySelector('.settings-search-kbd');
    if (!input || !dropdown) return;

    let selectedIndex = -1;
    let currentResults = [];

    function updateClearButton() {
        if (!clearBtn) return;
        if (input.value.trim().length > 0) {
            clearBtn.style.display = 'flex';
            if (kbd) kbd.style.display = 'none';
        } else {
            clearBtn.style.display = 'none';
            if (kbd && window.innerWidth > 768) kbd.style.display = '';
        }
    }

    function showSearchUI() {
        if (header) header.classList.add('search-active');
        if (backdrop) backdrop.style.display = 'block';
    }

    function hideSearchUI() {
        dropdown.style.display = 'none';
        if (header) header.classList.remove('search-active');
        if (backdrop) backdrop.style.display = 'none';
        selectedIndex = -1;
    }

    function renderResults(query) {
        updateClearButton();
        const q = (query || '').trim().toLowerCase();
        if (!q) {
            hideSearchUI();
            dropdown.innerHTML = '';
            currentResults = [];
            selectedIndex = -1;
            return;
        }

        currentResults = SETTINGS_SEARCH_INDEX.filter(item => {
            return item.title.toLowerCase().includes(q) ||
                   item.desc.toLowerCase().includes(q) ||
                   item.cat.toLowerCase().includes(q) ||
                   item.tab.toLowerCase().includes(q);
        });

        if (currentResults.length === 0) {
            dropdown.innerHTML = `<div class="search-empty-state"><i class="bi bi-search me-2"></i>No settings found matching "${query}".</div>`;
            dropdown.style.display = 'block';
            showSearchUI();
            selectedIndex = -1;
            return;
        }

        selectedIndex = 0;
        dropdown.innerHTML = currentResults.map((item, idx) => `
            <div class="search-item-row ${idx === 0 ? 'selected' : ''}" data-idx="${idx}">
                <div class="search-item-icon"><i class="bi ${item.icon}"></i></div>
                <div class="search-item-body">
                    <span class="search-item-title">${item.title}</span>
                    <span class="search-item-sub">${item.desc}</span>
                </div>
                <span class="search-item-cat">${item.cat}</span>
            </div>
        `).join('');

        dropdown.querySelectorAll('.search-item-row').forEach(row => {
            row.addEventListener('click', function() {
                const idx = parseInt(this.getAttribute('data-idx'));
                executeSearchJump(currentResults[idx]);
            });
        });

        dropdown.style.display = 'block';
        showSearchUI();
    }

    function executeSearchJump(item) {
        if (!item) return;
        hideSearchUI();
        input.value = '';
        updateClearButton();
        input.blur();

        if (window.switchTab) {
            window.switchTab(item.tab);
        }

        if (item.elId) {
            setTimeout(() => {
                const el = document.getElementById(item.elId);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('search-highlight-pulse');
                    setTimeout(() => el.classList.remove('search-highlight-pulse'), 2500);
                }
            }, 150);
        }
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            input.value = '';
            updateClearButton();
            hideSearchUI();
            input.focus();
        });
    }

    if (backdrop) {
        backdrop.addEventListener('click', function() {
            hideSearchUI();
            input.blur();
        });
    }

    input.addEventListener('input', function() {
        renderResults(this.value);
    });

    input.addEventListener('focus', function() {
        updateClearButton();
        if (this.value.trim()) {
            renderResults(this.value);
        }
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentResults.length > 0) {
                selectedIndex = (selectedIndex + 1) % currentResults.length;
                updateSelection();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentResults.length > 0) {
                selectedIndex = (selectedIndex - 1 + currentResults.length) % currentResults.length;
                updateSelection();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && selectedIndex < currentResults.length) {
                executeSearchJump(currentResults[selectedIndex]);
            }
        } else if (e.key === 'Escape') {
            hideSearchUI();
            input.blur();
        }
    });

    function updateSelection() {
        const rows = dropdown.querySelectorAll('.search-item-row');
        rows.forEach((r, idx) => {
            r.classList.toggle('selected', idx === selectedIndex);
            if (idx === selectedIndex) {
                r.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    // Global keyboard shortcut '/' or 'Ctrl+K'
    document.addEventListener('keydown', function(e) {
        const activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
            return;
        }
        if (e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k')) {
            e.preventDefault();
            input.focus();
            input.select();
        }
    });

    // Close on outside click
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target) && (!clearBtn || !clearBtn.contains(e.target))) {
            hideSearchUI();
        }
    });
}

function initMobileSearchToggle() {
    if (window.innerWidth > 768) return;
    var box = document.querySelector('.sp .settings-search-box');
    var icon = document.querySelector('.sp .settings-search-icon');
    var input = document.getElementById('settingsSearchInput');
    if (!box || !icon || !input) return;

    function expand() {
        box.classList.add('search-expanded');
        setTimeout(function() { input.focus(); }, 60);
    }
    function collapse() {
        if (!input.value.trim()) {
            box.classList.remove('search-expanded');
        }
    }

    icon.addEventListener('click', function(e) {
        if (!box.classList.contains('search-expanded')) {
            e.preventDefault();
            e.stopPropagation();
            expand();
        }
    });
    input.addEventListener('blur', function() {
        setTimeout(collapse, 200);
    });
    // Keyboard shortcut still works
    document.addEventListener('keydown', function(e) {
        if (window.innerWidth > 768) return;
        var activeTag = document.activeElement ? document.activeElement.tagName : '';
        if (activeTag === 'INPUT' || activeTag === 'TEXTAREA') return;
        if (e.key === '/' || ((e.ctrlKey || e.metaKey) && e.key === 'k')) {
            e.preventDefault();
            expand();
        }
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            box.classList.remove('search-expanded');
        }
    }, { passive: true });
}

function initSettingsPage() {
    if (typeof loadDevices === 'function') loadDevices();
    if (typeof prefetchWebAuthn === 'function') prefetchWebAuthn();
    if (typeof updateStabsScrollArrows === 'function') updateStabsScrollArrows();
    if (typeof initSettingsSearch === 'function') initSettingsSearch();
    initMobileSearchToggle();

    // Attach direct click event listeners to all mobile stab buttons
    document.querySelectorAll('.stab').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const tab = this.getAttribute('data-tab') || this.dataset.tab;
            if (tab && window.switchTab) {
                window.switchTab(tab, this);
            }
        });
    });

    // Attach direct click event listeners to all sidebar navigation items
    document.querySelectorAll('.snav-item').forEach(btn => {
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

    // Direct event listener bindings for Biometrics Registration (CSP compliant)
    const cardFp = document.getElementById('methodCardFp') || document.getElementById('methodCardFingerprint');
    if (cardFp) cardFp.addEventListener('click', () => selectBiometricMethod('fingerprint'));
    const cardFace = document.getElementById('methodCardFace');
    if (cardFace) cardFace.addEventListener('click', () => selectBiometricMethod('face'));
    const startBioBtn = document.getElementById('startBioBtn');
    if (startBioBtn) {
        startBioBtn.addEventListener('click', () => beginSelectedBiometricRegistration());
    }
    const cancelBioBtn = document.querySelector('.bio-cancel-btn');
    if (cancelBioBtn) cancelBioBtn.addEventListener('click', () => cancelBiometricRegistration());
    const retryBtn = document.getElementById('retryBtn');
    if (retryBtn) retryBtn.addEventListener('click', () => retryBiometricRegistration());
    const fallbackSwitchBtn = document.getElementById('fallbackSwitchBtn');
    if (fallbackSwitchBtn) fallbackSwitchBtn.addEventListener('click', () => switchBiometricMethodFallback());
    const registerOtherBtn = document.getElementById('registerOtherBtn');
    if (registerOtherBtn) registerOtherBtn.addEventListener('click', () => switchOrRegisterOtherMethod());

    // Check if hash or localStorage requested a specific tab (e.g., #tab-fingerprint or #fingerprint)
    const rawHash = window.location.hash.replace('#tab-', '').replace('#', '');
    const storedTab = localStorage.getItem('active_settings_tab');
    const targetTab = rawHash || storedTab || 'profile';
    if (targetTab && window.switchTab) {
        localStorage.removeItem('active_settings_tab');
        if (targetTab.startsWith('security-')) {
            const sub = targetTab.replace('security-', '');
            window.switchSecuritySub(sub);
        } else {
            window.switchTab(targetTab);
        }
    }

    // Initialize device binding status check
    if (typeof window.checkDeviceBindingStatus === 'function') {
        setTimeout(window.checkDeviceBindingStatus, 300);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSettingsPage);
} else {
    initSettingsPage();
}

// Explicit window bindings for external callers and inline fallbacks
window.selectBiometricMethod = selectBiometricMethod;
window.beginSelectedBiometricRegistration = beginSelectedBiometricRegistration;
window.beginFaceRegistration = beginFaceRegistration;
window.beginFingerprintRegistration = beginFingerprintRegistration;
window.cancelBiometricRegistration = cancelBiometricRegistration;
window.resetToSelectionStage = resetToSelectionStage;
window.switchOrRegisterOtherMethod = switchOrRegisterOtherMethod;
window.retryBiometricRegistration = retryBiometricRegistration;
window.switchBiometricMethodFallback = switchBiometricMethodFallback;
window.loadDevices = loadDevices;
window.removeDevice = removeDevice;

// ── Student Family & Guardian Binding Handlers ──
let studentLinkCountdownTimer = null;

async function generateParentLinkCode() {
    const btn = document.getElementById('generateParentCodeBtn');
    const displayCard = document.getElementById('parentCodeDisplayCard');
    const codeEl = document.getElementById('parentCodeValue');
    const qrContainer = document.getElementById('parentCodeQrBox');
    const timerEl = document.getElementById('parentCodeTimer');

    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating Code...';

    try {
        const resp = await fetch('{{ route("student.parent_link.generate_code") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        });

        const data = await resp.json();
        if (data.success) {
            if (codeEl) codeEl.textContent = data.code;
            if (displayCard) displayCard.style.display = 'block';

            // Generate QR Code if library is loaded
            if (qrContainer && typeof QRCode !== 'undefined') {
                qrContainer.innerHTML = '';
                const linkUrl = '{{ route("parent.link.form") }}?code=' + data.code;
                new QRCode(qrContainer, {
                    text: linkUrl,
                    width: 170,
                    height: 170,
                    colorDark: '#140d07',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });
            }

            // Start countdown timer
            if (studentLinkCountdownTimer) clearInterval(studentLinkCountdownTimer);
            let remaining = data.expires_in_seconds || 900;
            const updateTimerDisplay = () => {
                if (remaining <= 0) {
                    clearInterval(studentLinkCountdownTimer);
                    if (timerEl) timerEl.textContent = 'Expired. Click Generate to refresh.';
                    if (codeEl) codeEl.style.opacity = '0.5';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Generate New Code';
                    return;
                }
                const mins = Math.floor(remaining / 60);
                const secs = remaining % 60;
                if (timerEl) {
                    timerEl.textContent = `Valid for: ${mins}:${secs < 10 ? '0' : ''}${secs}`;
                }
                remaining--;
            };
            updateTimerDisplay();
            studentLinkCountdownTimer = setInterval(updateTimerDisplay, 1000);

            if (typeof showToast === 'function') {
                showToast('Link Code generated! Share it with your parent.', 'success');
            }
            btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Regenerate Code';
        } else {
            alert(data.message || 'Failed to generate link code.');
            btn.innerHTML = '<i class="bi bi-key-fill me-1"></i> Generate Parent Link Code';
        }
    } catch (err) {
        alert('Network error while generating code.');
        btn.innerHTML = '<i class="bi bi-key-fill me-1"></i> Generate Parent Link Code';
    } finally {
        btn.disabled = false;
    }
}

function copyParentLinkCode(btn) {
    const codeEl = document.getElementById('parentCodeValue');
    if (!codeEl) return;
    const code = codeEl.textContent.trim();
    navigator.clipboard.writeText(code).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
        if (typeof showToast === 'function') showToast('Code copied to clipboard!', 'success');
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    });
}

window.generateParentLinkCode = generateParentLinkCode;
window.copyParentLinkCode = copyParentLinkCode;
</script>
@if(Auth::user()->isStudent())
<script src="{{ asset('js/qrcode.min.js') }}"></script>
@endif
@endsection
