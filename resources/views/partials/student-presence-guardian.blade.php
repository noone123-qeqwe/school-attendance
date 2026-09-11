{{-- Student Presence Guardian: Continuous In-Classroom Presence Verification --}}
@auth
@if(auth()->user()->isStudent() || auth()->user()->hasRole('student'))
<div id="studentPresenceGuardian" style="display: none; position: fixed; bottom: 85px; left: 50%; transform: translateX(-50%); z-index: 1040; width: calc(100% - 32px); max-width: 440px; font-family: inherit; transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);">
    <div id="presenceCard" style="background: rgba(18, 14, 11, 0.94); border: 1px solid rgba(207, 164, 111, 0.35); border-radius: 18px; padding: 12px 16px; box-shadow: 0 16px 36px rgba(0, 0, 0, 0.55); backdrop-filter: blur(16px); color: #f3e7cd;">
        <div class="d-flex align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2" style="min-width: 0;">
                <span id="presencePulseDot" style="width: 10px; height: 10px; border-radius: 50%; background: #4ade80; display: inline-block; box-shadow: 0 0 10px #4ade80; flex-shrink: 0; animation: presencePulse 2s infinite;"></span>
                <div style="min-width: 0;">
                    <div class="d-flex align-items-center gap-2">
                        <span id="presenceTitle" style="font-size: 0.82rem; font-weight: 800; letter-spacing: 0.3px; color: #f3e7cd; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Classroom Presence Active</span>
                        <span id="presenceSubjectBadge" class="badge" style="background: rgba(207,164,111,0.2); color: #cfa46f; font-size: 0.68rem; border: 1px solid rgba(207,164,111,0.3); font-weight: 700;">Class</span>
                    </div>
                    <div id="presenceSubtitle" style="font-size: 0.72rem; color: #b39b82; margin-top: 1px;">Verifying physical attendance...</div>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="presencePingBtn" onclick="window.studentPresenceGuardian && window.studentPresenceGuardian.pingNow(true)" class="btn btn-sm" style="background: rgba(207,164,111,0.15); color: #f3e7cd; border: 1px solid rgba(207,164,111,0.3); border-radius: 10px; padding: 4px 8px; font-size: 0.72rem; font-weight: 700;" title="Refresh Presence Fix">
                    <i class="bi bi-geo-alt-fill"></i>
                </button>
                <button type="button" onclick="window.studentPresenceGuardian && window.studentPresenceGuardian.toggleCollapse()" class="btn btn-sm" style="background: transparent; color: #b39b82; border: none; padding: 4px 6px; font-size: 0.8rem;" aria-label="Toggle Details">
                    <i id="presenceToggleIcon" class="bi bi-chevron-down"></i>
                </button>
            </div>
        </div>

        {{-- Collapsible Details Section --}}
        <div id="presenceDetails" style="display: none; margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(207, 164, 111, 0.15); font-size: 0.72rem; color: #b39b82;">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>Allowed Radius:</span>
                <span id="presenceRadiusVal" style="color: #f3e7cd; font-weight: 700;">50m</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>Current Distance:</span>
                <span id="presenceDistanceVal" style="color: #f3e7cd; font-weight: 700;">Inside</span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span>Last Verified:</span>
                <span id="presenceLastCheckedVal" style="color: #f3e7cd; font-weight: 700;">Just now</span>
            </div>
            <div id="presenceGraceContainer" style="display: none; margin-top: 8px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 10px; padding: 8px 10px; color: #fbbf24;">
                <div class="d-flex align-items-center gap-1 fw-bold mb-1">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>Grace Period Active</span>
                </div>
                <div style="font-size: 0.68rem; color: #fde68a;">
                    Return to classroom within: <strong id="presenceGraceCountdown" style="font-size: 0.8rem; font-family: monospace; color: #fff;">05:00</strong> to prevent attendance from being marked as <strong>ESCAPED</strong>.
                </div>
            </div>
            <div id="presenceEscapedBanner" style="display: none; margin-top: 8px; background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.5); border-radius: 10px; padding: 8px 10px; color: #fca5a5;">
                <div class="d-flex align-items-center gap-1 fw-bold mb-1" style="color: #f87171;">
                    <i class="bi bi-person-x-fill"></i>
                    <span>Status Changed: ESCAPED</span>
                </div>
                <div style="font-size: 0.68rem;">
                    You remained outside the allowed attendance area. Your attendance has been finalized as Escaped. Please see your instructor.
                </div>
            </div>
        </div>
    </div>
</div>

<style @cspNonce>
@keyframes presencePulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.25); opacity: 1; box-shadow: 0 0 14px #4ade80; }
    100% { transform: scale(0.95); opacity: 0.7; }
}
@keyframes presenceWarningPulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 16px #f59e0b; }
    100% { transform: scale(0.95); opacity: 0.7; }
}
@keyframes presenceDangerPulse {
    0% { transform: scale(0.95); opacity: 0.7; }
    50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 16px #ef4444; }
    100% { transform: scale(0.95); opacity: 0.7; }
}
</style>

<script @cspNonce>
(function() {
    'use strict';

    class StudentPresenceGuardian {
        constructor() {
            this.container = document.getElementById('studentPresenceGuardian');
            this.card = document.getElementById('presenceCard');
            this.pulseDot = document.getElementById('presencePulseDot');
            this.titleEl = document.getElementById('presenceTitle');
            this.subTitleEl = document.getElementById('presenceSubtitle');
            this.badgeEl = document.getElementById('presenceSubjectBadge');
            this.radiusVal = document.getElementById('presenceRadiusVal');
            this.distanceVal = document.getElementById('presenceDistanceVal');
            this.lastCheckedVal = document.getElementById('presenceLastCheckedVal');
            this.detailsEl = document.getElementById('presenceDetails');
            this.graceContainer = document.getElementById('presenceGraceContainer');
            this.graceCountdownEl = document.getElementById('presenceGraceCountdown');
            this.escapedBanner = document.getElementById('presenceEscapedBanner');
            this.toggleIcon = document.getElementById('presenceToggleIcon');
            this.pingBtn = document.getElementById('presencePingBtn');

            this.activeSession = null;
            this.timer = null;
            this.countdownTimer = null;
            this.remainingGraceSeconds = 0;
            this.isChecking = false;
            this.isCollapsed = true;

            this.init();
        }

        async init() {
            // Check for active presence monitoring session
            await this.syncActiveSession();

            // Set up lifecycle listeners (app reopen, visibility change, online)
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && this.activeSession) {
                    this.pingNow(false);
                }
            });

            window.addEventListener('online', () => {
                if (this.activeSession) {
                    this.pingNow(false);
                }
            });
        }

        async syncActiveSession() {
            try {
                const res = await fetch('{{ route("student.presence.active") }}', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;

                const data = await res.json();
                if (data && data.has_active_session) {
                    this.startMonitoring(data);
                } else {
                    this.stopMonitoring();
                }
            } catch (err) {
                console.warn('[PresenceGuardian] Session sync error:', err);
            }
        }

        startMonitoring(sessionData) {
            this.activeSession = sessionData;
            if (this.container) this.container.style.display = 'block';

            if (this.badgeEl) this.badgeEl.textContent = sessionData.subject_code || 'Class';
            if (this.radiusVal) this.radiusVal.textContent = (sessionData.radius || 50) + 'm';

            this.updateUiState(sessionData);

            if (this.timer) clearInterval(this.timer);
            // Periodic presence check every 45 seconds
            this.timer = setInterval(() => this.pingNow(false), 45000);

            // Trigger immediate presence check
            this.pingNow(false);
        }

        stopMonitoring() {
            if (this.timer) clearInterval(this.timer);
            if (this.countdownTimer) clearInterval(this.countdownTimer);
            this.activeSession = null;
            if (this.container) this.container.style.display = 'none';
        }

        toggleCollapse() {
            this.isCollapsed = !this.isCollapsed;
            if (this.detailsEl) {
                this.detailsEl.style.display = this.isCollapsed ? 'none' : 'block';
            }
            if (this.toggleIcon) {
                this.toggleIcon.className = this.isCollapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
            }
        }

        async pingNow(isManual = false) {
            if (!this.activeSession || this.isChecking) return;

            if (isManual && this.pingBtn) {
                this.pingBtn.disabled = true;
                this.pingBtn.innerHTML = '<span class="spinner-border spinner-border-sm" style="width: 12px; height: 12px;"></span>';
            }

            this.isChecking = true;

            if (!navigator.geolocation) {
                this.handleLocationError('Geolocation not supported by device/browser');
                this.isChecking = false;
                if (isManual && this.pingBtn) {
                    this.pingBtn.disabled = false;
                    this.pingBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i>';
                }
                return;
            }

            navigator.geolocation.getCurrentPosition(
                async (position) => {
                    await this.sendVerificationPayload(position.coords);
                    this.isChecking = false;
                    if (isManual && this.pingBtn) {
                        this.pingBtn.disabled = false;
                        this.pingBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i>';
                    }
                },
                async (error) => {
                    await this.reportLocationFailure(error);
                    this.handleLocationError(error.message, error.code);
                    this.isChecking = false;
                    if (isManual && this.pingBtn) {
                        this.pingBtn.disabled = false;
                        this.pingBtn.innerHTML = '<i class="bi bi-geo-alt-fill"></i>';
                    }
                },
                { enableHighAccuracy: true, timeout: 12000, maximumAge: 10000 }
            );
        }

        async reportLocationFailure(error) {
            if (!this.activeSession) return;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const errCode = (error && error.code === 1) ? 'permission_denied' : 'position_unavailable';
            try {
                const res = await fetch('{{ route("student.presence.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        session_id: this.activeSession.session_id,
                        error_code: errCode,
                        error_message: error ? error.message : 'Location access failed'
                    })
                });

                const data = await res.json();
                if (data && (data.success || data.monitoring_status)) {
                    this.updateUiState(data);
                }
            } catch (e) {
                console.warn('[PresenceGuardian] Error reporting failure:', e);
            }
        }

        async sendVerificationPayload(coords) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            try {
                const res = await fetch('{{ route("student.presence.verify") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({
                        session_id: this.activeSession.session_id,
                        latitude: coords.latitude,
                        longitude: coords.longitude,
                        accuracy: coords.accuracy
                    })
                });

                const data = await res.json();

                if (!res.ok) {
                    console.warn('[PresenceGuardian] Verification server error:', data);
                    return;
                }

                if (data.session_active === false || data.monitoring_status === 'completed') {
                    this.handleSessionEnded(data);
                    return;
                }

                this.updateUiState(data);
            } catch (e) {
                console.warn('[PresenceGuardian] Network ping failure:', e);
            }
        }

        handleLocationError(msg, code) {
            const isDenied = (code === 1 || code === 'permission_denied');
            if (this.subTitleEl) {
                this.subTitleEl.textContent = isDenied 
                    ? 'GPS permission denied. Enable location to avoid Escape.' 
                    : 'Location unavailable. Please check GPS signal.';
            }
            if (this.distanceVal) {
                this.distanceVal.textContent = isDenied ? 'Permission Denied' : 'GPS Unavailable';
            }
        }

        handleSessionEnded(data) {
            if (this.pulseDot) {
                this.pulseDot.style.background = '#94a3b8';
                this.pulseDot.style.boxShadow = 'none';
                this.pulseDot.style.animation = 'none';
            }
            if (this.titleEl) this.titleEl.textContent = 'Attendance Session Concluded';
            if (this.subTitleEl) this.subTitleEl.textContent = `Final Status: ${data.status || 'Recorded'}`;
            if (this.graceContainer) this.graceContainer.style.display = 'none';

            setTimeout(() => {
                this.stopMonitoring();
            }, 6000);
        }

        updateUiState(data) {
            const nowTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            if (this.lastCheckedVal) this.lastCheckedVal.textContent = nowTime;

            if (data.distance !== undefined && this.distanceVal) {
                this.distanceVal.textContent = `${data.distance}m`;
            }

            if (data.status === 'Escaped' || data.monitoring_status === 'escaped') {
                // ESCAPED STATE
                if (this.card) this.card.style.border = '1px solid rgba(239, 68, 68, 0.6)';
                if (this.pulseDot) {
                    this.pulseDot.style.background = '#ef4444';
                    this.pulseDot.style.animation = 'presenceDangerPulse 1.5s infinite';
                }
                if (this.titleEl) {
                    this.titleEl.textContent = 'Attendance Status: ESCAPED';
                    this.titleEl.style.color = '#f87171';
                }
                if (this.subTitleEl) {
                    this.subTitleEl.textContent = 'Outside allowed area beyond grace period.';
                    this.subTitleEl.style.color = '#fca5a5';
                }
                if (this.graceContainer) this.graceContainer.style.display = 'none';
                if (this.escapedBanner) this.escapedBanner.style.display = 'block';
                if (this.countdownTimer) clearInterval(this.countdownTimer);
                return;
            }

            if (data.monitoring_status === 'warning') {
                // WARNING / OUTSIDE AREA STATE
                if (this.card) this.card.style.border = '1px solid rgba(245, 158, 11, 0.6)';
                if (this.pulseDot) {
                    this.pulseDot.style.background = '#f59e0b';
                    this.pulseDot.style.animation = 'presenceWarningPulse 1.2s infinite';
                }
                if (this.titleEl) {
                    this.titleEl.textContent = 'Warning: Outside Attendance Area';
                    this.titleEl.style.color = '#fbbf24';
                }
                if (this.subTitleEl) {
                    this.subTitleEl.textContent = `${data.distance}m away. Return to classroom immediately.`;
                    this.subTitleEl.style.color = '#fde68a';
                }
                if (this.escapedBanner) this.escapedBanner.style.display = 'none';
                if (this.graceContainer) this.graceContainer.style.display = 'block';

                if (data.remaining_grace_seconds !== undefined) {
                    this.startGraceCountdown(data.remaining_grace_seconds);
                }
                return;
            }

            // NORMAL / INSIDE AREA STATE
            if (this.card) this.card.style.border = '1px solid rgba(207, 164, 111, 0.35)';
            if (this.pulseDot) {
                this.pulseDot.style.background = '#4ade80';
                this.pulseDot.style.animation = 'presencePulse 2s infinite';
            }
            if (this.titleEl) {
                this.titleEl.textContent = 'Classroom Presence Verified';
                this.titleEl.style.color = '#f3e7cd';
            }
            if (this.subTitleEl) {
                this.subTitleEl.textContent = data.distance !== undefined ? `In class (${data.distance}m) • Status: ${data.status || 'Present'}` : 'Presence confirmed inside classroom.';
                this.subTitleEl.style.color = '#b39b82';
            }
            if (this.graceContainer) this.graceContainer.style.display = 'none';
            if (this.escapedBanner) this.escapedBanner.style.display = 'none';
            if (this.countdownTimer) clearInterval(this.countdownTimer);
        }

        startGraceCountdown(seconds) {
            this.remainingGraceSeconds = Math.max(0, Math.floor(seconds));
            if (this.countdownTimer) clearInterval(this.countdownTimer);

            const updateDisplay = () => {
                const mins = Math.floor(this.remainingGraceSeconds / 60);
                const secs = this.remainingGraceSeconds % 60;
                const formatted = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
                if (this.graceCountdownEl) this.graceCountdownEl.textContent = formatted;

                if (this.remainingGraceSeconds <= 0) {
                    clearInterval(this.countdownTimer);
                    this.pingNow(false);
                } else {
                    this.remainingGraceSeconds--;
                }
            };

            updateDisplay();
            this.countdownTimer = setInterval(updateDisplay, 1000);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        window.studentPresenceGuardian = new StudentPresenceGuardian();
    });
})();
</script>
@endif
@endauth
