@extends('layouts.app')
@section('page-title', 'Live QR Attendance - ' . $subject->name)

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
    border-radius: 24px;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.35);
    overflow: hidden;
}

.main-card-header {
    background: rgba(0, 0, 0, 0.35);
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
    border-radius: 24px;
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
    box-shadow: 0 0 36px rgba(207, 164, 111, 0.15);
}

.qr-container img { 
    border-radius: 18px; 
    box-shadow: 0 16px 36px rgba(0, 0, 0, 0.5);
    transition: all 0.3s ease;
    border: 4px solid #ffffff;
}

.qr-container img:hover { transform: scale(1.02); }

/* Buttons */
.modern-btn { 
    background: linear-gradient(135deg, var(--gold), #b88a44) !important;
    border: none !important;
    color: #1a1a2e !important;
    padding: 12px 24px !important;
    border-radius: 14px !important;
    font-weight: 700 !important;
    font-size: 0.92rem !important;
    box-shadow: 0 6px 20px rgba(207, 164, 111, 0.25) !important;
    transition: all 0.2s ease !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
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

.modern-btn.secondary {
    background: rgba(255, 255, 255, 0.08) !important;
    color: #f3e7cd !important;
    border: 1px solid rgba(207, 164, 111, 0.3) !important;
    box-shadow: none !important;
}

.modern-btn.secondary:hover {
    background: rgba(207, 164, 111, 0.18) !important;
    color: #ffffff !important;
    border-color: rgba(207, 164, 111, 0.5) !important;
}

/* Session Timer */
.session-timer { 
    background: rgba(0, 0, 0, 0.4) !important;
    backdrop-filter: blur(20px) !important;
    color: #f3e7cd !important;
    border: 1px solid rgba(212, 175, 55, 0.25) !important;
    border-radius: 16px !important;
    padding: 14px 20px !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
}

.timer-display { 
    font-family: 'Consolas', 'Courier New', monospace !important; 
    font-size: 1.4rem !important;
    color: #f59e0b;
}

/* Statistics Card */
.stats-card { 
    background: rgba(30, 21, 21, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(212, 175, 55, 0.15);
    border-radius: 22px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
    overflow: hidden;
    transition: all 0.3s ease;
}

.stats-header { 
    background: rgba(0, 0, 0, 0.35);
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
    display: inline-flex; 
    align-items: center; 
    gap: 8px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 3px 10px;
    border-radius: 99px;
}

.live-dot { 
    width: 8px; height: 8px; 
    background: #10b981; 
    border-radius: 50%; 
    box-shadow: 0 0 10px #10b981;
    animation: pulseDot 2s infinite;
}

@keyframes pulseDot {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.stats-grid-4 { 
    display: grid; 
    grid-template-columns: repeat(4, 1fr); 
    gap: 10px; 
    padding: 16px; 
}

@media (max-width: 991px) {
    .stats-grid-4 {
        grid-template-columns: repeat(2, 1fr);
    }
}

.stat-item { 
    text-align: center; 
    padding: 12px 8px; 
    background: rgba(0, 0, 0, 0.3);
    border-radius: 14px; 
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
}

.stat-number { 
    font-size: 1.6rem; 
    font-weight: 800; 
    margin-bottom: 2px;
    color: #f3e7cd;
    line-height: 1.2;
}

.stat-label { 
    font-size: 0.68rem; 
    font-weight: 700; 
    color: #b39b82; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
}

/* Roster Filter Tabs */
.roster-filter-pills {
    display: flex;
    gap: 6px;
    padding: 8px 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
    overflow-x: auto;
}

.roster-pill {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #b39b82;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 0.72rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}

.roster-pill:hover {
    color: #ffffff;
    border-color: rgba(207, 164, 111, 0.4);
}

.roster-pill.active {
    background: linear-gradient(135deg, #cfa46f, #8c6d46);
    color: #181614;
    border-color: #ffd700;
}

/* Glass Clock-ins Items */
.clockin-item { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 11px 14px; 
    background: rgba(0, 0, 0, 0.25);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 14px; 
    margin-bottom: 8px; 
    transition: all 0.25s ease;
}

.clockin-item:hover { 
    background: rgba(207, 164, 111, 0.08);
    border-color: rgba(207, 164, 111, 0.25);
}

.clockin-item.just-arrived {
    animation: highlightArrival 2.5s ease-out;
    border-color: rgba(16, 185, 129, 0.6);
}

@keyframes highlightArrival {
    0% { background: rgba(16, 185, 129, 0.25); transform: translateY(-4px); }
    100% { background: rgba(0, 0, 0, 0.25); transform: translateY(0); }
}

.avatar-circle { 
    width: 38px; height: 38px; 
    background: rgba(207, 164, 111, 0.15);
    color: #cfa46f; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-weight: 800; 
    font-size: 0.82rem;
    border: 1px solid rgba(207, 164, 111, 0.3);
    flex-shrink: 0;
}

.status-badge { 
    padding: 3px 8px; 
    border-radius: 20px; 
    font-size: 0.68rem; 
    font-weight: 800; 
    text-transform: uppercase; 
    display: inline-block;
}

.status-present { 
    background: rgba(16, 185, 129, 0.18); 
    color: #4ade80;
    border: 1px solid rgba(16, 185, 129, 0.35);
}

.status-late { 
    background: rgba(245, 158, 11, 0.18); 
    color: #fbbf24;
    border: 1px solid rgba(245, 158, 11, 0.35);
}

.status-absent { 
    background: rgba(239, 68, 68, 0.18); 
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.35);
}

.status-missing {
    background: rgba(255, 255, 255, 0.06);
    color: #a8a29e;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

/* Alert Boxes Dark Mode High Contrast */
.qr-alert {
    padding: 16px 20px;
    border-radius: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    width: 100%;
}

.qr-alert-success {
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #4ade80;
}
.qr-alert-success .qr-alert-title { color: #86efac; font-weight: 700; }
.qr-alert-success .qr-alert-body { color: #dcfce7; font-size: 0.85rem; }

.qr-alert-warning {
    background: rgba(245, 158, 11, 0.12);
    border: 1px solid rgba(245, 158, 11, 0.3);
    color: #fbbf24;
}
.qr-alert-warning .qr-alert-title { color: #fde68a; font-weight: 700; }
.qr-alert-warning .qr-alert-body { color: #fef3c7; font-size: 0.85rem; }

.qr-alert-danger {
    background: rgba(239, 68, 68, 0.12);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}
.qr-alert-danger .qr-alert-title { color: #fca5a5; font-weight: 700; }
.qr-alert-danger .qr-alert-body { color: #fee2e2; font-size: 0.85rem; }

.qr-alert-info {
    background: rgba(59, 130, 246, 0.12);
    border: 1px solid rgba(59, 130, 246, 0.3);
    color: #60a5fa;
}
.qr-alert-info .qr-alert-title { color: #93c5fd; font-weight: 700; }
.qr-alert-info .qr-alert-body { color: #dbeafe; font-size: 0.85rem; }

/* ── PROJECTOR MODE TICKER ── */
.projector-ticker-container {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(207, 164, 111, 0.25);
    border-radius: 20px;
    padding: 12px 18px;
    backdrop-filter: blur(16px);
}

.projector-ticker-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.8rem;
    font-weight: 800;
}

.projector-ticker-scroll {
    display: flex;
    gap: 10px;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: thin;
}

.projector-chip {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(0, 0, 0, 0.5);
    border: 1px solid rgba(207, 164, 111, 0.3);
    border-radius: 99px;
    padding: 5px 12px;
    white-space: nowrap;
    animation: chipSlide 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes chipSlide {
    from { opacity: 0; transform: translateY(8px) scale(0.92); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.projector-chip .chip-name {
    font-weight: 700;
    color: #ffffff;
    font-size: 0.88rem;
}

.projector-chip .chip-time {
    font-size: 0.72rem;
    color: #b39b82;
    font-family: monospace;
}

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
                            <div style="color:#b39b82;font-size:.82rem;margin-top:6px;">
                                Student Assistants:
                                {{ $studentAssistants->pluck('student.name')->filter()->join(', ') ?: 'None assigned' }}
                                <span id="teacherQrGenerator" class="ms-2">{{ !empty($activeSessionPayload['generated_by']) ? 'QR generated by ' . $activeSessionPayload['generated_by'] : '' }}</span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <!-- Sound Toggle Button -->
                            <button type="button" id="soundToggleBtn" class="btn modern-btn secondary" onclick="toggleClockInSound()" title="Toggle clock-in audio chimes" style="padding: 10px 14px !important;">
                                <i class="bi bi-volume-up-fill me-1" id="soundIcon"></i>
                                <span class="d-none d-sm-inline" id="soundText">Chime On</span>
                            </button>
                            <!-- Copy Link Button -->
                            <button type="button" id="copyLinkBtn" class="btn modern-btn secondary" style="display: none; padding: 10px 16px !important;" onclick="copyScanLink()">
                                <i class="bi bi-link-45deg me-1"></i> Copy Link
                            </button>
                            <!-- Projector Mode Fullscreen Button -->
                            <button type="button" id="projectorBtn" class="btn modern-btn" style="display: none; padding: 10px 18px !important; background: linear-gradient(135deg, #d97706, #b45309) !important; color: white !important;" onclick="openProjectorMode()">
                                <i class="bi bi-display me-1"></i> Projector Mode
                            </button>
                            <!-- Back to Subjects -->
                            <a href="{{ route('teacher.subjects') }}" class="btn modern-btn secondary" style="padding: 10px 16px !important;">
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
                            <span id="refreshCountdownText">15s</span>
                        </div>
                        <div class="progress" style="height: 6px; border-radius: 3px; max-width: 220px; margin: 10px auto; background: rgba(255,255,255,0.08); overflow: hidden;">
                            <div id="qrProgressIndicator" class="progress-bar" style="width: 100%; background: linear-gradient(90deg, #cfa46f, #e5be8a); transition: width 1s linear;"></div>
                        </div>
                        <small style="color: #b39b82; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; font-size: 0.75rem;">Regenerating Code & QR In</small>
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
                        <button id="emergencyQrBtn" class="btn modern-btn secondary" style="display:none;">
                            <i class="bi bi-shield-check me-2"></i> Emergency Teacher QR
                        </button>
                        <button id="extendSessionBtn" class="btn modern-btn secondary" style="display:none;">
                            <i class="bi bi-clock-history me-2"></i> Extend 10 min
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
        
        <!-- Live Statistics & Roster Sidebar -->
        <div id="sidebar" style="display: none; width: 370px; flex-shrink: 0; min-width: 320px;">
            <!-- Real-time Stats Card -->
            <div class="stats-card mb-4">
                <div class="stats-header">
                    <h5><i class="bi bi-bar-chart-fill me-2" style="color: #cfa46f;"></i>Live Statistics</h5>
                    <div class="live-indicator" id="liveStatusBadge">
                        <span class="live-dot"></span>
                        <small style="color: #4ade80; font-weight: 700; font-size: 0.75rem;">LIVE</small>
                    </div>
                </div>
                <div class="stats-grid-4">
                    <div class="stat-item">
                        <div class="stat-number" id="totalStudents">0</div>
                        <div class="stat-label">Enrolled</div>
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
                        <div class="stat-number" style="color: #f87171;" id="absentCount">0</div>
                        <div class="stat-label">Unmarked</div>
                    </div>
                </div>
                <div class="px-4 pb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size: 0.72rem; color: #b39b82; font-weight: 700; text-transform: uppercase;">Attendance Rate</span>
                        <span id="progressPercent" style="font-size: 0.8rem; font-weight: 800; color: #cfa46f;">0%</span>
                    </div>
                    <div class="progress" style="height: 8px; border-radius: 10px; background: rgba(255,255,255,0.08);">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 0%; background: linear-gradient(135deg, #cfa46f, #b88a44); border-radius: 10px; transition: width 0.4s ease;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Live Clock-ins Feed -->
            <div class="stats-card">
                <div class="stats-header">
                    <h5><i class="bi bi-people-fill me-2" style="color: #cfa46f;"></i>Class Roster</h5>
                    <small id="rosterCountBadge" style="color: #cfa46f; font-weight: 700; font-size: 0.75rem;">0 Checked In</small>
                </div>

                <!-- Filter Pills: All, Present, Late, Outside, Escaped, Missing -->
                <div class="roster-filter-pills" style="display: flex; flex-wrap: wrap; gap: 5px; padding: 0.75rem 1.25rem 0;">
                    <button type="button" class="roster-pill active" onclick="setRosterFilter('all', this)">All</button>
                    <button type="button" class="roster-pill" onclick="setRosterFilter('present', this)">Present (<span id="countPillPresent">0</span>)</button>
                    <button type="button" class="roster-pill" onclick="setRosterFilter('late', this)">Late (<span id="countPillLate">0</span>)</button>
                    <button type="button" class="roster-pill" onclick="setRosterFilter('outside', this)" style="border-color: rgba(245,158,11,0.4);"><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Outside (<span id="countPillOutside">0</span>)</button>
                    <button type="button" class="roster-pill" onclick="setRosterFilter('escaped', this)" style="border-color: rgba(239,68,68,0.4);"><i class="bi bi-person-x-fill text-danger me-1"></i>Escaped (<span id="countPillEscaped">0</span>)</button>
                    <button type="button" class="roster-pill" onclick="setRosterFilter('missing', this)">Unmarked (<span id="countPillMissing">0</span>)</button>
                </div>

                <div style="padding: 0.85rem 1.25rem 0.5rem;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text" style="background: rgba(0,0,0,0.3); border-color: rgba(212,175,55,0.2); color: #cfa46f;"><i class="bi bi-search"></i></span>
                        <input type="text" id="rosterSearch" class="form-control" placeholder="Search student name or ID..." style="background: rgba(0,0,0,0.3); border-color: rgba(212,175,55,0.2); color: #f3e7cd;" oninput="filterClockins()">
                    </div>
                </div>

                <div style="max-height: 440px; overflow-y: auto; padding: 0.5rem 1.25rem 1.25rem;">
                    <div id="clockinsList">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-clock-history" style="font-size: 3.2rem; opacity: 0.3; color: #cfa46f;"></i>
                            <h6 class="mt-3" style="color: #f3e7cd;">Waiting for students...</h6>
                            <p class="small mb-0" style="color: #b39b82;">Clock-ins will appear here in real-time</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="stats-card mt-4">
                <details id="sessionTimelineDetails">
                    <summary class="stats-header" style="cursor:pointer;color:#f3e7cd;">
                        <strong><i class="bi bi-clock-history me-2" style="color:#cfa46f;"></i>Session Activity</strong>
                    </summary>
                    <div style="padding:0 1.25rem 1rem;">
                        <small id="sessionTimelineUpdated" style="color:#b39b82;">Open to load recent events.</small>
                        <ul id="sessionTimelineList" class="list-unstyled mt-2 mb-0" style="max-height:280px;overflow-y:auto;color:#f3e7cd;"></ul>
                    </div>
                </details>
            </div>
        </div>
    </div>
</div>

<!-- Projector Mode Fullscreen Overlay Modal -->
<div id="projectorModal" style="display: none; position: fixed; inset: 0; background: #0c0a09; z-index: 99999; flex-direction: column; align-items: center; justify-content: space-between; padding: 24px; text-align: center; color: white; overflow-y: auto;">
    <div style="width: 100%; display: flex; justify-content: space-between; align-items: center; max-width: 1100px;">
        <div class="text-start">
            <span class="badge" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; font-size: 0.85rem; padding: 6px 16px; border-radius: 99px; font-weight: 800;">
                <i class="bi bi-broadcast me-1"></i> Live Attendance
            </span>
            <h3 style="font-size: 1.8rem; font-weight: 800; margin: 6px 0 0; color: #f3e7cd;">{{ $subject->name }}</h3>
            <div style="color: #b39b82; font-size: 0.95rem;">{{ $subject->code }} • Year {{ $subject->year_level }} • Section {{ $subject->section ?? 'Regular' }}</div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-outline-light rounded-pill px-3" onclick="toggleClockInSound()" title="Toggle Audio Chime">
                <i class="bi bi-volume-up-fill me-1" id="projectorSoundIcon"></i> Sound
            </button>
            <button type="button" class="btn btn-outline-light rounded-pill px-3" onclick="closeProjectorMode()">
                <i class="bi bi-x-lg me-1"></i> Exit Fullscreen
            </button>
        </div>
    </div>

    <div style="max-width: 700px; width: 100%; margin: auto; padding: 12px 0;">
        <!-- Large QR Display -->
        <div id="projectorQrWrapper" style="background: white; border-radius: 28px; padding: 22px; display: inline-block; box-shadow: 0 24px 64px rgba(0,0,0,0.8); margin-bottom: 16px;">
            <div id="projectorQrCode"></div>
        </div>

        <!-- Projector Big Attendance Code -->
        <div id="projectorCodeSection" style="background: rgba(255,255,255,0.06); border: 1.5px solid rgba(207,164,111,0.4); border-radius: 22px; padding: 14px 28px; max-width: 480px; margin: 0 auto 16px;">
            <div style="font-size: 0.82rem; text-transform: uppercase; font-weight: 800; color: #b39b82; letter-spacing: 1.5px; margin-bottom: 2px;">
                <i class="bi bi-key-fill text-warning me-1"></i> Attendance Code / PIN
            </div>
            <div id="projectorSessionCode" style="font-size: 3.4rem; font-weight: 900; letter-spacing: 8px; color: #ffd700; font-family: monospace; text-shadow: 0 0 24px rgba(255,215,0,0.5); line-height: 1.1;">
                ------
            </div>
            <div style="font-size: 0.8rem; color: #b39b82; margin-top: 4px;">
                Point mobile camera at QR or enter 6-digit code on your dashboard
            </div>
        </div>

        <!-- Metric badges -->
        <div class="d-flex justify-content-center align-items-center gap-3 mt-2 flex-wrap">
            <div style="background: rgba(255,255,255,0.06); padding: 10px 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 0.72rem; color: #b39b82; text-transform: uppercase; font-weight: 700;">Present</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #4ade80;" id="projectorCount">0 Present</div>
            </div>
            <div style="background: rgba(255,255,255,0.06); padding: 10px 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 0.72rem; color: #b39b82; text-transform: uppercase; font-weight: 700;">Regenerating In</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #fbbf24; font-family: monospace;" id="projectorCountdown">15s</div>
            </div>
            <div style="background: rgba(255,255,255,0.06); padding: 10px 20px; border-radius: 16px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="font-size: 0.72rem; color: #b39b82; text-transform: uppercase; font-weight: 700;">Session Timer</div>
                <div style="font-size: 1.5rem; font-weight: 800; color: #60a5fa; font-family: monospace;" id="projectorSessionRemaining">--:--</div>
            </div>
        </div>
    </div>

    <!-- Projector Recent Check-In Ticker -->
    <div id="projectorTickerWrapper" class="projector-ticker-container" style="max-width: 1000px; width: 100%; margin: 10px auto 0;">
        <div class="projector-ticker-header">
            <span class="live-dot" style="width: 7px; height: 7px;"></span>
            <span style="color: #cfa46f; letter-spacing: 0.5px;">RECENT CLASSROOM CHECK-INS</span>
        </div>
        <div id="projectorRecentChips" class="projector-ticker-scroll">
            <div class="text-muted small py-1" id="projectorEmptyTickerText">Waiting for students to scan or enter code...</div>
        </div>
    </div>
</div>

<script src="{{ asset('js/qrcode.min.js') }}"></script>
<script nonce="{{ csp_nonce() }}">
let currentSession = @json($activeSessionPayload ?? null);
let refreshInterval = null;
let clockinInterval = null;
let timerInterval = null;
let scheduleCheckInterval = null;
let refreshCountdownInterval = null;
let teacherLocation = null;
let teacherLocationIssue = '';
let locationRequestSerial = 0;
let refreshCountdownSeconds = 15;
let selectedRadius = 50;

// Audio & Roster State
let clockInSoundEnabled = true;
let cachedClockins = [];
let activeRosterFilter = 'all';
let knownClockedInIds = new Set();
let recentCheckInsQueue = [];

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[character]);
}

const startBtn = document.getElementById('startBtn');
const refreshBtn = document.getElementById('refreshBtn');
const emergencyQrBtn = document.getElementById('emergencyQrBtn');
const extendSessionBtn = document.getElementById('extendSessionBtn');
const timelineDetails = document.getElementById('sessionTimelineDetails');
const timelineList = document.getElementById('sessionTimelineList');

async function updateSessionTimeline() {
    if (!timelineDetails?.open || !currentSession) return;
    const sessionId = currentSession.session_id || currentSession.id;
    const template = @json(route('teacher.qr.timeline', ['session' => '__SESSION__']));
    try {
        const response = await fetch(template.replace('__SESSION__', encodeURIComponent(sessionId)), {
            credentials: 'same-origin', headers: { 'Accept': 'application/json' }
        });
        if (!response.ok) throw new Error('Activity unavailable');
        const data = await response.json();
        timelineList.replaceChildren();
        if (!data.events.length) {
            const item = document.createElement('li');
            item.textContent = 'No activity recorded yet.';
            timelineList.append(item);
        }
        data.events.forEach((event) => {
            const item = document.createElement('li');
            item.className = 'border-bottom py-2';
            item.style.borderColor = 'rgba(207,164,111,.15)';
            const label = event.action.replaceAll('_', ' ');
            item.textContent = `${new Date(event.at).toLocaleTimeString()} · ${label}`
                + (event.actor ? ` · ${event.actor}` : '')
                + (event.reason ? ` · ${event.reason}` : '');
            timelineList.append(item);
        });
        document.getElementById('sessionTimelineUpdated').textContent =
            'Last updated ' + new Date(data.updated_at).toLocaleTimeString();
    } catch (_) {
        document.getElementById('sessionTimelineUpdated').textContent = 'Activity unavailable. Reopen to retry.';
    }
}

timelineDetails?.addEventListener('toggle', updateSessionTimeline);
const stopBtn = document.getElementById('stopBtn');
const qrContainer = document.getElementById('qrCodeContainer');
const sidebar = document.getElementById('sidebar');
const mainSection = document.getElementById('mainSection');
const locationStatus = document.getElementById('locationStatus');

// Check schedule on page load or restore active session
document.addEventListener('DOMContentLoaded', () => {
    if (currentSession) {
        if (currentSession.radius_meters !== undefined && currentSession.radius_meters !== null) {
            selectedRadius = Number(currentSession.radius_meters);
        }
        showQRCode(currentSession.scan_url);
        updateUIForActiveSession();
        startIntervals();
    } else {
        checkScheduleStatus();
        scheduleCheckInterval = setInterval(checkScheduleStatus, 30000);
    }
    renderLocationHUD();
    if (!currentSession) captureTeacherLocation();
    subscribeToTeacherAttendanceUpdates();
});

// Cleanup intervals when page unloads
window.addEventListener('beforeunload', () => {
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);
    if (scheduleCheckInterval) clearInterval(scheduleCheckInterval);
    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);
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

        // A new session always receives a new device fix, never a preview or cached fix.
        if (selectedRadius > 0) {
            startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Getting accurate location...';
            teacherLocation = await captureTeacherLocation();
        }

        // When geofence is enabled, teacher laptop location is mandatory as the central reference
        if (selectedRadius > 0 && !teacherLocation) {
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Start Session';
            showTeacherToast('A fresh, accurate laptop location is required. Check location services and retry.', 'warning');
            const statusMessages = document.getElementById('statusMessages');
            if (statusMessages) {
                statusMessages.innerHTML = `
                    <div class="qr-alert qr-alert-warning">
                        <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                        <div>
                            <h6 class="qr-alert-title mb-1"><i class="bi bi-exclamation-triangle me-1"></i> Laptop Location Unavailable</h6>
                            <p class="qr-alert-body mb-0">
                                Geofencing uses your laptop as the central reference location for the attendance session. Please allow browser location permissions and click <strong>Retry Laptop GPS</strong>, or click <strong>Disable Geofence</strong> to proceed without location restrictions.
                            </p>
                        </div>
                    </div>
                `;
            }
            renderLocationHUD();
            return;
        }

        const lat = selectedRadius > 0 ? teacherLocation.latitude : null;
        const lng = selectedRadius > 0 ? teacherLocation.longitude : null;

        const bodyPayload = {
            subject_code: '{{ $subject->code }}',
            classroom_lat: lat,
            classroom_lng: lng,
            teacher_accuracy: selectedRadius > 0 ? teacherLocation.accuracy : null,
            radius_meters: selectedRadius
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
            if (data.radius_meters !== undefined && data.radius_meters !== null) {
                selectedRadius = Number(data.radius_meters);
            }
            renderLocationHUD();
            knownClockedInIds.clear();
            recentCheckInsQueue = [];

            showQRCode(data.scan_url);
            updateUIForActiveSession();
            startIntervals();
            
            if (scheduleCheckInterval) {
                clearInterval(scheduleCheckInterval);
                scheduleCheckInterval = null;
            }
            
            document.getElementById('statusMessages').innerHTML = '';
            showTeacherToast('Attendance session started successfully!', 'success');
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

// Audio chime handling
function toggleClockInSound() {
    clockInSoundEnabled = !clockInSoundEnabled;
    const icon = document.getElementById('soundIcon');
    const text = document.getElementById('soundText');
    const pIcon = document.getElementById('projectorSoundIcon');

    if (clockInSoundEnabled) {
        if (icon) icon.className = 'bi bi-volume-up-fill me-1';
        if (text) text.textContent = 'Chime On';
        if (pIcon) pIcon.className = 'bi bi-volume-up-fill me-1';
        showTeacherToast('Attendance audio chimes enabled', 'info');
        playClockInChime();
    } else {
        if (icon) icon.className = 'bi bi-volume-mute-fill me-1';
        if (text) text.textContent = 'Muted';
        if (pIcon) pIcon.className = 'bi bi-volume-mute-fill me-1';
        showTeacherToast('Attendance audio chimes muted', 'info');
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
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
        osc.frequency.setValueAtTime(880, ctx.currentTime + 0.08); // A5
        gain.gain.setValueAtTime(0.14, ctx.currentTime);
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
    if (!modal) return;
    modal.style.display = 'flex';
    if (document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    }
}

function closeProjectorMode() {
    const modal = document.getElementById('projectorModal');
    if (!modal) return;
    modal.style.display = 'none';
    if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen().catch(() => {});
    }
}

function updateUIForActiveSession() {
    startBtn.style.display = 'none';
    refreshBtn.style.display = currentSession?.signed_qr ? 'none' : 'inline-block';
    emergencyQrBtn.style.display = 'inline-block';
    extendSessionBtn.style.display = 'inline-block';
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
    } else {
        const codeSection = document.getElementById('attendanceCodeSection');
        if (codeSection) codeSection.style.display = 'none';
    }
}

function formatTtlCountdown(totalSecs) {
    if (totalSecs < 0) totalSecs = 0;
    const s = Math.floor(totalSecs);
    return `${s < 10 ? '0' : ''}${s}s`;
}

function resetRefreshTimers(initialSecs = null) {
    const ttl = Number(currentSession?.ttl) || 15;
    if (initialSecs !== null && initialSecs !== undefined) {
        refreshCountdownSeconds = initialSecs;
    } else if (currentSession && currentSession.remaining_ttl) {
        refreshCountdownSeconds = Number(currentSession.remaining_ttl);
        currentSession.remaining_ttl = null;
    } else {
        refreshCountdownSeconds = ttl;
    }

    const formatted = formatTtlCountdown(refreshCountdownSeconds);
    const cdText = document.getElementById('refreshCountdownText');
    if (cdText) cdText.textContent = formatted;
    const projCountdown = document.getElementById('projectorCountdown');
    if (projCountdown) projCountdown.textContent = formatted;
    
    const progressIndicator = document.getElementById('qrProgressIndicator');
    if (progressIndicator) {
        progressIndicator.style.transition = 'none';
        progressIndicator.style.width = Math.max(0, Math.min(100, (refreshCountdownSeconds / ttl) * 100)) + '%';
        progressIndicator.style.background = 'linear-gradient(90deg, #cfa46f, #e5be8a)';
        void progressIndicator.offsetWidth;
        progressIndicator.style.transition = 'width 1s linear';
    }

    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);
    refreshCountdownInterval = setInterval(() => {
        refreshCountdownSeconds--;
        if (refreshCountdownSeconds < 0) refreshCountdownSeconds = 0;

        const formatted = formatTtlCountdown(refreshCountdownSeconds);
        const pCd = document.getElementById('projectorCountdown');
        if (pCd) pCd.textContent = formatted;
        const cdEl = document.getElementById('refreshCountdownText');
        if (cdEl) cdEl.textContent = formatted;

        if (progressIndicator) {
            const percentage = Math.max(0, Math.min(100, (refreshCountdownSeconds / ttl) * 100));
            progressIndicator.style.width = percentage + '%';
            if (refreshCountdownSeconds <= 3) {
                progressIndicator.style.background = 'linear-gradient(90deg, #ef4444, #f59e0b)';
            } else {
                progressIndicator.style.background = 'linear-gradient(90deg, #cfa46f, #e5be8a)';
            }
        }

        if (refreshCountdownSeconds <= 0) {
            // Automatically regenerate QR code and attendance code every 15 seconds without full page reload
            performAutoRefresh(false);
        }
    }, 1000);
}

let isRefreshingQr = false;
async function performAutoRefresh(isManual = false) {
    if (isRefreshingQr) return;
    const sessionId = currentSession?.session_id || currentSession?.id;
    if (!sessionId && !currentSession) {
        if (isManual) showTeacherToast('No active attendance session to refresh', 'warning');
        return;
    }

    isRefreshingQr = true;
    const originalHtml = refreshBtn.innerHTML;
    if (isManual) {
        refreshBtn.disabled = true;
        refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Refreshing...';
    }

    try {
        const response = await fetch('{{ route("teacher.qr.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: sessionId,
                subject_code: '{{ $subject->code }}',
                manual: isManual
            })
        });

        const data = await response.json().catch(() => ({}));

        if (response.ok && data.success) {
            currentSession.token = data.token;
            currentSession.scan_url = data.scan_url;
            currentSession.ttl = data.ttl || 15;
            if (data.session_end && data.session_end !== currentSession.session_end) {
                currentSession.session_end = data.session_end;
                startSessionTimer(data.session_end);
            }
            currentSession.signed_qr = !!data.signed_qr;
            currentSession.generated_by = data.generated_by || null;
            const generatorLabel = document.getElementById('teacherQrGenerator');
            if (generatorLabel) generatorLabel.textContent = data.generated_by ? 'QR generated by ' + data.generated_by : '';
            if (data.signed_qr) {
                currentSession.session_code = null;
                currentSession.formatted_code = null;
                updateUIForActiveSession();
            }
            if (data.session_code) {
                currentSession.session_code = data.session_code;
                currentSession.formatted_code = data.formatted_code;
                const codeBox = document.getElementById('displaySessionCode');
                if (codeBox) {
                    codeBox.textContent = data.formatted_code || data.session_code;
                    codeBox.style.transition = 'transform 0.25s ease, color 0.25s ease';
                    codeBox.style.transform = 'scale(1.08)';
                    codeBox.style.color = '#ffffff';
                    setTimeout(() => {
                        codeBox.style.transform = 'scale(1)';
                        codeBox.style.color = '#ffd700';
                    }, 350);
                }
                const pCode = document.getElementById('projectorSessionCode');
                if (pCode) {
                    pCode.textContent = data.formatted_code || data.session_code;
                    pCode.style.transition = 'transform 0.25s ease, color 0.25s ease';
                    pCode.style.transform = 'scale(1.08)';
                    pCode.style.color = '#ffffff';
                    setTimeout(() => {
                        pCode.style.transform = 'scale(1)';
                        pCode.style.color = '#ffd700';
                    }, 350);
                }
            }
            showQRCode(data.scan_url);
            resetRefreshTimers(data.ttl || 15);
            if (isManual) {
                showTeacherToast('Attendance QR & Code refreshed successfully', 'success');
            }
        } else {
            const errorMsg = data.message || 'Unable to refresh QR code.';
            if (isManual) {
                showTeacherToast(errorMsg, 'warning');
            }
            if (data.session_expired || response.status === 404) {
                enterGracePeriod();
            } else {
                // Retry in 3 seconds on transient network failure
                resetRefreshTimers(3);
            }
        }
    } catch (error) {
        console.error('Error refreshing QR:', error);
        if (isManual) {
            showTeacherToast('Network error while refreshing QR code', 'error');
        }
        resetRefreshTimers(3);
    } finally {
        isRefreshingQr = false;
        if (isManual) {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = originalHtml;
        }
    }
}

function startIntervals() {
    document.getElementById('qrRefreshCountdown').style.display = 'block';
    resetRefreshTimers();
    startSessionTimer(currentSession.session_end);
    updateClockIns();
    if (clockinInterval) clearInterval(clockinInterval);
    clockinInterval = setInterval(updateClockIns, 3000); // 3-second live polling loop
}

// Manual Refresh QR button
refreshBtn.addEventListener('click', () => performAutoRefresh(true));

emergencyQrBtn.addEventListener('click', async () => {
    const sessionId = currentSession?.session_id || currentSession?.id;
    if (!sessionId || !confirm('Replace the current QR with a teacher-operated emergency QR?')) return;
    emergencyQrBtn.disabled = true;
    try {
        const template = @json(route('teacher.qr.emergency', ['session' => '__SESSION__']));
        const response = await fetch(template.replace('__SESSION__', encodeURIComponent(sessionId)), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Unable to generate emergency QR.');
        currentSession.token = data.token;
        currentSession.scan_url = data.scan_url;
        currentSession.ttl = Math.max(1, data.expires_at - Math.floor(Date.now() / 1000));
        currentSession.signed_qr = true;
        currentSession.session_code = null;
        currentSession.formatted_code = null;
        currentSession.generated_by = data.generated_by;
        showQRCode(data.scan_url);
        updateUIForActiveSession();
        resetRefreshTimers(currentSession.ttl);
        const label = document.getElementById('teacherQrGenerator');
        if (label) label.textContent = 'QR generated by ' + (data.generated_by || 'Teacher');
        showTeacherToast('Emergency teacher QR is active.', 'success');
    } catch (error) {
        showTeacherToast(error.message || 'Unable to generate emergency QR.', 'error');
    } finally {
        emergencyQrBtn.disabled = false;
    }
});

extendSessionBtn.addEventListener('click', async () => {
    const sessionId = currentSession?.session_id || currentSession?.id;
    if (!sessionId) return;
    extendSessionBtn.disabled = true;
    try {
        const template = @json(route('teacher.qr.extend', ['session' => '__SESSION__']));
        const response = await fetch(template.replace('__SESSION__', encodeURIComponent(sessionId)), {
            method: 'POST', credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ minutes: 10 })
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Unable to extend attendance.');
        currentSession.session_end = data.session_end;
        startSessionTimer(data.session_end);
        showTeacherToast('Attendance session extended by 10 minutes.', 'success');
    } catch (error) {
        showTeacherToast(error.message || 'Unable to extend attendance.', 'error');
    } finally {
        extendSessionBtn.disabled = false;
    }
});

// Stop session
stopBtn.addEventListener('click', async () => {
    const sessionId = currentSession?.session_id || currentSession?.id;
    if (!sessionId && !currentSession) {
        enterGracePeriod();
        return;
    }

    const originalStopHtml = stopBtn.innerHTML;
    stopBtn.disabled = true;
    refreshBtn.disabled = true;
    stopBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Stopping...';

    try {
        const response = await fetch('{{ route("teacher.qr.stop") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: sessionId,
                subject_code: '{{ $subject->code }}'
            })
        });

        const data = await response.json().catch(() => ({}));
        if (response.ok && data.success) {
            showTeacherToast('Attendance session ended successfully', 'info');
        } else {
            showTeacherToast(data.message || 'Session closed with notices', 'info');
        }
        enterGracePeriod();
    } catch (error) {
        console.error('Error stopping session:', error);
        showTeacherToast('Session stopped. Attendance review active.', 'info');
        enterGracePeriod();
    } finally {
        stopBtn.disabled = false;
        stopBtn.innerHTML = originalStopHtml;
    }
});

function enterGracePeriod() {
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);
    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);

    if (currentSession) {
        currentSession.active = false;
    }

    startBtn.style.display = 'none';
    refreshBtn.style.display = 'none';
    emergencyQrBtn.style.display = 'none';
    extendSessionBtn.style.display = 'none';
    stopBtn.style.display = 'none';
    document.getElementById('sessionTimer').style.display = 'none';
    document.getElementById('qrRefreshCountdown').style.display = 'none';
    document.getElementById('projectorBtn').style.display = 'none';
    document.getElementById('copyLinkBtn').style.display = 'none';
    const codeSection = document.getElementById('attendanceCodeSection');
    if (codeSection) codeSection.style.display = 'none';
    closeProjectorMode();
    
    qrContainer.classList.remove('active');
    qrContainer.innerHTML = `
        <div style="color: #b39b82; text-align: center; padding: 20px;">
            <i class="bi bi-clock-history" style="font-size: 4.5rem; opacity: 0.4; margin-bottom: 1rem; color: #cfa46f; display: block;"></i>
            <h5 style="color: #f3e7cd; font-weight: 800; font-size: 1.35rem;">Session Closed — Review Active</h5>
            <p style="color: #b39b82; font-size: 0.95rem; max-width: 440px; margin: 0 auto 20px;">Student scanning has ended. You can review the final attendance roster on the right or make manual adjustments.</p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="{{ route('teacher.subjects') }}" class="btn modern-btn">Finish & Back to Subjects</a>
                <a href="{{ route('teacher.qr', $subject->code) }}" class="btn modern-btn secondary"><i class="bi bi-arrow-repeat me-1"></i> New Session</a>
            </div>
        </div>
    `;
    
    document.getElementById('statusMessages').innerHTML = `
        <div class="qr-alert qr-alert-info">
            <i class="bi bi-info-circle-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
            <div>
                <h6 class="qr-alert-title mb-1"><i class="bi bi-clock-history me-1"></i> Session Completed</h6>
                <p class="qr-alert-body mb-0">Attendance has been recorded. Manual overrides can still be made using the roster menu on the right.</p>
            </div>
        </div>
    `;
}

function showQRCode(url) {
    qrContainer.innerHTML = `
        <div class="text-center" style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div style="background: #ffffff; padding: 14px; border-radius: 22px; box-shadow: 0 16px 48px rgba(0,0,0,0.5); display: inline-block;">
                <div id="localQrCanvas" style="display: flex; justify-content: center; align-items: center; min-width: 260px; min-height: 260px;"></div>
            </div>
            <p class="mt-3 mb-0" style="color: #f3e7cd; font-size: 1.05rem; font-weight: 700;">
                <i class="bi bi-phone me-1" style="color: #cfa46f;"></i> Point mobile camera at QR code to check in
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
                width: Math.min(460, Math.floor(window.innerWidth * 0.7)),
                height: Math.min(460, Math.floor(window.innerWidth * 0.7)),
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    }
}

function startSessionTimer(endTimeStr) {
    const timerDisplay = document.getElementById('timeRemaining');
    const projSessionRemaining = document.getElementById('projectorSessionRemaining');
    if (!timerDisplay) return;

    if (timerInterval) clearInterval(timerInterval);

    let targetTime;
    if (typeof endTimeStr === 'number' || (!isNaN(Number(endTimeStr)) && String(endTimeStr).trim() !== '')) {
        const num = Number(endTimeStr);
        targetTime = num < 1e11 ? num * 1000 : num;
    } else {
        targetTime = new Date(endTimeStr).getTime();
    }

    timerInterval = setInterval(() => {
        const now = new Date().getTime();
        const distance = targetTime - now;

        if (distance < 0) {
            clearInterval(timerInterval);
            timerDisplay.textContent = '00:00';
            if (projSessionRemaining) projSessionRemaining.textContent = '00:00';
            stopBtn.click();
            return;
        }

        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        const formatted = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

        timerDisplay.textContent = formatted;
        if (projSessionRemaining) projSessionRemaining.textContent = formatted;
    }, 1000);
}

function setRosterFilter(filter, el) {
    activeRosterFilter = filter;
    document.querySelectorAll('.roster-pill').forEach(pill => pill.classList.remove('active'));
    if (el) el.classList.add('active');
    renderClockinsList();
}

function filterClockins() {
    renderClockinsList();
}

function renderClockinsList() {
    const clockinsList = document.getElementById('clockinsList');
    if (!clockinsList) return;

    const query = (document.getElementById('rosterSearch')?.value || '').toLowerCase().trim();

    let items = cachedClockins;

    // Filter by tab pill
    if (activeRosterFilter === 'present') {
        items = items.filter(c => c.status === 'Present');
    } else if (activeRosterFilter === 'late') {
        items = items.filter(c => c.status === 'Late');
    } else if (activeRosterFilter === 'outside') {
        items = items.filter(c => c.status === 'Outside Area');
    } else if (activeRosterFilter === 'escaped') {
        items = items.filter(c => c.status === 'Escaped');
    } else if (activeRosterFilter === 'missing') {
        items = items.filter(c => c.status === 'Missing' || c.status === 'Absent');
    }

    // Filter by search query
    if (query) {
        items = items.filter(c => 
            (c.name && c.name.toLowerCase().includes(query)) || 
            (c.student_number && c.student_number.toLowerCase().includes(query)) ||
            (c.status && c.status.toLowerCase().includes(query))
        );
    }

    if (items.length === 0) {
        clockinsList.innerHTML = `
            <div class="text-center text-muted py-4">
                <i class="bi bi-search" style="font-size: 2.5rem; opacity: 0.3; color: #cfa46f;"></i>
                <h6 class="mt-2" style="color: #f3e7cd;">${query ? 'No matching students' : 'No students in this view'}</h6>
                <p class="small mb-0" style="color: #b39b82;">${query ? 'Try searching for another name or ID' : 'Waiting for students...'}</p>
            </div>
        `;
        return;
    }

    clockinsList.innerHTML = items.map(clockin => {
        const justArrivedClass = knownClockedInIds.has(clockin.id) ? 'just-arrived' : '';
        
        let badgeHtml = '';
        if (clockin.status === 'Escaped') {
            badgeHtml = `<span class="status-badge" style="background: rgba(239,68,68,0.22); color: #f87171; border: 1px solid rgba(239,68,68,0.45); font-weight: 700;"><i class="bi bi-person-x-fill me-1"></i>Escaped</span>`;
        } else if (clockin.status === 'Outside Area') {
            badgeHtml = `<span class="status-badge" style="background: rgba(245,158,11,0.22); color: #fbbf24; border: 1px solid rgba(245,158,11,0.45); font-weight: 700;"><i class="bi bi-exclamation-diamond-fill me-1"></i>Outside ${clockin.distance ? '(' + clockin.distance + ')' : ''}</span>`;
        } else if (clockin.status === 'Present') {
            badgeHtml = `<span class="status-badge status-present"><i class="bi bi-check-circle-fill me-1"></i>Present</span>`;
        } else if (clockin.status === 'Late') {
            badgeHtml = `<span class="status-badge status-late"><i class="bi bi-clock-fill me-1"></i>Late</span>`;
        } else {
            badgeHtml = `<span class="status-badge status-missing">Unmarked</span>`;
        }

        let metaHtml = `<div style="font-size: 0.7rem; color: #b39b82; margin-top: 2px;">In: ${clockin.time}</div>`;
        if (clockin.status === 'Escaped') {
            metaHtml = `<div style="font-size: 0.68rem; color: #f87171; font-weight: 600; margin-top: 2px;">Left area: ${clockin.escaped_at || clockin.outside_since || 'Active'}</div>`;
        } else if (clockin.status === 'Outside Area') {
            metaHtml = `<div style="font-size: 0.68rem; color: #fbbf24; font-weight: 600; margin-top: 2px;">Outside: ${clockin.outside_since || clockin.last_verified || 'Warning'}</div>`;
        } else if (clockin.last_verified && ['Present', 'Late'].includes(clockin.status)) {
            metaHtml = `<div style="font-size: 0.68rem; color: #94a3b8; margin-top: 2px;" title="Last presence check">Verified: ${clockin.last_verified} ${clockin.distance ? '(' + clockin.distance + ')' : ''}</div>`;
        }

        return `
            <div class="clockin-item ${clockin.status === 'Missing' ? 'status-missing' : ''} ${justArrivedClass}" id="clockin-row-${clockin.id}" style="${clockin.status === 'Escaped' ? 'border-left: 3px solid #ef4444;' : (clockin.status === 'Outside Area' ? 'border-left: 3px solid #f59e0b;' : '')}">
                <div class="clockin-avatar">
                    <div class="avatar-circle">${(clockin.name || 'ST').substring(0, 2).toUpperCase()}</div>
                </div>
                <div class="clockin-info flex-grow-1" style="min-width: 0;">
                    <div class="fw-bold" style="font-size: 0.88rem; color: #f3e7cd; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${clockin.name}</div>
                    <div style="font-size: 0.74rem; font-family: monospace; color: #b39b82;">${clockin.student_number}</div>
                </div>
                <div class="clockin-status text-end d-flex align-items-center gap-2">
                    <div style="text-align: right; min-width: 80px;">
                        ${badgeHtml}
                        ${metaHtml}
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm" style="background:transparent; border:none; padding:4px; color: #b39b82;" data-bs-toggle="dropdown" aria-label="Status actions">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="background: #1a1512; border: 1px solid rgba(212,175,55,0.25); font-size: 0.85rem; padding: 6px; border-radius: 12px;">
                            <li><a class="dropdown-item fw-bold text-success" href="#" onclick="overrideStatus(${clockin.id}, 'Present', event)" style="border-radius: 8px; padding: 6px 12px;"><i class="bi bi-check-circle me-2"></i>Mark Present</a></li>
                            <li><a class="dropdown-item fw-bold text-warning" href="#" onclick="overrideStatus(${clockin.id}, 'Late', event)" style="border-radius: 8px; padding: 6px 12px;"><i class="bi bi-clock me-2"></i>Mark Late</a></li>
                            <li><a class="dropdown-item fw-bold" href="#" onclick="overrideStatus(${clockin.id}, 'Escaped', event)" style="border-radius: 8px; padding: 6px 12px; color: #f87171;"><i class="bi bi-person-x me-2"></i>Mark Escaped</a></li>
                            <li><a class="dropdown-item fw-bold text-danger" href="#" onclick="overrideStatus(${clockin.id}, 'Absent', event)" style="border-radius: 8px; padding: 6px 12px;"><i class="bi bi-x-circle me-2"></i>Mark Absent</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function updateStatsCounters(stats) {
    if (!stats) return;

    const total = stats.total_students || 0;
    const present = stats.present !== undefined ? stats.present : (stats.clocked_in || 0);
    const late = stats.late || 0;
    const outsideArea = stats.outside_area || 0;
    const escaped = stats.escaped || 0;
    const clockedIn = stats.clocked_in || (present + late + escaped);
    const absent = stats.absent !== undefined ? stats.absent : Math.max(0, total - clockedIn);
    const progress = stats.progress || (total > 0 ? Math.round((clockedIn / total) * 100) : 0);

    const totalEl = document.getElementById('totalStudents');
    const clockedInEl = document.getElementById('clockedIn');
    const lateEl = document.getElementById('lateCount');
    const absentEl = document.getElementById('absentCount');
    const progressEl = document.getElementById('progressPercent');
    const barEl = document.getElementById('progressBar');
    const badgeEl = document.getElementById('rosterCountBadge');

    if (totalEl) totalEl.textContent = total;
    if (clockedInEl) clockedInEl.textContent = present;
    if (lateEl) lateEl.textContent = late;
    if (absentEl) absentEl.textContent = absent;
    if (progressEl) progressEl.textContent = progress + '%';
    if (barEl) barEl.style.width = progress + '%';
    if (badgeEl) badgeEl.textContent = `${clockedIn} / ${total} Present`;

    // Filter pill counts
    const pillPres = document.getElementById('countPillPresent');
    const pillLate = document.getElementById('countPillLate');
    const pillOutside = document.getElementById('countPillOutside');
    const pillEscaped = document.getElementById('countPillEscaped');
    const pillMiss = document.getElementById('countPillMissing');
    if (pillPres) pillPres.textContent = present;
    if (pillLate) pillLate.textContent = late;
    if (pillOutside) pillOutside.textContent = outsideArea;
    if (pillEscaped) pillEscaped.textContent = escaped;
    if (pillMiss) pillMiss.textContent = absent;

    // Projector modal live count
    const projCount = document.getElementById('projectorCount');
    if (projCount) projCount.textContent = `${clockedIn} / ${total} Present`;
}

function addProjectorTickerItem(item) {
    const ticker = document.getElementById('projectorRecentChips');
    const emptyText = document.getElementById('projectorEmptyTickerText');
    if (!ticker) return;

    if (emptyText) emptyText.style.display = 'none';

    const chip = document.createElement('div');
    chip.className = 'projector-chip';
    const safeName = escapeHtml(item.student_name || 'Student');
    const safeTime = escapeHtml(item.time || '');
    const safeStatus = ['Present', 'Late', 'Absent', 'Escaped'].includes(item.status) ? item.status : 'Present';
    chip.innerHTML = `
        <span class="avatar-circle" style="width:26px;height:26px;font-size:0.7rem;">${safeName.substring(0,2).toUpperCase()}</span>
        <span class="chip-name">${safeName}</span>
        <span class="chip-time">${safeTime}</span>
        <span class="status-badge status-${safeStatus.toLowerCase()}">${safeStatus}</span>
    `;

    ticker.insertBefore(chip, ticker.firstChild);

    // Keep max 12 items in projector ticker
    while (ticker.children.length > 12) {
        ticker.removeChild(ticker.lastChild);
    }
}

async function updateClockIns() {
    if (!currentSession) return;

    try {
        const response = await fetch(`{{ route("teacher.qr.clockins") }}?session_id=${currentSession.session_id}`);
        if (!response.ok) return;

        const data = await response.json();
        if (!data || !data.stats) return;

        const incomingClockins = data.clockins || [];

        // Detect newly arrived students via polling cycle
        if (cachedClockins && cachedClockins.length > 0) {
            incomingClockins.forEach(fresh => {
                if (['Present', 'Late'].includes(fresh.status)) {
                    const old = cachedClockins.find(c => c.id === fresh.id);
                    if (!old || !['Present', 'Late'].includes(old.status)) {
                        // New student check-in detected!
                        knownClockedInIds.add(fresh.id);
                        playClockInChime();
                        addProjectorTickerItem({
                            student_name: fresh.name,
                            status: fresh.status,
                            time: fresh.time
                        });
                        showTeacherToast(`${fresh.name} checked in (${fresh.status})`, 'success');
                    }
                }
            });
        } else {
            // First load: initialize known IDs
            incomingClockins.forEach(c => {
                if (['Present', 'Late'].includes(c.status)) {
                    knownClockedInIds.add(c.id);
                }
            });
        }

        cachedClockins = incomingClockins.map(clockin => ({
            ...clockin,
            id: Number.isSafeInteger(Number(clockin.id)) ? Number(clockin.id) : 0,
            name: escapeHtml(clockin.name || 'Student'),
            student_number: escapeHtml(clockin.student_number || ''),
            time: escapeHtml(clockin.time || ''),
            distance: escapeHtml(clockin.distance || ''),
            escaped_at: escapeHtml(clockin.escaped_at || ''),
            outside_since: escapeHtml(clockin.outside_since || ''),
            last_verified: escapeHtml(clockin.last_verified || ''),
        }));
        updateStatsCounters(data.stats);
        renderClockinsList();
    } catch (error) {
        console.warn('Clock-in update warning:', error);
    }
}

function handleIncomingCheckIn(payload) {
    playClockInChime();
    addProjectorTickerItem({
        student_name: payload.student_name,
        status: payload.status,
        time: payload.time
    });
    showTeacherToast(`${payload.student_name} checked in (${payload.status})`, 'success');
    updateClockIns();
}

function subscribeToTeacherAttendanceUpdates() {
    const echoInstance = window.teacherEcho || window.adminEcho || window.Echo;
    if (!echoInstance) {
        setTimeout(subscribeToTeacherAttendanceUpdates, 300);
        return;
    }

    if (window.teacherAttendanceSubscribed) return;
    window.teacherAttendanceSubscribed = true;

    try {
        echoInstance.private('teacher-dashboard.{{ Auth::id() }}')
            .listen('.attendance.updated', (payload) => {
                if (!currentSession || !payload || payload.subject_code !== '{{ $subject->code }}') {
                    return;
                }

                if (payload.type === 'clock_in') {
                    handleIncomingCheckIn(payload);
                    updateSessionTimeline();
                }
            })
            .listen('.attendance.qr.changed', (payload) => {
                if (!currentSession || payload.session_id !== (currentSession.session_id || currentSession.id)) return;
                performAutoRefresh(false);
                updateClockIns();
                updateSessionTimeline();
            })
            .listen('.attendance.session.changed', (payload) => {
                if (!currentSession || payload.session_id !== (currentSession.session_id || currentSession.id)) return;
                if (payload.change_type === 'closed') enterGracePeriod();
                if (payload.change_type === 'extended') {
                    currentSession.session_end = payload.session_ends_at;
                    startSessionTimer(payload.session_ends_at);
                }
                updateSessionTimeline();
            })
            .listen('.attendance.scan.alert', (payload) => {
                if (!currentSession || payload.session_id !== (currentSession.session_id || currentSession.id)) return;
                showTeacherToast('Attendance scan alert: ' + payload.reason.replaceAll('_', ' '), 'warning');
                updateSessionTimeline();
            });

        const connection = echoInstance.connector?.pusher?.connection;
        const liveBadge = document.getElementById('liveStatusBadge');
        let reconnectDelay = 1000;
        let reconnectTimer = null;
        const scheduleReconnect = () => {
            if (!connection || reconnectTimer) return;
            reconnectTimer = setTimeout(() => {
                reconnectTimer = null;
                if (connection.state === 'connected') return;
                try { connection.connect(); } catch (_) { /* Polling remains available. */ }
                if (currentSession) {
                    updateClockIns();
                    updateSessionTimeline();
                }
                reconnectDelay = Math.min(reconnectDelay * 2, 8000);
                scheduleReconnect();
            }, reconnectDelay);
        };
        connection?.bind('connected', () => {
            if (reconnectTimer) clearTimeout(reconnectTimer);
            reconnectTimer = null;
            reconnectDelay = 1000;
            if (currentSession) {
                performAutoRefresh(false);
                updateClockIns();
                updateSessionTimeline();
            }
            if (liveBadge) liveBadge.innerHTML = '<span class="live-dot"></span><small style="color:#4ade80;font-weight:700;font-size:.75rem;">LIVE STREAM</small>';
        });
        connection?.bind('disconnected', () => {
            if (liveBadge) liveBadge.textContent = 'Reconnecting…';
            scheduleReconnect();
        });
        connection?.bind('unavailable', scheduleReconnect);
        if (liveBadge) {
            liveBadge.innerHTML = '<span class="live-dot"></span><small style="color: #4ade80; font-weight: 700; font-size: 0.75rem;">LIVE STREAM</small>';
        }
    } catch (e) {
        console.warn('[Realtime] WebSocket subscribe error:', e);
    }
}

async function overrideStatus(studentId, newStatus, event) {
    event.preventDefault();
    if (!currentSession) return;
    
    // Optimistic UI update
    const target = cachedClockins.find(c => c.id === studentId);
    const oldStatus = target ? target.status : 'Missing';
    if (target) {
        target.status = newStatus;
        target.time = (new Date()).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        renderClockinsList();
    }

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
            if (target) target.status = oldStatus;
            renderClockinsList();
            showTeacherToast(data.message || 'Failed to update status', 'error');
        }
    } catch (error) {
        if (target) target.status = oldStatus;
        renderClockinsList();
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
        font-size: 0.85rem; font-weight: 600; max-width: 340px;
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

async function syncSessionClassroomLocation(sessionId, changes) {
    try {
        const payload = { session_id: sessionId, ...changes };

        const res = await fetch('{{ route("teacher.qr.update-location") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            if (currentSession) {
                currentSession.classroom_lat = data.classroom_lat;
                currentSession.classroom_lng = data.classroom_lng;
                currentSession.radius_meters = data.radius_meters;
            }
            renderLocationHUD();
            return true;
        }
        showTeacherToast(data.message || 'Unable to update the session location.', 'warning');
    } catch (e) {
        console.warn('Failed to sync laptop session location:', e);
        showTeacherToast('Unable to update the session location. Please retry.', 'warning');
    }
    return false;
}

function renderLocationHUD(state) {
    if (!locationStatus) return;

    if (state === 'acquiring') {
        locationStatus.innerHTML = `
            <div class="qr-alert qr-alert-info d-flex align-items-center justify-content-between p-3" style="border-radius: 14px;">
                <div class="d-flex align-items-center gap-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <div>
                        <div class="fw-bold" style="font-size: 0.9rem;">Getting accurate location...</div>
                        <div class="text-muted small">Detecting laptop coordinates as the central reference point for attendance.</div>
                    </div>
                </div>
            </div>
        `;
        return;
    }

    const savedCenter = currentSession && currentSession.classroom_lat !== null && currentSession.classroom_lng !== null
        ? { latitude: Number(currentSession.classroom_lat), longitude: Number(currentSession.classroom_lng) }
        : null;
    const center = savedCenter || teacherLocation;
    const hasCoords = center && Number.isFinite(center.latitude) && Number.isFinite(center.longitude);
    const radiusLabel = selectedRadius <= 0 ? 'Disabled' : selectedRadius + 'm';

    let html = `
        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(207,164,111,0.25); border-radius: 16px; padding: 14px 18px; margin-top: 10px;">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt-fill" style="color: ${hasCoords ? '#10b981' : (selectedRadius <= 0 ? '#60a5fa' : '#f59e0b')}; font-size: 1.15rem;"></i>
                    <span style="font-weight: 700; color: #f3e7cd; font-size: 0.92rem;">
                        ${selectedRadius <= 0 
                            ? 'Geofence: Disabled' 
                            : (hasCoords 
                                ? 'Teacher Laptop Geofence: Active (' + radiusLabel + ')' 
                                : 'Teacher Laptop Geofence: Location Needed')}
                    </span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1" onclick="recalibrateLocation()" style="font-size: 0.78rem;">
                        <i class="bi bi-arrow-repeat me-1"></i> ${currentSession ? 'Refresh/Update Location' : 'Retry Laptop GPS'}
                    </button>
                    <button type="button" class="btn btn-sm ${selectedRadius <= 0 ? 'btn-warning' : 'btn-outline-secondary'} rounded-pill px-3 py-1" onclick="toggleGeofenceDisable()" style="font-size: 0.78rem;">
                        <i class="bi bi-slash-circle me-1"></i> ${selectedRadius <= 0 ? 'Enable Geofence' : 'Disable Geofence'}
                    </button>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 text-muted small" style="font-size: 0.8rem;">
                <div>
                    ${hasCoords 
                        ? `<span><strong>${savedCenter ? 'Saved session center' : 'Laptop preview'}:</strong> ${center.latitude.toFixed(6)}, ${center.longitude.toFixed(6)}${!savedCenter ? ' (±' + Math.round(teacherLocation.accuracy) + 'm)' : ''} &mdash; ${savedCenter ? 'This center stays fixed until you update it.' : 'A new reading is required when starting the session.'}</span>`
                        : (selectedRadius <= 0 
                            ? `<span>Students anywhere (home/remote/labs) can record attendance without location restrictions.</span>`
                            : `<span><i class="bi bi-exclamation-triangle text-warning me-1"></i> ${teacherLocationIssue || 'Laptop location not detected. Allow browser location access to enable geofencing.'}</span>`)}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span style="color: #b39b82;">Radius:</span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn ${selectedRadius === 50 ? 'btn-warning' : 'btn-outline-secondary'} py-0 px-2" onclick="setSessionRadius(50)">50m</button>
                        <button type="button" class="btn ${selectedRadius === 100 ? 'btn-warning' : 'btn-outline-secondary'} py-0 px-2" onclick="setSessionRadius(100)">100m</button>
                        <button type="button" class="btn ${selectedRadius === 200 ? 'btn-warning' : 'btn-outline-secondary'} py-0 px-2" onclick="setSessionRadius(200)">200m</button>
                        <button type="button" class="btn ${selectedRadius <= 0 ? 'btn-danger' : 'btn-outline-secondary'} py-0 px-2" onclick="setSessionRadius(0)">Off</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    locationStatus.innerHTML = html;
}

async function recalibrateLocation() {
    const freshLocation = await captureTeacherLocation();
    if (!freshLocation) {
        showTeacherToast(teacherLocationIssue || 'Unable to get an accurate laptop location. Please retry.', 'warning');
        return;
    }
    if (currentSession && currentSession.session_id) {
        const updated = await syncSessionClassroomLocation(currentSession.session_id, {
            classroom_lat: freshLocation.latitude,
            classroom_lng: freshLocation.longitude,
            teacher_accuracy: freshLocation.accuracy
        });
        if (updated) showTeacherToast('Session center updated to the laptop’s current location.', 'success');
    } else {
        showTeacherToast('Laptop location ready. Starting a session will take another fresh reading.', 'success');
    }
}

async function setSessionRadius(radius) {
    if (currentSession && currentSession.session_id) {
        if (radius > 0 && (currentSession.classroom_lat === null || currentSession.classroom_lng === null)) {
            showTeacherToast('Update the laptop location before enabling the geofence.', 'warning');
            return;
        }
        if (!await syncSessionClassroomLocation(currentSession.session_id, { radius_meters: radius })) return;
        showTeacherToast('Geofence radius set to ' + (radius <= 0 ? 'Disabled' : radius + 'm'), 'success');
    }
    selectedRadius = radius;
    renderLocationHUD();
}

function toggleGeofenceDisable() {
    if (selectedRadius <= 0) {
        setSessionRadius(50);
    } else {
        setSessionRadius(0);
    }
}

function captureTeacherLocation() {
    if (!navigator.geolocation) {
        teacherLocationIssue = 'Location services are unavailable in this browser. Use HTTPS and enable device location.';
        renderLocationHUD();
        return Promise.resolve(null);
    }

    const requestSerial = ++locationRequestSerial;
    teacherLocation = null;
    teacherLocationIssue = '';
    renderLocationHUD('acquiring');

    return new Promise((resolve) => {
        let watchId = null;
        let finished = false;
        let bestAccuracy = Infinity;
        const finish = (location) => {
            if (finished) return;
            finished = true;
            clearTimeout(timer);
            if (watchId !== null) navigator.geolocation.clearWatch(watchId);
            if (requestSerial !== locationRequestSerial) return resolve(null);
            teacherLocation = location;
            if (!location) {
                teacherLocationIssue = Number.isFinite(bestAccuracy)
                    ? `Laptop GPS accuracy is only ±${Math.round(bestAccuracy)}m. Getting accurate location failed; move near a window and retry.`
                    : 'Could not get a fresh laptop location. Check browser permission and device location, then retry.';
            }
            renderLocationHUD();
            resolve(location);
        };
        const timer = setTimeout(() => finish(null), 15000);
        watchId = navigator.geolocation.watchPosition((pos) => {
            const { latitude, longitude, accuracy } = pos.coords;
            const age = Date.now() - pos.timestamp;
            if (!Number.isFinite(latitude) || Math.abs(latitude) > 90 ||
                !Number.isFinite(longitude) || Math.abs(longitude) > 180 ||
                !Number.isFinite(accuracy) || accuracy <= 0 || age < 0 || age > 10000) return;
            bestAccuracy = Math.min(bestAccuracy, accuracy);
            if (accuracy > 50) return;
            finish({ latitude, longitude, accuracy, timestamp: pos.timestamp });
        }, (error) => {
            if (error.code === 1) finish(null);
        }, { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 });
    });
}

// Keyboard shortcuts: 'F' or 'P' for projector mode, 'Escape' to exit
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeProjectorMode();
    }
});
</script>
@endsection
