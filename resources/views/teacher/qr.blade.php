@extends('layouts.app')
@section('page-title', 'QR Attendance - ' . $subject->name)

@push('styles')
<style>
:root {
    --tch-primary: #cfa46f;
    --tch-dark: #1e1515;
    --tch-light: #e5be8a;
    --tch-accent: #f59e0b;
}

/* Main Card Glass Effect */
.main-card {
    background: rgba(30, 21, 21, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 20px;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}

.main-card-header {
    background: rgba(0, 0, 0, 0.3);
    color: #f3e7cd;
    padding: 20px 24px;
    border-bottom: 1px solid rgba(212, 175, 55, 0.15);
}

.main-card-header h4 {
    margin-bottom: 4px;
    font-weight: 800;
    color: #f3e7cd;
    letter-spacing: -0.02em;
}

/* QR Container with Glass Morphism */
.qr-container { 
    min-height: 380px;
    background: rgba(0, 0, 0, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 2px dashed rgba(207, 164, 111, 0.25);
    border-radius: 20px;
    display: flex; 
    flex-direction: column; 
    align-items: center; 
    justify-content: center; 
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    padding: 28px;
}

.qr-container.active { 
    background: rgba(207, 164, 111, 0.04);
    border: 2px solid rgba(207, 164, 111, 0.4);
    box-shadow: 0 0 30px rgba(207, 164, 111, 0.15);
}

.qr-container img { 
    border-radius: 16px; 
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
    transition: all 0.3s ease;
    border: 4px solid #ffffff;
}

.qr-container img:hover { transform: scale(1.03); }

/* Buttons */
.modern-btn { 
    background: linear-gradient(135deg, var(--gold), #b88a44) !important;
    border: none !important;
    color: #1a1a2e !important;
    padding: 14px 28px !important;
    border-radius: 12px !important;
    font-weight: 700 !important;
    font-size: 0.95rem !important;
    box-shadow: 0 6px 20px rgba(207, 164, 111, 0.25) !important;
    transition: all 0.2s ease !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.modern-btn:hover { 
    transform: translateY(-2px) !important;
    box-shadow: 0 10px 28px rgba(207, 164, 111, 0.35) !important;
    filter: brightness(1.08);
}

.modern-btn:disabled {
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    transform: none !important;
    box-shadow: none !important;
}

.modern-btn.warning { 
    background: linear-gradient(135deg, #f59e0b, #d97706) !important;
    color: #1a1a2e !important;
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.25) !important;
}

.modern-btn.danger { 
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    color: #ffffff !important;
    box-shadow: 0 6px 20px rgba(239, 68, 68, 0.25) !important;
}

/* Glass Session Timer */
.session-timer { 
    background: rgba(0, 0, 0, 0.4) !important;
    backdrop-filter: blur(20px) !important;
    color: #f3e7cd !important;
    border: 1px solid rgba(212, 175, 55, 0.25) !important;
    border-radius: 16px !important;
    padding: 16px 24px !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
}

.timer-display { 
    font-family: 'Courier New', monospace !important; 
    font-size: 1.4rem !important;
    color: #f59e0b;
}

/* Glass Statistics Card */
.stats-card { 
    background: rgba(30, 21, 21, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 20px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    transition: all 0.3s ease;
}

.stats-header { 
    background: rgba(0, 0, 0, 0.3);
    color: #f3e7cd;
    padding: 16px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(212, 175, 55, 0.12);
}

.stats-header h5 { 
    margin: 0; 
    font-weight: 700; 
    font-size: 1rem;
    color: #f3e7cd;
}

.live-indicator { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

.live-dot { 
    width: 9px; height: 9px; 
    background: #10b981; 
    border-radius: 50%; 
    animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.stats-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 14px; 
    padding: 20px; 
}

.stat-item { 
    text-align: center; 
    padding: 14px; 
    background: rgba(0, 0, 0, 0.25);
    border-radius: 12px; 
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
}

.stat-number { 
    font-size: 1.8rem; 
    font-weight: 800; 
    margin-bottom: 4px;
    color: #f3e7cd;
}

.stat-label { 
    font-size: 0.75rem; 
    font-weight: 600; 
    color: #b39b82; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
}

/* Glass Clock-ins Items */
.clockin-item { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 12px 14px; 
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px; 
    margin-bottom: 10px; 
    transition: all 0.2s ease;
}

.clockin-item:hover { 
    background: rgba(207, 164, 111, 0.08);
    border-color: rgba(207, 164, 111, 0.25);
}

.avatar-circle { 
    width: 40px; height: 40px; 
    background: rgba(207, 164, 111, 0.15);
    color: #cfa46f; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-weight: 700; 
    font-size: 0.85rem;
    border: 1px solid rgba(207, 164, 111, 0.3);
}

.status-badge { 
    padding: 4px 10px; 
    border-radius: 20px; 
    font-size: 0.7rem; 
    font-weight: 700; 
    text-transform: uppercase; 
    display: inline-block;
}

.status-present { 
    background: rgba(16, 185, 129, 0.15); 
    color: #4ade80;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-late { 
    background: rgba(245, 158, 11, 0.15); 
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.status-absent { 
    background: rgba(239, 68, 68, 0.15); 
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-missing {
    background: rgba(255, 255, 255, 0.05);
    color: #888;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

/* Alert Boxes Dark Mode High Contrast */
.qr-alert {
    padding: 16px 20px;
    border-radius: 14px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    width: 100%;
}

.qr-alert-success {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #4ade80;
}
.qr-alert-success .qr-alert-title { color: #86efac; font-weight: 700; }
.qr-alert-success .qr-alert-body { color: #dcfce7; font-size: 0.85rem; }

.qr-alert-warning {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}
.qr-alert-warning .qr-alert-title { color: #fde68a; font-weight: 700; }
.qr-alert-warning .qr-alert-body { color: #fef3c7; font-size: 0.85rem; }

.qr-alert-danger {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}
.qr-alert-danger .qr-alert-title { color: #fca5a5; font-weight: 700; }
.qr-alert-danger .qr-alert-body { color: #fee2e2; font-size: 0.85rem; }

.qr-alert-info {
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
}
.qr-alert-info .qr-alert-title { color: #93c5fd; font-weight: 700; }
.qr-alert-info .qr-alert-body { color: #dbeafe; font-size: 0.85rem; }

@media (max-width: 768px) {
    #sidebar {
        width: 100% !important;
        min-width: auto !important;
        flex-shrink: 1 !important;
    }
}
</style>
@endpush

@section('content')

<div class="container-fluid ent-fade-up">
    <div class="row" style="display: flex; gap: 24px; flex-wrap: wrap;">
        <!-- Main QR Section -->
        <div style="flex: 1; min-width: 320px;" id="mainSection">
            <div class="main-card">
                <div class="main-card-header">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h4 class="mb-1" style="display: flex; align-items: center; gap: 8px;">
                                <i class="bi bi-qr-code-scan" style="color: #cfa46f;"></i> {{ $subject->name }} ({{ $subject->code }})
                            </h4>
                            <small style="color: #b39b82;">
                                Year {{ $subject->year_level }} • Semester {{ $subject->semester }} • 
                                <span class="badge" style="background: rgba(207,164,111,0.15); color: #cfa46f; border: 1px solid rgba(207,164,111,0.3); font-weight: 600;">
                                    Geofence: 50m Proximity
                                </span>
                            </small>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" id="copyLinkBtn" class="btn modern-btn" style="display: none; padding: 10px 18px !important; background: rgba(255,255,255,0.06) !important; color: #f3e7cd !important; border: 1px solid rgba(255,255,255,0.15) !important;" onclick="copyScanLink()">
                                <i class="bi bi-link-45deg me-1"></i> Copy Link
                            </button>
                            <button type="button" id="projectorBtn" class="btn modern-btn" style="display: none; padding: 10px 18px !important; background: linear-gradient(135deg, #d97706, #b45309) !important; color: white !important;" onclick="openProjectorMode()">
                                <i class="bi bi-display me-1"></i> Projector Mode
                            </button>
                            <a href="{{ route('teacher.subjects') }}" class="btn modern-btn" style="padding: 10px 18px !important; background: rgba(255,255,255,0.06) !important; color: #f3e7cd !important; border: 1px solid rgba(255,255,255,0.15) !important;">
                                <i class="bi bi-arrow-left me-1"></i> Subjects
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body text-center" style="min-height: 480px; padding: 2.5rem 1.5rem;">
                    
                    <!-- QR Code Display Area -->
                    <div id="qrCodeContainer" class="qr-container" style="margin: 1.5rem auto; max-width: 600px; min-height: 480px; padding: 24px;">
                        <div style="color: #b39b82;">
                            <i class="bi bi-qr-code" style="font-size: 5rem; opacity: 0.4; margin-bottom: 1rem; color: #cfa46f; display: block;"></i>
                            <h5 style="color: #f3e7cd; font-weight: 700; margin-bottom: 6px; font-size: 1.25rem;">Click "Start Session" to generate QR code</h5>
                            <p style="color: #b39b82; margin: 0; font-size: 0.95rem;">Students will scan this code to mark attendance</p>
                        </div>
                    </div>

                    <!-- Attendance Code Display Box (QR or Code) -->
                    <div id="attendanceCodeSection" style="display: none; background: rgba(0, 0, 0, 0.45); border: 1.5px solid rgba(207, 164, 111, 0.35); border-radius: 20px; padding: 18px 24px; max-width: 480px; margin: 1.5rem auto 0; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                        <div style="font-size: 0.76rem; font-weight: 800; color: #b39b82; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px;">
                            <i class="bi bi-key-fill text-warning me-1"></i> Class Attendance Code
                        </div>
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <div id="displaySessionCode" style="font-size: 2.6rem; font-weight: 900; letter-spacing: 6px; color: #ffd700; font-family: monospace; text-shadow: 0 2px 14px rgba(255,215,0,0.4);">
                                ------
                            </div>
                            <button type="button" class="btn btn-sm" onclick="copySessionCode()" style="background: rgba(207,164,111,0.15); color: #f3e7cd; border: 1px solid rgba(207,164,111,0.3); border-radius: 10px; padding: 6px 12px; font-weight: 700;" title="Copy Code">
                                <i class="bi bi-copy me-1"></i> Copy
                            </button>
                        </div>
                        <div style="font-size: 0.82rem; color: #b39b82; margin-top: 6px;">
                            Students can scan the QR code <strong>OR</strong> type this 6-digit code on their dashboard
                        </div>
                    </div>
                    
                    <!-- QR Refresh Countdown -->
                    <div id="qrRefreshCountdown" style="display: none; text-align: center; margin: 1.5rem auto;">
                        <div style="font-size: 3.2rem; font-weight: 800; font-family: monospace; color: #cfa46f; line-height: 1; text-shadow: 0 4px 12px rgba(0,0,0,0.4);">
                            <span id="refreshCountdownText">05:00</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 3px; max-width: 220px; margin: 10px auto; background: rgba(255,255,255,0.08); overflow: hidden;">
                            <div id="qrProgressIndicator" class="progress-bar" style="width: 100%; background: linear-gradient(90deg, #cfa46f, #e5be8a); transition: width 1s linear;"></div>
                        </div>
                        <small style="color: #b39b82; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 0.75rem;">Code Refresh In</small>
                    </div>
                    
                    <!-- Session Timer -->
                    <div id="sessionTimer" class="session-timer" style="display: none; margin: 1.5rem auto; max-width: 350px;">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <i class="bi bi-clock-fill" style="font-size: 1.2rem; color: #f59e0b;"></i>
                            <span>Session ends in: <span id="timeRemaining" class="timer-display">--:--</span></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-4 d-flex flex-wrap justify-content-center gap-3">
                        <button id="startBtn" class="btn modern-btn btn-lg">
                            <i class="bi bi-play-fill me-2"></i> Start Session
                        </button>
                        <button id="refreshBtn" class="btn modern-btn warning" style="display: none;">
                            <i class="bi bi-arrow-clockwise me-2"></i> Refresh QR
                        </button>
                        <button id="stopBtn" class="btn modern-btn danger" style="display: none;">
                            <i class="bi bi-stop-fill me-2"></i> Stop Session
                        </button>
                    </div>
                    
                    <div id="locationStatus" class="mt-4" style="max-width: 600px; margin-left: auto; margin-right: auto;"></div>
                    <div id="statusMessages" class="mt-3" style="max-width: 600px; margin-left: auto; margin-right: auto;"></div>
                </div>
            </div>
        </div>
        
        <!-- Live Statistics Sidebar -->
        <div id="sidebar" style="display: none; width: 350px; flex-shrink: 0; min-width: 320px;">
            <!-- Real-time Stats Card -->
            <div class="stats-card mb-4">
                <div class="stats-header">
                    <h5><i class="bi bi-bar-chart-fill me-2" style="color: #cfa46f;"></i>Live Statistics</h5>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <small style="color: #4ade80; font-weight: 700; font-size: 0.75rem;">LIVE</small>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" id="totalStudents">0</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #4ade80;" id="clockedIn">0</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #fbbf24;" id="lateCount">0</div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number" style="color: #60a5fa;" id="progressPercent">0%</div>
                        <div class="stat-label">Progress</div>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <div class="progress" style="height: 10px; border-radius: 10px; background: rgba(255,255,255,0.08);">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 0%; background: linear-gradient(135deg, #cfa46f, #b88a44); border-radius: 10px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Live Clock-ins Feed -->
            <div class="stats-card">
                <div class="stats-header">
                    <h5><i class="bi bi-people-fill me-2" style="color: #cfa46f;"></i>Live Clock-ins</h5>
                    <div class="live-dot"></div>
                </div>
                <div style="padding: 1rem 1.25rem 0.5rem;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="background: rgba(0,0,0,0.3); border-color: rgba(212,175,55,0.2); color: #cfa46f;"><i class="bi bi-search"></i></span>
                        <input type="text" id="rosterSearch" class="form-control" placeholder="Search student name or ID..." style="background: rgba(0,0,0,0.3); border-color: rgba(212,175,55,0.2); color: #f3e7cd;" oninput="filterClockins()">
                    </div>
                </div>
                <div style="max-height: 450px; overflow-y: auto; padding: 1rem 1.25rem 1.5rem;">
                    <div id="clockinsList">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-clock-history" style="font-size: 3.5rem; opacity: 0.3; color: #cfa46f;"></i>
                            <h6 class="mt-3" style="color: #f3e7cd;">Waiting for students...</h6>
                            <p class="small mb-0" style="color: #b39b82;">Clock-ins will appear here in real-time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Projector Mode Fullscreen Overlay Modal -->
<div id="projectorModal" style="display: none; position: fixed; inset: 0; background: #0f0b08; z-index: 99999; flex-direction: column; align-items: center; justify-content: center; padding: 30px; text-align: center; color: white;">
    <div style="position: absolute; top: 24px; right: 24px; display: flex; gap: 12px;">
        <button type="button" class="btn btn-outline-light btn-lg rounded-pill" onclick="closeProjectorMode()">
            <i class="bi bi-x-lg me-1"></i> Exit Fullscreen
        </button>
    </div>
    <div style="max-width: 600px; width: 100%;">
        <div class="mb-3">
            <span class="badge" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; font-size: 1rem; padding: 8px 20px; border-radius: 99px; font-weight: 700;">
                <i class="bi bi-broadcast me-1"></i> Live QR & Code Attendance
            </span>
        </div>
        <h2 style="font-size: 2.2rem; font-weight: 800; margin-bottom: 6px; color: #f3e7cd;">{{ $subject->name }}</h2>
        <p style="color: #b39b82; font-size: 1.1rem; margin-bottom: 20px;">{{ $subject->code }} • Scan with phone camera or enter code</p>

        <div id="projectorQrWrapper" style="background: white; border-radius: 28px; padding: 24px; display: inline-block; box-shadow: 0 20px 60px rgba(0,0,0,0.6); margin-bottom: 16px;">
            <div id="projectorQrCode"></div>
        </div>

        <!-- Projector Big Attendance Code -->
        <div id="projectorCodeSection" style="background: rgba(255,255,255,0.06); border: 1.5px solid rgba(207,164,111,0.4); border-radius: 22px; padding: 14px 28px; max-width: 480px; margin: 0 auto 20px;">
            <div style="font-size: 0.82rem; text-transform: uppercase; font-weight: 800; color: #b39b82; letter-spacing: 1.5px; margin-bottom: 4px;">
                <i class="bi bi-key-fill text-warning me-1"></i> Attendance Code / PIN
            </div>
            <div id="projectorSessionCode" style="font-size: 3.2rem; font-weight: 900; letter-spacing: 8px; color: #ffd700; font-family: monospace; text-shadow: 0 0 24px rgba(255,215,0,0.5);">
                ------
            </div>
        </div>

        <div class="d-flex justify-content-center align-items-center gap-4 mt-2">
            <div style="background: rgba(255,255,255,0.06); padding: 12px 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 0.8rem; color: #b39b82; text-transform: uppercase; font-weight: 600;">Attendance Progress</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #4ade80;" id="projectorCount">0 Present</div>
            </div>
            <div style="background: rgba(255,255,255,0.06); padding: 12px 24px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 0.8rem; color: #b39b82; text-transform: uppercase; font-weight: 600;">Code Refresh</div>
                <div style="font-size: 1.6rem; font-weight: 800; color: #fbbf24;" id="projectorCountdown">05:00</div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script nonce="{{ csp_nonce() }}">
let currentSession = null;
let refreshInterval = null;
let clockinInterval = null;
let timerInterval = null;
let scheduleCheckInterval = null;
let refreshCountdownInterval = null;
let teacherLocation = null;
let locationWatchId = null;
let locationTimeoutId = null;
let locationDowngraded = false;
let refreshCountdownSeconds = 300;

const startBtn = document.getElementById('startBtn');
const refreshBtn = document.getElementById('refreshBtn');
const stopBtn = document.getElementById('stopBtn');
const qrContainer = document.getElementById('qrCodeContainer');
const sidebar = document.getElementById('sidebar');
const mainSection = document.getElementById('mainSection');
const locationStatus = document.getElementById('locationStatus');

// Check schedule on page load
document.addEventListener('DOMContentLoaded', () => {
    checkScheduleStatus();
    captureTeacherLocation();
    // Check schedule status every 30 seconds
    scheduleCheckInterval = setInterval(checkScheduleStatus, 30000);
});

// Cleanup intervals when page unloads
window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);
    if (scheduleCheckInterval) clearInterval(scheduleCheckInterval);
    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);
    if (locationTimeoutId) clearTimeout(locationTimeoutId);
    if (locationWatchId !== null) navigator.geolocation.clearWatch(locationWatchId);
});

async function checkScheduleStatus() {
    try {
        const response = await fetch('{{ route("teacher.qr.schedule") }}?' + new URLSearchParams({
            subject_code: '{{ $subject->code }}'
        }));

        const scheduleData = await response.json();
        
        if (scheduleData.error) {
            showScheduleMessage('error', scheduleData.error);
            return;
        }

        updateScheduleUI(scheduleData);
    } catch (error) {
        console.error('Error checking schedule:', error);
    }
}

function updateScheduleUI(scheduleData) {
    const statusMessages = document.getElementById('statusMessages');
    
    switch (scheduleData.status) {
        case 'too_early':
            startBtn.disabled = true;
            startBtn.className = 'btn modern-btn warning btn-lg';
            startBtn.innerHTML = '<i class="bi bi-clock me-2"></i> Too Early';
            
            statusMessages.innerHTML = `
                <div class="qr-alert qr-alert-warning">
                    <i class="bi bi-clock-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <div>
                        <h6 class="qr-alert-title mb-1"><i class="bi bi-clock me-1"></i> Session Opens in ${scheduleData.schedule.session_opens}</h6>
                        <p class="qr-alert-body mb-0">
                            <strong>Class Time:</strong> ${scheduleData.schedule.class_start} - ${scheduleData.schedule.class_end}<br>
                            <strong>Current Time:</strong> ${scheduleData.schedule.current_time}<br>
                            <strong>Wait Time:</strong> ${scheduleData.message}
                        </p>
                    </div>
                </div>
            `;
            break;
            
        case 'ad_hoc':
        case 'ready':
        default:
            startBtn.disabled = false;
            startBtn.className = 'btn modern-btn btn-lg';
            startBtn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Start Session';
            
            statusMessages.innerHTML = `
                <div class="qr-alert qr-alert-success">
                    <i class="bi bi-check-circle-fill mb-2" style="font-size: 1.5rem; color: #10b981;"></i>
                    <div>
                        <h6 class="qr-alert-title mb-1"><i class="bi bi-check2-circle me-1"></i> Ready to Start Session</h6>
                        <p class="qr-alert-body mb-0">
                            ${scheduleData.message ? scheduleData.message : 'Ready to generate attendance QR code for this subject.'}
                        </p>
                    </div>
                </div>
            `;
            break;
    }
}

function showScheduleMessage(type, message) {
    const statusMessages = document.getElementById('statusMessages');
    
    if (type === 'no-schedule') {
        statusMessages.innerHTML = `
            <div class="qr-alert qr-alert-warning">
                <i class="bi bi-exclamation-triangle-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-info-circle me-1"></i> Cannot Start Session</h6>
                    <p class="qr-alert-body mb-0">${message}</p>
                </div>
            </div>
        `;
    } else if (type === 'error') {
        statusMessages.innerHTML = `
            <div class="qr-alert qr-alert-danger">
                <i class="bi bi-exclamation-triangle-fill mb-2" style="font-size: 1.5rem; color: #ef4444;"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-x-circle me-1"></i> Schedule Error</h6>
                    <p class="qr-alert-body mb-0">${message}</p>
                </div>
            </div>
        `;
    }
}

// Start session
startBtn.addEventListener('click', async () => {
    try {
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Starting...';

        const lat = teacherLocation ? teacherLocation.latitude : null;
        const lng = teacherLocation ? teacherLocation.longitude : null;

        const bodyPayload = {
            subject_code: '{{ $subject->code }}',
            classroom_lat: lat,
            classroom_lng: lng
        };

        const response = await fetch('{{ route("teacher.qr.start") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(bodyPayload)
        });

        const data = await response.json();

        if (data.success) {
            currentSession = data;
            showQRCode(data.scan_url);
            updateUIForActiveSession();
            startIntervals();
            
            if (scheduleCheckInterval) {
                clearInterval(scheduleCheckInterval);
                scheduleCheckInterval = null;
            }
            
            document.getElementById('statusMessages').innerHTML = '';
        } else {
            const statusMessages = document.getElementById('statusMessages');
            statusMessages.innerHTML = `
                <div class="qr-alert qr-alert-warning">
                    <i class="bi bi-exclamation-triangle-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <div>
                        <h6 class="qr-alert-title mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Cannot Start Session</h6>
                        <p class="qr-alert-body mb-0" style="white-space: pre-line;">${data.message}</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        const statusMessages = document.getElementById('statusMessages');
        statusMessages.innerHTML = `
            <div class="qr-alert qr-alert-danger">
                <i class="bi bi-wifi-off mb-2" style="font-size: 1.5rem; color: #ef4444;"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-exclamation-octagon me-1"></i> Network Error</h6>
                    <p class="qr-alert-body mb-0">${error.message}</p>
                </div>
            </div>
        `;
    } finally {
        startBtn.disabled = false;
        startBtn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Start Session';
    }
});

// Additional state & audio support
let clockInSoundEnabled = true;
let cachedClockins = [];

function toggleClockInSound() {
    clockInSoundEnabled = !clockInSoundEnabled;
    const icon = document.getElementById('soundIcon');
    if (clockInSoundEnabled) {
        icon.className = 'bi bi-volume-up-fill';
        showTeacherToast('Clock-in audio chimes enabled', 'info');
    } else {
        icon.className = 'bi bi-volume-mute-fill';
        showTeacherToast('Clock-in audio chimes muted', 'info');
    }
}

function playClockInChime() {
    if (!clockInSoundEnabled) return;
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08);
        gain.gain.setValueAtTime(0.12, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
        osc.start();
        osc.stop(ctx.currentTime + 0.35);
    } catch (e) {}
}

function copyScanLink() {
    if (!currentSession || !currentSession.scan_url) {
        showTeacherToast('No active session URL to copy', 'error');
        return;
    }
    navigator.clipboard.writeText(currentSession.scan_url).then(() => {
        showTeacherToast('Attendance scan URL copied to clipboard!', 'success');
    }).catch(() => {
        prompt('Copy this attendance scan URL:', currentSession.scan_url);
    });
}

function copySessionCode() {
    if (!currentSession || !currentSession.session_code) {
        showTeacherToast('No active attendance code to copy', 'error');
        return;
    }
    const cleanCode = currentSession.session_code;
    navigator.clipboard.writeText(cleanCode).then(() => {
        showTeacherToast(`Attendance code (${cleanCode}) copied to clipboard!`, 'success');
    }).catch(() => {
        prompt('Copy Attendance Code:', cleanCode);
    });
}

function openProjectorMode() {
    const modal = document.getElementById('projectorModal');
    modal.style.display = 'flex';
    if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    }
}

function closeProjectorMode() {
    const modal = document.getElementById('projectorModal');
    modal.style.display = 'none';
    if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
    }
}

function updateUIForActiveSession() {
    startBtn.style.display = 'none';
    refreshBtn.style.display = 'inline-block';
    stopBtn.style.display = 'inline-block';
    sidebar.style.display = 'block';
    document.getElementById('sessionTimer').style.display = 'block';
    document.getElementById('projectorBtn').style.display = 'inline-block';
    document.getElementById('copyLinkBtn').style.display = 'inline-block';

    if (currentSession && (currentSession.formatted_code || currentSession.session_code)) {
        const codeText = currentSession.formatted_code || currentSession.session_code;
        const codeBox = document.getElementById('displaySessionCode');
        if (codeBox) codeBox.textContent = codeText;
        const pCode = document.getElementById('projectorSessionCode');
        if (pCode) pCode.textContent = codeText;
        const codeSection = document.getElementById('attendanceCodeSection');
        if (codeSection) codeSection.style.display = 'block';
    }
}

function formatTtlCountdown(totalSecs) {
    if (totalSecs < 0) totalSecs = 0;
    const m = Math.floor(totalSecs / 60);
    const s = totalSecs % 60;
    return `${m < 10 ? '0' : ''}${m}:${s < 10 ? '0' : ''}${s}`;
}

function getRefreshIntervalSeconds() {
    const ttl = Number(currentSession?.ttl) || 300;
    return Math.max(ttl - 5, 290);
}

function resetRefreshTimers() {
    const ttl = Number(currentSession?.ttl) || 300;
    refreshCountdownSeconds = ttl;
    const formatted = formatTtlCountdown(refreshCountdownSeconds);
    document.getElementById('refreshCountdownText').textContent = formatted;
    const projCountdown = document.getElementById('projectorCountdown');
    if (projCountdown) projCountdown.textContent = formatted;
    
    const progressIndicator = document.getElementById('qrProgressIndicator');
    if (progressIndicator) {
        progressIndicator.style.transition = 'none';
        progressIndicator.style.width = '100%';
        void progressIndicator.offsetWidth;
        progressIndicator.style.transition = 'width 1s linear';
    }

    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);
    refreshCountdownInterval = setInterval(() => {
        refreshCountdownSeconds--;
        const formatted = formatTtlCountdown(refreshCountdownSeconds);
        const pCd = document.getElementById('projectorCountdown');
        if (pCd) pCd.textContent = formatted;
        document.getElementById('refreshCountdownText').textContent = formatted;

        if (refreshCountdownSeconds <= 0) {
            refreshCountdownSeconds = ttl;
            if (progressIndicator) {
                progressIndicator.style.transition = 'none';
                progressIndicator.style.width = '100%';
                void progressIndicator.offsetWidth;
                progressIndicator.style.transition = 'width 1s linear';
            }
        } else {
            if (progressIndicator) {
                const percentage = (refreshCountdownSeconds / ttl) * 100;
                progressIndicator.style.width = percentage + '%';
            }
        }
    }, 1000);

    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(() => refreshBtn.click(), getRefreshIntervalSeconds() * 1000);
}

function startIntervals() {
    document.getElementById('qrRefreshCountdown').style.display = 'block';
    resetRefreshTimers();
    startSessionTimer(currentSession.session_end);
    updateClockIns();
    if (clockinInterval) clearInterval(clockinInterval);
    clockinInterval = setInterval(updateClockIns, 5000);
}

// Refresh QR
refreshBtn.addEventListener('click', async () => {
    if (!currentSession) return;
    
    try {
        const response = await fetch('{{ route("teacher.qr.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: currentSession.session_id
            })
        });

        const data = await response.json();
        if (data.success) {
            currentSession.token = data.token;
            currentSession.scan_url = data.scan_url;
            currentSession.ttl = data.ttl || currentSession.ttl || 60;
            showQRCode(data.scan_url);
            resetRefreshTimers();
        }
    } catch (error) {
        console.error('Error refreshing:', error);
    }
});

// Stop session
stopBtn.addEventListener('click', async () => {
    if (!currentSession) return;
    
    try {
        await fetch('{{ route("teacher.qr.stop") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: currentSession.session_id
            })
        });
        
        enterGracePeriod();
    } catch (error) {
        console.error('Error stopping:', error);
        enterGracePeriod();
    }
});

function enterGracePeriod() {
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);
    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);

    startBtn.style.display = 'none';
    refreshBtn.style.display = 'none';
    stopBtn.style.display = 'none';
    document.getElementById('sessionTimer').style.display = 'none';
    document.getElementById('qrRefreshCountdown').style.display = 'none';
    document.getElementById('projectorBtn').style.display = 'none';
    document.getElementById('copyLinkBtn').style.display = 'none';
    closeProjectorMode();
    
    qrContainer.classList.remove('active');
    qrContainer.innerHTML = `
        <div style="color: #b39b82; text-align: center;">
            <i class="bi bi-clock-history" style="font-size: 4rem; opacity: 0.4; margin-bottom: 1rem; color: #cfa46f; display: block;"></i>
            <h5 style="color: #f3e7cd; font-weight: 700;">Session Closed — Grace Period Active</h5>
            <p style="color: #b39b82;">You can review and edit the roster on the right. You can leave this page when done.</p>
            <a href="{{ route('teacher.subjects') }}" class="btn modern-btn mt-3">Finish & Exit</a>
        </div>
    `;
    
    document.getElementById('statusMessages').innerHTML = `
        <div class="qr-alert qr-alert-info">
            <i class="bi bi-info-circle-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
            <div>
                <h6 class="qr-alert-title mb-1"><i class="bi bi-clock-history me-1"></i> Session Closed</h6>
                <p class="qr-alert-body mb-0">Students can no longer clock in. You can still make manual adjustments in the sidebar.</p>
            </div>
        </div>
    `;
}

function showQRCode(url) {
    qrContainer.innerHTML = `
        <div class="text-center" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div style="background: #ffffff; padding: 14px; border-radius: 20px; box-shadow: 0 16px 48px rgba(0,0,0,0.5); display: inline-block;">
                <div id="localQrCanvas" style="display: flex; justify-content: center; align-items: center; min-width: 250px; min-height: 250px;"></div>
            </div>
            <p class="mt-3 mb-0" style="color: #f3e7cd; font-size: 1.05rem; font-weight: 700;">
                <i class="bi bi-phone me-1" style="color: #cfa46f;"></i> Ask students to scan this QR code
            </p>
        </div>
    `;
    qrContainer.classList.add('active');

    const qrTarget = document.getElementById('localQrCanvas');
    if (qrTarget && typeof QRCode !== 'undefined') {
        new QRCode(qrTarget, {
            text: url,
            width: Math.min(320, Math.floor(window.innerWidth * 0.75)),
            height: Math.min(320, Math.floor(window.innerWidth * 0.75)),
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.M
        });
    }

    const projContainer = document.getElementById('projectorQrCode');
    if (projContainer) {
        projContainer.innerHTML = `
            <div style="background: #ffffff; padding: 18px; border-radius: 24px; box-shadow: 0 20px 60px rgba(0,0,0,0.7); display: inline-block;">
                <div id="projQrCanvas" style="display: flex; justify-content: center; align-items: center; min-width: 320px; min-height: 320px;"></div>
            </div>
        `;
        const projTarget = document.getElementById('projQrCanvas');
        if (projTarget && typeof QRCode !== 'undefined') {
            new QRCode(projTarget, {
                text: url,
                width: Math.min(500, Math.floor(window.innerWidth * 0.8)),
                height: Math.min(500, Math.floor(window.innerWidth * 0.8)),
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    }
}

function startSessionTimer(endTimeStr) {
    const timerDisplay = document.getElementById('timeRemaining');
    if (!timerDisplay) return;

    if (timerInterval) clearInterval(timerInterval);

    const targetTime = new Date(endTimeStr).getTime();

    timerInterval = setInterval(() => {
        const now = new Date().getTime();
        const distance = targetTime - now;

        if (distance < 0) {
            clearInterval(timerInterval);
            timerDisplay.textContent = '00:00';
            stopBtn.click();
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        timerDisplay.textContent = 
            (minutes < 10 ? '0' : '') + minutes + ':' + 
            (seconds < 10 ? '0' : '') + seconds;
    }, 1000);
}

function filterClockins() {
    const query = (document.getElementById('rosterSearch')?.value || '').toLowerCase().trim();
    renderClockinsList(query);
}

function renderClockinsList(query = '') {
    const clockinsList = document.getElementById('clockinsList');
    if (!clockinsList) return;

    let items = cachedClockins;
    if (query) {
        items = cachedClockins.filter(c => 
            (c.name && c.name.toLowerCase().includes(query)) || 
            (c.student_number && c.student_number.toLowerCase().includes(query)) ||
            (c.status && c.status.toLowerCase().includes(query))
        );
    }

    if (items.length === 0) {
        clockinsList.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-search" style="font-size: 2.5rem; opacity: 0.3; color: #cfa46f;"></i>
                <h6 class="mt-2" style="color: #f3e7cd;">${query ? 'No matching students' : 'Waiting for students...'}</h6>
                <p class="small mb-0" style="color: #b39b82;">${query ? 'Try a different search term' : 'Clock-ins will appear here in real-time'}</p>
            </div>
        `;
        return;
    }

    clockinsList.innerHTML = items.map(clockin => `
        <div class="clockin-item ${clockin.status === 'Missing' ? 'missing' : ''}">
            <div class="clockin-avatar">
                <div class="avatar-circle">${clockin.name.substring(0, 2).toUpperCase()}</div>
            </div>
            <div class="clockin-info flex-grow-1" style="min-width: 0;">
                <div class="fw-bold" style="font-size: 0.88rem; color: #f3e7cd; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clockin.name}</div>
                <div style="font-size: 0.75rem; font-family: monospace; color: #b39b82;">${clockin.student_number}</div>
            </div>
            <div class="clockin-status text-end d-flex align-items-center gap-2">
                <div style="text-align: right; min-width: 65px;">
                    <span class="status-badge status-${clockin.status.toLowerCase()}">${clockin.status}</span>
                    <div style="font-size: 0.7rem; color: #b39b82; margin-top: 2px;">${clockin.time}</div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-sm" style="background:transparent; border:none; padding:4px; color: #b39b82;" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="background: #1a1512; border: 1px solid rgba(212,175,55,0.2); font-size: 0.85rem; padding: 6px; border-radius: 10px;">
                        <li><a class="dropdown-item fw-bold text-success" href="#" onclick="overrideStatus(${clockin.id}, 'Present', event)" style="border-radius: 6px; padding: 6px 12px;"><i class="bi bi-check-circle me-2"></i>Mark Present</a></li>
                        <li><a class="dropdown-item fw-bold text-warning" href="#" onclick="overrideStatus(${clockin.id}, 'Late', event)" style="border-radius: 6px; padding: 6px 12px;"><i class="bi bi-clock me-2"></i>Mark Late</a></li>
                        <li><a class="dropdown-item fw-bold text-danger" href="#" onclick="overrideStatus(${clockin.id}, 'Absent', event)" style="border-radius: 6px; padding: 6px 12px;"><i class="bi bi-x-circle me-2"></i>Mark Absent</a></li>
                    </ul>
                </div>
            </div>
        </div>
    `).join('');
}

async function updateClockIns() {
    if (!currentSession) return;

    try {
        const response = await fetch(`{{ route("teacher.qr.clockins") }}?session_id=${currentSession.session_id}`);
        if (!response.ok) return;

        const data = await response.json();
        if (!data || !data.stats) return;

        document.getElementById('totalStudents').textContent = data.stats.total_students;
        document.getElementById('clockedIn').textContent = data.stats.clocked_in;
        document.getElementById('lateCount').textContent = data.stats.late;
        document.getElementById('progressPercent').textContent = data.stats.progress + '%';
        document.getElementById('progressBar').style.width = data.stats.progress + '%';

        const projCount = document.getElementById('projectorCount');
        if (projCount) projCount.textContent = `${data.stats.clocked_in} / ${data.stats.total_students} Present`;

        cachedClockins = data.clockins || [];
        filterClockins();
    } catch (error) {
        console.error('Error updating clock-ins:', error);
    }
}

function subscribeToTeacherAttendanceUpdates() {
    if (!window.teacherEcho) {
        setTimeout(subscribeToTeacherAttendanceUpdates, 250);
        return;
    }

    if (window.teacherAttendanceSubscribed) return;

    window.teacherAttendanceSubscribed = true;

    window.teacherEcho.private('teacher-dashboard.{{ Auth::id() }}')
        .listen('.attendance.updated', (payload) => {
            if (!currentSession || !payload || payload.subject_code !== '{{ $subject->code }}') {
                return;
            }

            if (payload.type === 'clock_in') {
                playClockInChime();
                updateClockIns();
                showTeacherToast(`${payload.student_name} clocked in for ${payload.subject_code} (${payload.status})`, 'success');
            }
        });
}

subscribeToTeacherAttendanceUpdates();

async function overrideStatus(studentId, newStatus, event) {
    event.preventDefault();
    if (!currentSession) return;
    
    try {
        const response = await fetch('{{ route("teacher.qr.override") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: currentSession.session_id,
                student_id: studentId,
                status: newStatus
            })
        });
        
        const data = await response.json();
        if (data.success) {
            updateClockIns();
            showTeacherToast(`Student marked as ${newStatus}`, 'success');
        } else {
            showTeacherToast(data.message || 'Failed to update status', 'error');
        }
    } catch (error) {
        showTeacherToast('Network error while updating status', 'error');
    }
}

function showTeacherToast(message, type = 'info') {
    const colors = {
        success: { bg: 'rgba(16,185,129,0.95)', border: 'rgba(16,185,129,0.6)' },
        warning: { bg: 'rgba(245,158,11,0.95)', border: 'rgba(245,158,11,0.6)' },
        error:   { bg: 'rgba(239,68,68,0.95)',  border: 'rgba(239,68,68,0.6)' },
        info:    { bg: 'rgba(59,130,246,0.95)',  border: 'rgba(59,130,246,0.6)' },
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: ${c.bg}; border: 1px solid ${c.border};
        color: white; padding: 14px 20px; border-radius: 14px;
        font-size: 0.85rem; font-weight: 600; max-width: 320px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        backdrop-filter: blur(12px);
        animation: slideInToast 0.35s ease;
        display: flex; align-items: center; gap: 10px;
    `;

    if (!document.getElementById('toastStyle')) {
        const style = document.createElement('style');
        style.id = 'toastStyle';
        style.textContent = `
            @keyframes slideInToast {
                from { opacity: 0; transform: translateX(60px); }
                to   { opacity: 1; transform: translateX(0); }
            }
            @keyframes fadeOutToast {
                from { opacity: 1; transform: translateX(0); }
                to   { opacity: 0; transform: translateX(60px); }
            }
        `;
        document.head.appendChild(style);
    }

    const icon = type === 'success' ? '<i class="bi bi-check-circle-fill"></i>' : type === 'warning' ? '<i class="bi bi-exclamation-triangle-fill"></i>' : type === 'error' ? '<i class="bi bi-x-circle-fill"></i>' : '<i class="bi bi-info-circle-fill"></i>';
    toast.innerHTML = `<span style="font-size:1.1rem;">${icon}</span><span>${message}</span>`;

    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.animation = 'fadeOutToast 0.35s ease forwards';
        setTimeout(() => toast.remove(), 350);
    }, 4000);
}

function captureTeacherLocation() {
    if (!navigator.geolocation) {
        locationStatus.innerHTML = `
            <div class="qr-alert qr-alert-warning">
                <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-geo-alt me-1"></i> Location Not Available</h6>
                    <p class="qr-alert-body mb-0">Your browser does not support geolocation. Students will be validated against the default campus coordinates.</p>
                </div>
            </div>
        `;
        return;
    }

    if (locationWatchId !== null) {
        navigator.geolocation.clearWatch(locationWatchId);
        locationWatchId = null;
    }
    if (locationTimeoutId) {
        clearTimeout(locationTimeoutId);
        locationTimeoutId = null;
    }
    locationDowngraded = false;

    locationStatus.innerHTML = `
        <div class="qr-alert qr-alert-info">
            <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
            <div>
                <h6 class="qr-alert-title mb-1"><i class="bi bi-geo-alt me-1"></i> Capturing Laptop Location...</h6>
                <p class="qr-alert-body mb-0">Please allow location access so the student scan area matches your laptop.</p>
            </div>
        </div>
    `;

    const onSuccess = (position) => {
        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

        const accuracy = position.coords.accuracy || 0;
        teacherLocation = {
            latitude: position.coords.latitude,
            longitude: position.coords.longitude,
            accuracy: accuracy
        };

        const isReliable = accuracy <= 200;
        startBtn.disabled = false;

        locationStatus.innerHTML = `
            <div class="qr-alert ${isReliable ? 'qr-alert-success' : 'qr-alert-warning'}">
                <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: ${isReliable ? '#10b981' : '#f59e0b'};"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-laptop me-1"></i> Using Laptop Location</h6>
                    <p class="qr-alert-body mb-1">Latitude: ${teacherLocation.latitude.toFixed(6)}, Longitude: ${teacherLocation.longitude.toFixed(6)}</p>
                    <p class="qr-alert-body mb-0">Accuracy: ${Math.round(accuracy)}m &mdash; ${isReliable ? '<i class="bi bi-check2 text-success me-1"></i> This is acceptable for classroom location.' : 'This is a weaker fix, but the session will still use it.'}</p>
                </div>
            </div>
        `;
    };

    const onError = (error) => {
        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
        if (locationTimeoutId) {
            clearTimeout(locationTimeoutId);
            locationTimeoutId = null;
        }

        if (!locationDowngraded && (error.code === error.TIMEOUT || error.code === error.POSITION_UNAVAILABLE)) {
            locationDowngraded = true;
            locationStatus.innerHTML = `
                <div class="qr-alert qr-alert-info">
                    <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    <div>
                        <h6 class="qr-alert-title mb-1"><i class="bi bi-arrow-repeat me-1"></i> Retrying location capture...</h6>
                        <p class="qr-alert-body mb-0">High accuracy did not respond. Trying a fallback request now.</p>
                    </div>
                </div>
            `;
            startLocationWatch(false);
            return;
        }

        teacherLocation = null;
        startBtn.disabled = false;

        locationStatus.innerHTML = `
            <div class="qr-alert qr-alert-info">
                <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
                <div>
                    <h6 class="qr-alert-title mb-1"><i class="bi bi-building-check me-1"></i> Using Campus Geofence</h6>
                    <p class="qr-alert-body mb-0">Device GPS fix was not detected. The attendance session will validate students using campus coordinates.</p>
                </div>
            </div>
        `;
    };

    const startLocationWatch = (highAccuracy) => {
        if (locationWatchId !== null) {
            navigator.geolocation.clearWatch(locationWatchId);
            locationWatchId = null;
        }
        if (locationTimeoutId) clearTimeout(locationTimeoutId);

        locationWatchId = navigator.geolocation.watchPosition(onSuccess, onError, {
            enableHighAccuracy: highAccuracy,
            timeout: highAccuracy ? 15000 : 20000,
            maximumAge: 10000
        });

        locationTimeoutId = setTimeout(() => {
            if (locationWatchId !== null) {
                navigator.geolocation.clearWatch(locationWatchId);
                locationWatchId = null;
            }
            if (!locationDowngraded) {
                locationDowngraded = true;
                locationStatus.innerHTML = `
                    <div class="qr-alert qr-alert-info">
                        <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
                        <div>
                            <h6 class="qr-alert-title mb-1"><i class="bi bi-arrow-repeat me-1"></i> Trying fallback...</h6>
                            <p class="qr-alert-body mb-0">If this still fails, default campus coordinates will be used.</p>
                        </div>
                    </div>
                `;
                startLocationWatch(false);
            } else {
                onError({ code: 0 });
            }
        }, highAccuracy ? 18000 : 22000);
    };

    startLocationWatch(true);
}
</script>
@endsection
