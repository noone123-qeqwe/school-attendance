<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#110A0A">
    <title>Offline Mode | {{ config('app.name', 'School Attendance System') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icons/icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/favicon-32x32.png">
    <style>
        :root{
            --bg:#110A0A;--card:rgba(26,17,16,.97);--gold:#CFA46F;--gold-lt:#F3E7CD;
            --gold-glow:rgba(207,164,111,.22);--muted:#B39B82;--danger:#EF4444;
            --success:#22C55E;--warn:#F59E0B;
        }
        *{box-sizing:border-box;margin:0;padding:0}
        body{
            font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
            background:var(--bg);
            background-image:radial-gradient(circle at 50% 15%,rgba(207,164,111,.08) 0%,transparent 60%),
                             radial-gradient(circle at 85% 85%,rgba(139,90,43,.06) 0%,transparent 50%);
            color:var(--gold-lt);min-height:100vh;
            display:flex;flex-direction:column;align-items:center;
            padding:20px 16px 60px;overflow-x:hidden;
        }

        /* ── status bar ─────────────────────── */
        .top-bar{width:100%;max-width:560px;display:flex;align-items:center;
            justify-content:space-between;margin-bottom:18px;gap:8px;flex-wrap:wrap;}
        .status-badge{
            display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:99px;
            background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.3);
            color:#F87171;font-size:.77rem;font-weight:700;letter-spacing:.4px;
            text-transform:uppercase;transition:all .3s;
        }
        .status-badge.server{background:rgba(245,158,11,.15);border-color:rgba(245,158,11,.35);color:#FBBF24;}
        .status-badge.online {background:rgba(34,197,94,.15);border-color:rgba(34,197,94,.35);color:#4ADE80;}
        .status-dot{width:6px;height:6px;border-radius:50%;background:#EF4444;animation:blink 1.5s infinite ease-in-out;}
        .status-badge.server .status-dot{background:var(--warn);box-shadow:0 0 7px var(--warn);}
        .status-badge.online  .status-dot{background:var(--success);box-shadow:0 0 7px var(--success);animation:none;}
        @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}
        .pending-info{font-size:.76rem;color:var(--muted);display:none;align-items:center;gap:5px;}

        /* ── main card ──────────────────────── */
        .card{
            width:100%;max-width:560px;background:var(--card);
            border:1px solid rgba(207,164,111,.25);border-radius:24px;padding:30px 26px;
            box-shadow:0 20px 50px rgba(0,0,0,.55),0 0 28px var(--gold-glow);
            position:relative;animation:fadeIn .6s ease-out;
        }
        .card::before{content:'';position:absolute;top:0;left:20%;right:20%;height:2px;
            background:linear-gradient(90deg,transparent,var(--gold),transparent);}
        @keyframes fadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}

        .reconnect-notice{
            display:none;background:rgba(34,197,94,.14);border:1px solid rgba(34,197,94,.35);
            color:#4ADE80;padding:10px 14px;border-radius:10px;font-size:.84rem;font-weight:600;
            margin-bottom:16px;align-items:center;justify-content:center;gap:8px;
        }
        .isp-tip{
            display:none;background:rgba(234,179,8,.12);border:1px solid rgba(234,179,8,.35);
            border-radius:12px;padding:14px;margin-bottom:18px;
            font-size:.82rem;color:#FDE047;text-align:left;line-height:1.55;
        }

        /* icon + heading */
        .icon-wrap{display:flex;justify-content:center;margin-bottom:16px;}
        .icon-circle{width:76px;height:76px;border-radius:50%;
            background:rgba(207,164,111,.1);border:1px solid rgba(207,164,111,.3);
            display:flex;align-items:center;justify-content:center;position:relative;}
        .icon-pulse{position:absolute;inset:0;border-radius:50%;border:2px solid var(--gold);
            opacity:.4;animation:ripple 2.5s cubic-bezier(.1,.8,.3,1) infinite;}
        @keyframes ripple{0%{transform:scale(.85);opacity:.8}100%{transform:scale(1.45);opacity:0}}
        .wifi-icon{width:36px;height:36px;fill:none;stroke:var(--gold);stroke-width:2;
            stroke-linecap:round;stroke-linejoin:round;}
        h1{font-size:1.42rem;font-weight:800;color:var(--gold-lt);margin-bottom:8px;
            letter-spacing:-.02em;text-align:center;}
        .subtitle{font-size:.88rem;color:var(--muted);line-height:1.6;margin-bottom:20px;text-align:center;}

        /* retry btn */
        .btn-retry{
            width:100%;padding:13px 20px;border-radius:12px;border:none;
            background:linear-gradient(135deg,var(--gold) 0%,#8F6E4A 100%);color:#110A0A;
            font-size:.92rem;font-weight:700;cursor:pointer;
            display:inline-flex;align-items:center;justify-content:center;gap:8px;
            transition:all .25s;box-shadow:0 4px 18px rgba(207,164,111,.35);
        }
        .btn-retry:hover{transform:translateY(-2px);box-shadow:0 6px 24px rgba(207,164,111,.5);}
        .btn-retry:active{transform:translateY(0);}

        /* profile box */
        .profile-box{background:rgba(17,10,10,.8);border:1px solid rgba(207,164,111,.2);
            border-radius:12px;padding:14px;margin-bottom:18px;display:none;}
        .profile-label{font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;
            color:var(--gold);font-weight:700;margin-bottom:5px;display:flex;align-items:center;gap:5px;}
        .profile-name{font-size:.96rem;font-weight:700;color:#fff;}
        .profile-meta{font-size:.79rem;color:var(--muted);margin-top:3px;}

        /* separator */
        .sep{display:flex;align-items:center;gap:10px;margin:22px 0 16px;color:var(--muted);font-size:.79rem;}
        .sep::before,.sep::after{content:'';flex:1;border-top:1px solid rgba(207,164,111,.18);}

        /* ── OFFLINE ATTENDANCE ──────────────── */
        #oaSection{display:none;}

        .pending-strip{
            display:none;padding:10px 14px;margin-bottom:14px;
            background:rgba(59,130,246,.1);border:1px solid rgba(59,130,246,.3);
            border-radius:10px;font-size:.81rem;color:#93C5FD;font-weight:600;
            align-items:center;gap:8px;
        }

        .oa-card{background:rgba(17,10,10,.7);border:1px solid rgba(207,164,111,.2);border-radius:16px;overflow:hidden;}
        .oa-card-hdr{padding:13px 17px;display:flex;align-items:center;justify-content:space-between;
            border-bottom:1px solid rgba(207,164,111,.14);background:rgba(207,164,111,.06);}
        .oa-title{font-size:.86rem;font-weight:700;color:var(--gold);display:flex;align-items:center;gap:7px;}
        .oa-offline-badge{font-size:.69rem;font-weight:700;background:rgba(245,158,11,.18);
            color:#FBBF24;border:1px solid rgba(245,158,11,.35);padding:2px 9px;border-radius:99px;}

        /* select */
        .oa-row{padding:13px 17px;border-bottom:1px solid rgba(207,164,111,.08);}
        .oa-lbl{font-size:.73rem;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);font-weight:700;margin-bottom:6px;}
        .oa-select{
            width:100%;background:rgba(15,10,8,.85);border:1px solid rgba(207,164,111,.25);
            border-radius:10px;padding:10px 36px 10px 13px;color:var(--gold-lt);font-size:.88rem;
            outline:none;cursor:pointer;transition:border .2s;appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23CFA46F' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat:no-repeat;background-position:right 13px center;
        }
        .oa-select:focus{border-color:var(--gold);}
        .oa-select option{background:#1c1008;}

        /* date row */
        .oa-date-row{padding:12px 17px;border-bottom:1px solid rgba(207,164,111,.08);
            display:flex;align-items:flex-end;gap:10px;}
        .oa-date-wrap{flex:1;}
        .oa-date-input{
            width:100%;background:rgba(15,10,8,.85);border:1px solid rgba(207,164,111,.25);
            border-radius:10px;padding:9px 12px;color:var(--gold-lt);font-size:.87rem;
            outline:none;transition:border .2s;
        }
        .oa-date-input:focus{border-color:var(--gold);}
        input[type='date']::-webkit-calendar-picker-indicator{filter:invert(.7) sepia(1) saturate(2) hue-rotate(10deg);}
        .oa-all-btn{
            padding:9px 13px;border-radius:10px;border:1px solid rgba(34,197,94,.35);
            background:rgba(34,197,94,.1);color:#4ADE80;font-size:.76rem;font-weight:700;
            cursor:pointer;white-space:nowrap;transition:all .2s;display:none;
        }
        .oa-all-btn:hover{background:rgba(34,197,94,.2);}

        /* student list */
        .oa-list{max-height:320px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:rgba(207,164,111,.3) transparent;}
        .oa-list::-webkit-scrollbar{width:4px;}
        .oa-list::-webkit-scrollbar-thumb{background:rgba(207,164,111,.3);border-radius:4px;}

        .oa-student{
            display:flex;align-items:center;gap:8px;padding:10px 17px;
            border-bottom:1px solid rgba(255,255,255,.03);transition:background .15s;
        }
        .oa-student:last-child{border-bottom:none;}
        .oa-student:hover{background:rgba(207,164,111,.04);}
        .oa-snum{font-size:.73rem;color:var(--muted);min-width:76px;font-family:monospace;}
        .oa-sname{flex:1;font-size:.86rem;color:var(--gold-lt);font-weight:500;}
        .oa-rgroup{display:flex;gap:4px;flex-shrink:0;}
        .oa-rbtn{
            padding:4px 8px;border-radius:7px;border:1px solid rgba(207,164,111,.18);
            font-size:.7rem;font-weight:700;cursor:pointer;
            background:transparent;color:var(--muted);transition:all .15s;white-space:nowrap;
        }
        .oa-rbtn.p{border-color:rgba(34,197,94,.3);color:rgba(34,197,94,.65);}
        .oa-rbtn.p.on{background:rgba(34,197,94,.2);color:#4ADE80;border-color:rgba(34,197,94,.6);}
        .oa-rbtn.l{border-color:rgba(245,158,11,.3);color:rgba(245,158,11,.65);}
        .oa-rbtn.l.on{background:rgba(245,158,11,.2);color:#FCD34D;border-color:rgba(245,158,11,.6);}
        .oa-rbtn.a{border-color:rgba(239,68,68,.3);color:rgba(239,68,68,.65);}
        .oa-rbtn.a.on{background:rgba(239,68,68,.2);color:#F87171;border-color:rgba(239,68,68,.6);}

        /* footer */
        .oa-footer{padding:13px 17px;display:flex;gap:10px;
            border-top:1px solid rgba(207,164,111,.14);background:rgba(207,164,111,.04);}
        .oa-save-btn{
            flex:1;padding:11px;border-radius:10px;border:none;
            background:linear-gradient(135deg,var(--gold) 0%,#8F6E4A 100%);
            color:#110A0A;font-size:.88rem;font-weight:700;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:6px;
            transition:all .25s;box-shadow:0 3px 14px rgba(207,164,111,.3);
        }
        .oa-save-btn:hover{transform:translateY(-1px);box-shadow:0 5px 20px rgba(207,164,111,.45);}
        .oa-save-btn:disabled{opacity:.5;cursor:default;transform:none;}

        /* empty / hint states */
        .oa-empty{padding:30px 16px;text-align:center;color:var(--muted);font-size:.84rem;line-height:1.6;}
        .oa-empty svg{display:block;margin:0 auto 12px;opacity:.4;}

        /* toast */
        .oa-toast{
            position:fixed;top:18px;left:50%;transform:translateX(-50%) translateY(-70px);
            padding:11px 22px;border-radius:14px;font-size:.83rem;font-weight:700;
            z-index:99999;display:flex;align-items:center;gap:9px;
            border:1px solid rgba(255,255,255,.15);box-shadow:0 10px 30px rgba(0,0,0,.5);
            transition:transform .4s cubic-bezier(.16,1,.3,1),opacity .3s;
            opacity:0;pointer-events:none;white-space:nowrap;
        }
        .oa-toast.show{transform:translateX(-50%) translateY(0);opacity:1;}
        .oa-toast.success{background:rgba(22,163,74,.94);color:#fff;}
        .oa-toast.error  {background:rgba(185,28,28,.94);color:#fff;}
        .oa-toast.info   {background:rgba(29,78,216,.94);color:#fff;}

        .app-footer{margin-top:20px;font-size:.72rem;color:rgba(179,155,130,.5);
            letter-spacing:.4px;text-align:center;}
        .spin{animation:spin .8s linear infinite;}
        @keyframes spin{100%{transform:rotate(360deg)}}
    </style>
</head>
<body>

<div class="top-bar">
    <div class="status-badge" id="statusBadge">
        <span class="status-dot"></span>
        <span id="statusBadgeText">No Connection</span>
    </div>
    <div class="pending-info" id="pendingInfo">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        <span id="pendingCount">0</span> record(s) pending sync
    </div>
</div>

<div class="card">
    <!-- reconnect notice -->
    <div class="reconnect-notice" id="reconnectNotice">
        <span>⚡ Connection restored! Reloading...</span>
    </div>

    <!-- ISP tip -->
    <div class="isp-tip" id="ispTipBox">
        <div style="font-weight:700;margin-bottom:5px;display:flex;align-items:center;gap:6px;">
            🌐 Internet Active &bull; Host Connection Blocked
        </div>
        <div>Your device has active internet, but your network provider cannot route to the school server.</div>
        <div style="margin-top:8px;font-size:.79rem;color:rgba(254,240,138,.85);">
            &bull; Try <strong>Cloudflare WARP (1.1.1.1)</strong><br>
            &bull; Or switch between Wi-Fi and Mobile Data.
        </div>
    </div>

    <!-- icon -->
    <div class="icon-wrap" id="offlineIcon">
        <div class="icon-circle">
            <div class="icon-pulse"></div>
            <svg class="wifi-icon" viewBox="0 0 24 24">
                <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01"/>
            </svg>
        </div>
    </div>
    <h1 id="offlineHeading">You're Offline</h1>
    <p class="subtitle" id="offlineMsg">
        We can't connect to the School Attendance server right now. Please check your Wi-Fi or mobile data connection.
    </p>

    <!-- cached profile -->
    <div class="profile-box" id="cachedProfileBox">
        <div class="profile-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <span id="cachedProfileLabel">Cached Profile</span>
        </div>
        <div class="profile-name" id="cachedName">User</div>
        <div class="profile-meta" id="cachedMeta"></div>
    </div>

    <!-- retry -->
    <button class="btn-retry" id="btnRetry" onclick="handleRetry()">
        <svg id="retryIcon" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/>
        </svg>
        <span id="retryText">Retry Connection</span>
    </button>

    <!-- ════════════ OFFLINE ATTENDANCE (teachers) ════════════ -->
    <div id="oaSection">
        <div class="sep">Offline Attendance Taking</div>

        <!-- pending strip -->
        <div class="pending-strip" id="pendingStrip">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            <span id="pendingStripText">0 records saved offline — will sync when connected.</span>
        </div>

        <div class="oa-card">
            <!-- header -->
            <div class="oa-card-hdr">
                <div class="oa-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    Take Attendance Offline
                </div>
                <span class="oa-offline-badge">Offline Mode</span>
            </div>

            <!-- subject selector -->
            <div class="oa-row">
                <div class="oa-lbl">Select Subject</div>
                <select id="oaSubjectSelect" class="oa-select" onchange="oaLoadStudents()">
                    <option value="">— choose a subject —</option>
                </select>
            </div>

            <!-- date + mark-all row -->
            <div class="oa-date-row">
                <div class="oa-date-wrap">
                    <div class="oa-lbl">Date</div>
                    <input type="date" id="oaDateInput" class="oa-date-input">
                </div>
                <button class="oa-all-btn" id="oaAllBtn" onclick="oaMarkAll('Present')">
                    ✓ All Present
                </button>
            </div>

            <!-- student list area -->
            <div id="oaStudentArea">
                <div class="oa-empty">
                    <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                    </svg>
                    Select a subject above to load the class list.
                </div>
            </div>

            <!-- save button -->
            <div class="oa-footer" id="oaFooter" style="display:none;">
                <button class="oa-save-btn" id="oaSaveBtn" onclick="oaSave()">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Save Offline
                </button>
            </div>
        </div>

        <p style="font-size:.75rem;color:var(--muted);margin-top:10px;text-align:center;">
            Records sync automatically when internet is restored.
        </p>
    </div>
    <!-- ════════════ end offline attendance ════════════ -->

    <div class="app-footer" style="margin-top:18px;">
        {{ config('app.name', 'School Attendance System') }} &bull; PWA Offline Mode
    </div>
</div>

<div class="oa-toast" id="oaToast"></div>

<!-- offline-attendance.js is pre-cached by the service worker -->
<script src="/js/offline-attendance.js"></script>

<script>
// ── Connectivity management ────────────────────────────────────────────
var currentState = 'no-internet';
var isReconnecting = false;
var probeInFlight = false;

function setStatus(state) {
    currentState = state;
    var badge = document.getElementById('statusBadge');
    var text  = document.getElementById('statusBadgeText');
    var head  = document.getElementById('offlineHeading');
    var msg   = document.getElementById('offlineMsg');
    var rt    = document.getElementById('retryText');
    var isp   = document.getElementById('ispTipBox');
    if (state === 'server-unreachable') {
        badge.className = 'status-badge server';
        text.textContent = 'Server Unreachable';
        head.textContent = 'Server Unreachable';
        msg.textContent  = 'Your internet is active, but your network provider cannot route to the school server.';
        if (isp) isp.style.display = 'block';
        if (!rt.textContent.includes('…')) rt.textContent = 'Retry Connecting to Server';
    } else {
        badge.className = 'status-badge';
        text.textContent = 'No Connection';
        head.textContent = "You're Offline";
        msg.textContent  = "We can't connect to the School Attendance server right now. Check your Wi-Fi or mobile data.";
        if (isp) isp.style.display = 'none';
        if (!rt.textContent.includes('…')) rt.textContent = 'Retry Connection';
    }
}

async function probeExternal() {
    if (!navigator.onLine) return false;
    for (var url of ['https://www.google.com/generate_204','https://1.1.1.1/cdn-cgi/trace']) {
        try {
            var ctrl = new AbortController();
            setTimeout(function(){ctrl.abort();}, 3500);
            await fetch(url, {method:'HEAD',mode:'no-cors',cache:'no-store',signal:ctrl.signal});
            return true;
        } catch(e){}
    }
    return false;
}

async function probeServer() {
    var urls = ['/api/ping?_t='+Date.now(), '/up?_t='+Date.now(), '/manifest.json?_t='+Date.now()];
    for (var url of urls) {
        try {
            var ctrl = new AbortController();
            setTimeout(function(){ctrl.abort();}, 6000);
            var res = await fetch(url, {method:'GET',cache:'no-store',signal:ctrl.signal,headers:{'Accept':'application/json,*/*'}});
            if (res.ok || res.status < 500) return 'connected';
            return 'server-unreachable';
        } catch(e){}
    }
    var hasNet = await probeExternal();
    return hasNet ? 'server-unreachable' : 'no-internet';
}

function onConnected() {
    if (isReconnecting) return;
    isReconnecting = true;
    var n = document.getElementById('reconnectNotice');
    var t = document.getElementById('retryText');
    var b = document.getElementById('statusBadge');
    if (n) n.style.display = 'flex';
    if (t) t.textContent = 'Connected! Reloading…';
    if (b) { b.className = 'status-badge online'; document.getElementById('statusBadgeText').textContent = 'Online'; }
    setTimeout(function() {
        var url = sessionStorage.getItem('pwa_intended_url');
        if (url) { sessionStorage.removeItem('pwa_intended_url'); window.location.href = url; }
        else { window.location.reload(); }
    }, 900);
}

async function handleRetry() {
    if (isReconnecting || probeInFlight) return;
    probeInFlight = true;
    var btn  = document.getElementById('btnRetry');
    var icon = document.getElementById('retryIcon');
    var text = document.getElementById('retryText');
    icon.classList.add('spin'); text.textContent = 'Checking…'; btn.disabled = true;
    try {
        var state = await probeServer();
        if (state === 'connected') { onConnected(); return; }
        setStatus(state);
    } catch(e) { setStatus('no-internet'); }
    finally {
        probeInFlight = false;
        setTimeout(function(){ icon.classList.remove('spin'); btn.disabled = false; }, 400);
    }
}

var autoPing = setInterval(async function() {
    if (isReconnecting || probeInFlight) return;
    probeInFlight = true;
    try {
        var s = await probeServer();
        if (s === 'connected') { clearInterval(autoPing); onConnected(); }
        else if (s !== currentState) setStatus(s);
    } catch(e){}
    finally { probeInFlight = false; }
}, 3000);

window.addEventListener('online',  function(){ setStatus('server-unreachable'); handleRetry(); });
window.addEventListener('offline', function(){ setStatus('no-internet'); });

(async function() {
    var s = await probeServer();
    if (s === 'connected') { onConnected(); return; }
    setStatus(s);
})();


// ── Cached profile ─────────────────────────────────────────────────────
try {
    var raw = localStorage.getItem('cached_user_profile') || localStorage.getItem('cached_student_profile');
    if (raw) {
        var prof = JSON.parse(raw);
        if (prof && prof.name) {
            var r = (prof.role || 'user').toLowerCase();
            document.getElementById('cachedProfileLabel').textContent = r.charAt(0).toUpperCase()+r.slice(1)+' (cached)';
            document.getElementById('cachedName').textContent = prof.name;
            var m = r.toUpperCase();
            if (prof.student_number) m += ' · ID: '+prof.student_number;
            if (prof.email) m += ' · '+prof.email;
            document.getElementById('cachedMeta').innerHTML = m;
            document.getElementById('cachedProfileBox').style.display = 'block';
        }
    }
} catch(e){}


// ── Offline Attendance (teachers only) ────────────────────────────────
var oaStudents = [];
var oaStatus   = {};  // { id: 'Present'|'Late'|'Absent' }

function oaToast(msg, type, dur) {
    var el = document.getElementById('oaToast');
    if (!el) return;
    el.textContent = msg;
    el.className = 'oa-toast show '+(type||'info');
    clearTimeout(el._tid);
    el._tid = setTimeout(function(){ el.classList.remove('show'); }, dur||3500);
}

function oaRefreshPending() {
    if (!window.OfflineAttendance) return;
    OfflineAttendance.getPendingCount().then(function(n) {
        var strip = document.getElementById('pendingStrip');
        var info  = document.getElementById('pendingInfo');
        var st    = document.getElementById('pendingStripText');
        var cnt   = document.getElementById('pendingCount');
        if (n > 0) {
            if (strip) strip.style.display = 'flex';
            if (info)  info.style.display  = 'flex';
            if (st)    st.textContent  = n+' record'+(n!==1?'s':'')+' saved offline — will sync when connected.';
            if (cnt)   cnt.textContent = n;
        } else {
            if (strip) strip.style.display = 'none';
            if (info)  info.style.display  = 'none';
        }
    });
}

function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function oaRenderRow(s) {
    var cur = oaStatus[s.id] || 'Absent';
    var btn = function(st, cls) {
        return '<button class="oa-rbtn '+cls+(cur===st?' on':'')+'" data-uid="'+s.id+'" data-st="'+st+'">'+st+'</button>';
    };
    return '<div class="oa-student" data-uid="'+s.id+'">' +
        '<span class="oa-snum">'+esc(s.student_number||'—')+'</span>' +
        '<span class="oa-sname">'+esc(s.name)+'</span>' +
        '<div class="oa-rgroup">'+btn('Present','p')+btn('Late','l')+btn('Absent','a')+'</div>' +
        '</div>';
}

function oaRenderList() {
    var area = document.getElementById('oaStudentArea');
    if (!oaStudents.length) {
        area.innerHTML = '<div class="oa-empty"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>No students found in this subject.</div>';
        return;
    }
    var html = '<div class="oa-list">';
    oaStudents.forEach(function(s){ html += oaRenderRow(s); });
    html += '</div>';
    area.innerHTML = html;

    area.querySelectorAll('.oa-rbtn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var uid = parseInt(this.dataset.uid);
            var st  = this.dataset.st;
            oaStatus[uid] = st;
            var row = area.querySelector('.oa-student[data-uid="'+uid+'"]');
            if (row) {
                row.querySelectorAll('.oa-rbtn').forEach(function(b){ b.classList.remove('on'); });
                this.classList.add('on');
            }
        });
    });
}

function oaMarkAll(status) {
    oaStudents.forEach(function(s){ oaStatus[s.id] = status; });
    oaRenderList();
}

function oaLoadStudents() {
    var sel = document.getElementById('oaSubjectSelect');
    var opt = sel.options[sel.selectedIndex];
    oaStudents = [];
    oaStatus   = {};

    document.getElementById('oaFooter').style.display  = 'none';
    document.getElementById('oaAllBtn').style.display  = 'none';

    if (!opt || !opt.dataset.students) {
        document.getElementById('oaStudentArea').innerHTML =
            '<div class="oa-empty"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>Select a subject above.</div>';
        return;
    }

    try { oaStudents = JSON.parse(opt.dataset.students); } catch(e) { oaStudents = []; }
    oaStudents.forEach(function(s){ oaStatus[s.id] = 'Absent'; });

    oaRenderList();
    if (oaStudents.length) {
        document.getElementById('oaFooter').style.display = 'flex';
        document.getElementById('oaAllBtn').style.display = 'flex';
    }
}

function oaSave() {
    if (!window.OfflineAttendance) { oaToast('Offline module unavailable.','error'); return; }
    var sel  = document.getElementById('oaSubjectSelect');
    var date = document.getElementById('oaDateInput').value;
    if (!sel.value) { oaToast('Please select a subject.','error'); return; }
    if (!date)      { oaToast('Please choose a date.','error'); return; }
    if (!oaStudents.length) { oaToast('No students to save.','error'); return; }

    var opt  = sel.options[sel.selectedIndex];
    var code = sel.value;
    var name = opt ? opt.textContent.replace(/^[^–]+ – /,'').replace(/ \(.*$/,'') : code;

    var btn = document.getElementById('oaSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg> Saving…';

    var promises = oaStudents.map(function(s) {
        return OfflineAttendance.saveRecord({
            subject_code:   code,
            subject_name:   name,
            user_id:        s.id,
            student_name:   s.name,
            student_number: s.student_number || '',
            status:         oaStatus[s.id] || 'Absent',
            date:           date,
            time:           new Date().toTimeString().substring(0, 8),
        });
    });

    Promise.all(promises).then(function(recs) {
        oaToast('✓ '+recs.length+' record'+(recs.length!==1?'s':'')+' saved. Will sync when online.','success',4500);
        oaRefreshPending();
        oaStudents.forEach(function(s){ oaStatus[s.id] = 'Absent'; });
        oaRenderList();
    }).catch(function(err) {
        oaToast('Save failed: '+err.message,'error');
    }).finally(function() {
        btn.disabled = false;
        btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Save Offline';
    });
}

function oaInit() {
    if (!window.OfflineAttendance) return;
    try {
        var p = JSON.parse(localStorage.getItem('cached_user_profile') || '{}');
        if (!p || p.role !== 'teacher') return;
        OfflineAttendance.init({ csrfToken: '', teacherId: p.id || null });
    } catch(e) { return; }

    // Set today as default date
    var now = new Date();
    var pad = function(n){ return String(n).padStart(2,'0'); };
    document.getElementById('oaDateInput').value =
        now.getFullYear()+'-'+pad(now.getMonth()+1)+'-'+pad(now.getDate());

    OfflineAttendance.getCachedRoster().then(function(subjects) {
        document.getElementById('oaSection').style.display = 'block';
        var sel = document.getElementById('oaSubjectSelect');

        if (!subjects || !subjects.length) {
            document.getElementById('oaStudentArea').innerHTML =
                '<div class="oa-empty">'+
                '<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>'+
                'No class lists are saved on this device.<br>'+
                'Open at least one class page while online to enable offline attendance.'+
                '</div>';
            oaRefreshPending();
            return;
        }

        subjects.forEach(function(s) {
            var o = document.createElement('option');
            o.value = s.code;
            o.textContent = s.code+' – '+s.name+(s.section?' ('+s.section+')':'');
            o.dataset.students = JSON.stringify(s.students || []);
            sel.appendChild(o);
        });
        oaRefreshPending();
    }).catch(function(){
        document.getElementById('oaSection').style.display = 'block';
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', oaInit);
} else {
    oaInit();
}

setInterval(oaRefreshPending, 12000);
</script>
</body>
</html>
