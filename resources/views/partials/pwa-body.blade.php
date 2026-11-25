@php
    $versionService = $versionService ?? app(\App\Services\VersionService::class);
    $installedVersion = $installedVersion ?? $appInstalledVersion ?? $versionService->getInstalledVersion();
    $latestVersion = $latestVersion ?? $appVersion ?? $versionService->getVersion();
    $buildId = $buildId ?? $appBuild ?? $versionService->getBuild();
    $commitHash = $commitHash ?? $appCommit ?? $versionService->getCommit();
    $swCacheVer = $swCacheVer ?? $versionService->getSwVersion();
    $swFileMtime = $swFileMtime ?? (file_exists(public_path('sw.js')) ? filemtime(public_path('sw.js')) : time());
    $swQueryVer = $swQueryVer ?? ('v' . preg_replace('/[^0-9]/', '', (string) $swCacheVer) . '_' . $swFileMtime);
    $initialChangelog = $initialChangelog ?? app(\App\Services\ChangelogService::class)->getRelease((string) $latestVersion);
@endphp
<!-- Floating PWA Install Banner -->
<div class="pwa-install-banner" id="pwaInstallBanner" style="display: none;">
    <div class="pwa-banner-icon">
        <img src="/images/icons/icon-72x72.png" alt="Smart Attendance">
    </div>
    <div class="pwa-banner-content">
        <div class="pwa-banner-title">Install Smart Attendance</div>
        <div class="pwa-banner-subtitle">Fast attendance clock-in, offline mode &amp; alerts</div>
    </div>
    <div class="pwa-banner-actions">
        <button type="button" class="pwa-btn-install pwa-install-trigger" id="pwaBannerInstallBtn">Install</button>
        <a href="/download/apk" id="pwaBannerApkBtn" download="SmartAttendance.apk"
           style="display:none; background:linear-gradient(135deg,#22C55E 0%,#16A34A 100%); color:#fff; border:none; border-radius:10px; padding:9px 14px; font-size:0.82rem; font-weight:700; cursor:pointer; text-decoration:none; align-items:center; gap:5px; white-space:nowrap;">
            ⬇ APK
        </a>
        <button type="button" class="pwa-btn-close" id="pwaBannerCloseBtn" aria-label="Dismiss">&times;</button>
    </div>
</div>

<!-- Universal PWA Install & Download Modal (Android, iOS & Desktop) -->
<div class="pwa-ios-modal" id="pwaIosModal" role="dialog" aria-modal="true" aria-labelledby="pwaModalTitle">
    <div class="pwa-ios-sheet">
        <div class="pwa-sheet-handle"></div>
        <div class="pwa-sheet-top-row">
            <div class="pwa-sheet-title-wrap">
                <span class="pwa-sheet-title-icon"><i class="bi bi-phone"></i></span>
                <h3 id="pwaModalTitle" class="pwa-sheet-title">Get Mobile App</h3>
            </div>
            <button type="button" id="pwaModalCloseIcon" class="pwa-sheet-close-btn" aria-label="Close modal">&times;</button>
        </div>

        <!-- 1. Main App Hero Card -->
        <div class="pwa-hero-card" id="pwaHeroCard">
            <div class="pwa-hero-header">
                <img src="/images/icons/icon-192x192.png" alt="Smart Attendance" class="pwa-hero-icon" id="pwaHeroIcon">
                <div class="pwa-hero-info">
                    <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                        <span class="pwa-hero-badge" id="pwaHeroBadge">Official Android App</span>
                        <span class="pwa-hero-verified" id="pwaHeroVerified"><i class="bi bi-patch-check-fill"></i> Verified</span>
                    </div>
                    <h4 class="pwa-hero-title" id="pwaHeroTitle">Smart Classroom Attendance</h4>
                    <div class="pwa-hero-meta" id="pwaHeroMeta">
                        <span id="pwaHeroVersion">v{{ $latestVersion }}</span>
                        <span class="pwa-meta-dot">•</span>
                        <span id="pwaHeroSize">~4 MB</span>
                        <span class="pwa-meta-dot">•</span>
                        <span id="pwaHeroPlatform">Android</span>
                    </div>
                </div>
            </div>

            <p class="pwa-hero-tagline" id="pwaHeroTagline">Install for faster clock-in, biometric access &amp; instant alerts.</p>

            <!-- Main CTA Download / Action Button -->
            <div class="pwa-cta-container">
                <button type="button" id="pwaModalDownloadApkBtn" class="pwa-hero-dl-btn" data-apk-url="{{ \Illuminate\Support\Facades\Route::has('pwa.download.apk') ? route('pwa.download.apk') : url('/download/apk') }}" data-action="download">
                    <span class="pwa-dl-btn-content" id="pwaDlBtnContent" style="display:flex; align-items:center; justify-content:center; gap:8px;">
                        <i class="bi bi-arrow-down-circle-fill pwa-dl-icon" id="pwaDlIcon"></i>
                        <span class="pwa-dl-label" id="pwaDlLabel">Download APK</span>
                    </span>
                    <span class="pwa-dl-progress-bar" id="pwaDlProgressBar"></span>
                </button>

                <div id="pwaDlStatusAlert" class="pwa-dl-status-banner">
                    <i class="bi bi-check-circle-fill" style="font-size:1.05rem; flex-shrink:0;"></i>
                    <div>
                        <div style="font-weight:700;">Download Started!</div>
                        <div style="font-size:0.72rem; opacity:0.9;">Open your Downloads folder and tap the APK to install.</div>
                    </div>
                </div>
            </div>

            <div class="pwa-hero-trust" id="pwaHeroTrust">
                <i class="bi bi-shield-check text-success"></i>
                <span id="pwaHeroTrustText">Official Smart Classroom Attendance App</span>
            </div>
        </div>

        <!-- 2. Simple 3-Step Visual Process -->
        <div class="pwa-mini-steps-section" id="pwaMiniStepsSection">
            <div class="pwa-mini-steps-title" id="pwaStepsHeaderTitle">HOW TO INSTALL</div>
            <div class="pwa-mini-steps-grid" id="pwaModalSteps">
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">1</div>
                    <div class="pwa-mini-step-label">DOWNLOAD</div>
                    <div class="pwa-mini-step-hint">Tap Download APK</div>
                </div>
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">2</div>
                    <div class="pwa-mini-step-label">INSTALL</div>
                    <div class="pwa-mini-step-hint">Open file &amp; tap Install</div>
                </div>
                <div class="pwa-mini-step-box">
                    <div class="pwa-mini-step-num">3</div>
                    <div class="pwa-mini-step-label">OPEN</div>
                    <div class="pwa-mini-step-hint">Launch from Drawer</div>
                </div>
            </div>
        </div>

        <!-- 3. Collapsible Troubleshooting Section -->
        <details class="pwa-help-details" id="pwaHelpDetails">
            <summary class="pwa-help-summary">
                <span style="display:flex; align-items:center; gap:6px;">
                    <i class="bi bi-question-circle"></i>
                    <span>Having trouble installing?</span>
                </span>
                <i class="bi bi-chevron-down pwa-help-chevron"></i>
            </summary>
            <div class="pwa-help-content" id="pwaHelpContent">
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Samsung / Pixel:</strong> Open the downloaded APK and tap Install. The icon appears in your App Drawer (swipe up). Long-press to add to Home.</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Xiaomi / Redmi / Poco:</strong> If home screen shortcuts are blocked: Settings &gt; Apps &gt; Permissions &gt; enable "Home screen shortcuts".</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Browser Warning:</strong> Tap "Details" or "Download anyway" if your browser displays a standard prompt for direct APK files.</div>
                </div>
                <div class="pwa-help-item">
                    <span class="pwa-help-bullet"></span>
                    <div><strong>Can't Find Download:</strong> Open your phone's <strong>Files</strong> or <strong>Downloads</strong> app and tap <code>SmartAttendance.apk</code>.</div>
                </div>
            </div>
        </details>

        <!-- 4. Secondary Action Buttons -->
        <div class="pwa-sheet-bottom-actions">
            <button type="button" class="pwa-sheet-btn-secondary" id="pwaIosCloseBtn">Got It</button>
            <button type="button" class="pwa-sheet-btn-check" id="pwaResetStateBtn">
                <i class="bi bi-arrow-repeat me-1"></i> Check Again
            </button>
        </div>
    </div>
</div>

<!-- Automatic "System Updated ✓" Success Toast -->
<div class="pwa-toast-system-updated" id="pwaSystemUpdatedToast" style="display: none;" role="status" aria-live="polite">
    <div class="pwa-toast-icon-wrap">
        <svg class="pwa-toast-check-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
    </div>
    <div class="pwa-toast-content">
        <div class="pwa-toast-title" id="pwaSystemUpdatedTitle">System Updated <span class="pwa-toast-check">&#x2713;</span></div>
        <div class="pwa-toast-desc" id="pwaSystemUpdatedDesc">You are now using the latest version.</div>
    </div>
    <button type="button" class="pwa-toast-close" id="pwaDismissUpdatedToastBtn" aria-label="Dismiss">&times;</button>
</div>

<!-- System Update Notification -->
<div class="pwa-update-backdrop" id="pwaUpdateBackdrop" style="display: none;"></div>
<div class="pwa-update-banner" id="pwaSystemUpdatePopup" style="display: none;">
    <div class="pwa-update-accent-bar"></div>
    <div class="pwa-update-glow"></div>
    <div class="pwa-update-inner">
        <div class="pwa-update-banner-header">
            <div class="pwa-update-icon-container">
                <div class="pwa-update-icon-glow-ring"></div>
                <img src="/images/icons/icon-72x72.png" class="pwa-update-app-icon" alt="Smart Attendance">
                <span class="pwa-update-pulse-indicator" title="New build ready"></span>
            </div>
            <div class="pwa-update-text-area">
                <div class="pwa-update-meta">
                    <span class="pwa-update-tag">&#10003; Update Ready</span>
                    <span class="pwa-update-version-badge" id="pwaUpdateVersionBadge">{{ $initialChangelog['version_display'] ?? ('v' . $latestVersion) }}</span>
                </div>
                <div class="pwa-update-title" id="pwaUpdateTitle">New Version Available</div>
                <div class="pwa-update-subtitle" id="pwaUpdateSubtitle">Improvements and fixes are ready to install.</div>
                <span style="display:none;" aria-hidden="true">Update Ready Refresh Now</span>
            </div>
            <button type="button" class="pwa-update-close-btn" id="pwaDismissUpdatePopupBtn" aria-label="Dismiss">&times;</button>
        </div>
        <div class="pwa-update-banner-actions">
            <button type="button" class="pwa-btn-update-later" id="pwaLaterUpdateBtn">Later</button>
            <button type="button" class="pwa-btn-update-apply" id="pwaApplyUpdateBtn">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="pwa-update-spin-icon" style="display:none; animation: ptr-spin 0.8s linear infinite;"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                <span id="pwaApplyUpdateBtnText">Update Now</span>
                <svg class="pwa-btn-arrow-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>
            </button>
        </div>
    </div>
</div>

<!-- Floating Subtle Fallback Pill (Appears when update is available if modal is snoozed/dismissed) -->
<div class="pwa-update-pill" id="pwaUpdatePill" style="display: none;" role="button" aria-label="Update Available">
    <span class="pwa-pill-dot"></span>
    <span class="pwa-pill-text">Update Available <strong id="pwaPillVersionBadge">{{ $initialChangelog['version_display'] ?? ('v' . $latestVersion) }}</strong></span>
    <span class="pwa-pill-btn">Update</span>
</div>

<!-- Real-time Connectivity Toast -->
<div class="pwa-network-toast" id="pwaNetworkToast"></div>

<script @cspNonce src="{{ asset('js/password-toggle.js') }}?v={{ file_exists(public_path('js/password-toggle.js')) ? filemtime(public_path('js/password-toggle.js')) : time() }}"></script>
<script @cspNonce>
    // ── Prevent Pull-to-Refresh & Overscroll System Reload ──
    // When scrolling up or down, preserve user's position and page state.
    // Prevent mobile browsers (Chrome / Safari / WebView) from triggering
    // a page refresh or reload when reaching the top boundary.
    (function() {
        if (typeof window === 'undefined' || !window.document) return;
        
        let startY = 0;
        let isTouching = false;

        window.addEventListener('touchstart', function(e) {
            if (e.touches && e.touches.length === 1) {
                startY = e.touches[0].clientY;
                isTouching = true;
            }
        }, { passive: true });

        window.addEventListener('touchmove', function(e) {
            if (!isTouching || !e.touches || e.touches.length !== 1) return;
            const currentY = e.touches[0].clientY;
            const deltaY = currentY - startY;

            // When user pulls DOWN (deltaY > 0) while at the top of the page (scrollTop <= 0)
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
            if (scrollTop <= 0 && deltaY > 0) {
                // Check if an ancestor scrollable element has scroll room upwards
                let target = e.target;
                let hasScrollableParent = false;
                while (target && target !== document.body && target !== document.documentElement) {
                    if (target.scrollHeight > target.clientHeight) {
                        const overflow = window.getComputedStyle(target).overflowY;
                        if ((overflow === 'auto' || overflow === 'scroll') && target.scrollTop > 0) {
                            hasScrollableParent = true;
                            break;
                        }
                    }
                    target = target.parentElement;
                }
                if (!hasScrollableParent && e.cancelable) {
                    e.preventDefault();
                }
            }
        }, { passive: false });

        window.addEventListener('touchend', function() {
            isTouching = false;
        }, { passive: true });

        window.addEventListener('touchcancel', function() {
            isTouching = false;
        }, { passive: true });
    })();

    // ── Universal Multi-Layer Persistent Device Key & Hardware Fingerprinting ──
    (function() {
        // Fast deterministic FNV-1a 32-bit hashing
        function fnv1a(str) {
            var hash = 2166136261;
            for (var i = 0; i < str.length; i++) {
                hash ^= str.charCodeAt(i);
                hash = Math.imul(hash, 16777619);
            }
            return (hash >>> 0).toString(16);
        }

        // Extract GPU vendor & renderer via WebGL unmasked debug extension
        function getWebGlInfo() {
            var out = { vendor: '', renderer: '' };
            try {
                var c = document.createElement('canvas');
                var gl = c.getContext('webgl') || c.getContext('experimental-webgl');
                if (gl) {
                    var dbg = gl.getExtension('WEBGL_debug_renderer_info');
                    if (dbg) {
                        out.vendor = gl.getParameter(dbg.UNMASKED_VENDOR_WEBGL) || '';
                        out.renderer = gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL) || '';
                    }
                }
            } catch(e) {}
            return out;
        }

        // Comprehensive Device Telemetry & Environmental Diagnostics
        window.getDeviceTelemetry = function() {
            try {
                var gl = getWebGlInfo();
                var s = window.screen || {};
                var n = navigator || {};
                var tz = '';
                try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch(e) {}

                var connType = 'unknown';
                try {
                    if (n.connection) {
                        connType = n.connection.effectiveType || n.connection.type || 'standard';
                    }
                } catch(e) {}

                return {
                    screen_res: (s.width || 0) + 'x' + (s.height || 0),
                    pixel_ratio: window.devicePixelRatio || 1,
                    color_depth: s.colorDepth || 24,
                    cores: n.hardwareConcurrency || 4,
                    memory_gb: n.deviceMemory || null,
                    touch_points: n.maxTouchPoints || 0,
                    platform: n.platform || '',
                    timezone: tz,
                    gpu_vendor: gl.vendor,
                    gpu_renderer: gl.renderer,
                    webdriver: !!n.webdriver,
                    connection_type: connType,
                };
            } catch(e) {
                return {};
            }
        };

        // Generate deterministic client hardware environment fingerprint
        window.getDeviceFingerprint = function() {
            try {
                var gl = getWebGlInfo();
                var components = [
                    (window.screen ? window.screen.width + 'x' + window.screen.height + 'x' + window.screen.colorDepth : ''),
                    (window.devicePixelRatio || 1),
                    (navigator.hardwareConcurrency || 4),
                    (navigator.maxTouchPoints || 0),
                    (navigator.platform || ''),
                    (Intl && Intl.DateTimeFormat ? Intl.DateTimeFormat().resolvedOptions().timeZone : ''),
                    gl.vendor,
                    gl.renderer
                ];

                // Add lightweight canvas fingerprinting
                try {
                    var canvas = document.createElement('canvas');
                    canvas.width = 120;
                    canvas.height = 30;
                    var ctx = canvas.getContext('2d');
                    if (ctx) {
                        ctx.textBaseline = 'top';
                        ctx.font = '14px Arial';
                        ctx.fillStyle = '#f60';
                        ctx.fillRect(10, 1, 62, 20);
                        ctx.fillStyle = '#069';
                        ctx.fillText('SmartAtt_Fp', 2, 15);
                        ctx.fillStyle = 'rgba(102, 204, 0, 0.7)';
                        ctx.fillText('SmartAtt_Fp', 4, 17);
                        components.push(canvas.toDataURL());
                    }
                } catch(ce) {}

                var raw = components.join('###');
                return fnv1a(raw) + fnv1a(raw.split('').reverse().join(''));
            } catch(e) {
                return 'fp_fallback';
            }
        };

        // Determine user-friendly hardware device model
        window.getDeviceModel = function() {
            try {
                if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceModel === 'function') {
                    return window.AndroidDeviceBridge.getNativeDeviceModel();
                }
                var ua = navigator.userAgent || '';
                if (/iPhone/i.test(ua)) return 'Apple iPhone';
                if (/iPad/i.test(ua)) return 'Apple iPad';
                if (/Android/i.test(ua)) {
                    var m = ua.match(/Android[^;]+; ([^)]+)\)/);
                    if (m && m[1]) return m[1].replace(/Build\/.+/, '').trim();
                    return 'Android Mobile';
                }
                if (/Macintosh/i.test(ua)) return 'Mac OS Computer';
                if (/Windows/i.test(ua)) return 'Windows PC';
                return 'Web Browser Device';
            } catch(e) {
                return 'Personal Device';
            }
        };

        // IndexedDB resilient storage layer
        function saveToIndexedDb(key) {
            if (!window.indexedDB) return;
            try {
                var req = indexedDB.open('smart_attendance_device_db', 1);
                req.onupgradeneeded = function(e) {
                    var db = e.target.result;
                    if (!db.objectStoreNames.contains('device_store')) {
                        db.createObjectStore('device_store');
                    }
                };
                req.onsuccess = function(e) {
                    try {
                        var db = e.target.result;
                        var tx = db.transaction('device_store', 'readwrite');
                        tx.objectStore('device_store').put(key, 'student_device_key');
                    } catch(err) {}
                };
            } catch(e) {}
        }

        function restoreFromIndexedDb() {
            if (!window.indexedDB) return;
            try {
                var req = indexedDB.open('smart_attendance_device_db', 1);
                req.onsuccess = function(e) {
                    try {
                        var db = e.target.result;
                        if (!db.objectStoreNames.contains('device_store')) return;
                        var tx = db.transaction('device_store', 'readonly');
                        var getReq = tx.objectStore('device_store').get('student_device_key');
                        getReq.onsuccess = function() {
                            var val = getReq.result;
                            if (val && typeof val === 'string' && val.length > 8) {
                                if (!localStorage.getItem('student_device_key')) {
                                    localStorage.setItem('student_device_key', val);
                                    localStorage.setItem('attendance_device_uuid', val);
                                    var sec = location.protocol === 'https:' ? '; Secure' : '';
                                    document.cookie = 'student_device_key=' + encodeURIComponent(val) + '; path=/; max-age=31536000; SameSite=Lax' + sec;
                                }
                            }
                        };
                    } catch(err) {}
                };
            } catch(e) {}
        }
        try { restoreFromIndexedDb(); } catch(e) {}

        // Multi-tier device key accessor & healing engine
        window.getOrCreateDeviceKey = function() {
            try {
                var key = null;

                // Tier 1: Native Android app bridge (100% persistent across app installs)
                if (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.getNativeDeviceId === 'function') {
                    key = window.AndroidDeviceBridge.getNativeDeviceId();
                }

                // Tier 2: localStorage
                if (!key) {
                    key = localStorage.getItem('student_device_key') || localStorage.getItem('attendance_device_uuid');
                }

                // Tier 3: Cookies
                if (!key) {
                    var m = document.cookie.match(/(?:^|;\s*)student_device_key=([^;]+)/);
                    if (m && m[1]) key = decodeURIComponent(m[1]);
                }

                // Tier 4: sessionStorage
                if (!key) {
                    key = sessionStorage.getItem('student_device_key') || sessionStorage.getItem('attendance_device_uuid');
                }

                // Tier 5: Fresh generation if uninitialized
                if (!key || key === 'undefined' || key === 'null' || key.trim() === '') {
                    key = (typeof crypto !== 'undefined' && crypto.randomUUID)
                        ? crypto.randomUUID()
                        : ('dev_' + Date.now().toString(36) + '_' + Math.random().toString(36).substring(2, 14));
                }

                // Synchronize across all persistence layers
                try {
                    localStorage.setItem('student_device_key', key);
                    localStorage.setItem('attendance_device_uuid', key);
                    sessionStorage.setItem('student_device_key', key);
                    sessionStorage.setItem('attendance_device_uuid', key);
                    var sec = location.protocol === 'https:' ? '; Secure' : '';
                    document.cookie = 'student_device_key=' + encodeURIComponent(key) + '; path=/; max-age=31536000; SameSite=Lax' + sec;
                    saveToIndexedDb(key);
                } catch(syncErr) {}

                return key;
            } catch(e) {
                return '';
            }
        };

        try { window.getOrCreateDeviceKey(); } catch(e) {}

        // Attach device headers to fetch requests
        if (typeof window.fetch === 'function' && !window.__deviceFetchIntercepted) {
            window.__deviceFetchIntercepted = true;
            var _origFetch = window.fetch;
            window.fetch = function(input, init) {
                try {
                    init = init || {};
                    var devKey  = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                    var devFp   = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                    var devMod  = window.getDeviceModel ? window.getDeviceModel() : '';
                    var devMeta = window.getDeviceTelemetry ? JSON.stringify(window.getDeviceTelemetry()) : '';

                    if (devKey) {
                        if (!init.headers) init.headers = {};
                        if (init.headers instanceof Headers) {
                            if (!init.headers.has('X-Device-Key')) init.headers.set('X-Device-Key', devKey);
                            if (!init.headers.has('X-Device-Fingerprint')) init.headers.set('X-Device-Fingerprint', devFp || devKey);
                            if (!init.headers.has('X-Device-Model') && devMod) init.headers.set('X-Device-Model', devMod);
                            if (!init.headers.has('X-Device-Metadata') && devMeta) init.headers.set('X-Device-Metadata', devMeta);
                        } else if (Array.isArray(init.headers)) {
                            init.headers.push(['X-Device-Key', devKey]);
                            init.headers.push(['X-Device-Fingerprint', devFp || devKey]);
                            if (devMod) init.headers.push(['X-Device-Model', devMod]);
                            if (devMeta) init.headers.push(['X-Device-Metadata', devMeta]);
                        } else {
                            if (!init.headers['X-Device-Key']) init.headers['X-Device-Key'] = devKey;
                            if (!init.headers['X-Device-Fingerprint']) init.headers['X-Device-Fingerprint'] = devFp || devKey;
                            if (!init.headers['X-Device-Model'] && devMod) init.headers['X-Device-Model'] = devMod;
                            if (!init.headers['X-Device-Metadata'] && devMeta) init.headers['X-Device-Metadata'] = devMeta;
                        }
                    }
                    if (!init.credentials) {
                        init.credentials = 'same-origin';
                    }
                } catch(err) {}
                return _origFetch.call(this, input, init);
            };
        }

        // Attach device headers to same-origin XMLHttpRequest
        if (typeof window.XMLHttpRequest !== 'undefined' && !window.__deviceXHRIntercepted) {
            window.__deviceXHRIntercepted = true;
            var _origOpen = XMLHttpRequest.prototype.open;
            var _origSend = XMLHttpRequest.prototype.send;
            XMLHttpRequest.prototype.open = function() {
                var url = arguments[1] || '';
                this.__isSameOrigin = (
                    typeof url === 'string' && (
                        url.startsWith('/') ||
                        url.startsWith(window.location.origin) ||
                        !url.startsWith('http')
                    )
                );
                return _origOpen.apply(this, arguments);
            };
            XMLHttpRequest.prototype.send = function() {
                try {
                    if (this.__isSameOrigin) {
                        var devKey  = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                        var devFp   = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                        var devMod  = window.getDeviceModel ? window.getDeviceModel() : '';
                        var devMeta = window.getDeviceTelemetry ? JSON.stringify(window.getDeviceTelemetry()) : '';
                        if (devKey) {
                            this.setRequestHeader('X-Device-Key', devKey);
                            this.setRequestHeader('X-Device-Fingerprint', devFp || devKey);
                            if (devMod) this.setRequestHeader('X-Device-Model', devMod);
                            if (devMeta) this.setRequestHeader('X-Device-Metadata', devMeta);
                        }
                    }
                } catch(e) {}
                return _origSend.apply(this, arguments);
            };
        }

        // Automatically inject hidden device binding fields into HTML forms on submit
        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (form && form.tagName === 'FORM' && form.method && form.method.toUpperCase() === 'POST') {
                try {
                    var devKey  = window.getOrCreateDeviceKey ? window.getOrCreateDeviceKey() : '';
                    var devFp   = window.getDeviceFingerprint ? window.getDeviceFingerprint() : '';
                    var devMod  = window.getDeviceModel ? window.getDeviceModel() : '';
                    var devMeta = window.getDeviceTelemetry ? JSON.stringify(window.getDeviceTelemetry()) : '';

                    if (devKey && !form.querySelector('input[name="device_key"]')) {
                        var inp1 = document.createElement('input');
                        inp1.type = 'hidden';
                        inp1.name = 'device_key';
                        inp1.value = devKey;
                        form.appendChild(inp1);
                    }
                    if (devFp && !form.querySelector('input[name="device_fingerprint"]')) {
                        var inp2 = document.createElement('input');
                        inp2.type = 'hidden';
                        inp2.name = 'device_fingerprint';
                        inp2.value = devFp;
                        form.appendChild(inp2);
                    }
                    if (devMod && !form.querySelector('input[name="device_model"]')) {
                        var inp3 = document.createElement('input');
                        inp3.type = 'hidden';
                        inp3.name = 'device_model';
                        inp3.value = devMod;
                        form.appendChild(inp3);
                    }
                    if (devMeta && !form.querySelector('input[name="device_metadata"]')) {
                        var inp4 = document.createElement('input');
                        inp4.type = 'hidden';
                        inp4.name = 'device_metadata';
                        inp4.value = devMeta;
                        form.appendChild(inp4);
                    }
                } catch(formErr) {}
            }
        }, true);
    })();

    // ── 1. Register Service Worker & Handle Real-Time Update Notifications ──
    let swRegistration = null;
    let deferredPrompt = null;
    let latestDetectedVersion = null;
    let latestDetectedSwVersion = null;
    let latestDetectedBuildId = null;
    let latestDetectedCommitHash = null;
    let currentChangelogData = null;
    let latestUpdatePolicy = { mode: 'silent', version: null };
    let isCheckingVersion = false;
    let checkVersionPromise = null;
    let lastVersionCheckTime = 0;
    const VERSION_CHECK_COOLDOWN_MS = 15 * 1000;

    // ── Handle URL Version Query Sync & Parameter Cleanup ──
    try {
        const _url = new URL(window.location.href);
        const _vParam = _url.searchParams.get('_v');
        if (_vParam) {
            // _v is only a cache-busting hint, never proof of installation.
            _url.searchParams.delete('_v');
            _url.searchParams.delete('_t');
            window.history.replaceState({}, document.title, _url.toString());
        }
    } catch(e) {}

    // Immutable constants capturing the document version as rendered by the server
    const DOC_INSTALLED_VER = document.querySelector('meta[name="app-installed-version"]')?.content || '{{ $installedVersion }}';
    const DOC_LATEST_VER = document.querySelector('meta[name="app-latest-version"]')?.content || '{{ $latestVersion }}';
    const DOC_BUILD_ID = document.querySelector('meta[name="app-build-id"]')?.content || '{{ $buildId }}';
    const DOC_COMMIT_HASH = document.querySelector('meta[name="app-commit-hash"]')?.content || '{{ $commitHash }}';
    const DOC_SW_VER = document.querySelector('meta[name="sw-build-version"]')?.content || '{{ $swCacheVer }}';
    const DOC_SW_MTIME = parseInt(document.querySelector('meta[name="sw-build-mtime"]')?.content || '{{ $swFileMtime }}', 10);
    let latestServerTimestamp = DOC_SW_MTIME;

    function releaseKey(build, commit, swVersion) {
        return [build || '', commit || '', swVersion || ''].join('|');
    }

    function getLatestReleaseKey() {
        return releaseKey(latestDetectedBuildId || DOC_BUILD_ID, latestDetectedCommitHash || DOC_COMMIT_HASH, latestDetectedSwVersion || DOC_SW_VER);
    }

    function hasUnappliedRelease() {
        const latest = getLatestReleaseKey();
        // The HTML currently on screen is the only trustworthy evidence that a
        // release actually loaded. A localStorage flag can survive a failed reload.
        const loaded = releaseKey(DOC_BUILD_ID, DOC_COMMIT_HASH, DOC_SW_VER);
        if (latest === loaded) {
            try { localStorage.setItem('pwa_applied_release_key', loaded); } catch(e) {}
            return false;
        }
        return latest !== '||';
    }

    // Announcement identity is the published version, never a build or git commit.
    function getUpdateAnnouncementKey(version) {
        return 'release:' + String(version).replace(/^[vV]/, '');
    }

    function readUpdatePreference(key) {
        try { return localStorage.getItem(key) || sessionStorage.getItem(key) || ''; } catch (e) {
            try { return sessionStorage.getItem(key) || ''; } catch (ignored) { return ''; }
        }
    }

    function writeUpdatePreference(key, value) {
        try { localStorage.setItem(key, value); } catch (e) {}
        try { sessionStorage.setItem(key, value); } catch (e) {}
    }

    function updatePromptMode(version, isManualCheck = false) {
        const minimum = latestUpdatePolicy.minimum_supported_version;
        if (typeof minimum === 'string' && /^\d+(\.\d+)*$/.test(minimum)
                && compareSemver(minimum, version) <= 0 && compareSemver(getInstalledVersion(), minimum) < 0) {
            return 'critical';
        }
        if (latestUpdatePolicy.version !== version || !['announcement', 'critical'].includes(latestUpdatePolicy.mode)) {
            return isManualCheck ? 'announcement' : 'silent';
        }
        // Rebuilding the same release never repeats its announcement or critical prompt.
        if (compareSemver(version, getInstalledVersion()) <= 0) return isManualCheck ? 'announcement' : 'silent';
        return latestUpdatePolicy.mode;
    }

    // Cross-tab synchronization via BroadcastChannel
    let pwaBroadcastChannel = null;
    try {
        if ('BroadcastChannel' in window) {
            pwaBroadcastChannel = new BroadcastChannel('pwa_update_channel');
            pwaBroadcastChannel.onmessage = function(e) {
                if (e.data && e.data.type === 'APP_UPDATED') {
                    checkServerVersion(true);
                }
            };
        }
    } catch(e) {}

    window.addEventListener('storage', function(e) {
        if (e.key === 'pwa_tab_updated_at' && e.newValue) {
            checkServerVersion(true);
        }
    });

    function updateChangelogUI(changelog) {
        if (!changelog) return;
        currentChangelogData = changelog;

        const badge = document.getElementById('pwaUpdateVersionBadge');
        if (badge) {
            badge.textContent = changelog.version_display || ('Version ' + (changelog.version || latestDetectedVersion || DOC_LATEST_VER));
        }

        const pillBadge = document.getElementById('pwaPillVersionBadge');
        if (pillBadge) {
            const clean = String(changelog.version || latestDetectedVersion || DOC_LATEST_VER).trim();
            pillBadge.textContent = clean.startsWith('v') ? clean : 'v' + clean;
        }

        const titleEl = document.getElementById('pwaUpdateTitle');
        if (titleEl) {
            titleEl.textContent = 'Update Available';
        }

        const subEl = document.getElementById('pwaUpdateSubtitle');
        if (subEl) {
            const verDisplay = changelog.version || latestDetectedVersion || DOC_LATEST_VER;
            subEl.textContent = changelog.description || ('A new version (Version ' + verDisplay + ') is available to install.');
        }
    }

    // ── 1.1 Semantic & Continuous Version Comparison (Latest Version > Installed Version) ──
    function parseSemver(v) {
        if (!v && v !== 0) return [0];
        let cleaned = String(v).trim().replace(/^Version\s*/i, '').replace(/^[vV]/, '');
        if (cleaned.includes('_')) {
            cleaned = cleaned.split('_')[0];
        }
        const parts = cleaned.split('.').map(function(p) {
            const num = parseInt(p.replace(/[^\d]/g, ''), 10);
            return isNaN(num) ? 0 : num;
        });
        return parts.length ? parts : [0];
    }

    function compareSemver(v1, v2) {
        const p1 = parseSemver(v1);
        const p2 = parseSemver(v2);
        const maxLen = Math.max(p1.length, p2.length);
        for (let i = 0; i < maxLen; i++) {
            const num1 = p1[i] !== undefined ? p1[i] : 0;
            const num2 = p2[i] !== undefined ? p2[i] : 0;
            if (num1 > num2) return 1;
            if (num1 < num2) return -1;
        }
        return 0;
    }

    function parseSwNum(v) {
        if (!v) return 0;
        const num = parseInt(String(v).replace(/[^\d]/g, ''), 10);
        return isNaN(num) ? 0 : num;
    }

    function getInstalledVersion() {
        // Server-rendered HTML identifies the code this tab is actually running.
        // Storage may be ahead after an interrupted update or another tab's update.
        return document.querySelector('meta[name="app-installed-version"]')?.content || DOC_INSTALLED_VER;
    }

    function getInstalledSwVersion() {
        const metaSw = document.querySelector('meta[name="sw-build-version"]')?.content || DOC_SW_VER || '';
        const storedSw = localStorage.getItem('pwa_installed_sw_version');
        if (metaSw && storedSw) {
            if (parseSwNum(metaSw) >= parseSwNum(storedSw)) {
                localStorage.setItem('pwa_installed_sw_version', metaSw);
                return metaSw;
            }
            return storedSw;
        }
        return metaSw || storedSw || '';
    }

    function getLatestVersion(serverData = null) {
        if (serverData && serverData.installed_version) {
            return serverData.installed_version;
        }
        return DOC_INSTALLED_VER;
    }

    // ── 1.2 DOM Health: Ensure PWA Modals & Overlays live in document.body ──
    function ensurePwaModalsInBody() {
        if (!document.body) return false;
        ['pwaInstallBanner', 'pwaIosModal', 'pwaUpdateBackdrop', 'pwaSystemUpdatePopup', 'pwaUpdatePill', 'pwaSystemUpdatedToast', 'pwaNetworkToast'].forEach(function(id) {
            const el = document.getElementById(id);
            if (el && el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });

        // Ensure update popup buttons have direct listeners bound in addition to delegated listeners
        const applyBtn = document.getElementById('pwaApplyUpdateBtn');
        if (applyBtn && !applyBtn.__hasUpdateListener) {
            applyBtn.__hasUpdateListener = true;
            applyBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                applySystemUpdate();
            });
        }
        const laterBtn = document.getElementById('pwaLaterUpdateBtn');
        if (laterBtn && !laterBtn.__hasLaterListener) {
            laterBtn.__hasLaterListener = true;
            laterBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                hideAppUpdatePopup(latestDetectedVersion);
            });
        }
        const dismissBtn = document.getElementById('pwaDismissUpdatePopupBtn');
        if (dismissBtn && !dismissBtn.__hasDismissListener) {
            dismissBtn.__hasDismissListener = true;
            dismissBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                hideAppUpdatePopup(latestDetectedVersion);
            });
        }

        return true;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensurePwaModalsInBody);
    } else {
        ensurePwaModalsInBody();
    }

    // Initialize client stored version safely on initial visit without overwriting existing version records
    try {
        const _stored = localStorage.getItem('pwa_installed_version');
        if (!_stored) {
            localStorage.setItem('pwa_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_app_version', DOC_INSTALLED_VER);
            if (DOC_SW_VER) localStorage.setItem('pwa_installed_sw_version', DOC_SW_VER);
            if (DOC_SW_MTIME) localStorage.setItem('pwa_applied_sw_mtime', String(DOC_SW_MTIME));
        }
    } catch(e) {}

    function getAppliedSwMtime() {
        const stored = localStorage.getItem('pwa_applied_sw_mtime');
        if (stored) {
            return parseInt(stored, 10);
        }
        return DOC_SW_MTIME || 0;
    }

    function checkInstantUpdateAvailable() {
        // Never offer an update based only on cached HTML or a published version
        // number. The server check confirms a deployable build is available.
        return false;
    }

    // Legacy manual-update pill. Automatic announcements do not leave a floating reminder.
    function showUpdateFallbackPill(version = null) {
        // Do not show pill if already up to date
        const installedVer = getInstalledVersion();
        const targetVer = version || latestDetectedVersion || getLatestVersion();
        if (compareSemver(targetVer, installedVer) <= 0 && !hasUnappliedRelease()) {
            hideUpdateFallbackPill();
            return;
        }
        ensurePwaModalsInBody();
        const pill = document.getElementById('pwaUpdatePill');
        if (!pill) return;
        const badge = document.getElementById('pwaPillVersionBadge');
        if (badge) {
            const clean = String(targetVer).trim();
            badge.textContent = clean.startsWith('v') ? clean : 'v' + clean;
        }
        pill.style.display = 'flex';
        void pill.offsetHeight;
        pill.classList.add('show');
    }

    function hideUpdateFallbackPill() {
        const pill = document.getElementById('pwaUpdatePill');
        if (!pill) return;
        pill.classList.remove('show');
        pill.style.display = 'none';
    }

    function hideModalElementsIfUpToDate() {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup) {
            popup.classList.remove('show');
            popup.style.display = 'none';
        }
        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
        }
        hideUpdateFallbackPill();
    }

    // ── Toast Helper: "System Updated ✓" (Auto-dismissing unobtrusive notification) ──
    let systemUpdatedToastTimer = null;
    function showSystemUpdatedToast(version = null) {
        ensurePwaModalsInBody();
        const toast = document.getElementById('pwaSystemUpdatedToast');
        if (!toast) return;

        if (version) {
            const descEl = document.getElementById('pwaSystemUpdatedDesc');
            if (descEl) {
                const cleanVer = String(version).trim();
                const verText = cleanVer.startsWith('v') ? cleanVer : 'v' + cleanVer;
                descEl.textContent = 'You are now using the latest version (' + verText + ').';
            }
        }

        toast.classList.remove('pwa-toast-hide');
        toast.style.display = 'flex';

        if (systemUpdatedToastTimer) clearTimeout(systemUpdatedToastTimer);
        systemUpdatedToastTimer = setTimeout(hideSystemUpdatedToast, 4000);
    }

    function hideSystemUpdatedToast() {
        const toast = document.getElementById('pwaSystemUpdatedToast');
        if (!toast) return;
        toast.classList.add('pwa-toast-hide');
        setTimeout(() => {
            toast.style.display = 'none';
            toast.classList.remove('pwa-toast-hide');
        }, 300);
    }

    const dirtyUpdateForms = new Set();
    document.addEventListener('input', trackUpdateFormChanges, true);
    document.addEventListener('change', trackUpdateFormChanges, true);
    document.addEventListener('reset', event => dirtyUpdateForms.delete(event.target), true);
    document.addEventListener('keydown', event => {
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (event.key === 'Tab' && popup?.dataset.updateMode === 'critical' && popup.classList.contains('show')) {
            event.preventDefault();
            document.getElementById('pwaApplyUpdateBtn')?.focus({ preventScroll: true });
        }
    });

    function trackUpdateFormChanges(event) {
        const field = event.target;
        if (!field.form || field.form.method.toLowerCase() === 'get' || field.type === 'search') return;
        dirtyUpdateForms.add(field.form);
    }

    function activeAttendanceUpdateReason() {
        for (const form of dirtyUpdateForms) {
            if (form.isConnected) return 'Save your changes or leave this page before updating.';
            dirtyUpdateForms.delete(form);
        }
        const cameraActive = Array.from(document.querySelectorAll('video')).some(video =>
            video.srcObject?.getVideoTracks?.().some(track => track.readyState === 'live')
        );
        if (cameraActive) return 'Finish using the camera before updating.';
        const scanner = document.getElementById('studentScannerModal');
        if (scanner && scanner.style.display === 'flex') {
            return 'Close the attendance scanner before updating.';
        }
        if (typeof window.attendanceUpdateGuard === 'function') {
            try { return window.attendanceUpdateGuard() || null; } catch (e) {
                return 'Finish the attendance session before updating.';
            }
        }
        return null;
    }

    async function updateBlockReason() {
        const activeReason = activeAttendanceUpdateReason();
        if (activeReason) return activeReason;
        if (window.OfflineAttendance?.getPendingCount) {
            try {
                const pending = await window.OfflineAttendance.getPendingCount();
                if (pending > 0) {
                    return pending + ' attendance record' + (pending === 1 ? ' is' : 's are') + ' waiting to sync. Sync them before updating.';
                }
            } catch (e) {
                return 'Unable to verify offline attendance records. Please reconnect and try again.';
            }
        }
        return null;
    }

    // ── Toast/Prompt Helper: "Update Available" (When a newer version exists) ──
    async function showUpdateReadyPrompt(version = null, force = false, changelog = null, isManualCheck = false) {
        if (version) latestDetectedVersion = version;

        const targetVersion = version || latestDetectedVersion || getLatestVersion();
        const installedVer = getInstalledVersion();
        let promptMode = updatePromptMode(targetVersion, isManualCheck);

        if (promptMode === 'silent') {
            hideModalElementsIfUpToDate();
            return;
        }

        // A newer build can need applying even when the display version is unchanged.
        if (!isManualCheck && compareSemver(targetVersion, installedVer) <= 0 && !hasUnappliedRelease()) {
            hideModalElementsIfUpToDate();
            return;
        }

        // If elements are not yet parsed into DOM, wait for DOMContentLoaded
        if (!document.body || !document.getElementById('pwaSystemUpdatePopup')) {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    showUpdateReadyPrompt(targetVersion, force, changelog, isManualCheck);
                }, { once: true });
                return;
            }
        }

        ensurePwaModalsInBody();

        const activeWorkReason = await updateBlockReason();
        if (activeWorkReason && !isManualCheck) {
            hideModalElementsIfUpToDate();
            return;
        }
        // A manual check during work can explain the update without trapping
        // the user in a required dialog that prevents saving that same work.
        if (activeWorkReason && isManualCheck) promptMode = 'announcement';

        // Prevent duplicate popup if already visible on screen unless forced or targetVersion changed
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup && (popup.classList.contains('show') || popup.style.display === 'flex') && !force && !isManualCheck
                && popup.dataset.updateMode === promptMode && popup.dataset.updateVersion === targetVersion) {
            return;
        }

        const currentUpdateKey = getUpdateAnnouncementKey(targetVersion);
        // Check dismiss state in sessionStorage (within tab) OR localStorage (across navigations)
        const dismissedTag = readUpdatePreference('pwa_update_dismissed_tag');
        const announcedTag = readUpdatePreference('pwa_update_announced_tag');
        if (!isManualCheck && promptMode !== 'critical' && (dismissedTag === currentUpdateKey || announcedTag === currentUpdateKey)) {
            hideModalElementsIfUpToDate();
            return;
        }

        // Active prompt: hide pill if showing
        hideUpdateFallbackPill();

        if (changelog) {
            updateChangelogUI(changelog);
        } else if (version) {
            const verParam = '?v=' + encodeURIComponent(version);
            fetch('/pwa/version' + verParam, { cache: 'no-store' })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    if (d && d.changelog) {
                        updateChangelogUI(d.changelog);
                    }
                })
                .catch(function() {});
        }

        const titleEl = document.getElementById('pwaUpdateTitle');
        if (titleEl) titleEl.textContent = promptMode === 'critical' ? 'Important update required' : 'Update Available';

        const badge = document.getElementById('pwaUpdateVersionBadge');
        if (badge) {
            const cleanVer = String(targetVersion).trim();
            badge.textContent = cleanVer.toLowerCase().startsWith('v') ? ('Version ' + cleanVer.substring(1)) : ('Version ' + cleanVer);
        }

        const subEl = document.getElementById('pwaUpdateSubtitle');
        if (subEl) subEl.textContent = promptMode === 'critical'
            ? 'Please update to continue with this release. Your work will be protected before refreshing.'
            : compareSemver(targetVersion, installedVer) > 0
            ? 'A new version (Version ' + targetVersion + ') is available to install.'
            : 'A new app update is ready to install.';
        if (subEl && activeWorkReason) subEl.textContent = activeWorkReason;

        const btnText = document.getElementById('pwaApplyUpdateBtnText');
        if (btnText) btnText.textContent = 'Update Now';

        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.toggle('show', promptMode === 'critical');
            backdrop.style.setProperty('display', promptMode === 'critical' ? 'block' : 'none', 'important');
        }

        for (const id of ['pwaLaterUpdateBtn', 'pwaDismissUpdatePopupBtn']) {
            const button = document.getElementById(id);
            if (button) button.style.setProperty('display', promptMode === 'critical' ? 'none' : '', 'important');
        }
        if (popup) {
            popup.dataset.updateMode = promptMode;
            popup.dataset.updateVersion = targetVersion;
            popup.setAttribute('role', promptMode === 'critical' ? 'dialog' : 'region');
            if (promptMode === 'critical') popup.setAttribute('aria-modal', 'true');
            else popup.removeAttribute('aria-modal');
            popup.setAttribute('aria-labelledby', 'pwaUpdateTitle');
            popup.setAttribute('aria-describedby', 'pwaUpdateSubtitle');
            popup.classList.add('show');
            popup.style.setProperty('display', 'flex', 'important');
        }
        if (promptMode === 'announcement' && !isManualCheck) {
            writeUpdatePreference('pwa_update_announced_tag', currentUpdateKey);
        }
        if (promptMode === 'critical') {
            document.getElementById('pwaApplyUpdateBtn')?.focus({ preventScroll: true });
        }
    }

    // Alias for backward compatibility with existing tests and scripts
    function showAppUpdatePopup(version, force = false, changelog = null) {
        return showUpdateReadyPrompt(version, force, changelog, force);
    }

    function hideAppUpdatePopup(version) {
        const targetVersion = version || latestDetectedVersion || getLatestVersion();
        const popup = document.getElementById('pwaSystemUpdatePopup');
        if (popup?.dataset.updateMode === 'critical' && popup.classList.contains('show')) return;
        if (popup) {
            popup.classList.remove('show');
            popup.style.display = 'none';
        }

        const backdrop = document.getElementById('pwaUpdateBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.style.display = 'none';
        }
        hideUpdateFallbackPill();

        const currentUpdateKey = getUpdateAnnouncementKey(targetVersion);
        
        // Suppress this exact release across reloads and browser restarts.
        const dismissNow = String(Date.now());
        writeUpdatePreference('pwa_update_dismissed_ver', targetVersion);
        writeUpdatePreference('pwa_update_dismissed_tag', currentUpdateKey);
        writeUpdatePreference('pwa_update_dismissed_at', dismissNow);
    }

    async function applySystemUpdate() {
        const blocked = await updateBlockReason();
        if (blocked) {
            const subtitle = document.getElementById('pwaUpdateSubtitle');
            if (subtitle) subtitle.textContent = blocked;
            return false;
        }
        const btnText = document.getElementById('pwaApplyUpdateBtnText');
        if (btnText) btnText.textContent = 'Updating...';
        const applyBtn = document.getElementById('pwaApplyUpdateBtn');
        if (applyBtn) {
            const spinIcon = applyBtn.querySelector('.pwa-update-spin-icon');
            if (spinIcon) spinIcon.style.display = 'inline-block';
            applyBtn.style.opacity = '0.85';
            applyBtn.style.pointerEvents = 'none';
        }

        const targetVer = latestDetectedVersion || getLatestVersion();
        const targetKey = getLatestReleaseKey();
        sessionStorage.setItem('pwa_pending_release_key', targetKey);
        sessionStorage.setItem('pwa_pending_version', targetVer);
        sessionStorage.setItem('pwa_updating', 'true');
        // If activation fails, retain a quiet retry pill instead of a modal loop.
        writeUpdatePreference('pwa_update_dismissed_tag', getUpdateAnnouncementKey(targetVer));

        // Cross-tab broadcast
        if (pwaBroadcastChannel) {
            try { 
                pwaBroadcastChannel.postMessage({ type: 'APP_UPDATED', version: targetVer }); 
                pwaBroadcastChannel.postMessage({ type: 'VERSION_CHANGED', version: targetVer });
            } catch(e) {}
        }
        try { localStorage.setItem('pwa_tab_updated_at', String(Date.now())); } catch(e) {}

        // Applying an update is a client/service-worker action. Public clients
        // must never mutate the server's global release metadata.

        // Hide modal and pill
        hideModalElementsIfUpToDate();

        // Signal service worker to skip waiting and clear old caches
        if (swRegistration && swRegistration.waiting) {
            try { swRegistration.waiting.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
        } else if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            try { navigator.serviceWorker.controller.postMessage({ action: 'skipWaiting', type: 'SKIP_WAITING' }); } catch(e) {}
        }

        if (navigator.serviceWorker && navigator.serviceWorker.controller) {
            try { navigator.serviceWorker.controller.postMessage({ action: 'clearCache', type: 'CLEAR_CACHE' }); } catch(e) {}
        }

        // Clear browser caches and reload with cache busting query while preserving existing query params
        let reloaded = false;
        const doReload = async () => {
            if (!reloaded) {
                reloaded = true;
                const reason = await updateBlockReason();
                if (reason) {
                    sessionStorage.removeItem('pwa_updating');
                    sessionStorage.removeItem('pwa_pending_release_key');
                    sessionStorage.removeItem('pwa_pending_version');
                    if (applyBtn) {
                        applyBtn.style.opacity = '';
                        applyBtn.style.pointerEvents = '';
                        const spinIcon = applyBtn.querySelector('.pwa-update-spin-icon');
                        if (spinIcon) spinIcon.style.display = 'none';
                    }
                    await showUpdateReadyPrompt(targetVer, true, currentChangelogData, true);
                    const subtitle = document.getElementById('pwaUpdateSubtitle');
                    if (subtitle) subtitle.textContent = reason;
                    return;
                }
                try {
                    const currentUrl = new URL(window.location.href);
                    currentUrl.searchParams.set('_v', targetVer);
                    currentUrl.searchParams.set('_t', String(Date.now()));
                    window.location.replace(currentUrl.toString());
                } catch(e) {
                    window.location.reload(true);
                }
            }
        };

        if ('caches' in window) {
            caches.keys()
                .then(keys => Promise.all(keys.filter(k => k.startsWith('attendance-')).map(k => caches.delete(k))))
                .then(() => setTimeout(doReload, 300))
                .catch(() => setTimeout(doReload, 200));
        } else {
            setTimeout(doReload, 200);
        }

        // Fail-safe reload if caches.delete hangs
        setTimeout(doReload, 1200);
    }

    async function checkServerVersion(force = false, isManualCheck = false) {
        const now = Date.now();
        if (!force && !isManualCheck && (now - lastVersionCheckTime < VERSION_CHECK_COOLDOWN_MS)) {
            return { upToDate: true };
        }
        if (isCheckingVersion && checkVersionPromise) {
            if (isManualCheck) {
                return checkVersionPromise.then(async result => {
                    if (result?.updateAvailable) await showUpdateReadyPrompt(result.version, true, currentChangelogData, true);
                    return result;
                });
            }
            return checkVersionPromise;
        }
        lastVersionCheckTime = now;
        isCheckingVersion = true;

        checkVersionPromise = (async () => {
            try {
                // Trigger Service Worker update check asynchronously in background WITHOUT blocking
                if (swRegistration) {
                    try { swRegistration.update().catch(() => {}); } catch(e) {}
                }

                const installedVer = getInstalledVersion();
                let latestVer = getLatestVersion();

                let isUpdateAvailable = false;
                let updateChangelog = currentChangelogData;
                let serverData = null;
                let serverChecked = false;

                // Fetch server version with AbortController timeout (6 seconds)
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 6000);

                try {
                    const res = await fetch('/pwa/version?_t=' + now, {
                        cache: 'no-store',
                        headers: { 'Accept': 'application/json' },
                        signal: controller.signal
                    });
                    clearTimeout(timeoutId);

                    if (res.ok) {
                        serverData = await res.json();
                        if (serverData) {
                            serverChecked = true;
                            // A published future version is not installable by a
                            // browser refresh. Compare only the deployed build.
                            latestVer = getLatestVersion(serverData);
                            latestDetectedVersion = latestVer;
                            latestUpdatePolicy = serverData.update || { mode: 'silent', version: latestVer };
                            if (serverData.sw_version) {
                                latestDetectedSwVersion = serverData.sw_version;
                            }
                            if (serverData.build) latestDetectedBuildId = serverData.build;
                            if (serverData.commit) latestDetectedCommitHash = serverData.commit;
                            if (serverData.timestamp) {
                                latestServerTimestamp = serverData.timestamp;
                            }
                            if (serverData.changelog && compareSemver(serverData.changelog.version, latestVer) === 0) {
                                updateChangelog = serverData.changelog;
                                updateChangelogUI(updateChangelog);
                            }

                            // The server's installed_version is a shared release baseline,
                            // not this browser's installed version. Never downgrade local state.
                            const currentActiveVer = installedVer || serverData.installed_version || latestVer;
                            if (currentActiveVer) {
                                const liveVerTag = 'v' + String(currentActiveVer).replace(/^v/i, '');
                                document.querySelectorAll('[data-app-version-tag], #loginAppVersionDesktop, #loginAppVersionMobile, #currentAppReleaseBadge, #pwaCurrentVersionBadge').forEach(el => {
                                    el.textContent = liveVerTag;
                                });
                            }

                            isUpdateAvailable = compareSemver(latestVer, installedVer) > 0 || hasUnappliedRelease();
                        }
                    }
                } catch (fetchErr) {
                    clearTimeout(timeoutId);
                    // Network failure or abort — fail gracefully without breaking UI
                }

                // A waiting worker is not proof of a new release. It may be a
                // previously installed worker for the same server build.

                if (!serverChecked && !isUpdateAvailable) {
                    // A failed/offline check is not evidence that the app is current.
                    return { upToDate: null, checkFailed: true };
                }

                if (isUpdateAvailable) {
                    const targetKey = getLatestReleaseKey();
                    const alreadyAutoReloaded = sessionStorage.getItem('pwa_auto_reloaded_key') === targetKey;
                    const alreadyUpdating = sessionStorage.getItem('pwa_updating') === 'true';

                    if (!isManualCheck && !alreadyAutoReloaded && !alreadyUpdating) {
                        const blockedReason = await updateBlockReason();
                        if (!blockedReason) {
                            try { sessionStorage.setItem('pwa_auto_reloaded_key', targetKey); } catch(e) {}
                            await applySystemUpdate();
                            return { upToDate: false, updateAvailable: true, autoApplied: true, version: latestVer };
                        }
                    }

                    await showUpdateReadyPrompt(latestVer, isManualCheck, updateChangelog, isManualCheck);
                    return { upToDate: false, updateAvailable: true, version: latestVer };
                } else {
                    // Up to date: close modal elements if currently showing, without writing false dismissal cooldown to storage
                    hideModalElementsIfUpToDate();
                    return { upToDate: true, version: installedVer };
                }
            } finally {
                isCheckingVersion = false;
            }
        })();

        return checkVersionPromise;
    }

    // Confirm installation only after the new document has actually loaded.
    try {
        const pendingKey = sessionStorage.getItem('pwa_pending_release_key');
        const pendingVer = sessionStorage.getItem('pwa_pending_version');
        if (pendingKey) {
            sessionStorage.removeItem('pwa_pending_release_key');
            sessionStorage.removeItem('pwa_pending_version');
            sessionStorage.removeItem('pwa_updating');
            if (pendingKey === releaseKey(DOC_BUILD_ID, DOC_COMMIT_HASH, DOC_SW_VER)
                    && compareSemver(getInstalledVersion(), pendingVer) >= 0) {
                localStorage.setItem('pwa_applied_release_key', pendingKey);
                setTimeout(() => showSystemUpdatedToast(pendingVer), 400);
            }
        }
    } catch(e) {}

    // Expose helpers globally
    window.showSystemUpdatedToast = showSystemUpdatedToast;
    window.hideSystemUpdatedToast = hideSystemUpdatedToast;
    window.showUpdateReadyPrompt = showUpdateReadyPrompt;
    window.showAppUpdatePopup = showAppUpdatePopup;
    window.hideAppUpdatePopup = hideAppUpdatePopup;
    window.applySystemUpdate = applySystemUpdate;
    window.checkServerVersion = checkServerVersion;
    window.checkInstantUpdateAvailable = checkInstantUpdateAvailable;
    window.showUpdateFallbackPill = showUpdateFallbackPill;
    window.hideUpdateFallbackPill = hideUpdateFallbackPill;

    // ── Universal Background Lifecycle Triggers Across Desktop & Mobile ──
    // Sync stale localStorage installed version: if the server-rendered meta says we're at the latest,
    // update localStorage so checkInstantUpdateAvailable() doesn't produce a false positive.
    try {
        if (DOC_INSTALLED_VER && DOC_LATEST_VER && compareSemver(DOC_INSTALLED_VER, DOC_LATEST_VER) >= 0) {
            // Server confirms we are up to date — align localStorage with actual installed version
            localStorage.setItem('app_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_installed_version', DOC_INSTALLED_VER);
            localStorage.setItem('pwa_app_version', DOC_INSTALLED_VER);
        }
    } catch(e) {}

    // Only a fresh server response or a waiting service worker can prompt.
    checkServerVersion(false);

    // 2. Initial check when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => checkServerVersion(false));
    } else {
        checkServerVersion(false);
    }

    // Check active tabs often enough that a release is not delayed by a long polling interval.
    // Focus, reconnect, and service-worker signals also trigger an immediate check.
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            checkServerVersion(false);
        }
    }, 20 * 1000);

    // Coalesce lifecycle signals into one immediate check when a user returns.
    // Force bypasses the normal polling cooldown, which may have just elapsed in
    // a backgrounded mobile tab while its interval timer was throttled.
    let resumeVersionCheckTimer = null;
    function checkVersionOnResume() {
        if (document.visibilityState !== 'visible') return;
        if (resumeVersionCheckTimer) clearTimeout(resumeVersionCheckTimer);
        resumeVersionCheckTimer = setTimeout(() => {
            resumeVersionCheckTimer = null;
            checkServerVersion(true);
        }, 250);
    }

    // 4. Tab visibility change (e.g. user returns to the app from another tab/app)
    document.addEventListener('visibilitychange', checkVersionOnResume);

    // 5. Window focus
    window.addEventListener('focus', checkVersionOnResume);

    // 6. Page show (e.g. navigation / mobile bfcache)
    window.addEventListener('pageshow', checkVersionOnResume);

    // 7. Network reconnect
    window.addEventListener('online', checkVersionOnResume);

    // 8. Navigation state changes
    window.addEventListener('popstate', () => {
        checkServerVersion(false);
    });

    // ── 2. Service Worker Registration & Real-Time Sync ──
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            // Only the explicit apply action owns a guarded reload. A worker
            // activation, including one from another tab, never refreshes this page.
            if (sessionStorage.getItem('pwa_updating') !== 'true') {
                checkServerVersion(true);
            }
        });

        const initServiceWorker = async () => {
            try {
                const reg = await navigator.serviceWorker.register('/sw.js?v={{ $swQueryVer }}', { 
                    scope: '/',
                    updateViaCache: 'none'
                });
                swRegistration = reg;

                // Check version on registration
                try { reg.update().catch(() => {}); } catch(e) {}
                checkServerVersion(false);

                // If an update is waiting:
                if (reg.waiting) {
                    checkServerVersion(true);
                }

                // When new update finishes installing:
                reg.addEventListener('updatefound', () => {
                    const newWorker = reg.installing;
                    if (newWorker) {
                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed') {
                                checkServerVersion(true);
                            }
                        });
                    }
                });

                // Listen for broadcast messages from service worker
                navigator.serviceWorker.addEventListener('message', (event) => {
                    if (event.data && (event.data.type === 'UPDATE_AVAILABLE' || event.data.type === 'SW_UPDATED' || event.data.type === 'UPDATE_WAITING')) {
                        console.log('[PWA] Automatic update signal received:', event.data.version);
                        checkServerVersion(true);
                    }
                });

            } catch (err) {
                console.warn('PWA service worker registration notice:', err);
            }
        };

        // Initialize immediately or on DOMContentLoaded — NEVER wait for window.load!
        if (document.readyState !== 'loading') {
            initServiceWorker();
        } else {
            document.addEventListener('DOMContentLoaded', initServiceWorker);
        }
    }

    // ── 3. Standalone State & Universal Install Display Logic ──
    function isNativeAndroidApp() {
        return Boolean(
            (window.AndroidDeviceBridge && typeof window.AndroidDeviceBridge.isNativeApp === 'function' && window.AndroidDeviceBridge.isNativeApp()) ||
            window.AttendancePasskey ||
            /SmartAttendanceApp/i.test(navigator.userAgent || '')
        );
    }

    function checkIsStandalone() {
        return isNativeAndroidApp() ||
               window.matchMedia('(display-mode: standalone)').matches ||
               window.navigator.standalone === true ||
               document.referrer.includes('android-app://') ||
               window.matchMedia('(display-mode: fullscreen)').matches ||
               window.matchMedia('(display-mode: minimal-ui)').matches;
    }

    async function checkIsAppInstalled() {
        if (checkIsStandalone()) return true;
        if (localStorage.getItem('pwa_apk_downloaded') === 'true') return true;
        if ('getInstalledRelatedApps' in navigator) {
            try {
                const related = await navigator.getInstalledRelatedApps();
                if (related && related.length > 0) {
                    localStorage.setItem('pwa_app_installed', 'true');
                    return true;
                }
            } catch(e) {}
        }
        if (deferredPrompt) {
            localStorage.removeItem('pwa_app_installed');
            return false;
        }
        return false;
    }

    function scheduleInstallBanner() {
        @auth
        // Suppress install banner and prompts completely on authenticated dashboard/portal
        return;
        @endauth
        if (checkIsStandalone()) return;
        if (localStorage.getItem('pwa_app_installed') === 'true' || localStorage.getItem('pwa_apk_downloaded') === 'true') return;
        if (sessionStorage.getItem('pwa_banner_dismissed') === 'true') return;
        const dismissedUntil = localStorage.getItem('pwa_banner_dismissed_until');
        if (dismissedUntil && Date.now() < parseInt(dismissedUntil, 10)) return;

        setTimeout(() => {
            if (checkIsStandalone()) return;
            if (localStorage.getItem('pwa_app_installed') === 'true' || localStorage.getItem('pwa_apk_downloaded') === 'true') return;
            const banner = document.getElementById('pwaInstallBanner');
            if (banner && banner.style.display !== 'flex') {
                banner.style.display = 'flex';
            }
        }, 2200);
    }

    function hideInstallBanner(dismissDays = 0) {
        const banner = document.getElementById('pwaInstallBanner');
        if (banner) banner.style.display = 'none';
        sessionStorage.setItem('pwa_banner_dismissed', 'true');
        if (dismissDays > 0) {
            localStorage.setItem('pwa_banner_dismissed_until', String(Date.now() + dismissDays * 86400000));
        }
    }

    function syncPwaInstallVisibility() {
        const triggers = document.querySelectorAll('.pwa-install-trigger');
        const banner = document.getElementById('pwaInstallBanner');
        const downloadRow = document.getElementById('smartAppDownloadRow');
        @auth
        // On authenticated dashboard/portal: ensure no install triggers or banner are visible
        triggers.forEach(el => { el.style.setProperty('display', 'none', 'important'); el.style.visibility = 'hidden'; });
        if (banner) banner.style.display = 'none';
        if (downloadRow) downloadRow.style.display = 'none';
        @else
        const standalone = checkIsStandalone();
        const installedFlag = localStorage.getItem('pwa_app_installed') === 'true' || localStorage.getItem('pwa_apk_downloaded') === 'true';
        if (standalone || installedFlag) {
            triggers.forEach(el => { el.style.setProperty('display', 'none', 'important'); el.style.visibility = 'hidden'; });
            if (banner) banner.style.display = 'none';
            if (downloadRow) downloadRow.style.display = 'none';
            return;
        }

        if ('getInstalledRelatedApps' in navigator) {
            navigator.getInstalledRelatedApps().then(related => {
                if (related && related.length > 0) {
                    localStorage.setItem('pwa_app_installed', 'true');
                    triggers.forEach(el => { el.style.setProperty('display', 'none', 'important'); el.style.visibility = 'hidden'; });
                    if (banner) banner.style.display = 'none';
                    if (downloadRow) downloadRow.style.display = 'none';
                }
            }).catch(() => {});
        }

        triggers.forEach(el => {
            if (el.id === 'pwaBannerInstallBtn') return;
            el.style.setProperty('display', el.getAttribute('data-display') || 'inline-flex', 'important');
            el.style.visibility = 'visible';
        });

        scheduleInstallBanner();
        @endauth
    }

    // Initialize display when DOM is ready and window loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncPwaInstallVisibility);
    } else {
        syncPwaInstallVisibility();
    }
    window.addEventListener('load', syncPwaInstallVisibility);

    // ── 4. Capture Native beforeinstallprompt Event ──
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        if (!checkIsStandalone() && localStorage.getItem('pwa_apk_downloaded') !== 'true') {
            localStorage.removeItem('pwa_app_installed');
        }
        console.log('[PWA] Native install prompt captured and ready.');
        syncPwaInstallVisibility();
    });

    // ── 5. Redesigned Guide Modal Builder & Download Handler ──
    let isDownloadingApk = false;

    function handleHeroCtaClick(btn) {
        if (!btn) return;
        const action = btn.getAttribute('data-action') || 'download';

        // 1. If already installed: open application
        if (action === 'open') {
            closePwaGuideModal();
            window.location.href = '/home';
            return;
        }

        // 2. If desktop / browser native prompt is available
        if (action === 'native_prompt') {
            if (deferredPrompt) {
                closePwaGuideModal();
                triggerPwaInstall();
            } else {
                showNetworkToast('Follow the installation steps below', 'online');
            }
            return;
        }

        // 3. If guide action (iOS, desktop browser menu)
        if (action === 'guide') {
            const stepsSec = document.getElementById('pwaMiniStepsSection');
            if (stepsSec) {
                stepsSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            return;
        }

        // 4. If direct APK download (Android / Default)
        if (action === 'download') {
            if (isDownloadingApk) return;
            isDownloadingApk = true;

            const apkUrl = btn.getAttribute('data-apk-url') || '/download/apk';
            const labelEl = document.getElementById('pwaDlLabel');
            const iconEl = document.getElementById('pwaDlIcon');
            const progressEl = document.getElementById('pwaDlProgressBar');
            const alertEl = document.getElementById('pwaDlStatusAlert');

            // 1. Set DOWNLOADING state
            if (labelEl) labelEl.textContent = 'DOWNLOADING...';
            if (iconEl) iconEl.className = 'bi bi-arrow-repeat pwa-dl-icon pwa-spin-fast';
            if (progressEl) {
                progressEl.style.transition = 'width 0.75s ease-out';
                progressEl.style.width = '70%';
            }

            if (window.triggerHaptic) {
                window.triggerHaptic('light');
            } else if (navigator.vibrate) {
                navigator.vibrate(50);
            }

            // 2. After simulated packaging delay, trigger file download & complete progress
            setTimeout(() => {
                const tempLink = document.createElement('a');
                tempLink.href = apkUrl;
                tempLink.download = 'SmartAttendance.apk';
                tempLink.style.display = 'none';
                document.body.appendChild(tempLink);
                tempLink.click();
                setTimeout(() => { tempLink.remove(); }, 300);

                if (progressEl) {
                    progressEl.style.width = '100%';
                }

                // DOWNLOAD STARTED state
                if (labelEl) labelEl.textContent = 'DOWNLOAD STARTED';
                if (iconEl) iconEl.className = 'bi bi-check-circle-fill pwa-dl-icon';

                // Reveal status banner
                if (alertEl) {
                    alertEl.style.display = 'flex';
                }

                // Mark APK as downloaded so the floating install banner stops appearing.
                try {
                    localStorage.setItem('pwa_apk_downloaded', 'true');
                    localStorage.setItem('pwa_app_installed', 'true');
                } catch (e) {}
                hideInstallBanner(30);
                syncPwaInstallVisibility();

                if (window.triggerHaptic) {
                    window.triggerHaptic('success');
                } else if (navigator.vibrate) {
                    navigator.vibrate([60, 40, 60]);
                }

                // After 3.5s, ready to download again if needed
                setTimeout(() => {
                    if (progressEl) {
                        progressEl.style.transition = 'none';
                        progressEl.style.width = '0%';
                    }
                    if (labelEl) labelEl.textContent = 'Download Again';
                    if (iconEl) iconEl.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
                    isDownloadingApk = false;
                }, 3500);
            }, 800);
        }
    }

    async function checkAppInstallStateAgain(btn) {
        if (btn) {
            btn.style.opacity = '0.7';
            btn.disabled = true;
        }

        if ('serviceWorker' in navigator && swRegistration) {
            try { await swRegistration.update(); } catch(e) {}
        }

        const isStandalone = checkIsStandalone();
        const isInstalled = isStandalone || await checkIsAppInstalled();

        if (btn) {
            btn.style.opacity = '1';
            btn.disabled = false;
        }

        if (isInstalled) {
            showNetworkToast('✓ Smart Attendance is installed!', 'online');
            showPwaGuideModal('already_installed');
            return;
        }

        if (deferredPrompt) {
            closePwaGuideModal();
            triggerPwaInstall();
            return;
        }

        showNetworkToast('Checking app... Open your Downloads folder and tap the APK to install.', 'online');
    }

    function showPwaGuideModal(forceMode = null) {
        ensurePwaModalsInBody();
        const modal = document.getElementById('pwaIosModal');
        const titleEl = document.getElementById('pwaModalTitle');
        const badgeEl = document.getElementById('pwaHeroBadge');
        const verifiedEl = document.getElementById('pwaHeroVerified');
        const heroTitleEl = document.getElementById('pwaHeroTitle');
        const versionEl = document.getElementById('pwaHeroVersion');
        const sizeEl = document.getElementById('pwaHeroSize');
        const platformEl = document.getElementById('pwaHeroPlatform');
        const taglineEl = document.getElementById('pwaHeroTagline');
        const heroDlBtn = document.getElementById('pwaModalDownloadApkBtn');
        const dlIcon = document.getElementById('pwaDlIcon');
        const dlLabel = document.getElementById('pwaDlLabel');
        const dlProgressBar = document.getElementById('pwaDlProgressBar');
        const dlStatusAlert = document.getElementById('pwaDlStatusAlert');
        const heroTrustText = document.getElementById('pwaHeroTrustText');
        const stepsHeader = document.getElementById('pwaStepsHeaderTitle');
        const stepsGrid = document.getElementById('pwaModalSteps');
        const helpDetails = document.getElementById('pwaHelpDetails');
        const helpContent = document.getElementById('pwaHelpContent');
        const closeBtn = document.getElementById('pwaIosCloseBtn');
        const checkBtn = document.getElementById('pwaResetStateBtn');

        if (!modal) return;

        // Reset previous download progress and banner state on each open
        if (dlProgressBar) {
            dlProgressBar.style.transition = 'none';
            dlProgressBar.style.width = '0%';
        }
        if (dlStatusAlert) {
            dlStatusAlert.style.display = 'none';
        }
        if (helpDetails) {
            helpDetails.removeAttribute('open');
        }

        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        const isIosSafari = isIos && !ua.includes('crios') && !ua.includes('fxios') && !ua.includes('edgios');
        const isInApp = /fban|fbav|fb_iab|fbios|instagram|line\/|twitter|snapchat|micromessenger|tiktok|kakaotalk|threads/i.test(ua);
        const isSamsung = ua.includes('samsungbrowser');
        const isAndroid = /android/.test(ua);
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';

        // Default close behavior
        if (closeBtn) {
            closeBtn.textContent = 'Got It';
            closeBtn.onclick = (e) => {
                e.preventDefault();
                closePwaGuideModal();
            };
        }

        if (forceMode === 'already_installed' || checkIsStandalone()) {
            if (titleEl) titleEl.textContent = 'App Installed';
            if (badgeEl) {
                badgeEl.textContent = '✓ Installed';
                badgeEl.style.color = '#4ADE80';
                badgeEl.style.borderColor = 'rgba(74, 222, 128, 0.35)';
                badgeEl.style.background = 'rgba(74, 222, 128, 0.12)';
            }
            if (taglineEl) taglineEl.textContent = 'Smart Classroom Attendance is installed and ready to use.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'open');
                heroDlBtn.style.background = 'linear-gradient(135deg, #22C55E 0%, #16A34A 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Open App';
            if (dlIcon) dlIcon.className = 'bi bi-box-arrow-up-right pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Application Ready';
            if (stepsHeader) stepsHeader.textContent = 'QUICK ACCESS';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#22C55E;color:#0E0609;">1</div>
                        <div class="pwa-mini-step-label">DRAWER</div>
                        <div class="pwa-mini-step-hint">Swipe up for icon</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#CFA46F;color:#0E0609;">2</div>
                        <div class="pwa-mini-step-label">SHORTCUT</div>
                        <div class="pwa-mini-step-hint">Long-press to Home</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#CFA46F;color:#0E0609;">3</div>
                        <div class="pwa-mini-step-label">INSTANT</div>
                        <div class="pwa-mini-step-hint">Biometrics &amp; QR</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find App:</strong> Swipe up into your App Drawer and search for "Smart Attendance".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Add Shortcut:</strong> Long-press the icon and tap "Add to Home".</div>
                    </div>
                `;
            }
            if (closeBtn) closeBtn.textContent = 'Close';

        } else if (forceMode === 'insecure_context' || !isSecure) {
            if (titleEl) titleEl.textContent = 'HTTPS Required';
            if (badgeEl) {
                badgeEl.textContent = 'Security Note';
                badgeEl.style.color = '#F87171';
                badgeEl.style.borderColor = 'rgba(239, 68, 68, 0.35)';
                badgeEl.style.background = 'rgba(239, 68, 68, 0.12)';
            }
            if (taglineEl) taglineEl.textContent = 'Browsers require a secure HTTPS connection or localhost to install web apps.';
            if (heroDlBtn) heroDlBtn.setAttribute('data-action', 'guide');
            if (dlLabel) dlLabel.textContent = 'HTTPS Setup Needed';
            if (dlIcon) dlIcon.className = 'bi bi-shield-exclamation pwa-dl-icon';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO ACCESS';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num" style="background:#EF4444;color:#FFFFFF;">!</div>
                        <div class="pwa-mini-step-label">HTTP</div>
                        <div class="pwa-mini-step-hint">Unencrypted</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">LOCAL</div>
                        <div class="pwa-mini-step-hint">Use localhost</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">TUNNEL</div>
                        <div class="pwa-mini-step-hint">HTTPS tunnel</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>On PC / Laptop:</strong> Access using <code>http://localhost:8002</code> instead of a local network IP.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>On Mobile:</strong> Connect via an HTTPS tunnel (Cloudflare or ngrok).</div>
                    </div>
                `;
            }
            if (closeBtn) closeBtn.textContent = 'Close';

        } else if (isInApp) {
            if (titleEl) titleEl.textContent = 'Smart Attendance App';
            if (badgeEl) badgeEl.textContent = 'Application';
            if (taglineEl) taglineEl.textContent = 'Install or download the app for the best standalone experience.';
            if (heroDlBtn) heroDlBtn.setAttribute('data-action', 'download');
            if (dlLabel) dlLabel.textContent = 'Download APK';
            if (dlIcon) dlIcon.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
            if (stepsHeader) stepsHeader.textContent = 'GET APP';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">DOWNLOAD</div>
                        <div class="pwa-mini-step-hint">Tap Download APK</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">INSTALL</div>
                        <div class="pwa-mini-step-hint">Open and install</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">LAUNCH</div>
                        <div class="pwa-mini-step-hint">Launch direct app</div>
                    </div>
                `;
            }

        } else if (isIosSafari || isIos) {
            if (titleEl) titleEl.textContent = 'Install on iOS';
            if (badgeEl) {
                badgeEl.textContent = 'iOS Web App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'iOS';
            if (sizeEl) sizeEl.textContent = 'PWA';
            if (taglineEl) taglineEl.textContent = 'Add to your Home Screen for faster clock-in & biometric access.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'guide');
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Follow Steps Below';
            if (dlIcon) dlIcon.className = 'bi bi-phone pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Web Application';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">SHARE</div>
                        <div class="pwa-mini-step-hint">Tap Safari Share</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">ADD</div>
                        <div class="pwa-mini-step-hint">Add to Home Screen</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">OPEN</div>
                        <div class="pwa-mini-step-hint">Launch from Home</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Must Use Safari:</strong> On iOS, Chrome and third-party browsers cannot directly add PWAs to the Home Screen. Open in Safari to install.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find "Add to Home Screen":</strong> In Safari, tap the Share icon and scroll down in the action sheet.</div>
                    </div>
                `;
            }

        } else if (!isAndroid) {
            // Desktop PC / Mac / Linux
            if (titleEl) titleEl.textContent = 'Install on Computer';
            if (badgeEl) {
                badgeEl.textContent = 'Desktop Web App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'Desktop';
            if (sizeEl) sizeEl.textContent = 'PWA';
            if (taglineEl) taglineEl.textContent = 'Install directly onto your computer for rapid desktop access.';
            if (heroDlBtn) {
                if (deferredPrompt) {
                    heroDlBtn.setAttribute('data-action', 'native_prompt');
                    if (dlLabel) dlLabel.textContent = 'Install App';
                    if (dlIcon) dlIcon.className = 'bi bi-download pwa-dl-icon';
                } else {
                    heroDlBtn.setAttribute('data-action', 'guide');
                    if (dlLabel) dlLabel.textContent = 'Install via Browser';
                    if (dlIcon) dlIcon.className = 'bi bi-laptop pwa-dl-icon';
                }
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (heroTrustText) heroTrustText.textContent = 'Official Web Application';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">BAR</div>
                        <div class="pwa-mini-step-hint">Click ⊕ / ⬇ in URL bar</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">CONFIRM</div>
                        <div class="pwa-mini-step-hint">Click Install in popup</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">LAUNCH</div>
                        <div class="pwa-mini-step-hint">Open from Start / Apps</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Windows Start Menu:</strong> Installed apps appear in your Start Menu. Search for "Smart Attendance".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Desktop Shortcut:</strong> Open <code>chrome://apps</code> (or <code>brave://apps</code>), right-click Smart Attendance, and select <strong>Create shortcuts &gt; Desktop</strong>.</div>
                    </div>
                `;
            }

        } else {
            // Android Default
            if (titleEl) titleEl.textContent = 'Get Mobile App';
            if (badgeEl) {
                badgeEl.textContent = 'Official Android App';
                badgeEl.style.color = '#E8C064';
                badgeEl.style.borderColor = 'rgba(207, 164, 111, 0.3)';
                badgeEl.style.background = 'rgba(207, 164, 111, 0.15)';
            }
            if (platformEl) platformEl.textContent = 'Android';
            if (sizeEl) sizeEl.textContent = '~4 MB';
            if (taglineEl) taglineEl.textContent = 'Install for faster clock-in, biometric access &amp; instant alerts.';
            if (heroDlBtn) {
                heroDlBtn.setAttribute('data-action', 'download');
                heroDlBtn.style.background = 'linear-gradient(135deg, #E8C064 0%, #CFA46F 100%)';
            }
            if (dlLabel) dlLabel.textContent = 'Download APK';
            if (dlIcon) dlIcon.className = 'bi bi-arrow-down-circle-fill pwa-dl-icon';
            if (heroTrustText) heroTrustText.textContent = 'Official Smart Classroom Attendance App';
            if (stepsHeader) stepsHeader.textContent = 'HOW TO INSTALL';
            if (stepsGrid) {
                stepsGrid.innerHTML = `
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">1</div>
                        <div class="pwa-mini-step-label">DOWNLOAD</div>
                        <div class="pwa-mini-step-hint">Tap Download APK</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">2</div>
                        <div class="pwa-mini-step-label">INSTALL</div>
                        <div class="pwa-mini-step-hint">Open file &amp; tap Install</div>
                    </div>
                    <div class="pwa-mini-step-box">
                        <div class="pwa-mini-step-num">3</div>
                        <div class="pwa-mini-step-label">OPEN</div>
                        <div class="pwa-mini-step-hint">Launch from Drawer</div>
                    </div>
                `;
            }
            if (helpContent) {
                helpContent.innerHTML = `
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Samsung / Pixel:</strong> Open the downloaded APK and tap Install. The icon appears in your App Drawer (swipe up). Long-press to add to Home.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Xiaomi / Redmi / Poco:</strong> If home screen shortcuts are blocked: Settings &gt; Apps &gt; Permissions &gt; enable "Home screen shortcuts".</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Browser Warning:</strong> Tap "Details" or "Download anyway" if your browser displays a standard prompt for direct APK files.</div>
                    </div>
                    <div class="pwa-help-item">
                        <span class="pwa-help-bullet"></span>
                        <div><strong>Can't Find Download:</strong> Open your phone's <strong>Files</strong> or <strong>Downloads</strong> app and tap <code>SmartAttendance.apk</code>.</div>
                    </div>
                `;
            }
        }

        const apkBannerBtn = document.getElementById('pwaBannerApkBtn');
        if (isAndroid && apkBannerBtn) {
            apkBannerBtn.style.display = 'inline-flex';
        }

        modal.style.display = 'flex';
    }

    function closePwaGuideModal() {
        const modal = document.getElementById('pwaIosModal');
        if (modal) modal.style.display = 'none';
        localStorage.setItem('pwa_ios_prompt_dismissed', 'true');
    }

    function resetPwaStateAndRetry() {
        checkAppInstallStateAgain(document.getElementById('pwaResetStateBtn'));
    }

    // ── 6. Universal Trigger Install Handler ──
    async function triggerPwaInstall(triggerEl) {
        if (window.__pwaActionInProgress) return;
        window.__pwaActionInProgress = true;
        setTimeout(() => { window.__pwaActionInProgress = false; }, 1500);

        // 1. Check if ALREADY open in standalone window
        if (checkIsStandalone()) {
            showPwaGuideModal('already_installed');
            return;
        }

        // 2. Security / HTTPS check
        const isSecure = window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        if (!isSecure) {
            showPwaGuideModal('insecure_context');
            return;
        }

        // 3. Native prompt immediately available: ALWAYS prioritize native prompt
        if (deferredPrompt) {
            try {
                if (triggerEl) triggerEl.classList.add('pwa-btn-loading');
                const promptEvent = deferredPrompt;
                deferredPrompt = null;
                promptEvent.prompt();
                const choice = await promptEvent.userChoice;
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
                
                if (choice && choice.outcome === 'accepted') {
                    localStorage.setItem('pwa_app_installed', 'true');
                    hideInstallBanner(30);
                    const isAndroidDevice = /android/.test(ua);
                    if (isAndroidDevice) {
                        showNetworkToast('✓ Installed! Swipe UP to open App Drawer, then long-press app & tap "Add to Home".', 'online');
                    } else if (!isIos) {
                        showNetworkToast('✓ Installed! Find it in Windows Start Menu or open chrome://apps for a Desktop shortcut.', 'online');
                    } else {
                        showNetworkToast('✓ Smart Attendance installed successfully!', 'online');
                    }
                    syncPwaInstallVisibility();
                    return;
                } else {
                    showNetworkToast('Installation postponed. You can install anytime from the menu.', 'offline');
                    return;
                }
            } catch(err) {
                console.warn('[PWA] Prompt error:', err);
                if (triggerEl) triggerEl.classList.remove('pwa-btn-loading');
            }
        }

        // 4. If deferredPrompt not yet available, wait briefly in case beforeinstallprompt is in flight
        const ua = (window.navigator.userAgent || '').toLowerCase();
        const isIos = /iphone|ipad|ipod/.test(ua);
        if (!deferredPrompt && !isIos && isSecure && !window.__pwaWaitedBeforePrompt) {
            window.__pwaWaitedBeforePrompt = true;
            if (triggerEl) {
                const originalHtml = triggerEl.innerHTML;
                triggerEl.innerHTML = '<span class="pwa-spinner"></span> Opening installer...';
                triggerEl.classList.add('pwa-btn-loading');

                const promptFired = await Promise.race([
                    new Promise(resolve => {
                        const handler = () => {
                            window.removeEventListener('beforeinstallprompt', handler);
                            resolve(true);
                        };
                        window.addEventListener('beforeinstallprompt', handler, { once: true });
                    }),
                    new Promise(resolve => setTimeout(() => resolve(false), 900))
                ]);

                triggerEl.innerHTML = originalHtml;
                triggerEl.classList.remove('pwa-btn-loading');

                if (promptFired && deferredPrompt) {
                    try {
                        const promptEvent = deferredPrompt;
                        deferredPrompt = null;
                        promptEvent.prompt();
                        const choice = await promptEvent.userChoice;
                        if (choice && choice.outcome === 'accepted') {
                            localStorage.setItem('pwa_app_installed', 'true');
                            hideInstallBanner(30);
                            showNetworkToast('✓ Smart Attendance installed! Check your App Drawer (swipe up) or Home screen.', 'online');
                            syncPwaInstallVisibility();
                            return;
                        } else {
                            showNetworkToast('Installation postponed.', 'offline');
                            return;
                        }
                    } catch(e) {}
                }
            }
        }

        // 5. Show browser-tailored guide modal (iOS Safari, desktop address bar, Android menu, etc.)
        showPwaGuideModal();
    }

    // Expose helpers globally
    window.triggerPwaInstall = triggerPwaInstall;
    window.showPwaGuideModal = showPwaGuideModal;
    window.closePwaGuideModal = closePwaGuideModal;
    window.checkIsStandalone = checkIsStandalone;

    // ── 7. Global Event Delegation (Guarantees ALL install buttons work anytime) ──
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closePwaGuideModal();
        }
    });

    document.addEventListener('click', (e) => {
        const target = e.target;
        if (!target) return;

        // Install button clicked
        const installTrigger = target.closest('.pwa-install-trigger') || target.closest('#pwaInstallBtn') || target.closest('#downloadAppBtn');
        if (installTrigger) {
            e.preventDefault();
            e.stopPropagation();
            triggerPwaInstall(installTrigger);
            return;
        }

        // Close floating banner
        const closeBannerTrigger = target.closest('#pwaBannerCloseBtn');
        if (closeBannerTrigger) {
            e.preventDefault();
            hideInstallBanner(3);
            return;
        }

        // Direct APK download links (login page or floating banner)
        const apkDirectLink = target.closest('a[download="SmartAttendance.apk"], #pwaBannerApkBtn');
        if (apkDirectLink) {
            try {
                localStorage.setItem('pwa_apk_downloaded', 'true');
                localStorage.setItem('pwa_app_installed', 'true');
            } catch (err) {}
            hideInstallBanner(30);
            syncPwaInstallVisibility();
            return;
        }

        // Download APK / Hero CTA in redesigned modal
        const heroCtaTrigger = target.closest('#pwaModalDownloadApkBtn');
        if (heroCtaTrigger) {
            e.preventDefault();
            e.stopPropagation();
            handleHeroCtaClick(heroCtaTrigger);
            return;
        }

        // Check Again button in modal
        const resetTrigger = target.closest('#pwaResetStateBtn');
        if (resetTrigger) {
            e.preventDefault();
            e.stopPropagation();
            checkAppInstallStateAgain(resetTrigger);
            return;
        }

        // Close guide modal
        const closeTrigger = target.closest('#pwaIosCloseBtn') || target.closest('#pwaModalCloseIcon');
        if (closeTrigger) {
            e.preventDefault();
            closePwaGuideModal();
            return;
        }

        // Modal backdrop click
        const iosModal = document.getElementById('pwaIosModal');
        if (iosModal && target === iosModal) {
            closePwaGuideModal();
            return;
        }

        // Dismiss "System Updated ✓" Toast
        const dismissUpdatedToast = target.closest('#pwaDismissUpdatedToastBtn');
        if (dismissUpdatedToast) {
            e.preventDefault();
            hideSystemUpdatedToast();
            return;
        }

        // Floating update pill clicked
        const pillTrigger = target.closest('#pwaUpdatePill');
        if (pillTrigger) {
            e.preventDefault();
            e.stopPropagation();
            hideUpdateFallbackPill();
            showUpdateReadyPrompt(latestDetectedVersion, true, currentChangelogData, true);
            return;
        }

        // Apply Update button clicked ("Refresh Now")
        const applyUpdateBtn = target.closest('#pwaApplyUpdateBtn');
        if (applyUpdateBtn) {
            e.preventDefault();
            applySystemUpdate();
            return;
        }

        // Dismiss Update Ready prompt (Dismiss for current session)
        const dismissUpdateBtn = target.closest('#pwaDismissUpdatePopupBtn') || target.closest('#pwaLaterUpdateBtn') || (target.id === 'pwaUpdateBackdrop');
        if (dismissUpdateBtn) {
            e.preventDefault();
            hideAppUpdatePopup(latestDetectedVersion);
            return;
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        const installBanner = document.getElementById('pwaInstallBanner');
        const iosModal = document.getElementById('pwaIosModal');
        const downloadRow = document.getElementById('smartAppDownloadRow');
        if (installBanner) installBanner.style.display = 'none';
        if (iosModal) iosModal.style.display = 'none';
        if (downloadRow) downloadRow.style.display = 'none';
        localStorage.setItem('pwa_prompt_dismissed', 'true');
        localStorage.setItem('pwa_app_installed', 'true');
        document.querySelectorAll('.pwa-install-trigger').forEach(el => {
            el.style.display = 'none';
        });
        const currentUa = (window.navigator.userAgent || '').toLowerCase();
        if (/android/.test(currentUa)) {
            showNetworkToast('✓ Smart Attendance installed! Check your App Drawer (swipe up) to add it to Home.', 'online');
        } else if (!/iphone|ipad|ipod/.test(currentUa)) {
            showNetworkToast('✓ Smart Attendance installed! Find it in Windows Start Menu or open chrome://apps for Desktop shortcut.', 'online');
        }
    });

    // ── 7. Real-time Network Connectivity Notifications ──
    const networkToast = document.getElementById('pwaNetworkToast');
    function showNetworkToast(message, type) {
        const toast = document.getElementById('pwaNetworkToast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = `pwa-network-toast show ${type}`;
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3500);
    }

    window.addEventListener('offline', () => {
        showNetworkToast('⚡ You are currently offline. Offline mode active.', 'offline');
    });

    window.addEventListener('online', () => {
        showNetworkToast('✓ Connection restored! Back online.', 'online');
    });

    // ── 8. Cache User Session for Offline Mode ──
    @auth
        @php
            $offlineUserProfile = [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
                'role' => auth()->user()->role,
                'email' => auth()->user()->email,
                'student_number' => auth()->user()->student_number ?? '',
                'course' => auth()->user()->course ?? '',
                'year_level' => auth()->user()->year_level ?? '',
                'section' => auth()->user()->section ?? '',
                'timestamp' => now()->toIso8601String(),
            ];
        @endphp
        try {
            const userProfile = @json($offlineUserProfile);
            localStorage.setItem('cached_user_profile', JSON.stringify(userProfile));
            localStorage.setItem('cached_student_profile', JSON.stringify(userProfile));
            const metaCsrf = document.querySelector('meta[name="csrf-token"]')?.content;
            if (metaCsrf) {
                localStorage.setItem('cached_csrf_token', metaCsrf);
            }
        } catch(e) {}
    @endauth
</script>
