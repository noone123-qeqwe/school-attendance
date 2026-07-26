@extends('teacher.layout')
@section('page-title', 'QR Attendance - ' . $subject->name)

@section('content')
<style>
    .tch-qr-container {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 24px;
        height: calc(100vh - 140px);
    }
    
    @media (max-width: 1200px) {
        .tch-qr-container {
            grid-template-columns: 1fr;
            height: auto;
        }
    }

    .tch-qr-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .tch-qr-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .tch-qr-header {
        padding: 20px 24px;
        border-bottom: 1px solid #f8fafc;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .tch-qr-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .tch-qr-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: #fff0f3;
        color: #7c2d12;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .tch-qr-display {
        padding: 40px;
        text-align: center;
        min-height: 400px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .tch-qr-code {
        width: 280px;
        height: 280px;
        border: 4px solid #f1f5f9;
        border-radius: 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
    }

    .tch-qr-info {
        text-align: center;
        color: #64748b;
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .tch-controls {
        padding: 20px 24px;
        border-top: 1px solid #f8fafc;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .tch-btn {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.875rem;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        text-decoration: none;
    }

    .tch-btn-primary {
        background: linear-gradient(135deg, #7c2d12, #9a3412);
        color: white;
        box-shadow: 0 4px 14px rgba(124,45,18,0.25);
    }

    .tch-btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(124,45,18,0.35);
    }

    .tch-btn-secondary {
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .tch-btn-secondary:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .tch-btn-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .tch-btn-danger:hover {
        background: #fee2e2;
        border-color: #fca5a5;
    }

    .tch-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .tch-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        padding: 20px 24px;
    }

    .tch-stat {
        text-align: center;
    }

    .tch-stat-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #7c2d12;
        margin-bottom: 4px;
    }

    .tch-stat-label {
        font-size: 0.75rem;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .tch-clockins {
        flex: 1;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .tch-clockins-list {
        flex: 1;
        overflow-y: auto;
        padding: 0 24px 20px;
    }

    .tch-clockin-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 0;
        border-bottom: 1px solid #f8fafc;
    }

    .tch-clockin-item:last-child {
        border-bottom: none;
    }

    .tch-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #7c2d12, #9a3412);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
        flex-shrink: 0;
    }

    .tch-clockin-info {
        flex: 1;
        min-width: 0;
    }

    .tch-clockin-name {
        font-weight: 600;
        color: #1e293b;
        font-size: 0.875rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .tch-clockin-number {
        font-size: 0.75rem;
        color: #64748b;
    }

    .tch-clockin-status {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
    }

    .tch-status-badge {
        padding: 2px 8px;
        border-radius: 99px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .tch-status-present {
        background: #f0fdf4;
        color: #16a34a;
    }

    .tch-status-late {
        background: #fffbeb;
        color: #d97706;
    }

    .tch-clockin-time {
        font-size: 0.7rem;
        color: #94a3b8;
    }

    .tch-empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #94a3b8;
    }

    .tch-empty-state i {
        font-size: 2rem;
        margin-bottom: 12px;
        opacity: 0.3;
    }

    .tch-progress-bar {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
        margin: 16px 0;
    }

    .tch-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #7c2d12, #9a3412);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    .tch-session-timer {
        font-size: 0.875rem;
        color: #64748b;
        text-align: center;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 16px;
    }
</style>

<div class="tch-qr-container">
    <!-- Main QR Display -->
    <div class="tch-qr-main">
        <!-- Subject Info -->
        <div class="tch-qr-card">
            <div class="tch-qr-header">
                <div class="tch-qr-title">
                    <div class="tch-qr-icon">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <div>
                        <h2 style="font-size: 1.125rem; font-weight: 700; color: #1e293b; margin: 0;">{{ $subject->name }}</h2>
                        <p style="font-size: 0.875rem; color: #64748b; margin: 0;">{{ $subject->code }} • Year {{ $subject->year_level }} • Semester {{ $subject->semester }}</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;">Today</div>
                    <div style="font-size: 0.875rem; font-weight: 600; color: #1e293b;">{{ now()->format('M d, Y') }}</div>
                </div>
            </div>
        </div>

        <!-- QR Code Display -->
        <div class="tch-qr-card" style="flex: 1;">
            <div class="tch-qr-header">
                <div class="tch-qr-title">
                    <div class="tch-qr-icon">
                        <i class="bi bi-camera-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">QR Code Scanner</h3>
                        <p style="font-size: 0.875rem; color: #64748b; margin: 0;">Students scan this code to clock in</p>
                    </div>
                </div>
                <div id="sessionStatus" style="display: none;">
                    <span class="tch-status-badge tch-status-present">Active</span>
                </div>
            </div>

            <div class="tch-qr-display">
                <div id="qrCodeContainer" class="tch-qr-code">
                    <div style="color: #94a3b8; text-align: center;">
                        <i class="bi bi-qr-code" style="font-size: 3rem; margin-bottom: 12px; opacity: 0.3;"></i>
                        <p style="margin: 0; font-size: 0.875rem;">Click "Start Session" to generate QR code</p>
                    </div>
                </div>

                <div id="sessionTimer" class="tch-session-timer" style="display: none;">
                    Session ends in: <span id="timeRemaining">--:--</span>
                </div>

                <div class="tch-qr-info">
                    <p><strong>Instructions for Students:</strong></p>
                    <p>1. Scan the QR code with your phone camera<br>
                    2. Allow location access when prompted<br>
                    3. Complete fingerprint verification<br>
                    4. Attendance will be recorded automatically</p>
                </div>
            </div>

            <div class="tch-controls">
                <button id="startBtn" class="tch-btn tch-btn-primary">
                    <i class="bi bi-play-fill"></i> Start Session
                </button>
                <button id="refreshBtn" class="tch-btn tch-btn-secondary" style="display: none;">
                    <i class="bi bi-arrow-clockwise"></i> Refresh QR
                </button>
                <button id="stopBtn" class="tch-btn tch-btn-danger" style="display: none;">
                    <i class="bi bi-stop-fill"></i> Stop Session
                </button>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="tch-sidebar" id="sidebar" style="display: none;">
        <!-- Stats -->
        <div class="tch-qr-card">
            <div class="tch-qr-header">
                <div class="tch-qr-title">
                    <div class="tch-qr-icon">
                        <i class="bi bi-bar-chart-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">Today's Stats</h3>
                    </div>
                </div>
            </div>
            <div class="tch-stats-grid">
                <div class="tch-stat">
                    <div id="totalStudents" class="tch-stat-value">0</div>
                    <div class="tch-stat-label">Total Students</div>
                </div>
                <div class="tch-stat">
                    <div id="clockedIn" class="tch-stat-value">0</div>
                    <div class="tch-stat-label">Clocked In</div>
                </div>
                <div class="tch-stat">
                    <div id="lateCount" class="tch-stat-value">0</div>
                    <div class="tch-stat-label">Late</div>
                </div>
                <div class="tch-stat">
                    <div id="progressPercent" class="tch-stat-value">0%</div>
                    <div class="tch-stat-label">Progress</div>
                </div>
            </div>
            <div style="padding: 0 24px 20px;">
                <div class="tch-progress-bar">
                    <div id="progressBar" class="tch-progress-fill" style="width: 0%;"></div>
                </div>
            </div>
        </div>

        <!-- Live Clock-ins -->
        <div class="tch-qr-card tch-clockins">
            <div class="tch-qr-header">
                <div class="tch-qr-title">
                    <div class="tch-qr-icon">
                        <i class="bi bi-clock-fill"></i>
                    </div>
                    <div>
                        <h3 style="font-size: 1rem; font-weight: 700; color: #1e293b; margin: 0;">Live Clock-ins</h3>
                    </div>
                </div>
            </div>
            <div id="clockinsList" class="tch-clockins-list">
                <div class="tch-empty-state">
                    <i class="bi bi-people"></i>
                    <p style="margin: 0; font-size: 0.875rem;">No clock-ins yet</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadQRCodeLibrary() {
    if (window.QRCode || window.qrcode) {
        return Promise.resolve();
    }

    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
        script.async = true;
        script.onload = () => resolve();
        script.onerror = () => {
            const fallback = document.createElement('script');
            fallback.src = 'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
            fallback.async = true;
            fallback.onload = () => resolve();
            fallback.onerror = () => reject(new Error('Failed to load QRCode library'));
            document.head.appendChild(fallback);
        };
        document.head.appendChild(script);
    });
}

let currentSession = null;
let refreshInterval = null;
let clockinInterval = null;
let timerInterval = null;

const startBtn = document.getElementById('startBtn');
const refreshBtn = document.getElementById('refreshBtn');
const stopBtn = document.getElementById('stopBtn');
const qrContainer = document.getElementById('qrCodeContainer');
const sessionStatus = document.getElementById('sessionStatus');
const sessionTimer = document.getElementById('sessionTimer');
const timeRemaining = document.getElementById('timeRemaining');
const sidebar = document.getElementById('sidebar');

// Start session
startBtn.addEventListener('click', async () => {
    try {
        startBtn.disabled = true;
        startBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Starting...';

        const response = await fetch('{{ route("teacher.qr.start") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                subject_code: '{{ $subject->code }}'
            }),
            credentials: 'same-origin',
        });

        const text = await response.text();
        let data;

        try {
            data = JSON.parse(text);
        } catch (parseError) {
            throw new Error(`Server returned non-JSON response (${response.status}): ${text}`);
        }

        if (!response.ok) {
            throw new Error(data.message || `Server returned ${response.status}`);
        }

        if (data.success) {
            currentSession = data;
            await showQRCode(data.scan_url);
            startBtn.style.display = 'none';
            refreshBtn.style.display = 'inline-flex';
            stopBtn.style.display = 'inline-flex';
            sessionStatus.style.display = 'block';
            sessionTimer.style.display = 'block';
            sidebar.style.display = 'flex';

            // Start intervals
            startRefreshInterval();
            startClockinsPolling();
            startSessionTimer(data.session_end);
        } else {
            alert(data.message || 'Failed to start session');
        }
    } catch (error) {
        console.error('Error starting session:', error);

        if (error instanceof SyntaxError) {
            alert('Failed to start session: invalid server response. Check the browser console for details.');
        } else {
            alert(error.message || 'Failed to start session. Please refresh the page and try again.');
        }
    } finally {
        startBtn.disabled = false;
        startBtn.innerHTML = '<i class="bi bi-play-fill"></i> Start Session';
    }
});

// Refresh QR
refreshBtn.addEventListener('click', async () => {
    if (!currentSession) return;

    try {
        const response = await fetch('{{ route("teacher.qr.refresh") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: currentSession.session_id
            })
        });

        const data = await response.json();

        if (data.success) {
            await showQRCode(data.scan_url);
            currentSession.token = data.token;
            currentSession.scan_url = data.scan_url;
        }
    } catch (error) {
        console.error('Error refreshing QR:', error);
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
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                session_id: currentSession.session_id
            })
        });

        resetUI();
    } catch (error) {
        console.error('Error stopping session:', error);
        resetUI();
    }
});

async function showQRCode(url) {
    qrContainer.innerHTML = '';
    await loadQRCodeLibrary();

    const QR = window.QRCode || window.qrcode;
    if (!QR) {
        throw new Error('QRCode library is not available');
    }

    if (typeof QR.toCanvas === 'function') {
        QR.toCanvas(qrContainer, url, {
            width: 280,
            height: 280,
            margin: 2,
            color: {
                dark: '#7c2d12',
                light: '#ffffff'
            }
        });
        return;
    }

    new QR(qrContainer, {
        text: url,
        width: 280,
        height: 280,
        colorDark: '#7c2d12',
        colorLight: '#ffffff'
    });
}

function startRefreshInterval() {
    refreshInterval = setInterval(() => {
        refreshBtn.click();
    }, 25000); // Refresh every 25 seconds
}

function startClockinsPolling() {
    clockinInterval = setInterval(updateClockIns, 3000); // Poll every 3 seconds
    updateClockIns(); // Initial load
}

function startSessionTimer(sessionEndTimestamp) {
    timerInterval = setInterval(() => {
        const now = Math.floor(Date.now() / 1000);
        const remaining = sessionEndTimestamp - now;

        if (remaining <= 0) {
            timeRemaining.textContent = '00:00';
            resetUI();
            return;
        }

        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        timeRemaining.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }, 1000);
}

async function updateClockIns() {
    if (!currentSession) return;

    try {
        const response = await fetch(`{{ route("teacher.qr.clockins") }}?session_id=${currentSession.session_id}`);
        const data = await response.json();

        // Update stats
        document.getElementById('totalStudents').textContent = data.stats.total_students;
        document.getElementById('clockedIn').textContent = data.stats.clocked_in;
        document.getElementById('lateCount').textContent = data.stats.late;
        document.getElementById('progressPercent').textContent = data.stats.progress + '%';
        document.getElementById('progressBar').style.width = data.stats.progress + '%';

        // Update clock-ins list
        const clockinsList = document.getElementById('clockinsList');
        
        if (data.clockins.length === 0) {
            clockinsList.innerHTML = `
                <div class="tch-empty-state">
                    <i class="bi bi-people"></i>
                    <p style="margin: 0; font-size: 0.875rem;">No clock-ins yet</p>
                </div>
            `;
        } else {
            clockinsList.innerHTML = data.clockins.map(clockin => `
                <div class="tch-clockin-item">
                    <div class="tch-avatar">${clockin.name.substring(0, 2).toUpperCase()}</div>
                    <div class="tch-clockin-info">
                        <div class="tch-clockin-name">${clockin.name}</div>
                        <div class="tch-clockin-number">${clockin.student_number}</div>
                    </div>
                    <div class="tch-clockin-status">
                        <span class="tch-status-badge tch-status-${clockin.status.toLowerCase()}">${clockin.status}</span>
                        <div class="tch-clockin-time">${clockin.time}</div>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Error updating clock-ins:', error);
    }
}

function resetUI() {
    currentSession = null;
    
    // Clear intervals
    if (refreshInterval) clearInterval(refreshInterval);
    if (clockinInterval) clearInterval(clockinInterval);
    if (timerInterval) clearInterval(timerInterval);

    // Reset UI
    startBtn.style.display = 'inline-flex';
    refreshBtn.style.display = 'none';
    stopBtn.style.display = 'none';
    sessionStatus.style.display = 'none';
    sessionTimer.style.display = 'none';
    sidebar.style.display = 'none';

    qrContainer.innerHTML = `
        <div style="color: #94a3b8; text-align: center;">
            <i class="bi bi-qr-code" style="font-size: 3rem; margin-bottom: 12px; opacity: 0.3;"></i>
            <p style="margin: 0; font-size: 0.875rem;">Click "Start Session" to generate QR code</p>
        </div>
    `;

    // Reset stats
    document.getElementById('totalStudents').textContent = '0';
    document.getElementById('clockedIn').textContent = '0';
    document.getElementById('lateCount').textContent = '0';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressBar').style.width = '0%';

    // Reset clock-ins
    document.getElementById('clockinsList').innerHTML = `
        <div class="tch-empty-state">
            <i class="bi bi-people"></i>
            <p style="margin: 0; font-size: 0.875rem;">No clock-ins yet</p>
        </div>
    `;
}
</script>
@endsection