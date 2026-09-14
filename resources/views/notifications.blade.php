@extends('layouts.app')

@section('page-title', 'Notifications')
@section('page-sub', 'Class alerts, attendance warnings and announcements')

@section('content')
<style>
    /* ══════════════════════════════════════════════════════════════
       LUXURY NOTIFICATION CENTER — DARK & GOLD HIGH-FIDELITY
       ══════════════════════════════════════════════════════════════ */
    :root {
        --notif-bg-card: rgba(22, 14, 12, 0.72);
        --notif-bg-card-unread: rgba(36, 22, 18, 0.88);
        --notif-border: rgba(255, 209, 102, 0.12);
        --notif-border-hover: rgba(255, 209, 102, 0.32);
        --notif-gold: #ffd166;
        --notif-gold-soft: #cfa46f;
        --notif-glow: rgba(255, 209, 102, 0.18);
    }

    .notif-wrapper {
        max-width: 1080px;
        margin: 0 auto;
        padding: 12px 16px 60px;
    }

    /* ── Hero Header ── */
    .notif-hero {
        position: relative;
        background: linear-gradient(135deg, rgba(32, 18, 14, 0.85) 0%, rgba(18, 11, 9, 0.95) 100%);
        border: 1px solid var(--notif-border);
        border-radius: 24px;
        padding: 26px 30px;
        margin-bottom: 22px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        overflow: hidden;
    }
    .notif-hero::before {
        content: '';
        position: absolute;
        top: -60px;
        right: -60px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(255, 209, 102, 0.12) 0%, transparent 70%);
        border-radius: 50%;
        pointer-events: none;
    }
    .notif-hero-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
        position: relative;
        z-index: 2;
    }
    .notif-hero-left {
        display: flex;
        align-items: center;
        gap: 18px;
    }
    .notif-hero-icon {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        background: linear-gradient(135deg, rgba(255, 209, 102, 0.22) 0%, rgba(207, 164, 111, 0.08) 100%);
        border: 1px solid rgba(255, 209, 102, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--notif-gold);
        font-size: 1.6rem;
        box-shadow: 0 8px 24px rgba(255, 209, 102, 0.15);
        flex-shrink: 0;
    }
    .notif-hero-text h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: #fff6e5;
        margin: 0 0 4px;
        letter-spacing: -0.3px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .notif-hero-text p {
        font-size: 0.85rem;
        color: rgba(243, 231, 205, 0.7);
        margin: 0;
    }
    .notif-hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .notif-btn-mark-all {
        background: linear-gradient(135deg, rgba(255, 209, 102, 0.2) 0%, rgba(207, 164, 111, 0.1) 100%);
        border: 1px solid rgba(255, 209, 102, 0.35);
        color: var(--notif-gold);
        padding: 9px 18px;
        border-radius: 12px;
        font-size: 0.82rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s ease;
        backdrop-filter: blur(10px);
        text-decoration: none;
    }
    .notif-btn-mark-all:hover {
        background: linear-gradient(135deg, rgba(255, 209, 102, 0.3) 0%, rgba(207, 164, 111, 0.18) 100%);
        border-color: var(--notif-gold);
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(255, 209, 102, 0.2);
    }
    .notif-btn-mark-all:disabled {
        opacity: 0.45;
        cursor: not-allowed;
        transform: none !important;
        box-shadow: none !important;
    }

    /* ── Quick Stats Row ── */
    .notif-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 22px;
    }
    .notif-stat-card {
        background: rgba(24, 15, 13, 0.65);
        border: 1px solid var(--notif-border);
        border-radius: 16px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        text-decoration: none;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(14px);
    }
    .notif-stat-card:hover {
        background: rgba(34, 20, 16, 0.85);
        border-color: var(--notif-border-hover);
        transform: translateY(-2px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.35);
    }
    .notif-stat-card.active {
        border-color: var(--notif-gold);
        background: rgba(40, 24, 18, 0.9);
        box-shadow: 0 0 0 1px rgba(255, 209, 102, 0.4), 0 8px 24px rgba(255, 209, 102, 0.12);
    }
    .notif-stat-info {
        display: flex;
        flex-direction: column;
    }
    .notif-stat-num {
        font-size: 1.45rem;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.1;
    }
    .notif-stat-label {
        font-size: 0.74rem;
        font-weight: 600;
        color: rgba(243, 231, 205, 0.65);
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-top: 3px;
    }
    .notif-stat-icon {
        width: 38px;
        height: 38px;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        flex-shrink: 0;
    }

    /* ── Controls Bar: Tabs, Filter Pills & Search ── */
    .notif-controls-card {
        background: rgba(22, 14, 12, 0.6);
        border: 1px solid var(--notif-border);
        border-radius: 18px;
        padding: 14px 18px;
        margin-bottom: 20px;
        backdrop-filter: blur(16px);
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .notif-tabs-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }
    .notif-status-tabs {
        display: flex;
        align-items: center;
        gap: 6px;
        background: rgba(0, 0, 0, 0.25);
        padding: 4px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }
    .notif-tab {
        padding: 7px 14px;
        border-radius: 9px;
        font-size: 0.8rem;
        font-weight: 700;
        color: rgba(243, 231, 205, 0.7);
        text-decoration: none;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .notif-tab:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.05);
    }
    .notif-tab.active {
        background: linear-gradient(135deg, rgba(255, 209, 102, 0.25) 0%, rgba(207, 164, 111, 0.15) 100%);
        color: var(--notif-gold);
        border: 1px solid rgba(255, 209, 102, 0.35);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }
    .notif-tab-badge {
        font-size: 0.65rem;
        font-weight: 800;
        padding: 2px 6px;
        border-radius: 99px;
        background: rgba(0, 0, 0, 0.35);
    }
    .notif-tab.active .notif-tab-badge {
        background: rgba(255, 209, 102, 0.25);
        color: #fff6e5;
    }

    .notif-search-box {
        position: relative;
        flex: 1;
        min-width: 200px;
        max-width: 320px;
    }
    .notif-search-input {
        width: 100%;
        height: 38px;
        padding: 0 34px 0 34px;
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 209, 102, 0.14);
        border-radius: 10px;
        color: #ffffff;
        font-size: 0.82rem;
        outline: none;
        transition: all 0.2s;
    }
    .notif-search-input:focus {
        border-color: var(--notif-gold);
        background: rgba(0, 0, 0, 0.45);
        box-shadow: 0 0 0 2px rgba(255, 209, 102, 0.2);
    }
    .notif-search-icon {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 209, 102, 0.5);
        font-size: 0.85rem;
        pointer-events: none;
    }
    .notif-search-clear {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.85rem;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .notif-search-clear:hover { color: #ffffff; }

    /* Category Filter Pills */
    .notif-cat-pills {
        display: flex;
        align-items: center;
        gap: 8px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 2px;
    }
    .notif-cat-pills::-webkit-scrollbar { display: none; }
    .notif-pill {
        padding: 5px 12px;
        border-radius: 99px;
        font-size: 0.75rem;
        font-weight: 700;
        text-decoration: none;
        color: rgba(243, 231, 205, 0.65);
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.07);
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        transition: all 0.2s ease;
    }
    .notif-pill:hover {
        color: #ffffff;
        background: rgba(255, 255, 255, 0.08);
        border-color: rgba(255, 209, 102, 0.25);
    }
    .notif-pill.active {
        background: rgba(255, 209, 102, 0.16);
        color: var(--notif-gold);
        border-color: rgba(255, 209, 102, 0.4);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    /* ── Notifications List & Cards ── */
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .notif-item-card {
        position: relative;
        background: var(--notif-bg-card);
        border: 1px solid var(--notif-border);
        border-radius: 18px;
        padding: 20px 22px;
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
    }
    .notif-item-card:hover {
        background: rgba(30, 18, 15, 0.85);
        border-color: var(--notif-border-hover);
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.38);
    }
    .notif-item-card.unread {
        background: var(--notif-bg-card-unread);
        border-color: rgba(255, 209, 102, 0.24);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 209, 102, 0.1);
    }
    .notif-item-card.unread::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: linear-gradient(180deg, var(--notif-gold) 0%, #cfa46f 100%);
        border-top-left-radius: 18px;
        border-bottom-left-radius: 18px;
        box-shadow: 0 0 12px rgba(255, 209, 102, 0.6);
    }
    .notif-item-card.archived-card {
        opacity: 0.85;
        border-style: dashed;
        background: rgba(18, 12, 10, 0.6);
    }

    .notif-card-inner {
        display: flex;
        gap: 18px;
        align-items: flex-start;
    }

    /* Icon Squircle per Notification Type */
    .notif-type-avatar {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
        border: 1px solid;
    }
    .type-avatar-absence {
        background: rgba(239, 68, 68, 0.14);
        color: #f87171;
        border-color: rgba(239, 68, 68, 0.28);
    }
    .type-avatar-warning_3,
    .type-avatar-warning_consecutive_3 {
        background: rgba(225, 29, 72, 0.18);
        color: #fb7185;
        border-color: rgba(225, 29, 72, 0.36);
        animation: pulseWarning 2.5s infinite ease-in-out;
    }
    .type-avatar-warning_2,
    .type-avatar-warning {
        background: rgba(245, 158, 11, 0.14);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.28);
    }
    .type-avatar-system_update {
        background: rgba(16, 185, 129, 0.14);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .type-avatar-excuse {
        background: rgba(20, 184, 166, 0.14);
        color: #2dd4bf;
        border-color: rgba(20, 184, 166, 0.3);
    }
    .type-avatar-custom,
    .type-avatar-announcement {
        background: rgba(59, 130, 246, 0.14);
        color: #60a5fa;
        border-color: rgba(59, 130, 246, 0.3);
    }

    @keyframes pulseWarning {
        0%, 100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.4); }
        50% { box-shadow: 0 0 0 6px rgba(225, 29, 72, 0); }
    }

    .notif-card-main {
        flex: 1;
        min-width: 0;
    }

    .notif-top-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 8px;
        flex-wrap: wrap;
    }
    .notif-badges-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .notif-kind-badge {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 3px 9px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .kind-badge-absence { background: rgba(239, 68, 68, 0.18); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.32); }
    .kind-badge-critical { background: rgba(225, 29, 72, 0.22); color: #fda4af; border: 1px solid rgba(225, 29, 72, 0.38); }
    .kind-badge-warning { background: rgba(245, 158, 11, 0.18); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.32); }
    .kind-badge-system { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.32); }
    .kind-badge-notice { background: rgba(59, 130, 246, 0.18); color: #93c5fd; border: 1px solid rgba(59, 130, 246, 0.32); }

    .notif-unread-pill {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        padding: 2px 7px;
        border-radius: 99px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.4);
    }
    .notif-unread-pill::before {
        content: '';
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: #ffffff;
        display: inline-block;
    }

    .notif-timestamp {
        font-size: 0.74rem;
        color: rgba(243, 231, 205, 0.55);
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .notif-message-text {
        font-size: 0.92rem;
        line-height: 1.55;
        color: #f1ede4;
        margin-bottom: 12px;
        word-break: break-word;
    }

    .notif-meta-tags {
        display: flex;
        align-items: center;
        gap: 14px;
        font-size: 0.78rem;
        color: rgba(243, 231, 205, 0.65);
        flex-wrap: wrap;
        margin-bottom: 14px;
    }
    .notif-meta-item {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, 0.03);
        padding: 2px 8px;
        border-radius: 6px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    /* Action Buttons Row */
    .notif-actions-row {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding-top: 10px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }
    .btn-notif-action {
        padding: 6px 13px;
        border-radius: 9px;
        font-size: 0.77rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        border: 1px solid;
    }
    .btn-notif-excuse {
        background: rgba(245, 158, 11, 0.16);
        color: #fbbf24;
        border-color: rgba(245, 158, 11, 0.32);
    }
    .btn-notif-excuse:hover {
        background: rgba(245, 158, 11, 0.28);
        color: #ffffff;
        border-color: #fbbf24;
        transform: translateY(-1px);
    }
    .btn-notif-records {
        background: rgba(255, 209, 102, 0.12);
        color: var(--notif-gold);
        border-color: rgba(255, 209, 102, 0.28);
    }
    .btn-notif-records:hover {
        background: rgba(255, 209, 102, 0.24);
        color: #ffffff;
        border-color: var(--notif-gold);
        transform: translateY(-1px);
    }
    .btn-notif-read {
        background: rgba(255, 255, 255, 0.06);
        color: #e2e8f0;
        border-color: rgba(255, 255, 255, 0.12);
    }
    .btn-notif-read:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.24);
    }
    .btn-notif-archive {
        background: rgba(255, 255, 255, 0.04);
        color: rgba(243, 231, 205, 0.65);
        border-color: rgba(255, 255, 255, 0.08);
    }
    .btn-notif-archive:hover {
        background: rgba(255, 255, 255, 0.09);
        color: #ffffff;
    }
    .btn-notif-unarchive {
        background: rgba(16, 185, 129, 0.14);
        color: #34d399;
        border-color: rgba(16, 185, 129, 0.3);
    }
    .btn-notif-unarchive:hover {
        background: rgba(16, 185, 129, 0.26);
        color: #ffffff;
    }
    .btn-notif-delete {
        background: transparent;
        color: rgba(239, 68, 68, 0.7);
        border-color: transparent;
        padding: 6px 10px;
        margin-left: auto;
    }
    .btn-notif-delete:hover {
        background: rgba(239, 68, 68, 0.12);
        color: #ef4444;
        border-color: rgba(239, 68, 68, 0.25);
    }

    /* ── Empty State ── */
    .notif-empty-state {
        text-align: center;
        padding: 64px 20px;
        background: rgba(22, 14, 12, 0.5);
        border: 1px dashed rgba(255, 209, 102, 0.15);
        border-radius: 20px;
    }
    .notif-empty-icon {
        width: 72px;
        height: 72px;
        border-radius: 20px;
        background: rgba(255, 209, 102, 0.08);
        border: 1px solid rgba(255, 209, 102, 0.18);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--notif-gold);
        font-size: 2rem;
        margin-bottom: 16px;
    }
    .notif-empty-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #fff6e5;
        margin-bottom: 6px;
    }
    .notif-empty-desc {
        font-size: 0.85rem;
        color: rgba(243, 231, 205, 0.6);
        max-width: 360px;
        margin: 0 auto 20px;
    }

    /* ── Mobile Responsive Overrides ── */
    @media (max-width: 768px) {
        .notif-wrapper {
            padding: 8px 12px 70px;
        }
        .notif-hero {
            padding: 20px 18px;
            border-radius: 20px;
            margin-bottom: 16px;
        }
        .notif-hero-content {
            flex-direction: column;
            align-items: stretch;
            gap: 14px;
        }
        .notif-hero-actions {
            width: 100%;
        }
        .notif-btn-mark-all {
            width: 100%;
            justify-content: center;
            min-height: 42px;
        }
        .notif-stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .notif-stat-card {
            padding: 12px 14px;
            border-radius: 14px;
        }
        .notif-stat-num { font-size: 1.25rem; }
        .notif-controls-card {
            padding: 12px 14px;
            border-radius: 16px;
            margin-bottom: 16px;
        }
        .notif-tabs-row {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }
        .notif-status-tabs {
            width: 100%;
            justify-content: space-between;
        }
        .notif-tab {
            flex: 1;
            justify-content: center;
            padding: 8px 6px;
        }
        .notif-search-box {
            max-width: 100%;
            width: 100%;
        }
        .notif-item-card {
            padding: 16px;
            border-radius: 16px;
        }
        .notif-card-inner {
            gap: 12px;
        }
        .notif-type-avatar {
            width: 40px;
            height: 40px;
            font-size: 1.15rem;
            border-radius: 12px;
        }
        .notif-top-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
        }
        .notif-actions-row {
            gap: 6px;
        }
        .btn-notif-action {
            flex: 1;
            justify-content: center;
            min-height: 38px;
        }
        .btn-notif-delete {
            flex: 0 0 auto;
            min-height: 38px;
            width: 38px;
            justify-content: center;
            padding: 0;
        }
    }
</style>

<div class="notif-wrapper">
    {{-- Hero Banner --}}
    <div class="notif-hero">
        <div class="notif-hero-content">
            <div class="notif-hero-left">
                <div class="notif-hero-icon">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <div class="notif-hero-text">
                    <h1>
                        Notifications & Alerts
                        @if(($counts['unread'] ?? 0) > 0)
                            <span class="notif-unread-pill" id="heroUnreadPill">{{ $counts['unread'] }} NEW</span>
                        @endif
                    </h1>
                    <p>Official class attendance records, warning alerts, and academic updates</p>
                </div>
            </div>
            <div class="notif-hero-actions">
                <button type="button" 
                        class="notif-btn-mark-all" 
                        id="btnMarkAllRead" 
                        onclick="markAllNotificationsRead()"
                        {{ ($counts['unread'] ?? 0) === 0 ? 'disabled' : '' }}>
                    <i class="bi bi-check2-all"></i> Mark All as Read
                </button>
            </div>
        </div>
    </div>

    {{-- Quick Stats Cards --}}
    <div class="notif-stats-grid">
        <a href="{{ route('notifications') }}" 
           class="notif-stat-card {{ !request('status') || request('status') === 'active' ? 'active' : '' }}">
            <div class="notif-stat-info">
                <span class="notif-stat-num" id="statActiveCount">{{ $counts['active'] ?? 0 }}</span>
                <span class="notif-stat-label">Active Notices</span>
            </div>
            <div class="notif-stat-icon" style="background:rgba(255,209,102,0.12); color:#ffd166;">
                <i class="bi bi-bell"></i>
            </div>
        </a>

        <a href="{{ route('notifications') }}?status=unread" 
           class="notif-stat-card {{ request('status') === 'unread' ? 'active' : '' }}">
            <div class="notif-stat-info">
                <span class="notif-stat-num" id="statUnreadCount" style="color:{{ ($counts['unread'] ?? 0) > 0 ? '#f87171' : '#ffffff' }};">{{ $counts['unread'] ?? 0 }}</span>
                <span class="notif-stat-label">Unread</span>
            </div>
            <div class="notif-stat-icon" style="background:rgba(239,68,68,0.14); color:#ef4444;">
                <i class="bi bi-envelope-exclamation-fill"></i>
            </div>
        </a>

        <a href="{{ route('notifications') }}?type=absence" 
           class="notif-stat-card {{ request('type') === 'absence' ? 'active' : '' }}">
            <div class="notif-stat-info">
                <span class="notif-stat-num" id="statAbsencesCount">{{ $counts['absences'] ?? 0 }}</span>
                <span class="notif-stat-label">Absences</span>
            </div>
            <div class="notif-stat-icon" style="background:rgba(245,158,11,0.14); color:#fbbf24;">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
        </a>

        <a href="{{ route('notifications') }}?status=archived" 
           class="notif-stat-card {{ request('status') === 'archived' ? 'active' : '' }}">
            <div class="notif-stat-info">
                <span class="notif-stat-num" id="statArchivedCount">{{ $counts['archived'] ?? 0 }}</span>
                <span class="notif-stat-label">Archived</span>
            </div>
            <div class="notif-stat-icon" style="background:rgba(148,163,184,0.12); color:#94a3b8;">
                <i class="bi bi-archive-fill"></i>
            </div>
        </a>
    </div>

    {{-- Filter & Search Controls Card --}}
    <div class="notif-controls-card">
        <div class="notif-tabs-row">
            {{-- Status Tabs --}}
            <div class="notif-status-tabs">
                <a href="{{ route('notifications') }}" 
                   class="notif-tab {{ !request('status') || request('status') === 'active' ? 'active' : '' }}">
                    <i class="bi bi-inbox-fill"></i> Active
                    <span class="notif-tab-badge">{{ $counts['active'] ?? 0 }}</span>
                </a>
                <a href="{{ route('notifications') }}?status=unread" 
                   class="notif-tab {{ request('status') === 'unread' ? 'active' : '' }}">
                    <i class="bi bi-envelope-fill"></i> Unread
                    <span class="notif-tab-badge">{{ $counts['unread'] ?? 0 }}</span>
                </a>
                <a href="{{ route('notifications') }}?status=archived" 
                   class="notif-tab {{ request('status') === 'archived' ? 'active' : '' }}">
                    <i class="bi bi-archive-fill"></i> Archived
                    <span class="notif-tab-badge">{{ $counts['archived'] ?? 0 }}</span>
                </a>
            </div>

            {{-- Quick Client & Server Search --}}
            <form method="GET" action="{{ route('notifications') }}" class="notif-search-box" id="notifSearchForm">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                @if(request('type'))
                    <input type="hidden" name="type" value="{{ request('type') }}">
                @endif
                <i class="bi bi-search notif-search-icon"></i>
                <input type="text" 
                       name="search" 
                       id="notifSearchInput" 
                       class="notif-search-input" 
                       placeholder="Search alerts or subjects..." 
                       value="{{ request('search') }}"
                       autocomplete="off">
                @if(request('search'))
                    <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => request('type')])) }}" class="notif-search-clear" title="Clear search">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
            </form>
        </div>

        {{-- Category Pills --}}
        <div class="notif-cat-pills">
            <a href="{{ route('notifications', array_filter(['status' => request('status')])) }}" 
               class="notif-pill {{ !request('type') ? 'active' : '' }}">
                <i class="bi bi-stars"></i> All Types
            </a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'absence'])) }}" 
               class="notif-pill {{ request('type') === 'absence' ? 'active' : '' }}">
                <i class="bi bi-clipboard-x-fill" style="color:#f87171;"></i> Absences ({{ $counts['absences'] ?? 0 }})
            </a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'warning'])) }}" 
               class="notif-pill {{ request('type') === 'warning' ? 'active' : '' }}">
                <i class="bi bi-exclamation-octagon-fill" style="color:#fbbf24;"></i> Warnings ({{ $counts['warnings'] ?? 0 }})
            </a>
            <a href="{{ route('notifications', array_filter(['status' => request('status'), 'type' => 'system'])) }}" 
               class="notif-pill {{ request('type') === 'system' ? 'active' : '' }}">
                <i class="bi bi-rocket-takeoff-fill" style="color:#34d399;"></i> System Updates
            </a>
        </div>
    </div>

    {{-- Notifications List --}}
    @php
        $getVisualDetails = function($type) {
            if ($type === 'system_update') {
                return [
                    'avatar_class' => 'type-avatar-system_update',
                    'icon' => 'bi-rocket-takeoff-fill',
                    'badge_class' => 'kind-badge-system',
                    'badge_label' => 'System Update'
                ];
            } elseif ($type === 'warning_3' || $type === 'warning_consecutive_3') {
                return [
                    'avatar_class' => 'type-avatar-warning_3',
                    'icon' => 'bi-exclamation-octagon-fill',
                    'badge_class' => 'kind-badge-critical',
                    'badge_label' => 'Critical Warning'
                ];
            } elseif ($type === 'warning_2' || str_contains($type, 'warning')) {
                return [
                    'avatar_class' => 'type-avatar-warning_2',
                    'icon' => 'bi-exclamation-triangle-fill',
                    'badge_class' => 'kind-badge-warning',
                    'badge_label' => 'Attendance Warning'
                ];
            } elseif ($type === 'absence') {
                return [
                    'avatar_class' => 'type-avatar-absence',
                    'icon' => 'bi-clipboard-x-fill',
                    'badge_class' => 'kind-badge-absence',
                    'badge_label' => 'Absence Recorded'
                ];
            } elseif (str_contains($type, 'excuse')) {
                return [
                    'avatar_class' => 'type-avatar-excuse',
                    'icon' => 'bi-file-earmark-medical-fill',
                    'badge_class' => 'kind-badge-warning',
                    'badge_label' => 'Excuse Update'
                ];
            } else {
                return [
                    'avatar_class' => 'type-avatar-custom',
                    'icon' => 'bi-info-circle-fill',
                    'badge_class' => 'kind-badge-notice',
                    'badge_label' => 'Notice'
                ];
            }
        };
    @endphp

    <div class="notif-list" id="notifListContainer">
        @forelse($notifications as $notification)
            @php 
                $vis = $getVisualDetails($notification->type); 
                $isArchived = $notification->isArchived();
                $isUnread = !$notification->is_read;
            @endphp
            <div class="notif-item-card {{ $isUnread ? 'unread' : '' }} {{ $isArchived ? 'archived-card' : '' }}" 
                 id="notifCard_{{ $notification->id }}" 
                 data-notif-id="{{ $notification->id }}"
                 data-is-unread="{{ $isUnread ? 'true' : 'false' }}">
                <div class="notif-card-inner">
                    {{-- Avatar Icon --}}
                    <div class="notif-type-avatar {{ $vis['avatar_class'] }}">
                        <i class="bi {{ $vis['icon'] }}"></i>
                    </div>

                    {{-- Main Content --}}
                    <div class="notif-card-main">
                        <div class="notif-top-row">
                            <div class="notif-badges-left">
                                <span class="notif-kind-badge {{ $vis['badge_class'] }}">
                                    <i class="bi {{ $vis['icon'] }}"></i>
                                    {{ $vis['badge_label'] }}
                                </span>
                                @if($isUnread)
                                    <span class="notif-unread-pill" id="itemUnreadBadge_{{ $notification->id }}">NEW</span>
                                @endif
                                @if($notification->subject_code)
                                    <span class="notif-meta-item">
                                        <i class="bi bi-book-fill" style="color:var(--notif-gold);"></i>
                                        {{ $notification->subject_code }}
                                    </span>
                                @endif
                            </div>
                            <span class="notif-timestamp" title="{{ $notification->created_at->format('M d, Y h:i A') }}">
                                <i class="bi bi-clock"></i> {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <div class="notif-message-text">
                            {{ $notification->message }}
                        </div>

                        <div class="notif-meta-tags">
                            @if($notification->subject)
                                <span class="notif-meta-item">
                                    <i class="bi bi-mortarboard"></i>
                                    {{ $notification->subject->name }}
                                </span>
                            @endif
                            @if($notification->sender)
                                <span class="notif-meta-item">
                                    <i class="bi bi-person-badge"></i>
                                    From {{ $notification->sender->name }}
                                </span>
                            @endif
                            <span class="notif-meta-item">
                                <i class="bi bi-calendar-event"></i>
                                {{ $notification->created_at->format('M j, Y • g:i A') }}
                            </span>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="notif-actions-row">
                            {{-- For Absences or Warnings: Provide Direct Excuse / Record links --}}
                            @if($notification->type === 'absence' || str_contains($notification->type, 'warning'))
                                <a href="{{ route('excuses') }}" class="btn-notif-action btn-notif-excuse">
                                    <i class="bi bi-file-earmark-medical"></i> Submit Excuse
                                </a>
                                <a href="{{ route('attendance.records') }}" class="btn-notif-action btn-notif-records">
                                    <i class="bi bi-clock-history"></i> View Records
                                </a>
                            @endif

                            {{-- Mark as Read Button (if unread) --}}
                            @if($isUnread)
                                <button type="button" 
                                        class="btn-notif-action btn-notif-read" 
                                        id="btnMarkRead_{{ $notification->id }}"
                                        onclick="markSingleNotificationRead({{ $notification->id }})">
                                    <i class="bi bi-check2"></i> Mark as Read
                                </button>
                            @endif

                            {{-- Archive / Unarchive Button --}}
                            @if($isArchived)
                                <button type="button" 
                                        class="btn-notif-action btn-notif-unarchive" 
                                        onclick="unarchiveNotification({{ $notification->id }})">
                                    <i class="bi bi-arrow-up-circle"></i> Restore
                                </button>
                            @else
                                <button type="button" 
                                        class="btn-notif-action btn-notif-archive" 
                                        onclick="archiveNotification({{ $notification->id }})">
                                    <i class="bi bi-archive"></i> Archive
                                </button>
                            @endif

                            {{-- Delete Button --}}
                            <button type="button" 
                                    class="btn-notif-action btn-notif-delete" 
                                    title="Delete notification"
                                    aria-label="Delete notification"
                                    onclick="deleteNotification({{ $notification->id }})">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="notif-empty-state">
                <div class="notif-empty-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <div class="notif-empty-title">
                    @if(request('status') === 'unread')
                        All caught up!
                    @elseif(request('status') === 'archived')
                        No archived notifications
                    @elseif(request('search'))
                        No notifications match "{{ request('search') }}"
                    @else
                        No notifications found
                    @endif
                </div>
                <div class="notif-empty-desc">
                    @if(request('status') === 'unread')
                        You have read all your alerts and notifications. New updates will appear here in real-time.
                    @elseif(request('status') === 'archived')
                        Notifications you archive will be saved here for your reference.
                    @else
                        Attendance warnings, absence notices, and system alerts will automatically be posted here.
                    @endif
                </div>
                @if(request()->hasAny(['status', 'type', 'search']))
                    <a href="{{ route('notifications') }}" class="btn-notif-action btn-notif-records" style="display:inline-flex;">
                        <i class="bi bi-arrow-left"></i> View All Active Notifications
                    </a>
                @else
                    <a href="{{ route('home') }}" class="btn-notif-action btn-notif-records" style="display:inline-flex;">
                        <i class="bi bi-grid-fill"></i> Return to Dashboard
                    </a>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     INTERACTIVE JAVASCRIPT — CSP NONCE PROTECTED
     ══════════════════════════════════════════════════════════════ --}}
<script nonce="{{ csp_nonce() }}">
(function() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    function triggerHapticFeedback(type = 'light') {
        if (typeof window.triggerHaptic === 'function') {
            window.triggerHaptic(type);
        }
    }

    function updateBadgeElements(unreadCount) {
        // Update hero pill
        const heroPill = document.getElementById('heroUnreadPill');
        const markAllBtn = document.getElementById('btnMarkAllRead');
        const statUnread = document.getElementById('statUnreadCount');

        if (unreadCount <= 0) {
            if (heroPill) heroPill.style.display = 'none';
            if (markAllBtn) markAllBtn.disabled = true;
            if (statUnread) {
                statUnread.textContent = '0';
                statUnread.style.color = '#ffffff';
            }
        } else {
            if (heroPill) {
                heroPill.style.display = 'inline-flex';
                heroPill.textContent = unreadCount + ' NEW';
            }
            if (markAllBtn) markAllBtn.disabled = false;
            if (statUnread) {
                statUnread.textContent = unreadCount;
                statUnread.style.color = '#f87171';
            }
        }

        // Also sync top navbar bell
        if (typeof window.refreshNotificationBell === 'function') {
            window.refreshNotificationBell();
        } else {
            const topNavBadge = document.getElementById('topNavNotifBadge');
            if (topNavBadge) {
                if (unreadCount <= 0) topNavBadge.remove();
                else topNavBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
            }
        }
    }

    // Mark single notification as read
    window.markSingleNotificationRead = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        const readBtn = document.getElementById('btnMarkRead_' + id);
        const itemBadge = document.getElementById('itemUnreadBadge_' + id);

        fetch('/notifications/' + id + '/read', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (card) {
                    card.classList.remove('unread');
                    card.setAttribute('data-is-unread', 'false');
                }
                if (readBtn) readBtn.remove();
                if (itemBadge) itemBadge.remove();
                if (typeof data.unread_count !== 'undefined') {
                    updateBadgeElements(data.unread_count);
                }
                if (typeof showPremiumToast === 'function') {
                    showPremiumToast('Marked as read', 'success');
                }
            }
        })
        .catch(err => {
            console.error('Error marking read:', err);
        });
    };

    // Mark all notifications as read
    window.markAllNotificationsRead = function() {
        triggerHapticFeedback('medium');
        const markAllBtn = document.getElementById('btnMarkAllRead');
        if (markAllBtn) {
            markAllBtn.disabled = true;
            markAllBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Marking...';
        }

        fetch('{{ route("notifications.read") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update all unread cards in view
                document.querySelectorAll('.notif-item-card.unread').forEach(card => {
                    card.classList.remove('unread');
                    card.setAttribute('data-is-unread', 'false');
                });
                document.querySelectorAll('[id^="btnMarkRead_"]').forEach(btn => btn.remove());
                document.querySelectorAll('[id^="itemUnreadBadge_"]').forEach(badge => badge.remove());

                updateBadgeElements(0);

                if (markAllBtn) {
                    markAllBtn.innerHTML = '<i class="bi bi-check2-all"></i> All Read';
                }
                if (typeof showPremiumToast === 'function') {
                    showPremiumToast('All notifications marked as read', 'success');
                }
            }
        })
        .catch(err => {
            console.error('Error marking all read:', err);
            if (markAllBtn) {
                markAllBtn.disabled = false;
                markAllBtn.innerHTML = '<i class="bi bi-check2-all"></i> Mark All as Read';
            }
        });
    };

    // Archive notification
    window.archiveNotification = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        if (!card) return;

        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateX(20px)';

        fetch('/notifications/' + id + '/archive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    card.remove();
                    if (typeof data.unread_count !== 'undefined') {
                        updateBadgeElements(data.unread_count);
                    }
                    const activeCountEl = document.getElementById('statActiveCount');
                    if (activeCountEl) {
                        const cur = parseInt(activeCountEl.textContent) || 0;
                        if (cur > 0) activeCountEl.textContent = cur - 1;
                    }
                    const archivedCountEl = document.getElementById('statArchivedCount');
                    if (archivedCountEl) {
                        const cur = parseInt(archivedCountEl.textContent) || 0;
                        archivedCountEl.textContent = cur + 1;
                    }
                }, 250);
                if (typeof showPremiumToast === 'function') {
                    showPremiumToast('Notification archived', 'success');
                }
            } else {
                card.style.opacity = '1';
                card.style.transform = 'none';
            }
        })
        .catch(err => {
            console.error('Error archiving:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
        });
    };

    // Unarchive notification
    window.unarchiveNotification = function(id) {
        triggerHapticFeedback('light');
        const card = document.getElementById('notifCard_' + id);
        if (!card) return;

        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'translateX(-20px)';

        fetch('/notifications/' + id + '/unarchive', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    card.remove();
                    const activeCountEl = document.getElementById('statActiveCount');
                    if (activeCountEl) {
                        const cur = parseInt(activeCountEl.textContent) || 0;
                        activeCountEl.textContent = cur + 1;
                    }
                    const archivedCountEl = document.getElementById('statArchivedCount');
                    if (archivedCountEl) {
                        const cur = parseInt(archivedCountEl.textContent) || 0;
                        if (cur > 0) archivedCountEl.textContent = cur - 1;
                    }
                }, 250);
                if (typeof showPremiumToast === 'function') {
                    showPremiumToast('Notification restored to active', 'success');
                }
            } else {
                card.style.opacity = '1';
                card.style.transform = 'none';
            }
        })
        .catch(err => {
            console.error('Error unarchiving:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
        });
    };

    // Delete notification
    window.deleteNotification = function(id) {
        if (!confirm('Are you sure you want to delete this notification?')) return;
        triggerHapticFeedback('heavy');

        const card = document.getElementById('notifCard_' + id);
        if (!card) return;

        card.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
        card.style.opacity = '0';
        card.style.transform = 'scale(0.95)';

        fetch('/notifications/' + id, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                setTimeout(() => {
                    card.remove();
                    if (typeof data.unread_count !== 'undefined') {
                        updateBadgeElements(data.unread_count);
                    }
                }, 250);
                if (typeof showPremiumToast === 'function') {
                    showPremiumToast('Notification deleted', 'success');
                }
            } else {
                card.style.opacity = '1';
                card.style.transform = 'none';
            }
        })
        .catch(err => {
            console.error('Error deleting:', err);
            card.style.opacity = '1';
            card.style.transform = 'none';
        });
    };

    // Client-side instant filter on search input keyup
    const searchInput = document.getElementById('notifSearchInput');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.toLowerCase().trim();
            debounceTimer = setTimeout(() => {
                const cards = document.querySelectorAll('.notif-item-card');
                let visibleCount = 0;
                cards.forEach(card => {
                    const text = card.textContent.toLowerCase();
                    if (!query || text.includes(query)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });
            }, 150);
        });
    }
})();
</script>
@endsection