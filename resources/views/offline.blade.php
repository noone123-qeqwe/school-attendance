<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#110A0A">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Offline Mode | {{ config('app.name', 'School Attendance System') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
    <style>
        :root {
            --bg-color: #110A0A;
            --card-bg: rgba(26, 17, 16, 0.95);
            --gold: #CFA46F;
            --gold-light: #F3E7CD;
            --gold-glow: rgba(207, 164, 111, 0.25);
            --text-muted: #B39B82;
            --danger: #EF4444;
            --success: #22C55E;
            --warning: #F59E0B;
            --info: #3B82F6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg-color);
            background-image: 
                radial-gradient(circle at 50% 15%, rgba(207, 164, 111, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 85% 85%, rgba(139, 90, 43, 0.06) 0%, transparent 50%);
            color: var(--gold-light);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow-x: hidden;
        }

        .offline-container {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid rgba(207, 164, 111, 0.25);
            border-radius: 24px;
            padding: 40px 32px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6), 0 0 30px var(--gold-glow);
            position: relative;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            animation: fadeIn 0.6s ease-out;
            transition: max-width 0.3s ease;
        }

        .offline-container.is-teacher-mode {
            max-width: 760px;
            text-align: left;
            padding: 32px 28px;
        }

        .offline-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--gold), transparent);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .icon-wrapper {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .offline-container.is-teacher-mode .icon-wrapper {
            width: 56px;
            height: 56px;
            margin: 0 0 16px 0;
        }

        .icon-circle {
            width: 74px;
            height: 74px;
            border-radius: 50%;
            background: rgba(207, 164, 111, 0.1);
            border: 1px solid rgba(207, 164, 111, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }

        .offline-container.is-teacher-mode .icon-circle {
            width: 52px;
            height: 52px;
        }

        .icon-pulse {
            position: absolute;
            inset: 0;
            border-radius: 50%;
            border: 2px solid var(--gold);
            opacity: 0.4;
            animation: ripple 2.5s cubic-bezier(0.1, 0.8, 0.3, 1) infinite;
        }

        @keyframes ripple {
            0% { transform: scale(0.85); opacity: 0.8; }
            100% { transform: scale(1.45); opacity: 0; }
        }

        .wifi-icon {
            width: 38px;
            height: 38px;
            fill: none;
            stroke: var(--gold);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .offline-container.is-teacher-mode .wifi-icon {
            width: 26px;
            height: 26px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 99px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #F87171;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .status-badge.server-mode {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.35);
            color: #FBBF24;
        }

        .status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #EF4444;
            animation: blink 1.5s infinite ease-in-out;
        }

        .status-badge.server-mode .status-dot {
            background: #F59E0B;
            box-shadow: 0 0 8px #F59E0B;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--gold-light);
            margin-bottom: 8px;
            letter-spacing: -0.02em;
        }

        p.offline-desc {
            font-size: 0.9rem;
            color: var(--text-muted);
            line-height: 1.55;
            margin-bottom: 20px;
        }

        .cached-profile-box {
            display: none;
            background: rgba(17, 10, 10, 0.8);
            border: 1px solid rgba(207, 164, 111, 0.2);
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 20px;
            text-align: left;
        }

        .cached-profile-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gold);
            font-weight: 700;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cached-profile-name {
            font-size: 1rem;
            font-weight: 700;
            color: #FFFFFF;
        }

        .cached-profile-meta {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 4px;
        }

        /* ── Teacher Offline Workspace ── */
        .teacher-workspace {
            display: none;
            margin-top: 10px;
        }

        .oa-nav-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
            border-bottom: 1px solid rgba(207, 164, 111, 0.2);
            padding-bottom: 10px;
        }

        .oa-tab-btn {
            background: transparent;
            border: 1px solid rgba(207, 164, 111, 0.25);
            color: var(--text-muted);
            padding: 8px 16px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }

        .oa-tab-btn:hover {
            color: var(--gold-light);
            border-color: rgba(207, 164, 111, 0.5);
        }

        .oa-tab-btn.active {
            background: linear-gradient(135deg, rgba(207, 164, 111, 0.22), rgba(207, 164, 111, 0.08));
            border-color: var(--gold);
            color: #FFFFFF;
            box-shadow: 0 0 14px var(--gold-glow);
        }

        .oa-badge {
            background: #EF4444;
            color: #FFFFFF;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 2px 7px;
            border-radius: 99px;
        }

        .oa-controls-grid {
            display: grid;
            grid-template-columns: 1fr 180px;
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (max-width: 580px) {
            .oa-controls-grid {
                grid-template-columns: 1fr;
            }
        }

        .oa-label {
            display: block;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--gold);
            margin-bottom: 6px;
        }

        .oa-select, .oa-input {
            width: 100%;
            background: rgba(17, 10, 10, 0.9);
            border: 1px solid rgba(207, 164, 111, 0.35);
            border-radius: 10px;
            color: #FFFFFF;
            padding: 10px 14px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .oa-select:focus, .oa-input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 10px var(--gold-glow);
        }

        .oa-quick-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 14px;
            background: rgba(207, 164, 111, 0.08);
            border: 1px solid rgba(207, 164, 111, 0.2);
            border-radius: 12px;
            margin-bottom: 14px;
        }

        .oa-quick-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gold-light);
        }

        .oa-quick-buttons {
            display: flex;
            gap: 6px;
        }

        .oa-quick-btn {
            background: rgba(17, 10, 10, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--text-muted);
            padding: 4px 10px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .oa-quick-btn.present:hover { background: rgba(34, 197, 94, 0.2); color: #4ade80; border-color: #22c55e; }
        .oa-quick-btn.late:hover { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: #f59e0b; }
        .oa-quick-btn.absent:hover { background: rgba(239, 68, 68, 0.2); color: #f87171; border-color: #ef4444; }

        .oa-search-bar {
            margin-bottom: 12px;
        }

        .oa-search-input {
            width: 100%;
            background: rgba(17, 10, 10, 0.7);
            border: 1px solid rgba(207, 164, 111, 0.25);
            border-radius: 10px;
            color: #FFFFFF;
            padding: 8px 12px;
            font-size: 0.84rem;
        }

        .oa-students-list {
            max-height: 380px;
            overflow-y: auto;
            padding-right: 4px;
            margin-bottom: 16px;
        }

        .oa-students-list::-webkit-scrollbar {
            width: 6px;
        }
        .oa-students-list::-webkit-scrollbar-thumb {
            background: rgba(207, 164, 111, 0.3);
            border-radius: 6px;
        }

        .oa-student-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(17, 10, 10, 0.7);
            border: 1px solid rgba(207, 164, 111, 0.16);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
        }

        .oa-student-row:hover {
            border-color: rgba(207, 164, 111, 0.35);
            background: rgba(207, 164, 111, 0.04);
        }

        .oa-student-info {
            min-width: 0;
            flex: 1;
        }

        .oa-student-name {
            font-size: 0.9rem;
            font-weight: 700;
            color: #FFFFFF;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .oa-student-num {
            font-size: 0.76rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .oa-status-pills {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .oa-pill {
            padding: 5px 9px;
            border-radius: 8px;
            font-size: 0.74rem;
            font-weight: 700;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--text-muted);
            transition: all 0.15s ease;
            user-select: none;
        }

        .oa-pill[data-val="Present"].active {
            background: rgba(34, 197, 94, 0.25);
            border-color: #22C55E;
            color: #4ADE80;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.35);
        }

        .oa-pill[data-val="Late"].active {
            background: rgba(245, 158, 11, 0.25);
            border-color: #F59E0B;
            color: #FBBF24;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.35);
        }

        .oa-pill[data-val="Absent"].active {
            background: rgba(239, 68, 68, 0.25);
            border-color: #EF4444;
            color: #F87171;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.35);
        }

        .oa-pill[data-val="Excused"].active {
            background: rgba(168, 85, 247, 0.25);
            border-color: #A855F7;
            color: #C084FC;
            box-shadow: 0 0 10px rgba(168, 85, 247, 0.35);
        }

        .btn-save-oa {
            width: 100%;
            padding: 13px 20px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, var(--gold) 0%, #8F6E4A 100%);
            color: #110A0A;
            font-size: 0.95rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 18px rgba(207, 164, 111, 0.35);
        }

        .btn-save-oa:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(207, 164, 111, 0.5);
            background: linear-gradient(135deg, #dfb783 0%, #9e7b54 100%);
        }

        .oa-banner-alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.84rem;
            font-weight: 600;
            margin-bottom: 14px;
            display: none;
            align-items: center;
            gap: 8px;
        }

        .oa-banner-alert.success {
            display: flex;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.4);
            color: #4ADE80;
        }

        .oa-banner-alert.warning {
            display: flex;
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #FBBF24;
        }

        /* ── Queue Table / List ── */
        .oa-queue-list {
            max-height: 380px;
            overflow-y: auto;
            margin-bottom: 16px;
        }

        .oa-queue-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: rgba(17, 10, 10, 0.7);
            border: 1px solid rgba(207, 164, 111, 0.18);
            border-radius: 12px;
            padding: 10px 14px;
            margin-bottom: 8px;
        }

        .oa-empty-state {
            padding: 30px 16px;
            text-align: center;
            color: var(--text-muted);
            font-size: 0.88rem;
        }

        .btn-retry {
            width: 100%;
            padding: 13px 20px;
            border-radius: 12px;
            border: none;
            background: rgba(207, 164, 111, 0.15);
            border: 1px solid rgba(207, 164, 111, 0.35);
            color: var(--gold-light);
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.25s ease;
            margin-top: 14px;
        }

        .btn-retry:hover {
            background: rgba(207, 164, 111, 0.25);
            border-color: var(--gold);
        }

        .reconnect-notice {
            display: none;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.35);
            color: #4ADE80;
            padding: 10px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .student-offline-box {
            display: none;
            background: rgba(17, 10, 10, 0.85);
            border: 1px solid rgba(207, 164, 111, 0.25);
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: left;
            line-height: 1.55;
            font-size: 0.86rem;
            color: var(--text-muted);
        }

        .student-offline-box strong {
            color: var(--gold-light);
        }

        .app-footer {
            margin-top: 24px;
            font-size: 0.74rem;
            color: rgba(179, 155, 130, 0.6);
            letter-spacing: 0.5px;
            text-align: center;
        }

        .spin {
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body data-is-offline-page="true">

    <div class="offline-container" id="offlineContainer">
        <!-- Reconnect alert -->
        <div class="reconnect-notice" id="reconnectNotice">
            <span>⚡ Connection restored! Reloading page...</span>
        </div>

        <div class="status-badge" id="statusBadge">
            <span class="status-dot"></span> <span id="statusBadgeText">No Connection</span>
        </div>

        <div class="icon-wrapper">
            <div class="icon-pulse"></div>
            <div class="icon-circle">
                <svg class="wifi-icon" viewBox="0 0 24 24">
                    <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01" />
                </svg>
            </div>
        </div>

        <h1 id="offlineHeading">You're Offline</h1>
        <p class="offline-desc" id="offlineMsg">
            We can't connect to the School Attendance server right now.
        </p>

        <!-- Cached Profile Details -->
        <div class="cached-profile-box" id="cachedProfileBox">
            <div class="cached-profile-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span id="cachedProfileLabel">Cached Profile</span>
            </div>
            <div class="cached-profile-name" id="cachedName">User</div>
            <div class="cached-profile-meta" id="cachedMeta">ID: --</div>
        </div>

        <!-- Student Offline Advisory Box -->
        <div class="student-offline-box" id="studentOfflineBox">
            <div style="font-weight: 700; color: var(--gold); margin-bottom: 6px;">📱 Student Offline Notice</div>
            QR Code scanning and attendance check-ins require an active internet connection to securely sign QR tokens and verify your physical classroom presence. When your connection returns, this screen will automatically reconnect.
        </div>

        <!-- ── Teacher Offline Attendance Workspace ── -->
        <div class="teacher-workspace" id="teacherWorkspace">
            <!-- Tabs Navigation -->
            <div class="oa-nav-tabs">
                <button type="button" class="oa-tab-btn active" id="tabMarkBtn" onclick="switchOaTab('mark')">
                    <span>📝 Mark Attendance</span>
                </button>
                <button type="button" class="oa-tab-btn" id="tabQueueBtn" onclick="switchOaTab('queue')">
                    <span>⏳ Pending Queue</span>
                    <span class="oa-badge" id="oaQueueBadge" style="display:none;">0</span>
                </button>
            </div>

            <!-- Tab 1: Mark Attendance Form -->
            <div id="viewMarkTab">
                <div id="oaSaveBanner" class="oa-banner-alert success"></div>

                <div class="oa-controls-grid">
                    <div>
                        <label class="oa-label" for="oaSubjectSelect">Select Class / Subject</label>
                        <select id="oaSubjectSelect" class="oa-select" onchange="handleSubjectChange()">
                            <option value="">-- Choose Subject --</option>
                        </select>
                    </div>
                    <div>
                        <label class="oa-label" for="oaDateInput">Attendance Date</label>
                        <input type="date" id="oaDateInput" class="oa-input">
                    </div>
                </div>

                <div id="oaRosterContainer" style="display:none;">
                    <div class="oa-quick-actions">
                        <div class="oa-quick-title" id="oaRosterHeaderTitle">Enrolled Students (0)</div>
                        <div class="oa-quick-buttons">
                            <button type="button" class="oa-quick-btn present" onclick="setBulkStatus('Present')">✓ All Present</button>
                            <button type="button" class="oa-quick-btn late" onclick="setBulkStatus('Late')">⏱ All Late</button>
                            <button type="button" class="oa-quick-btn absent" onclick="setBulkStatus('Absent')">✗ All Absent</button>
                        </div>
                    </div>

                    <div class="oa-search-bar">
                        <input type="text" id="oaStudentSearch" class="oa-search-input" placeholder="Search student name or number..." oninput="filterStudents()">
                    </div>

                    <div class="oa-students-list" id="oaStudentsList">
                        <!-- Populated by JavaScript -->
                    </div>

                    <button type="button" class="btn-save-oa" id="btnSaveAttendance" onclick="saveOfflineAttendance()">
                        <span>💾 Save Attendance Locally</span>
                    </button>
                </div>

                <div id="oaNoRosterAlert" class="oa-banner-alert warning" style="display:none;">
                    <span>⚠️ No class roster cached on this device yet. When you are next online, open your subject once to cache student lists for offline use.</span>
                </div>
            </div>

            <!-- Tab 2: Pending Sync Queue -->
            <div id="viewQueueTab" style="display:none;">
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:12px;">
                    <div style="font-size:0.85rem; font-weight:700; color:var(--gold-light);" id="queueSummaryText">
                        0 records awaiting sync
                    </div>
                    <button type="button" class="oa-quick-btn" id="btnQueueSyncNow" onclick="handleQueueSync()" style="display:flex; align-items:center; gap:6px;">
                        <span id="queueSyncIcon">🔄</span> <span>Sync Now</span>
                    </button>
                </div>

                <div class="oa-queue-list" id="oaQueueList">
                    <!-- Populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- ISP / Network Route Notice Box -->
        <div class="isp-tip-box" id="ispTipBox" style="display: none; background: rgba(234, 179, 8, 0.12); border: 1px solid rgba(234, 179, 8, 0.35); border-radius: 12px; padding: 14px; margin-bottom: 16px; font-size: 0.84rem; color: #FDE047; text-align: left; line-height: 1.55;">
            <div style="font-weight: 700; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                <span>🌐 Internet Active &bull; Host Connection Blocked</span>
            </div>
            <div>Your device has active internet, but your network provider cannot route to the school attendance server.</div>
        </div>

        <button class="btn-retry" id="btnRetry" onclick="handleRetry()">
            <svg id="retryIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
            </svg>
            <span id="retryText">Retry Connection</span>
        </button>

        <div class="app-footer">
            {{ config('app.name', 'School Attendance System') }} &bull; PWA Offline Mode
        </div>
    </div>

    <!-- Offline Attendance Engine -->
    <script src="/js/offline-attendance.js"></script>

    <script>
        // ── State Variables ──
        let currentUser = null;
        let cachedSubjects = [];
        let currentSubjectStudents = [];
        let studentStatusMap = {};
        let currentState = 'no-internet';
        let isReconnecting = false;
        let probeInFlight = false;

        // ── Initialize Date Picker to Today ──
        const dateInput = document.getElementById('oaDateInput');
        if (dateInput) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            dateInput.value = `${yyyy}-${mm}-${dd}`;
        }

        // ── Load Cached Profile & Initialize Mode ──
        try {
            const raw = localStorage.getItem('cached_user_profile') || localStorage.getItem('cached_student_profile');
            if (raw) {
                currentUser = JSON.parse(raw);
                if (currentUser && currentUser.name) {
                    const role = (currentUser.role || 'User').toLowerCase();
                    const roleName = role.charAt(0).toUpperCase() + role.slice(1);
                    const labelEl = document.getElementById('cachedProfileLabel');
                    if (labelEl) labelEl.textContent = `Cached ${roleName} Profile`;

                    document.getElementById('cachedName').textContent = currentUser.name;
                    let metaText = (currentUser.role ? currentUser.role.toUpperCase() : 'USER');
                    if (currentUser.student_number) metaText += ` &bull; ID: ${currentUser.student_number}`;
                    if (currentUser.course) metaText += ` &bull; ${currentUser.course}`;
                    if (currentUser.email && !currentUser.student_number) metaText += ` &bull; ${currentUser.email}`;
                    document.getElementById('cachedMeta').innerHTML = metaText;
                    document.getElementById('cachedProfileBox').style.display = 'block';

                    if (role === 'teacher') {
                        setupTeacherMode();
                    } else if (role === 'student') {
                        document.getElementById('studentOfflineBox').style.display = 'block';
                    }
                }
            }
        } catch (e) {
            console.warn('[OfflineView] Profile parse error:', e);
        }

        // Check if there are cached subjects regardless of cached profile
        if (!currentUser || currentUser.role !== 'teacher') {
            if (window.OfflineAttendance) {
                OfflineAttendance.init();
                OfflineAttendance.getCachedRoster().then(function(roster) {
                    if (roster && roster.length > 0) {
                        setupTeacherMode();
                    }
                });
            }
        }

        function setupTeacherMode() {
            const container = document.getElementById('offlineContainer');
            const workspace = document.getElementById('teacherWorkspace');
            const heading = document.getElementById('offlineHeading');
            const desc = document.getElementById('offlineMsg');

            if (container) container.classList.add('is-teacher-mode');
            if (workspace) workspace.style.display = 'block';
            if (heading) heading.textContent = 'Offline Attendance';
            if (desc) desc.textContent = 'You are currently offline. You can record classroom attendance locally; all records will automatically synchronize when connection is restored.';

            if (window.OfflineAttendance) {
                OfflineAttendance.init({
                    teacherId: currentUser ? currentUser.id : null,
                    csrfToken: localStorage.getItem('cached_csrf_token') || '',
                });

                loadCachedSubjects();
                refreshQueueCount();
            }
        }

        function loadCachedSubjects() {
            if (!window.OfflineAttendance) return;
            OfflineAttendance.getCachedRoster(currentUser ? currentUser.id : null).then(function(subjects) {
                cachedSubjects = subjects || [];
                const select = document.getElementById('oaSubjectSelect');
                const noRosterAlert = document.getElementById('oaNoRosterAlert');

                if (!select) return;
                select.innerHTML = '<option value="">-- Choose Subject --</option>';

                if (cachedSubjects.length === 0) {
                    if (noRosterAlert) noRosterAlert.style.display = 'flex';
                    return;
                }

                if (noRosterAlert) noRosterAlert.style.display = 'none';
                cachedSubjects.forEach(function(s) {
                    const opt = document.createElement('option');
                    opt.value = s.code;
                    opt.textContent = `${s.code} - ${s.name} ${s.section ? '(' + s.section + ')' : ''}`;
                    select.appendChild(opt);
                });

                // Preselect first subject if only 1
                if (cachedSubjects.length === 1) {
                    select.value = cachedSubjects[0].code;
                    handleSubjectChange();
                }
            });
        }

        function handleSubjectChange() {
            const select = document.getElementById('oaSubjectSelect');
            const rosterContainer = document.getElementById('oaRosterContainer');
            const headerTitle = document.getElementById('oaRosterHeaderTitle');
            const list = document.getElementById('oaStudentsList');

            if (!select || !select.value) {
                if (rosterContainer) rosterContainer.style.display = 'none';
                return;
            }

            const code = select.value.toUpperCase();
            const subject = cachedSubjects.find(s => String(s.code).toUpperCase() === code);

            if (!subject || !subject.students || subject.students.length === 0) {
                if (headerTitle) headerTitle.textContent = `${code} (0 students)`;
                if (list) list.innerHTML = '<div class="oa-empty-state">No students found in cached roster.</div>';
                if (rosterContainer) rosterContainer.style.display = 'block';
                currentSubjectStudents = [];
                studentStatusMap = {};
                return;
            }

            currentSubjectStudents = subject.students;
            studentStatusMap = {};
            currentSubjectStudents.forEach(st => {
                studentStatusMap[st.id] = 'Present';
            });

            if (headerTitle) headerTitle.textContent = `${subject.code} (${currentSubjectStudents.length} students)`;
            renderStudentList(currentSubjectStudents);
            if (rosterContainer) rosterContainer.style.display = 'block';
        }

        function renderStudentList(students) {
            const list = document.getElementById('oaStudentsList');
            if (!list) return;

            if (students.length === 0) {
                list.innerHTML = '<div class="oa-empty-state">No matching students found.</div>';
                return;
            }

            let html = '';
            students.forEach(st => {
                const currentStatus = studentStatusMap[st.id] || 'Present';
                html += `
                    <div class="oa-student-row" data-student-id="${st.id}">
                        <div class="oa-student-info">
                            <div class="oa-student-name">${escapeHtml(st.name || 'Student')}</div>
                            <div class="oa-student-num">ID: ${escapeHtml(st.student_number || '--')}</div>
                        </div>
                        <div class="oa-status-pills">
                            <span class="oa-pill ${currentStatus === 'Present' ? 'active' : ''}" data-val="Present" onclick="setStudentStatus(${st.id}, 'Present')">Present</span>
                            <span class="oa-pill ${currentStatus === 'Late' ? 'active' : ''}" data-val="Late" onclick="setStudentStatus(${st.id}, 'Late')">Late</span>
                            <span class="oa-pill ${currentStatus === 'Absent' ? 'active' : ''}" data-val="Absent" onclick="setStudentStatus(${st.id}, 'Absent')">Absent</span>
                            <span class="oa-pill ${currentStatus === 'Excused' ? 'active' : ''}" data-val="Excused" onclick="setStudentStatus(${st.id}, 'Excused')">Excused</span>
                        </div>
                    </div>
                `;
            });
            list.innerHTML = html;
        }

        function setStudentStatus(studentId, status) {
            studentStatusMap[studentId] = status;
            const row = document.querySelector(`.oa-student-row[data-student-id="${studentId}"]`);
            if (row) {
                row.querySelectorAll('.oa-pill').forEach(pill => {
                    pill.classList.toggle('active', pill.dataset.val === status);
                });
            }
        }

        function setBulkStatus(status) {
            currentSubjectStudents.forEach(st => {
                studentStatusMap[st.id] = status;
            });
            renderStudentList(currentSubjectStudents);
        }

        function filterStudents() {
            const query = (document.getElementById('oaStudentSearch')?.value || '').toLowerCase().trim();
            if (!query) {
                renderStudentList(currentSubjectStudents);
                return;
            }
            const filtered = currentSubjectStudents.filter(st => {
                const nameMatch = (st.name || '').toLowerCase().includes(query);
                const numMatch = (st.student_number || '').toLowerCase().includes(query);
                return nameMatch || numMatch;
            });
            renderStudentList(filtered);
        }

        function saveOfflineAttendance() {
            const select = document.getElementById('oaSubjectSelect');
            const date = document.getElementById('oaDateInput')?.value;
            const banner = document.getElementById('oaSaveBanner');

            if (!select || !select.value) {
                alert('Please select a subject.');
                return;
            }
            if (!date) {
                alert('Please select an attendance date.');
                return;
            }
            if (currentSubjectStudents.length === 0) {
                alert('No students to save.');
                return;
            }

            const code = select.value.toUpperCase();
            const subject = cachedSubjects.find(s => String(s.code).toUpperCase() === code);
            const subjectName = subject ? subject.name : '';
            const nowTime = new Date().toTimeString().substring(0, 8);

            const recordsToSave = currentSubjectStudents.map(st => ({
                subject_code: code,
                subject_name: subjectName,
                user_id: parseInt(st.id),
                student_name: st.name || '',
                student_number: st.student_number || '',
                status: studentStatusMap[st.id] || 'Present',
                date: date,
                time: nowTime,
            }));

            if (window.OfflineAttendance) {
                OfflineAttendance.saveBatch(recordsToSave).then(function(saved) {
                    if (banner) {
                        banner.className = 'oa-banner-alert success';
                        banner.innerHTML = `<span>✓ Saved ${saved.length} attendance records offline for ${code} on ${date}. Will sync when online.</span>`;
                        banner.style.display = 'flex';
                        setTimeout(() => { banner.style.display = 'none'; }, 7000);
                    }
                    refreshQueueCount();
                }).catch(function(err) {
                    console.error('[OfflineView] Save failed:', err);
                    alert('Failed to save records: ' + (err.message || err));
                });
            }
        }

        function switchOaTab(tabName) {
            const tabMarkBtn = document.getElementById('tabMarkBtn');
            const tabQueueBtn = document.getElementById('tabQueueBtn');
            const viewMarkTab = document.getElementById('viewMarkTab');
            const viewQueueTab = document.getElementById('viewQueueTab');

            if (tabName === 'mark') {
                tabMarkBtn.classList.add('active');
                tabQueueBtn.classList.remove('active');
                viewMarkTab.style.display = 'block';
                viewQueueTab.style.display = 'none';
            } else {
                tabMarkBtn.classList.remove('active');
                tabQueueBtn.classList.add('active');
                viewMarkTab.style.display = 'none';
                viewQueueTab.style.display = 'block';
                renderQueueList();
            }
        }

        function refreshQueueCount() {
            if (!window.OfflineAttendance) return;
            OfflineAttendance.getPendingCount().then(function(count) {
                const badge = document.getElementById('oaQueueBadge');
                if (badge) {
                    badge.textContent = count;
                    badge.style.display = count > 0 ? 'inline-block' : 'none';
                }
            });
        }

        function renderQueueList() {
            const container = document.getElementById('oaQueueList');
            const summaryText = document.getElementById('queueSummaryText');
            if (!container || !window.OfflineAttendance) return;

            Promise.all([
                OfflineAttendance.getPendingRecords(),
                OfflineAttendance.getFailedRecords()
            ]).then(function(results) {
                const pending = results[0] || [];
                const failed = results[1] || [];
                const all = pending.concat(failed);

                if (summaryText) {
                    summaryText.textContent = `${all.length} record${all.length !== 1 ? 's' : ''} awaiting sync`;
                }

                if (all.length === 0) {
                    container.innerHTML = '<div class="oa-empty-state">✓ All records have been synchronized with the server.</div>';
                    return;
                }

                let html = '';
                all.forEach(rec => {
                    const isFailed = rec.sync_status === 'failed';
                    html += `
                        <div class="oa-queue-row">
                            <div>
                                <div style="font-weight:700; color:#fff; font-size:0.88rem;">
                                    ${escapeHtml(rec.student_name || 'Student #' + rec.user_id)}
                                </div>
                                <div style="font-size:0.75rem; color:var(--text-muted); margin-top:2px;">
                                    ${escapeHtml(rec.subject_code)} &bull; Date: ${escapeHtml(rec.date)} ${rec.time ? '&bull; ' + rec.time : ''}
                                </div>
                            </div>
                            <div style="text-align:right;">
                                <span class="oa-pill active" data-val="${rec.status}">${rec.status}</span>
                                <div style="font-size:0.7rem; color:${isFailed ? '#f87171' : 'var(--gold)'}; margin-top:4px;">
                                    ${isFailed ? '⚠️ Retry needed' : '⏳ Pending'}
                                </div>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            });
        }

        function handleQueueSync() {
            const icon = document.getElementById('queueSyncIcon');
            if (icon) icon.classList.add('spin');

            if (window.OfflineAttendance) {
                OfflineAttendance.syncNow();
                setTimeout(() => {
                    if (icon) icon.classList.remove('spin');
                    renderQueueList();
                    refreshQueueCount();
                }, 1500);
            }
        }

        window.addEventListener('offline-attendance-synced', function(e) {
            refreshQueueCount();
            renderQueueList();
        });

        function escapeHtml(str) {
            return String(str || '').replace(/[&<>"']/g, function(m) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
            });
        }

        // ── Connection Probing & Reconnect Handlers ──
        function updateUI(state) {
            currentState = state;
            const badge = document.getElementById('statusBadge');
            const badgeText = document.getElementById('statusBadgeText');
            const heading = document.getElementById('offlineHeading');
            const msg = document.getElementById('offlineMsg');
            const retryText = document.getElementById('retryText');
            const ispTip = document.getElementById('ispTipBox');

            if (state === 'server-unreachable') {
                if (badge) badge.classList.add('server-mode');
                if (badgeText) badgeText.textContent = 'Server Unreachable';
                if (heading && !document.getElementById('offlineContainer').classList.contains('is-teacher-mode')) {
                    heading.textContent = 'Server Unreachable';
                }
                if (msg && !document.getElementById('offlineContainer').classList.contains('is-teacher-mode')) {
                    msg.innerHTML = 'Your internet is active, but your network provider cannot route to the school server.';
                }
                if (ispTip) ispTip.style.display = 'block';
                if (retryText && !retryText.textContent.includes('...')) retryText.textContent = 'Retry Connecting to Server';
            } else {
                if (badge) badge.classList.remove('server-mode');
                if (badgeText) badgeText.textContent = 'No Connection';
                if (heading && !document.getElementById('offlineContainer').classList.contains('is-teacher-mode')) {
                    heading.textContent = "You're Offline";
                }
                if (msg && !document.getElementById('offlineContainer').classList.contains('is-teacher-mode')) {
                    msg.textContent = "We can't connect to the School Attendance server right now. Please check your Wi-Fi or mobile data.";
                }
                if (ispTip) ispTip.style.display = 'none';
                if (retryText && !retryText.textContent.includes('...')) retryText.textContent = 'Retry Connection';
            }
        }

        async function probeExternalInternet() {
            if (!navigator.onLine) return false;
            try {
                const c1 = new AbortController();
                const t1 = setTimeout(() => c1.abort(), 3500);
                await fetch('https://www.google.com/generate_204', { method: 'HEAD', mode: 'no-cors', cache: 'no-store', signal: c1.signal });
                clearTimeout(t1);
                return true;
            } catch (e) {}

            try {
                const c2 = new AbortController();
                const t2 = setTimeout(() => c2.abort(), 3500);
                await fetch('https://1.1.1.1/cdn-cgi/trace', { method: 'HEAD', mode: 'no-cors', cache: 'no-store', signal: c2.signal });
                clearTimeout(t2);
                return true;
            } catch (e) {}

            return false;
        }

        async function probeConnectivity() {
            const probeUrls = [
                '/api/ping?_t=' + Date.now(),
                '/up?_t=' + Date.now(),
                '/manifest.json?_t=' + Date.now()
            ];

            for (const url of probeUrls) {
                try {
                    const controller = new AbortController();
                    const timer = setTimeout(() => controller.abort(), 6000);
                    const res = await fetch(url, {
                        method: 'GET',
                        cache: 'no-store',
                        signal: controller.signal,
                        headers: { 'Accept': 'application/json, */*' }
                    });
                    clearTimeout(timer);
                    if (res.ok || res.status < 500) {
                        return 'connected';
                    }
                    return 'server-unreachable';
                } catch (err) {
                    continue;
                }
            }

            const hasInternet = await probeExternalInternet();
            return hasInternet ? 'server-unreachable' : 'no-internet';
        }

        function handleConnected() {
            if (isReconnecting) return;
            isReconnecting = true;
            const notice = document.getElementById('reconnectNotice');
            const retryText = document.getElementById('retryText');
            if (notice) notice.style.display = 'flex';
            if (retryText) retryText.textContent = 'Connected! Reloading...';

            // Trigger sync if teacher has pending records before reloading
            if (window.OfflineAttendance) {
                OfflineAttendance.syncNow();
            }

            setTimeout(() => {
                const intended = sessionStorage.getItem('pwa_intended_url');
                if (intended) {
                    sessionStorage.removeItem('pwa_intended_url');
                    window.location.href = intended;
                } else {
                    window.location.reload();
                }
            }, 1000);
        }

        async function handleRetry() {
            if (isReconnecting || probeInFlight) return;
            probeInFlight = true;
            const btn = document.getElementById('btnRetry');
            const icon = document.getElementById('retryIcon');
            const text = document.getElementById('retryText');

            if (icon) icon.classList.add('spin');
            if (text) text.textContent = 'Checking connection...';
            if (btn) btn.disabled = true;

            try {
                const state = await probeConnectivity();
                if (state === 'connected') {
                    handleConnected();
                    return;
                }
                updateUI(state);
                setTimeout(() => {
                    if (icon) icon.classList.remove('spin');
                    if (btn) btn.disabled = false;
                }, 400);
            } catch (err) {
                updateUI('no-internet');
                if (icon) icon.classList.remove('spin');
                if (btn) btn.disabled = false;
            } finally {
                probeInFlight = false;
            }
        }

        // Automatic background ping every 4 seconds
        let autoPingInterval = null;
        function startAutoProbe() {
            if (autoPingInterval) clearInterval(autoPingInterval);
            autoPingInterval = setInterval(async () => {
                if (isReconnecting || probeInFlight) return;
                probeInFlight = true;
                try {
                    const state = await probeConnectivity();
                    if (state === 'connected') {
                        handleConnected();
                    } else if (state !== currentState) {
                        updateUI(state);
                    }
                } catch (e) {} finally {
                    probeInFlight = false;
                }
            }, 4000);
        }

        window.addEventListener('online', () => {
            updateUI('server-unreachable');
            handleRetry();
        });
        window.addEventListener('offline', () => {
            updateUI('no-internet');
        });

        (async () => {
            const state = await probeConnectivity();
            if (state === 'connected') {
                handleConnected();
            } else {
                updateUI(state);
            }
            startAutoProbe();
        })();
    </script>
</body>
</html>
