/**
 * Offline Attendance Module
 * ─────────────────────────
 * IndexedDB-based offline attendance recording with automatic sync.
 * Only available to authenticated teachers.
 *
 * Usage:
 *   OfflineAttendance.init({ csrfToken, teacherId });
 *   OfflineAttendance.saveRecord({ ... });
 *   OfflineAttendance.syncNow();
 */
(function(window) {
    'use strict';

    const DB_NAME = 'offline_attendance_db';
    const DB_VERSION = 1;
    const STORE_RECORDS = 'pending_records';
    const STORE_ROSTER = 'roster_cache';
    const SYNC_ENDPOINT = '/teacher/offline-attendance/sync';
    const ROSTER_ENDPOINT = '/teacher/offline-attendance/roster';
    const PING_ENDPOINT = '/api/ping';
    const BATCH_SIZE = 20;
    const PING_INTERVAL_MS = 15000;      // Check connectivity every 15s
    const RETRY_BASE_MS = 30000;         // 30s base retry
    const MAX_RETRY_MS = 300000;         // 5 min max
    const SYNCED_RETENTION_DAYS = 7;     // Auto-clean synced records after 7 days

    let db = null;
    let csrfToken = '';
    let teacherId = null;
    let connectionState = navigator.onLine ? 'online' : 'offline'; // 'online' | 'offline' | 'syncing'
    let pingTimer = null;
    let syncTimer = null;
    let isSyncing = false;
    let initialized = false;

    // ──────────────────────────────────────────────
    //  IndexedDB Setup
    // ──────────────────────────────────────────────
    function openDB() {
        return new Promise(function(resolve, reject) {
            if (db) { resolve(db); return; }
            var request = indexedDB.open(DB_NAME, DB_VERSION);
            request.onupgradeneeded = function(e) {
                var database = e.target.result;
                if (!database.objectStoreNames.contains(STORE_RECORDS)) {
                    var store = database.createObjectStore(STORE_RECORDS, { keyPath: 'local_id', autoIncrement: true });
                    store.createIndex('sync_status', 'sync_status', { unique: false });
                    store.createIndex('subject_code', 'subject_code', { unique: false });
                    store.createIndex('date', 'date', { unique: false });
                    store.createIndex('created_at', 'created_at', { unique: false });
                }
                if (!database.objectStoreNames.contains(STORE_ROSTER)) {
                    database.createObjectStore(STORE_ROSTER, { keyPath: 'code' });
                }
            };
            request.onsuccess = function(e) {
                db = e.target.result;
                resolve(db);
            };
            request.onerror = function(e) {
                console.error('[OfflineAttendance] IndexedDB error:', e.target.error);
                reject(e.target.error);
            };
        });
    }

    // ──────────────────────────────────────────────
    //  Record CRUD
    // ──────────────────────────────────────────────
    function saveRecord(record) {
        return openDB().then(function(database) {
            return new Promise(function(resolve, reject) {
                var tx = database.transaction(STORE_RECORDS, 'readwrite');
                var store = tx.objectStore(STORE_RECORDS);
                var data = Object.assign({}, record, {
                    teacher_id: teacherId,
                    sync_status: 'pending',
                    sync_attempts: 0,
                    sync_error: null,
                    created_at: new Date().toISOString(),
                    updated_at: new Date().toISOString(),
                });
                var req = store.add(data);
                req.onsuccess = function() {
                    data.local_id = req.result;
                    resolve(data);
                    updateUI();
                    dispatchEvent('offline-attendance-saved', data);
                };
                req.onerror = function() { reject(req.error); };
            });
        });
    }

    function getPendingRecords() {
        return openDB().then(function(database) {
            return new Promise(function(resolve, reject) {
                var tx = database.transaction(STORE_RECORDS, 'readonly');
                var store = tx.objectStore(STORE_RECORDS);
                var index = store.index('sync_status');
                var req = index.getAll('pending');
                req.onsuccess = function() { resolve(req.result || []); };
                req.onerror = function() { reject(req.error); };
            });
        });
    }

    function getFailedRecords() {
        return openDB().then(function(database) {
            return new Promise(function(resolve, reject) {
                var tx = database.transaction(STORE_RECORDS, 'readonly');
                var store = tx.objectStore(STORE_RECORDS);
                var index = store.index('sync_status');
                var req = index.getAll('failed');
                req.onsuccess = function() { resolve(req.result || []); };
                req.onerror = function() { reject(req.error); };
            });
        });
    }

    function getPendingCount() {
        return Promise.all([getPendingRecords(), getFailedRecords()]).then(function(results) {
            return results[0].length + results[1].length;
        });
    }

    function updateRecordStatus(localId, status, error) {
        return openDB().then(function(database) {
            return new Promise(function(resolve, reject) {
                var tx = database.transaction(STORE_RECORDS, 'readwrite');
                var store = tx.objectStore(STORE_RECORDS);
                var req = store.get(localId);
                req.onsuccess = function() {
                    var rec = req.result;
                    if (!rec) { resolve(null); return; }
                    rec.sync_status = status;
                    rec.updated_at = new Date().toISOString();
                    if (error) rec.sync_error = error;
                    if (status === 'failed') rec.sync_attempts = (rec.sync_attempts || 0) + 1;
                    if (status === 'synced') rec.synced_at = new Date().toISOString();
                    var putReq = store.put(rec);
                    putReq.onsuccess = function() { resolve(rec); };
                    putReq.onerror = function() { reject(putReq.error); };
                };
                req.onerror = function() { reject(req.error); };
            });
        });
    }

    function cleanupSyncedRecords() {
        var cutoff = new Date();
        cutoff.setDate(cutoff.getDate() - SYNCED_RETENTION_DAYS);

        return openDB().then(function(database) {
            return new Promise(function(resolve) {
                var tx = database.transaction(STORE_RECORDS, 'readwrite');
                var store = tx.objectStore(STORE_RECORDS);
                var index = store.index('sync_status');
                var req = index.openCursor('synced');
                var count = 0;
                req.onsuccess = function(e) {
                    var cursor = e.target.result;
                    if (cursor) {
                        var rec = cursor.value;
                        if (rec.synced_at && new Date(rec.synced_at) < cutoff) {
                            cursor.delete();
                            count++;
                        }
                        cursor.continue();
                    } else {
                        if (count > 0) console.log('[OfflineAttendance] Cleaned', count, 'old synced records');
                        resolve(count);
                    }
                };
                req.onerror = function() { resolve(0); };
            });
        });
    }

    // ──────────────────────────────────────────────
    //  Connectivity Detection
    // ──────────────────────────────────────────────
    function checkServerReachable() {
        return fetch(PING_ENDPOINT, {
            method: 'GET',
            cache: 'no-store',
            signal: AbortSignal.timeout ? AbortSignal.timeout(5000) : undefined,
        }).then(function(resp) {
            return resp.ok;
        }).catch(function() {
            return false;
        });
    }

    function setConnectionState(newState) {
        var old = connectionState;
        connectionState = newState;
        if (old !== newState) {
            updateUI();
            dispatchEvent('offline-attendance-connection', { state: newState, previous: old });
        }
    }

    function startConnectivityMonitor() {
        window.addEventListener('online', function() {
            checkServerReachable().then(function(reachable) {
                if (reachable) {
                    setConnectionState('online');
                    triggerSync();
                }
            });
        });

        window.addEventListener('offline', function() {
            setConnectionState('offline');
        });

        // Periodic ping
        if (pingTimer) clearInterval(pingTimer);
        pingTimer = setInterval(function() {
            if (isSyncing) return;
            checkServerReachable().then(function(reachable) {
                if (reachable && connectionState === 'offline') {
                    setConnectionState('online');
                    triggerSync();
                } else if (!reachable && connectionState === 'online') {
                    setConnectionState('offline');
                }
            });
        }, PING_INTERVAL_MS);

        // Initial check
        checkServerReachable().then(function(reachable) {
            setConnectionState(reachable ? 'online' : 'offline');
        });
    }

    // ──────────────────────────────────────────────
    //  Sync Engine
    // ──────────────────────────────────────────────
    function triggerSync() {
        if (isSyncing) return;
        syncPendingRecords();
    }

    function syncPendingRecords() {
        if (isSyncing) return Promise.resolve();
        isSyncing = true;
        setConnectionState('syncing');

        return Promise.all([getPendingRecords(), getFailedRecords()])
            .then(function(results) {
                var allPending = results[0].concat(results[1]);
                if (allPending.length === 0) {
                    isSyncing = false;
                    setConnectionState('online');
                    return;
                }

                // Process in batches
                var batches = [];
                for (var i = 0; i < allPending.length; i += BATCH_SIZE) {
                    batches.push(allPending.slice(i, i + BATCH_SIZE));
                }

                return processBatches(batches, 0);
            })
            .catch(function(err) {
                console.error('[OfflineAttendance] Sync error:', err);
                isSyncing = false;
                setConnectionState('offline');
                scheduleRetry();
            });
    }

    function processBatches(batches, index) {
        if (index >= batches.length) {
            isSyncing = false;
            checkServerReachable().then(function(reachable) {
                setConnectionState(reachable ? 'online' : 'offline');
            });
            updateUI();
            cleanupSyncedRecords();
            return Promise.resolve();
        }

        var batch = batches[index];
        var payload = batch.map(function(rec) {
            return {
                local_id: rec.local_id,
                subject_code: rec.subject_code,
                subject_name: rec.subject_name || '',
                user_id: rec.user_id,
                status: rec.status,
                date: rec.date,
                time: rec.time || '',
            };
        });

        return fetch(SYNC_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ records: payload }),
        })
        .then(function(resp) {
            if (!resp.ok) throw new Error('Server returned ' + resp.status);
            return resp.json();
        })
        .then(function(data) {
            if (!data.success) throw new Error(data.message || 'Sync failed');

            var syncedCount = 0;

            // Mark synced
            var promises = [];
            if (data.synced) {
                data.synced.forEach(function(s) {
                    if (s.local_id) {
                        syncedCount++;
                        promises.push(updateRecordStatus(s.local_id, 'synced', null));
                    }
                });
            }
            // Mark skipped as synced (they already exist on server)
            if (data.skipped) {
                data.skipped.forEach(function(s) {
                    if (s.local_id) {
                        syncedCount++;
                        promises.push(updateRecordStatus(s.local_id, 'synced', 'Skipped: ' + (s.reason || '')));
                    }
                });
            }
            // Mark failed
            if (data.failed) {
                data.failed.forEach(function(f) {
                    if (f.local_id) {
                        promises.push(updateRecordStatus(f.local_id, 'failed', f.reason || 'Unknown error'));
                    }
                });
            }

            return Promise.all(promises).then(function() {
                if (syncedCount > 0) {
                    dispatchEvent('offline-attendance-synced', {
                        count: syncedCount,
                        summary: data.summary,
                    });
                    showSyncToast(syncedCount);
                }
                return processBatches(batches, index + 1);
            });
        })
        .catch(function(err) {
            console.error('[OfflineAttendance] Batch sync failed:', err);
            // Mark all in this batch as failed
            var failPromises = batch.map(function(rec) {
                return updateRecordStatus(rec.local_id, 'failed', err.message);
            });
            return Promise.all(failPromises).then(function() {
                isSyncing = false;
                setConnectionState('offline');
                scheduleRetry();
            });
        });
    }

    function scheduleRetry() {
        if (syncTimer) clearTimeout(syncTimer);
        getPendingCount().then(function(count) {
            if (count === 0) return;
            // Exponential backoff
            var delay = Math.min(RETRY_BASE_MS * Math.pow(2, Math.floor(Math.random() * 3)), MAX_RETRY_MS);
            console.log('[OfflineAttendance] Scheduling retry in', Math.round(delay / 1000), 's');
            syncTimer = setTimeout(function() {
                checkServerReachable().then(function(reachable) {
                    if (reachable) {
                        setConnectionState('online');
                        triggerSync();
                    } else {
                        scheduleRetry();
                    }
                });
            }, delay);
        });
    }

    // ──────────────────────────────────────────────
    //  Roster Cache (for offline class lists)
    // ──────────────────────────────────────────────
    function fetchAndCacheRoster() {
        return fetch(ROSTER_ENDPOINT, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            if (!data.success || !data.subjects) return;
            return openDB().then(function(database) {
                var tx = database.transaction(STORE_ROSTER, 'readwrite');
                var store = tx.objectStore(STORE_ROSTER);
                data.subjects.forEach(function(s) {
                    store.put(s);
                });
            });
        })
        .catch(function(err) {
            console.warn('[OfflineAttendance] Roster fetch failed:', err);
        });
    }

    function getCachedRoster() {
        return openDB().then(function(database) {
            return new Promise(function(resolve) {
                var tx = database.transaction(STORE_ROSTER, 'readonly');
                var store = tx.objectStore(STORE_ROSTER);
                var req = store.getAll();
                req.onsuccess = function() { resolve(req.result || []); };
                req.onerror = function() { resolve([]); };
            });
        });
    }

    function getCachedSubject(code) {
        return openDB().then(function(database) {
            return new Promise(function(resolve) {
                var tx = database.transaction(STORE_ROSTER, 'readonly');
                var store = tx.objectStore(STORE_ROSTER);
                var req = store.get(code.toUpperCase());
                req.onsuccess = function() { resolve(req.result || null); };
                req.onerror = function() { resolve(null); };
            });
        });
    }

    // ──────────────────────────────────────────────
    //  UI: Floating Status Indicator
    // ──────────────────────────────────────────────
    function injectStatusUI() {
        if (document.getElementById('offlineAttendanceStatus')) return;

        var html = '' +
            '<div id="offlineAttendanceStatus" class="oa-status-wrap" style="display:none;">' +
                '<div class="oa-status-pill" id="oaStatusPill">' +
                    '<span class="oa-status-dot" id="oaStatusDot"></span>' +
                    '<span class="oa-status-label" id="oaStatusLabel">Online</span>' +
                    '<span class="oa-pending-badge" id="oaPendingBadge" style="display:none;">0</span>' +
                '</div>' +
                '<button type="button" class="oa-sync-btn" id="oaSyncBtn" title="Sync Now" style="display:none;">' +
                    '<i class="bi bi-arrow-repeat"></i>' +
                '</button>' +
            '</div>';

        var container = document.createElement('div');
        container.innerHTML = html;
        document.body.appendChild(container.firstElementChild);

        // Sync button click
        var syncBtn = document.getElementById('oaSyncBtn');
        if (syncBtn) {
            syncBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (!isSyncing) triggerSync();
            });
        }

        // Inject styles
        if (!document.getElementById('oaStatusStyles')) {
            var style = document.createElement('style');
            style.id = 'oaStatusStyles';
            style.textContent = getStatusCSS();
            document.head.appendChild(style);
        }
    }

    function getStatusCSS() {
        return '' +
            '.oa-status-wrap{position:fixed;bottom:calc(88px + env(safe-area-inset-bottom,16px));right:16px;z-index:99990;display:flex;align-items:center;gap:8px;animation:oaSlideIn .35s cubic-bezier(.16,1,.3,1)}' +
            '@keyframes oaSlideIn{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}' +
            '@keyframes oaSpin{to{transform:rotate(360deg)}}' +
            '.oa-status-pill{display:flex;align-items:center;gap:8px;padding:8px 14px;background:rgba(15,23,42,.92);border:1px solid rgba(207,164,111,.3);border-radius:99px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px);box-shadow:0 8px 24px rgba(0,0,0,.45);cursor:default;transition:all .25s ease;user-select:none}' +
            '.oa-status-pill:hover{border-color:rgba(207,164,111,.5);box-shadow:0 8px 30px rgba(0,0,0,.55)}' +
            '.oa-status-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0;transition:background .3s}' +
            '.oa-status-dot.online{background:#22c55e;box-shadow:0 0 8px #22c55e}' +
            '.oa-status-dot.offline{background:#ef4444;box-shadow:0 0 8px #ef4444;animation:oaPulse 2s infinite}' +
            '.oa-status-dot.syncing{background:#f59e0b;box-shadow:0 0 8px #f59e0b;animation:oaPulse 1s infinite}' +
            '@keyframes oaPulse{0%,100%{opacity:1}50%{opacity:.4}}' +
            '.oa-status-label{font-size:.78rem;font-weight:700;color:#f3e7cd;letter-spacing:.3px}' +
            '.oa-pending-badge{min-width:20px;height:20px;display:flex;align-items:center;justify-content:center;padding:0 6px;background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;font-size:.7rem;font-weight:800;border-radius:99px;box-shadow:0 2px 8px rgba(239,68,68,.45)}' +
            '.oa-sync-btn{width:36px;height:36px;border-radius:50%;background:rgba(207,164,111,.15);border:1px solid rgba(207,164,111,.35);color:#cfa46f;display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all .2s;font-size:.95rem}' +
            '.oa-sync-btn:hover{background:rgba(207,164,111,.3);transform:scale(1.08)}' +
            '.oa-sync-btn.spinning i{animation:oaSpin .8s linear infinite}' +
            '.oa-sync-toast{position:fixed;top:20px;left:50%;transform:translateX(-50%) translateY(-60px);padding:12px 22px;border-radius:14px;font-size:.85rem;font-weight:700;z-index:100001;display:flex;align-items:center;gap:10px;background:rgba(22,163,74,.95);color:#fff;border:1px solid rgba(255,255,255,.2);box-shadow:0 10px 30px rgba(0,0,0,.5);transition:transform .4s cubic-bezier(.16,1,.3,1),opacity .3s;opacity:0;pointer-events:none}' +
            '.oa-sync-toast.show{transform:translateX(-50%) translateY(0);opacity:1}' +
            /* Offline attendance form banner */
            '.oa-offline-banner{display:flex;align-items:center;gap:10px;padding:12px 16px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);border-radius:12px;color:#fbbf24;font-size:.82rem;font-weight:600;margin-bottom:16px;animation:oaSlideIn .3s ease}' +
            '.oa-offline-banner i{font-size:1.1rem}' +
            '.oa-offline-banner.success{background:rgba(34,197,94,.12);border-color:rgba(34,197,94,.35);color:#86efac}';
    }

    function updateUI() {
        var statusWrap = document.getElementById('offlineAttendanceStatus');
        var dot = document.getElementById('oaStatusDot');
        var label = document.getElementById('oaStatusLabel');
        var badge = document.getElementById('oaPendingBadge');
        var syncBtn = document.getElementById('oaSyncBtn');

        if (!statusWrap) return;

        // Update dot and label
        if (dot) {
            dot.className = 'oa-status-dot ' + connectionState;
        }
        if (label) {
            var labels = { online: 'Online', offline: 'Offline', syncing: 'Syncing...' };
            label.textContent = labels[connectionState] || 'Online';
        }
        if (syncBtn) {
            syncBtn.classList.toggle('spinning', connectionState === 'syncing');
        }

        // Update badge
        getPendingCount().then(function(count) {
            if (badge) {
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
            if (syncBtn) {
                syncBtn.style.display = count > 0 ? 'flex' : 'none';
            }
            // Show the whole status bar if offline or has pending
            if (statusWrap) {
                statusWrap.style.display = (connectionState !== 'online' || count > 0) ? 'flex' : 'none';
            }
        });
    }

    function showSyncToast(count) {
        var existing = document.getElementById('oaSyncToast');
        if (existing) existing.remove();

        var toast = document.createElement('div');
        toast.id = 'oaSyncToast';
        toast.className = 'oa-sync-toast';
        toast.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + count + ' attendance record' + (count !== 1 ? 's' : '') + ' synced successfully!';
        document.body.appendChild(toast);

        requestAnimationFrame(function() {
            requestAnimationFrame(function() {
                toast.classList.add('show');
            });
        });

        setTimeout(function() {
            toast.classList.remove('show');
            setTimeout(function() { toast.remove(); }, 400);
        }, 4000);

        // Haptic feedback
        if (window.triggerHaptic) window.triggerHaptic('success');
    }

    // ──────────────────────────────────────────────
    //  Service Worker Background Sync
    // ──────────────────────────────────────────────
    function registerBackgroundSync() {
        if ('serviceWorker' in navigator && 'SyncManager' in window) {
            navigator.serviceWorker.ready.then(function(registration) {
                return registration.sync.register('sync-offline-attendance');
            }).catch(function(err) {
                console.warn('[OfflineAttendance] Background Sync registration failed:', err);
            });
        }
    }

    // ──────────────────────────────────────────────
    //  Event Helpers
    // ──────────────────────────────────────────────
    function dispatchEvent(name, detail) {
        try {
            window.dispatchEvent(new CustomEvent(name, { detail: detail }));
        } catch(e) {}
    }

    // ──────────────────────────────────────────────
    //  Classroom Form Interception
    // ──────────────────────────────────────────────
    function interceptClassroomForm(formEl, subjectCode, subjectName) {
        if (!formEl || formEl.dataset.oaIntercepted) return;
        formEl.dataset.oaIntercepted = 'true';

        formEl.addEventListener('submit', function(e) {
            if (connectionState === 'online') return; // Let normal submission happen

            e.preventDefault();
            e.stopPropagation();

            var formData = new FormData(formEl);
            var date = formData.get('date');
            var attendance = {};

            // Parse attendance[user_id] = status from form data
            for (var pair of formData.entries()) {
                var match = pair[0].match(/^attendance\[(\d+)\]$/);
                if (match) {
                    attendance[match[1]] = pair[1];
                }
            }

            if (!date || Object.keys(attendance).length === 0) {
                if (window.showPremiumToast) {
                    window.showPremiumToast('Please select a date and mark attendance.', 'error');
                }
                return;
            }

            var promises = [];
            Object.keys(attendance).forEach(function(userId) {
                // Try to get student name from the form
                var studentRow = formEl.querySelector('[data-student-id="' + userId + '"]');
                var studentName = studentRow ? (studentRow.dataset.studentName || '') : '';
                var studentNumber = studentRow ? (studentRow.dataset.studentNumber || '') : '';

                promises.push(saveRecord({
                    subject_code: subjectCode,
                    subject_name: subjectName || '',
                    user_id: parseInt(userId),
                    student_name: studentName,
                    student_number: studentNumber,
                    status: attendance[userId],
                    date: date,
                    time: new Date().toTimeString().substring(0, 8),
                }));
            });

            Promise.all(promises).then(function(records) {
                registerBackgroundSync();
                if (window.showPremiumToast) {
                    window.showPremiumToast('✓ ' + records.length + ' attendance records saved offline. Will sync when online.', 'success');
                }
                // Show banner
                showOfflineBanner(formEl, records.length);
            }).catch(function(err) {
                console.error('[OfflineAttendance] Save error:', err);
                if (window.showPremiumToast) {
                    window.showPremiumToast('Failed to save offline records: ' + err.message, 'error');
                }
            });
        });
    }

    function showOfflineBanner(formEl, count) {
        var existing = formEl.parentElement.querySelector('.oa-offline-banner.success');
        if (existing) existing.remove();

        var banner = document.createElement('div');
        banner.className = 'oa-offline-banner success';
        banner.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + count + ' record' + (count !== 1 ? 's' : '') + ' saved locally. Will auto-sync when connection is restored.';
        formEl.parentElement.insertBefore(banner, formEl);

        setTimeout(function() { banner.style.opacity = '0'; setTimeout(function() { banner.remove(); }, 300); }, 8000);
    }

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────
    function init(config) {
        if (initialized) return;
        initialized = true;

        csrfToken = config.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        teacherId = config.teacherId || null;

        openDB().then(function() {
            injectStatusUI();
            startConnectivityMonitor();

            // Cache roster when online
            if (navigator.onLine) {
                fetchAndCacheRoster();
            }

            // Listen for online to sync
            window.addEventListener('online', function() {
                setTimeout(function() { triggerSync(); }, 2000);
            });

            // Initial UI update
            updateUI();

            // Cleanup old synced records
            cleanupSyncedRecords();

            console.log('[OfflineAttendance] Initialized for teacher', teacherId);
        });
    }

    // Expose public API
    window.OfflineAttendance = {
        init: init,
        saveRecord: saveRecord,
        getPendingRecords: getPendingRecords,
        getPendingCount: getPendingCount,
        syncNow: triggerSync,
        getConnectionState: function() { return connectionState; },
        isOnline: function() { return connectionState === 'online'; },
        isOffline: function() { return connectionState !== 'online'; },
        getCachedRoster: getCachedRoster,
        getCachedSubject: getCachedSubject,
        interceptClassroomForm: interceptClassroomForm,
        fetchRoster: fetchAndCacheRoster,
    };

})(window);
