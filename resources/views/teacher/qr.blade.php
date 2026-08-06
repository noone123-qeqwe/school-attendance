@extends('layouts.app')
@section('page-title', 'QR Attendance - ' . $subject->name)

@push('styles')
<style>
:root {
    --tch-primary: #7c2d12;
    --tch-dark: #5c1a0b;
    --tch-light: #9a3412;
    --tch-accent: #f59e0b;
    --glass-bg: rgba(255, 255, 255, 0.25);
    --glass-border: rgba(255, 255, 255, 0.18);
}

/* Glass Morphism Base */
.glass {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

/* QR Container with Glass Morphism */
.qr-container { 
    min-height: 380px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 2px solid rgba(124, 45, 18, 0.2);
    border-radius: 24px;
    display: flex; flex-direction: column; 
    align-items: center; justify-content: center; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.qr-container::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(124, 45, 18, 0.05) 50%, transparent 70%);
    pointer-events: none;
}

.qr-container.active { 
    background: linear-gradient(145deg, rgba(124, 45, 18, 0.1) 0%, rgba(92, 26, 11, 0.05) 100%);
    border: 2px solid rgba(124, 45, 18, 0.4);
    box-shadow: 0 16px 64px rgba(124, 45, 18, 0.2);
    transform: translateY(-2px);
}

.qr-container img { 
    border-radius: 20px; 
    box-shadow: 0 12px 40px rgba(124, 45, 18, 0.3);
    transition: all 0.3s ease;
    border: 3px solid rgba(124, 45, 18, 0.6);
}

.qr-container img:hover { transform: scale(1.05); }

/* Modern Maroon Buttons */
.modern-btn { 
    background: linear-gradient(135deg, var(--tch-primary) 0%, var(--tch-dark) 100%) !important;
    border: none !important;
    color: white !important;
    padding: 16px 32px !important;
    border-radius: 16px !important;
    font-weight: 700 !important;
    font-size: 0.95rem !important;
    box-shadow: 0 8px 24px rgba(124, 45, 18, 0.4) !important;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    position: relative;
    overflow: hidden;
}

.modern-btn::before {
    content: '';
    position: absolute;
    top: 0; left: -100%; width: 100%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.modern-btn:hover::before {
    left: 100%;
}

.modern-btn:hover { 
    transform: translateY(-3px) !important;
    box-shadow: 0 12px 36px rgba(124, 45, 18, 0.5) !important;
    background: linear-gradient(135deg, var(--tch-light) 0%, var(--tch-primary) 100%) !important;
}

.modern-btn.warning { 
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important;
    box-shadow: 0 8px 24px rgba(245, 158, 11, 0.4) !important;
}

.modern-btn.danger { 
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
    box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4) !important;
}

/* Glass Session Timer */
.session-timer { 
    background: linear-gradient(135deg, rgba(124, 45, 18, 0.9) 0%, rgba(92, 26, 11, 0.8) 100%) !important;
    backdrop-filter: blur(20px) !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    border-radius: 20px !important;
    padding: 20px 28px !important;
    font-weight: 700 !important;
    box-shadow: 0 8px 32px rgba(124, 45, 18, 0.3) !important;
}

.timer-display { 
    font-family: 'Courier New', monospace !important; 
    font-size: 1.4rem !important;
    text-shadow: 0 2px 8px rgba(0,0,0,0.3) !important;
}

/* Glass Statistics Card */
.stats-card { 
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 24px;
    box-shadow: 0 12px 40px rgba(124, 45, 18, 0.15);
    overflow: hidden;
    transition: all 0.3s ease;
    position: relative;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(45deg, transparent 30%, rgba(124, 45, 18, 0.03) 50%, transparent 70%);
    pointer-events: none;
}

.stats-card:hover { 
    transform: translateY(-4px);
    box-shadow: 0 16px 48px rgba(124, 45, 18, 0.2);
}

.stats-header { 
    background: linear-gradient(135deg, var(--tch-primary) 0%, var(--tch-dark) 100%);
    color: white;
    padding: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.stats-header h5 { 
    margin: 0; 
    font-weight: 700; 
    font-size: 1.1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.live-indicator { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
}

.live-dot { 
    width: 10px; height: 10px; 
    background: #10b981; 
    border-radius: 50%; 
    animation: pulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); }
    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}

.stats-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 20px; 
    padding: 28px; 
}

.stat-item { 
    text-align: center; 
    padding: 20px; 
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.4) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(10px);
    border-radius: 16px; 
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-2px);
    background: linear-gradient(145deg, rgba(124, 45, 18, 0.1) 0%, rgba(255, 255, 255, 0.2) 100%);
}

.stat-number { 
    font-size: 2.2rem; 
    font-weight: 800; 
    margin-bottom: 8px;
    background: linear-gradient(135deg, var(--tch-primary), var(--tch-light));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-label { 
    font-size: 0.75rem; 
    font-weight: 600; 
    color: #64748b; 
    text-transform: uppercase; 
    letter-spacing: 0.5px; 
}

/* Glass Clock-ins Items */
.clockin-item { 
    display: flex; 
    align-items: center; 
    gap: 16px; 
    padding: 20px; 
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px; 
    margin-bottom: 16px; 
    transition: all 0.3s ease;
    animation: slideInRight 0.5s ease;
}

.clockin-item:hover { 
    background: linear-gradient(145deg, rgba(124, 45, 18, 0.1) 0%, rgba(255, 255, 255, 0.2) 100%);
    transform: translateX(8px);
    box-shadow: 0 8px 24px rgba(124, 45, 18, 0.15);
}

@keyframes slideInRight {
    from { opacity: 0; transform: translateX(-30px); }
    to { opacity: 1; transform: translateX(0); }
}

.avatar-circle { 
    width: 52px; height: 52px; 
    background: linear-gradient(135deg, var(--tch-primary) 0%, var(--tch-light) 100%);
    color: white; 
    border-radius: 50%; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-weight: 700; 
    font-size: 1rem;
    box-shadow: 0 6px 20px rgba(124, 45, 18, 0.4);
    border: 2px solid rgba(255, 255, 255, 0.3);
}

.status-badge { 
    padding: 6px 14px; 
    border-radius: 25px; 
    font-size: 0.7rem; 
    font-weight: 700; 
    text-transform: uppercase; 
    margin-bottom: 4px;
    backdrop-filter: blur(10px);
}

.status-present { 
    background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
    color: white;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.status-late { 
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); 
    color: white;
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

/* Main Card Glass Effect */
.main-card {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    border-radius: 24px;
    box-shadow: 0 12px 40px rgba(124, 45, 18, 0.15);
    overflow: hidden;
}

.main-card-header {
    background: linear-gradient(135deg, var(--tch-primary) 0%, var(--tch-dark) 100%);
    color: white;
    padding: 24px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.main-card-header h4 {
    margin-bottom: 8px;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .row {
        flex-direction: column !important;
        gap: 20px !important;
    }
    
    #sidebar {
        width: 100% !important;
        min-width: auto !important;
        flex-shrink: 1 !important;
    }
}
</style>
@endpush

@section('content')

<div class="container-fluid">
    <div class="row" style="display: flex; gap: 24px;">
        <!-- Main QR Section -->
        <div style="flex: 1; min-width: 0;" id="mainSection">
            <div class="main-card shadow-sm">
                <div class="main-card-header">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1">{{ $subject->name }} ({{ $subject->code }})</h4>
                <small class="opacity-75">Year {{ $subject->year_level }} â€¢ Semester {{ $subject->semester }}</small>
            </div>
            <a href="{{ route('teacher.subjects') }}" class="btn modern-btn" style="background: rgba(255,255,255,0.16); color: white; border: 1px solid rgba(255,255,255,0.25);">
                <i class="bi bi-arrow-left me-2"></i> Back to Subjects
            </a>
        </div>
                <div class="card-body text-center" style="min-height: 500px; padding: 3rem 2rem;">
                    
                    <!-- QR Code Display Area -->
                    <div id="qrCodeContainer" class="qr-container" style="margin: 2rem auto;">
                        <div style="color: #666;">
                            <i class="bi bi-qr-code" style="font-size: 4.5rem; opacity: 0.4; margin-bottom: 1.5rem; color: var(--tch-primary);"></i>
                            <h5 style="color: var(--tch-primary); font-weight: 700;">Click "Start Session" to generate QR code</h5>
                            <p class="text-muted">Students will scan this code to mark attendance</p>
                        </div>
                    </div>
                    
                    <!-- QR Refresh Countdown -->
                    <div id="qrRefreshCountdown" style="display: none; text-align: center; margin: 1rem auto; color: #666; font-size: 0.9rem;">
                        <small>QR refreshing in: <strong id="refreshCountdownText" style="color: var(--tch-primary); font-weight: 700;">--</strong>s</small>
                    </div>
                    
                    <!-- Session Timer -->
                    <div id="sessionTimer" class="session-timer" style="display: none; margin: 2rem auto; max-width: 350px;">
                        <div class="d-flex align-items-center justify-content-center gap-3">
                            <i class="bi bi-clock-fill" style="font-size: 1.2rem;"></i>
                            <span>Session ends in: <span id="timeRemaining" class="timer-display">--:--</span></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="mt-5 d-flex flex-wrap justify-content-center gap-4">
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
                    
                    <div id="locationStatus" class="mt-4"></div>
                    <div id="statusMessages" class="mt-4"></div>
                </div>
            </div>
        </div>
        
        <!-- Live Statistics Sidebar -->
        <div id="sidebar" style="display: none; width: 350px; flex-shrink: 0; min-width: 350px;">
            <!-- Real-time Stats Card -->
            <div class="stats-card mb-4">
                <div class="stats-header">
                    <h5><i class="bi bi-bar-chart-fill me-2"></i>Live Statistics</h5>
                    <div class="live-indicator">
                        <span class="live-dot"></span>
                        <small class="text-success fw-bold opacity-75">LIVE</small>
                    </div>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number" id="totalStudents">0</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-success" id="clockedIn">0</div>
                        <div class="stat-label">Present</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-warning" id="lateCount">0</div>
                        <div class="stat-label">Late</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number text-info" id="progressPercent">0%</div>
                        <div class="stat-label">Progress</div>
                    </div>
                </div>
                <div class="px-4 pb-4">
                    <div class="progress" style="height: 12px; border-radius: 10px; background: rgba(255,255,255,0.3);">
                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             style="width: 0%; background: linear-gradient(135deg, var(--tch-primary) 0%, var(--tch-light) 100%); border-radius: 10px;"></div>
                    </div>
                </div>
            </div>
            
            <!-- Live Clock-ins Feed -->
            <div class="stats-card">
                <div class="stats-header">
                    <h5><i class="bi bi-people-fill me-2"></i>Live Clock-ins</h5>
                    <div class="live-dot"></div>
                </div>
                <div style="max-height: 450px; overflow-y: auto; padding: 1.5rem;">
                    <div id="clockinsList">
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-clock-history" style="font-size: 3.5rem; opacity: 0.3; color: var(--tch-primary);"></i>
                            <h6 class="mt-3" style="color: var(--tch-primary);">Waiting for students...</h6>
                            <p class="small mb-0">Clock-ins will appear here in real-time</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
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
let refreshCountdownSeconds = 25;

const startBtn = document.getElementById('startBtn');
const refreshBtn = document.getElementById('refreshBtn');
const stopBtn = document.getElementById('stopBtn');
const qrContainer = document.getElementById('qrCodeContainer');
const sidebar = document.getElementById('sidebar');
const mainSection = document.getElementById('mainSection');
const locationStatus = document.getElementById('locationStatus');

// Check schedule on page load
document.addEventListener('DOMContentLoaded', () => {
    startBtn.disabled = true;
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

        if (!scheduleData.has_schedule) {
            showScheduleMessage('no-schedule', scheduleData.message);
            startBtn.disabled = true;
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
                <div class="alert alert-warning d-flex align-items-center" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                    <i class="bi bi-clock-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #92400e; font-weight: 700;">â° Session Opens in ${scheduleData.schedule.session_opens}</h6>
                        <p class="mb-0 small" style="color: #78350f;">
                            <strong>Class Time:</strong> ${scheduleData.schedule.class_start} - ${scheduleData.schedule.class_end}<br>
                            <strong>Current Time:</strong> ${scheduleData.schedule.current_time}<br>
                            <strong>Wait Time:</strong> ${scheduleData.message}
                        </p>
                    </div>
                </div>
            `;
            break;
            
        case 'ready':
            startBtn.disabled = teacherLocation === null;
            startBtn.className = 'btn modern-btn btn-lg';
            startBtn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Start Session';
            
            statusMessages.innerHTML = `
                <div class="alert alert-success d-flex align-items-center" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                    <i class="bi bi-check-circle-fill mb-2" style="font-size: 1.5rem; color: #10b981;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #047857; font-weight: 700;">âœ… Ready to Start Session</h6>
                        <p class="mb-0 small" style="color: #065f46;">
                            <strong>Class Time:</strong> ${scheduleData.schedule.class_start} - ${scheduleData.schedule.class_end}<br>
                            <strong>Current Time:</strong> ${scheduleData.schedule.current_time}
                        </p>
                        ${teacherLocation === null ? '<p class="mb-0 small" style="color: #1d4ed8;">Waiting for laptop location. Start will be enabled once location is captured.</p>' : ''}
                    </div>
                </div>
            `;
            break;
            
        case 'ended':
            startBtn.disabled = true;
            startBtn.className = 'btn modern-btn danger btn-lg';
            startBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i> Class Ended';
            
            statusMessages.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 16px;">
                    <i class="bi bi-x-circle-fill me-3" style="font-size: 1.5rem; color: #ef4444;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #b91c1c; font-weight: 700;">âŒ Class Has Ended</h6>
                        <p class="mb-0 small" style="color: #991b1b;">
                            Class ended at ${scheduleData.schedule.class_end}. Cannot start new attendance session.
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
            <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px;">
                <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem; color: #3b82f6;"></i>
                <div>
                    <h6 class="mb-1" style="color: #1d4ed8; font-weight: 700;">â„¹ï¸ No Class Today</h6>
                    <p class="mb-0 small" style="color: #1e40af;">${message}</p>
                </div>
            </div>
        `;
    } else if (type === 'error') {
        statusMessages.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 16px;">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem; color: #ef4444;"></i>
                <div>
                    <h6 class="mb-1" style="color: #b91c1c; font-weight: 700;">âŒ Schedule Error</h6>
                    <p class="mb-0 small" style="color: #991b1b;">${message}</p>
                </div>
            </div>
        `;
    }
}

// Start session
startBtn.addEventListener('click', async () => {
    try {
        if (!teacherLocation) {
            alert('âŒ Location not captured! Please wait for the location to be confirmed before starting the session.');
            return;
        }

        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Starting...';

        const bodyPayload = {
            subject_code: '{{ $subject->code }}',
            classroom_lat: teacherLocation.latitude,
            classroom_lng: teacherLocation.longitude
        };

        console.log('Starting session with coordinates:', bodyPayload);

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
            
            // Clear schedule check since session is now active
            if (scheduleCheckInterval) {
                clearInterval(scheduleCheckInterval);
                scheduleCheckInterval = null;
            }
            
            // Clear status messages
            document.getElementById('statusMessages').innerHTML = '';
            
            console.log('Session started successfully:', data);
        } else {
            // Show detailed error message from backend
            const statusMessages = document.getElementById('statusMessages');
            statusMessages.innerHTML = `
                <div class="alert alert-warning d-flex align-items-center" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                    <i class="bi bi-exclamation-triangle-fill mb-2" style="font-size: 1.5rem; color: #f59e0b;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #92400e; font-weight: 700;">âš ï¸ Cannot Start Session</h6>
                        <p class="mb-0 small" style="color: #78350f; white-space: pre-line;">${data.message}</p>
                    </div>
                </div>
            `;
            console.error('Session start failed:', data);
        }
    } catch (error) {
        console.error('Error:', error);
        const statusMessages = document.getElementById('statusMessages');
        statusMessages.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                <i class="bi bi-wifi-off mb-2" style="font-size: 1.5rem; color: #ef4444;"></i>
                <div>
                    <h6 class="mb-1" style="color: #b91c1c; font-weight: 700;">ðŸŒ Network Error</h6>
                    <p class="mb-0 small" style="color: #991b1b;">${error.message}</p>
                </div>
            </div>
        `;
    } finally {
        startBtn.disabled = teacherLocation === null;
        startBtn.innerHTML = '<i class="bi bi-play-fill me-2"></i> Start Session';
    }
});

// Additional functions
function updateUIForActiveSession() {
    startBtn.style.display = 'none';
    refreshBtn.style.display = 'inline-block';
    stopBtn.style.display = 'inline-block';
    sidebar.style.display = 'block';
    document.getElementById('sessionTimer').style.display = 'block';
}

function getRefreshIntervalSeconds() {
    const ttl = Number(currentSession?.ttl) || 20;
    return Math.max(ttl - 5, 10);
}

function resetRefreshTimers() {
    const ttl = Number(currentSession?.ttl) || 20;
    refreshCountdownSeconds = ttl;
    document.getElementById('refreshCountdownText').textContent = refreshCountdownSeconds;

    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);
    refreshCountdownInterval = setInterval(() => {
        refreshCountdownSeconds--;
        if (refreshCountdownSeconds <= 0) {
            refreshCountdownSeconds = ttl;
        }
        document.getElementById('refreshCountdownText').textContent = refreshCountdownSeconds;
    }, 1000);

    if (refreshInterval) clearInterval(refreshInterval);
    refreshInterval = setInterval(() => refreshBtn.click(), getRefreshIntervalSeconds() * 1000);
}

function startIntervals() {
    document.getElementById('qrRefreshCountdown').style.display = 'block';
    resetRefreshTimers();
    // Removed polling interval - relying on WebSockets via subscribeToTeacherAttendanceUpdates()
    startSessionTimer(currentSession.session_end);
    updateClockIns(); // Initial fetch
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
            showQRCode(data.scan_url);
            currentSession.token = data.token;
            currentSession.ttl = data.ttl || currentSession.ttl || 25;
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
        
        resetUI();
    } catch (error) {
        console.error('Error stopping:', error);
        resetUI();
    }
});

function showQRCode(url) {
    // Use reliable online QR service with better error handling
    const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(url);
    
    qrContainer.innerHTML = `
        <div class="text-center">
            <img src="${qrUrl}" 
                 alt="Attendance QR Code" 
                 class="img-fluid mb-3" 
                 style="max-width: 300px; width: 100%; height: auto;"
                 onload="this.style.opacity='1'"
                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjMwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZjBmMGYwIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCwgc2Fucy1zZXJpZiIgZm9udC1zaXplPSIxOCIgZmlsbD0iIzk5OTk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPkVycm9yIExvYWRpbmcgUVI8L3RleHQ+PC9zdmc+'"
                 style="opacity: 0; transition: opacity 0.3s ease;">
            <h5 style="color: var(--tch-primary); font-weight: 700;">
                <i class="bi bi-qr-code-scan me-2"></i>Scan to Clock In
            </h5>
            <p class="text-muted">Students scan this code to mark their attendance</p>
            <small class="badge" style="background: linear-gradient(135deg, var(--tch-primary), var(--tch-light)); color: white; padding: 8px 16px; border-radius: 20px;">
                Session Active
            </small>
        </div>
    `;
    qrContainer.classList.add('active');
    
    // Add loading animation
    qrContainer.style.transform = 'scale(0.95)';
    setTimeout(() => {
        qrContainer.style.transform = 'scale(1)';
    }, 100);
}

function startSessionTimer(sessionEndTimestamp) {
    timerInterval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const remaining = sessionEndTimestamp - now;

        if (remaining <= 0) {
            document.getElementById('timeRemaining').textContent = '00:00';
            
            // Show auto-closure message
            const statusMessages = document.getElementById('statusMessages');
            statusMessages.innerHTML = `
                <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px;">
                    <i class="bi bi-info-circle-fill me-3" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #1d4ed8; font-weight: 700;">ðŸ•’ Session Auto-Closed</h6>
                        <p class="mb-0 small" style="color: #1e40af;">20-minute attendance window has ended. Students not present have been automatically marked absent.</p>
                    </div>
                </div>
            `;
            
            resetUI();
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        const timeDisplay = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        document.getElementById('timeRemaining').textContent = timeDisplay;
        
        // Show warning when less than 2 minutes remaining
        if (remaining <= 120 && remaining > 60) {
            document.getElementById('sessionTimer').style.background = 'linear-gradient(135deg, rgba(245, 158, 11, 0.9) 0%, rgba(217, 119, 6, 0.8) 100%)';
        } else if (remaining <= 60) {
            document.getElementById('sessionTimer').style.background = 'linear-gradient(135deg, rgba(239, 68, 68, 0.9) 0%, rgba(220, 38, 38, 0.8) 100%)';
        }
    }, 1000);
}

async function updateClockIns() {
    if (!currentSession) {
        console.warn('updateClockIns skipped: no currentSession');
        return;
    }

    try {
        const response = await fetch(`{{ route("teacher.qr.clockins") }}?session_id=${currentSession.session_id}`);
        if (!response.ok) {
            console.warn('Clockins fetch failed:', response.status, response.statusText);
            return;
        }

        const data = await response.json();
        if (!data || !data.stats) {
            console.warn('Clockins response missing stats:', data);
            return;
        }

        document.getElementById('totalStudents').textContent = data.stats.total_students;
        document.getElementById('clockedIn').textContent = data.stats.clocked_in;
        document.getElementById('lateCount').textContent = data.stats.late;
        document.getElementById('progressPercent').textContent = data.stats.progress + '%';
        document.getElementById('progressBar').style.width = data.stats.progress + '%';

        const clockinsList = document.getElementById('clockinsList');
        
        if (data.clockins.length === 0) {
            clockinsList.innerHTML = `
                <div class="text-center text-muted py-5">
                    <i class="bi bi-clock-history" style="font-size: 3rem; opacity: 0.3;"></i>
                    <h6 class="mt-3">Waiting for students...</h6>
                    <p class="small mb-0">Clock-ins will appear here</p>
                </div>
            `;
        } else {
            clockinsList.innerHTML = data.clockins.map(clockin => `
                <div class="clockin-item">
                    <div class="clockin-avatar">
                        <div class="avatar-circle">${clockin.name.substring(0, 2).toUpperCase()}</div>
                    </div>
                    <div class="clockin-info flex-grow-1">
                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">${clockin.name}</div>
                        <div class="text-muted" style="font-size: 0.75rem; font-family: monospace;">${clockin.student_number}</div>
                    </div>
                    <div class="clockin-status text-end">
                        <span class="status-badge status-${clockin.status.toLowerCase()}">${clockin.status}</span>
                        <div class="text-muted" style="font-size: 0.7rem;">${clockin.time}</div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error updating clock-ins:', error);
    }
}

function subscribeToTeacherAttendanceUpdates() {
    if (!window.teacherEcho) {
        setTimeout(subscribeToTeacherAttendanceUpdates, 250);
        return;
    }

    if (window.teacherAttendanceSubscribed) {
        return;
    }

    window.teacherAttendanceSubscribed = true;

    window.teacherEcho.private('teacher-dashboard.{{ Auth::id() }}')
        .listen('.attendance.updated', (payload) => {
            if (!currentSession || !payload || payload.subject_code !== '{{ $subject->code }}') {
                return;
            }

            if (payload.type === 'clock_in') {
                updateClockIns();
                showTeacherToast(`${payload.student_name} clocked in for ${payload.subject_code} (${payload.status})`, 'success');
            }
        });
}

subscribeToTeacherAttendanceUpdates();

function showTeacherToast(message, type = 'info') {
    const colors = {
        success: { bg: 'rgba(16,185,129,0.9)', border: 'rgba(16,185,129,0.6)' },
        warning: { bg: 'rgba(245,158,11,0.9)', border: 'rgba(245,158,11,0.6)' },
        error:   { bg: 'rgba(239,68,68,0.9)',  border: 'rgba(239,68,68,0.6)' },
        info:    { bg: 'rgba(59,130,246,0.9)',  border: 'rgba(59,130,246,0.6)' },
    };
    const c = colors[type] || colors.info;

    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: ${c.bg}; border: 1px solid ${c.border};
        color: white; padding: 14px 20px; border-radius: 14px;
        font-size: 0.85rem; font-weight: 600; max-width: 320px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        backdrop-filter: blur(12px);
        animation: slideInToast 0.35s ease;
        display: flex; align-items: center; gap: 10px;
    `;

    // Add CSS animation if not already present
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

    const icon = type === 'success' ? 'âœ…' : type === 'warning' ? 'âš ï¸' : type === 'error' ? 'âŒ' : 'â„¹ï¸';
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
            <div class="alert alert-warning d-flex align-items-center" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                <i class="bi bi-geo-alt-fill me-3" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <div>
                    <h6 class="mb-1" style="color: #92400e; font-weight: 700;">ðŸ“ Location Not Available</h6>
                    <p class="mb-0 small" style="color: #78350f;">Your browser does not support geolocation. Students will be validated against the default campus coordinates.</p>
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
        <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
            <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: #3b82f6;"></i>
            <div>
                <h6 class="mb-1" style="color: #1d4ed8; font-weight: 700;">ðŸ“ Capturing Laptop Location...</h6>
                <p class="mb-0 small" style="color: #1e40af;">Please allow location access so the student scan area matches your laptop.</p>
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
            <div class="alert ${isReliable ? 'alert-success' : 'alert-warning'} d-flex align-items-center" style="background: linear-gradient(135deg, ${isReliable ? 'rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%' : 'rgba(249, 115, 22, 0.1) 0%, rgba(234, 88, 12, 0.1) 100%'}); border: 1px solid ${isReliable ? 'rgba(16, 185, 129, 0.3)' : 'rgba(249, 115, 22, 0.3)'}; border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                <i class="bi bi-geo-alt-fill mb-2" style="font-size: 1.5rem; color: ${isReliable ? '#10b981' : '#f97316'};"></i>
                <div>
                    <h6 class="mb-1" style="color: ${isReliable ? '#047857' : '#9a3412'}; font-weight: 700;">ðŸ“ Using Laptop Location</h6>
                    <p class="mb-0 small" style="color: ${isReliable ? '#065f46' : '#7c2d12'};">Latitude: ${teacherLocation.latitude.toFixed(6)}, Longitude: ${teacherLocation.longitude.toFixed(6)}</p>
                    <p class="mb-0 small" style="color: ${isReliable ? '#065f46' : '#7c2d12'};">Accuracy: ${Math.round(accuracy)}m${isReliable ? ' â€” this is acceptable for classroom location.' : ' â€” this is a weaker fix, but the session will still use it.'}</p>
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
                <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px;">
                    <i class="bi bi-geo-alt-fill me-3" style="font-size: 1.5rem; color: #3b82f6;"></i>
                    <div>
                        <h6 class="mb-1" style="color: #1d4ed8; font-weight: 700;">ðŸ“ Retrying with lower accuracy...</h6>
                        <p class="mb-0 small" style="color: #1e40af;">High accuracy did not respond. Trying a fallback request now.</p>
                    </div>
                </div>
            `;
            startLocationWatch(false);
            return;
        }

        teacherLocation = null;
        const message = error.code === error.PERMISSION_DENIED
            ? 'Location permission denied. Please allow location access to use your laptop location.'
            : 'Unable to get your location. Please retry or check your browser settings.';

        startBtn.disabled = true;

        locationStatus.innerHTML = `
            <div class="alert alert-warning d-flex align-items-center" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(217, 119, 6, 0.1) 100%); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 16px; flex-direction: column; justify-content: center; text-align: center;">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 1.5rem; color: #f59e0b;"></i>
                <div>
                    <h6 class="mb-1" style="color: #92400e; font-weight: 700;">âš ï¸ Location Access Needed</h6>
                    <p class="mb-0 small" style="color: #78350f;">${message}</p>
                    <p class="mb-0 small"><strong>Please retry location capture before starting the session.</strong></p>
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
                    <div class="alert alert-info d-flex align-items-center" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 16px;">
                        <i class="bi bi-geo-alt-fill me-3" style="font-size: 1.5rem; color: #3b82f6;"></i>
                        <div>
                            <h6 class="mb-1" style="color: #1d4ed8; font-weight: 700;">ðŸ“ Trying low-accuracy fallback...</h6>
                            <p class="mb-0 small" style="color: #1e40af;">If this still fails, default campus coordinates will be used.</p>
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

function resetUI() {
    currentSession = null;
    
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);
    if (refreshCountdownInterval) clearInterval(refreshCountdownInterval);

    startBtn.style.display = 'inline-block';
    refreshBtn.style.display = 'none';
    stopBtn.style.display = 'none';
    document.getElementById('sessionTimer').style.display = 'none';
    document.getElementById('qrRefreshCountdown').style.display = 'none';
    
    qrContainer.classList.remove('active');
    sidebar.style.display = 'none';

    qrContainer.innerHTML = `
        <div style="color: #666;">
            <i class="bi bi-qr-code" style="font-size: 4.5rem; opacity: 0.4; margin-bottom: 1.5rem; color: var(--tch-primary);"></i>
            <h5 style="color: var(--tch-primary); font-weight: 700;">Click "Start Session" to generate QR code</h5>
            <p class="text-muted">Students will scan this code to mark attendance</p>
        </div>
    `;

    document.getElementById('totalStudents').textContent = '0';
    document.getElementById('clockedIn').textContent = '0';
    document.getElementById('lateCount').textContent = '0';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressBar').style.width = '0%';
    
    // Reset clock-ins list
    document.getElementById('clockinsList').innerHTML = `
        <div class="text-center text-muted py-5">
            <i class="bi bi-clock-history" style="font-size: 3.5rem; opacity: 0.3; color: var(--tch-primary);"></i>
            <h6 class="mt-3" style="color: var(--tch-primary);">Waiting for students...</h6>
            <p class="small mb-0">Clock-ins will appear here in real-time</p>
        </div>
    `;
    
    // Restart schedule checking when session ends
    if (!scheduleCheckInterval) {
        checkScheduleStatus();
        scheduleCheckInterval = setInterval(checkScheduleStatus, 30000);
    }
}
</script>
@endsection
