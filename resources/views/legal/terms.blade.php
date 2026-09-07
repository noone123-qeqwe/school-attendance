@extends('layouts.app')

@section('portal-title', 'Terms & Conditions')

@section('content')
<style>
    body { background: #100608; color: #f8e7d3; font-family: 'Inter', sans-serif; }
    
    .legal-wrapper {
        min-height: calc(100vh - 70px);
        padding: 36px 20px 60px 20px;
        background: radial-gradient(circle at 10% 10%, rgba(216, 179, 92, 0.09), transparent 30%),
                    radial-gradient(circle at 90% 90%, rgba(138, 21, 21, 0.15), transparent 40%),
                    linear-gradient(145deg, #150a07 0%, #1c0c0e 50%, #100608 100%);
        box-sizing: border-box;
    }

    .legal-container {
        max-width: 1100px;
        margin: 0 auto;
    }

    /* Hero Header */
    .legal-hero {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.05) 0%, rgba(216, 179, 92, 0.05) 100%);
        border: 1px solid rgba(216, 179, 92, 0.22);
        border-radius: 24px;
        padding: 36px 32px;
        margin-bottom: 30px;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
        position: relative;
        overflow: hidden;
    }
    .legal-hero::after {
        content: '';
        position: absolute;
        width: 260px; height: 260px;
        background: radial-gradient(circle, rgba(216, 179, 92, 0.12) 0%, transparent 70%);
        top: -80px; right: -60px;
        border-radius: 50%; pointer-events: none;
    }

    .legal-title {
        font-family: 'Outfit', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: #ffffff;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .legal-title i {
        color: #d8b35c;
    }

    .legal-subtitle {
        font-size: 0.95rem;
        color: rgba(248, 231, 211, 0.8);
        max-width: 780px;
        line-height: 1.6;
        margin-bottom: 20px;
    }

    .meta-badges {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: center;
    }
    .meta-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 0.8rem;
        color: #f8e7d3;
        font-weight: 500;
    }
    .meta-badge.gold {
        background: rgba(216, 179, 92, 0.15);
        border-color: rgba(216, 179, 92, 0.35);
        color: #fde68a;
        font-weight: 700;
    }

    /* Grid Layout for TOC + Content */
    .legal-grid {
        display: grid;
        grid-template-columns: 280px 1fr;
        gap: 28px;
        align-items: start;
    }

    /* Sticky Sidebar Navigation */
    .legal-toc {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 24px;
        position: sticky;
        top: 85px;
        backdrop-filter: blur(16px);
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }
    .legal-toc-title {
        font-size: 0.85rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #d8b35c;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .legal-toc-links {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .legal-toc-links li a {
        display: block;
        padding: 8px 12px;
        border-radius: 10px;
        font-size: 0.84rem;
        color: rgba(248, 231, 211, 0.75);
        text-decoration: none;
        transition: all 0.2s ease;
        line-height: 1.35;
    }
    .legal-toc-links li a:hover {
        background: rgba(216, 179, 92, 0.14);
        color: #ffffff;
        padding-left: 16px;
    }

    /* Main Policy Content Card */
    .legal-content-card {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 44px 40px;
        backdrop-filter: blur(20px);
        box-shadow: 0 24px 60px rgba(0, 0, 0, 0.4);
    }

    /* Document Content Typography */
    .policy-section {
        margin-bottom: 40px;
        padding-bottom: 32px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    }
    .policy-section:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    .policy-section h3 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: #d8b35c;
        margin-bottom: 16px;
        letter-spacing: 0.3px;
        scroll-margin-top: 100px;
    }
    .policy-section h4 {
        font-size: 1.05rem;
        font-weight: 600;
        color: #ffffff;
        margin: 22px 0 10px 0;
    }
    .policy-section p {
        font-size: 0.94rem;
        line-height: 1.75;
        color: rgba(248, 231, 211, 0.88);
        margin-bottom: 14px;
    }
    .policy-section ul {
        padding-left: 24px;
        margin-bottom: 16px;
    }
    .policy-section li {
        font-size: 0.92rem;
        line-height: 1.75;
        color: rgba(248, 231, 211, 0.85);
        margin-bottom: 8px;
    }
    .policy-section li strong {
        color: #ffffff;
    }
    .policy-section code {
        background: rgba(216, 179, 92, 0.16);
        color: #fde68a;
        padding: 2px 6px;
        border-radius: 6px;
        font-size: 0.85rem;
        font-family: monospace;
    }
    .policy-section a {
        color: #d8b35c;
        text-decoration: underline;
        text-underline-offset: 3px;
    }
    .policy-section a:hover {
        color: #fde68a;
    }

    .contact-box {
        background: rgba(216, 179, 92, 0.08);
        border: 1px solid rgba(216, 179, 92, 0.25);
        border-radius: 14px;
        padding: 20px 24px;
        margin: 18px 0;
    }
    .contact-box p {
        margin-bottom: 6px;
        color: #f8e7d3;
    }
    .contact-box p:last-child {
        margin-bottom: 0;
    }

    /* Actions Bar */
    .legal-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        flex-wrap: wrap;
        gap: 12px;
    }
    .legal-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 12px;
        font-size: 0.88rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
        cursor: pointer;
        border: 1px solid transparent;
    }
    .legal-btn-back {
        background: rgba(255, 255, 255, 0.08);
        color: #f8e7d3;
        border-color: rgba(255, 255, 255, 0.16);
    }
    .legal-btn-back:hover {
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
    }
    .legal-btn-gold {
        background: linear-gradient(135deg, #d8b35c, #b8974d);
        color: #1a080a;
        font-weight: 700;
    }
    .legal-btn-gold:hover {
        background: linear-gradient(135deg, #c9a551, #a7843f);
        color: #000;
    }

    @media (max-width: 900px) {
        .legal-grid {
            grid-template-columns: 1fr;
        }
        .legal-toc {
            display: none;
        }
        .legal-hero {
            padding: 28px 20px;
        }
        .legal-title {
            font-size: 1.6rem;
        }
        .legal-content-card {
            padding: 30px 20px;
            border-radius: 18px;
        }
    }

    @media print {
        body { background: #fff !important; color: #000 !important; }
        .legal-wrapper { background: none !important; padding: 0 !important; }
        .legal-hero, .legal-content-card { box-shadow: none !important; border: 1px solid #ddd !important; color: #000 !important; }
        .legal-toc, .legal-actions, .meta-badges, .burger-btn, #sidebar, .top-header { display: none !important; }
        .policy-section h3 { color: #8a1515 !important; }
        .policy-section p, .policy-section li { color: #222 !important; }
    }
</style>

<div class="legal-wrapper">
    <div class="legal-container">
        
        <!-- Hero Section with Document Metadata -->
        <div class="legal-hero">
            <h1 class="legal-title">
                <i class="bi bi-file-earmark-text"></i>
                {{ $terms['title'] }}
            </h1>
            <p class="legal-subtitle">
                These Terms & Conditions establish the official rules, acceptable use standards, and zero-tolerance anti-fraud regulations governing your use of the Smart Classroom Attendance System.
            </p>

            <div class="meta-badges">
                <span class="meta-badge gold">
                    <i class="bi bi-tag-fill"></i> Version {{ $terms['version'] }}
                </span>
                <span class="meta-badge">
                    <i class="bi bi-calendar-check"></i> Effective Date: {{ $terms['effective_date'] }}
                </span>
                <span class="meta-badge">
                    <i class="bi bi-clock-history"></i> Last Updated: {{ $terms['updated_at'] }}
                </span>
                <span class="meta-badge">
                    <i class="bi bi-building"></i> Osmeña Colleges
                </span>
            </div>
        </div>

        <div class="legal-grid">
            <!-- Quick Table of Contents (Sticky on Desktop) -->
            <aside class="legal-toc" aria-label="Table of Contents">
                <div class="legal-toc-title">
                    <i class="bi bi-list-nested"></i> Table of Contents
                </div>
                <ul class="legal-toc-links">
                    <li><a href="#introduction">1. Acceptance of Terms</a></li>
                    <li><a href="#eligibility">2. Authorized Users</a></li>
                    <li><a href="#account-security">3. Account Security</a></li>
                    <li><a href="#anti-fraud">4. Strict Anti-Fraud Policy</a></li>
                    <li><a href="#attendance-protocol">5. Scanning Protocols</a></li>
                    <li><a href="#system-availability">6. System Availability</a></li>
                    <li><a href="#attendance-disputes">7. Dispute Resolution</a></li>
                    <li><a href="#account-termination">8. Account Termination</a></li>
                    <li><a href="#intellectual-property">9. Intellectual Property</a></li>
                    <li><a href="#limitation-liability">10. Liability Limits</a></li>
                    <li><a href="#governing-law">11. Governing Law</a></li>
                    <li><a href="#changes-terms">12. Changes to Terms</a></li>
                    <li><a href="#contact-info">13. Contact & Support</a></li>
                </ul>
            </aside>

            <!-- Terms Body -->
            <main class="legal-content-card">
                {!! $terms['content'] !!}

                <!-- Action links -->
                <div class="legal-actions">
                    <div>
                        @auth
                            <a href="{{ route('home') }}" class="legal-btn legal-btn-back">
                                <i class="bi bi-arrow-left"></i> Return to Portal
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="legal-btn legal-btn-back">
                                <i class="bi bi-arrow-left"></i> Return to Sign In
                            </a>
                        @endauth
                    </div>

                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('privacy') }}" class="legal-btn legal-btn-gold">
                            <i class="bi bi-shield-check"></i> View Privacy Notice
                        </a>
                        <button type="button" onclick="window.print()" class="legal-btn legal-btn-back" title="Print Terms">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </main>
        </div>

    </div>
</div>
@endsection
