@extends('layouts.app')
@section('portal-title', $subject->name . ' — Class Workspace')
@section('page-title', $subject->name)

@section('content')
@php
    $scheduleDays = $subject->schedules->pluck('day')->map(function ($day) {
        return ['Monday' => 'Mon', 'Tuesday' => 'Tue', 'Wednesday' => 'Wed', 'Thursday' => 'Thu', 'Friday' => 'Fri', 'Saturday' => 'Sat'][$day] ?? $day;
    })->unique()->join(', ');

    $firstSchedule = $subject->schedules->first();
    $scheduleWindow = ($firstSchedule && $firstSchedule->start_time && $firstSchedule->end_time)
        ? \Carbon\Carbon::parse($firstSchedule->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($firstSchedule->end_time)->format('g:i A')
        : 'Flexible / TBA';

    $totalStudents = $students->count();
    $classTotal = $classStats['total'] ?? 0;
    $classPresent = $classStats['present'] ?? 0;
    $classLate = $classStats['late'] ?? 0;
    $classAbsent = $classStats['absent'] ?? 0;
    $classExcused = $classStats['excused'] ?? 0;
    $classRate = $classStats['rate'] ?? 0;

    $presentWidth = $classTotal > 0 ? round(($classPresent / $classTotal) * 100, 1) : 0;
    $lateWidth = $classTotal > 0 ? round(($classLate / $classTotal) * 100, 1) : 0;
    $excusedWidth = $classTotal > 0 ? round(($classExcused / $classTotal) * 100, 1) : 0;
    $absentWidth = $classTotal > 0 ? max(0, round(100 - $presentWidth - $lateWidth - $excusedWidth, 1)) : 0;
@endphp

<div class="tc-workspace" id="classWorkspace" data-subject-code="{{ $subject->code }}" data-base-url="{{ route('teacher.classroom.show', $subject->code) }}">
    @if(session('success'))
        <div class="portal-banner is-success mb-3" role="status">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-check-circle-fill"></i></span>
                <div><div class="portal-banner-title">{{ session('success') }}</div></div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="portal-banner is-error mb-3" role="alert">
            <div class="portal-banner-lead">
                <span class="portal-banner-icon" aria-hidden="true"><i class="bi bi-exclamation-circle-fill"></i></span>
                <div><div class="portal-banner-title">{{ session('error') }}</div></div>
            </div>
        </div>
    @endif

    <header class="tc-header">
        <div class="tc-header-top">
            <div class="tc-header-copy">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <span class="tc-code-pill portal-mono">{{ $subject->code }}</span>
                    @if($subject->course)
                        <span class="tc-meta-tag">{{ $subject->course }}</span>
                    @endif
                </div>
                <h1 class="tc-title">{{ $subject->name }}</h1>
            </div>
            <div class="tc-actions-bar">
                <a href="{{ route('teacher.qr', $subject->code) }}" class="ent-btn ent-btn-primary">
                    <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Start QR Session
                </a>
                <a href="{{ route('teacher.subjects.edit', $subject->id) }}" class="ent-btn ent-btn-secondary">
                    <i class="bi bi-gear" aria-hidden="true"></i> Edit Class
                </a>
            </div>
        </div>

        <div class="tc-meta-strip">
            <div class="tc-meta-cell">
                <span class="tc-meta-kicker">Schedule</span>
                <span class="tc-meta-val">{{ $scheduleDays ?: 'No schedule set' }}</span>
            </div>
            <div class="tc-meta-cell">
                <span class="tc-meta-kicker">Time Window</span>
                <span class="tc-meta-val portal-tabular">{{ $scheduleWindow }}</span>
            </div>
            <div class="tc-meta-cell">
                <span class="tc-meta-kicker">Year / Semester</span>
                <span class="tc-meta-val">Year {{ $subject->year_level }} &middot; Sem {{ $subject->semester }}</span>
            </div>
            <div class="tc-meta-cell">
                <span class="tc-meta-kicker">Section</span>
                <span class="tc-meta-val">{{ $subject->section ?: 'All Sections' }}</span>
            </div>
        </div>
    </header>

    <div class="tc-tab-nav" role="tablist" aria-label="Class workspace sections">
        <button
            type="button"
            role="tab"
            id="tab-btn-overview"
            class="tc-tab-btn {{ $activeTab === 'overview' ? 'active' : '' }}"
            data-tab="overview"
            aria-controls="overview-tab"
            aria-selected="{{ $activeTab === 'overview' ? 'true' : 'false' }}">
            <i class="bi bi-grid-1x2" aria-hidden="true"></i>
            <span>Overview</span>
        </button>

        <button
            type="button"
            role="tab"
            id="tab-btn-roster"
            class="tc-tab-btn {{ $activeTab === 'roster' ? 'active' : '' }}"
            data-tab="roster"
            aria-controls="roster-tab"
            aria-selected="{{ $activeTab === 'roster' ? 'true' : 'false' }}">
            <i class="bi bi-people" aria-hidden="true"></i>
            <span>Student Roster</span>
            <span class="tc-tab-count portal-mono">{{ $totalStudents }}</span>
        </button>

        <button
            type="button"
            role="tab"
            id="tab-btn-records"
            class="tc-tab-btn {{ $activeTab === 'records' ? 'active' : '' }}"
            data-tab="records"
            aria-controls="records-tab"
            aria-selected="{{ $activeTab === 'records' ? 'true' : 'false' }}">
            <i class="bi bi-clipboard-check" aria-hidden="true"></i>
            <span>Attendance Records</span>
            <span class="tc-tab-count portal-mono">{{ $classTotal }}</span>
        </button>

        <button
            type="button"
            role="tab"
            id="tab-btn-sessions"
            class="tc-tab-btn {{ $activeTab === 'sessions' ? 'active' : '' }}"
            data-tab="sessions"
            aria-controls="sessions-tab"
            aria-selected="{{ $activeTab === 'sessions' ? 'true' : 'false' }}">
            <i class="bi bi-qr-code" aria-hidden="true"></i>
            <span>Attendance Sessions</span>
            <span class="tc-tab-count portal-mono">{{ $attendanceSessions->count() }}</span>
        </button>
    </div>

    <section
        id="overview-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-overview"
        class="tc-tab-panel {{ $activeTab === 'overview' ? 'active' : '' }}"
        @if($activeTab !== 'overview') hidden @endif>

        <div class="tc-stats-strip mb-4" aria-label="Class attendance summary">
            <div class="tc-stat-chip">
                <span class="tc-stat-label">Enrolled Students</span>
                <span class="tc-stat-value portal-mono">{{ $totalStudents }}</span>
            </div>
            <div class="tc-stat-chip">
                <span class="tc-stat-label">Attendance Rate</span>
                <span class="tc-stat-value portal-mono">{{ $classRate }}%</span>
            </div>
            <div class="tc-stat-chip is-present">
                <span class="tc-stat-label">Present</span>
                <span class="tc-stat-value portal-mono">{{ $classPresent }}</span>
            </div>
            <div class="tc-stat-chip is-late">
                <span class="tc-stat-label">Late</span>
                <span class="tc-stat-value portal-mono">{{ $classLate }}</span>
            </div>
            <div class="tc-stat-chip is-absent">
                <span class="tc-stat-label">Absent</span>
                <span class="tc-stat-value portal-mono">{{ $classAbsent }}</span>
            </div>
            <div class="tc-stat-chip is-excused">
                <span class="tc-stat-label">Excused</span>
                <span class="tc-stat-value portal-mono">{{ $classExcused }}</span>
            </div>
        </div>

        <div class="tc-panel mb-4">
            <div class="tc-panel-head">
                <div>
                    <h2 class="tc-panel-title">Recent Class Check-Ins</h2>
                    <p class="tc-panel-sub">Latest recorded entries exclusively for {{ $subject->code }}.</p>
                </div>
                <button type="button" class="portal-panel-link border-0 bg-transparent p-0" data-switch-tab="records">
                    Open Attendance Records <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </div>
            <div class="tc-panel-body">
                @if($classTotal > 0)
                    <div class="tc-breakdown-bar mb-3" role="img" aria-label="Status share: {{ $classPresent }} present, {{ $classLate }} late, {{ $classExcused }} excused, {{ $classAbsent }} absent">
                        @if($presentWidth > 0)<span class="tc-seg is-present" style="width: {{ $presentWidth }}%;"></span>@endif
                        @if($lateWidth > 0)<span class="tc-seg is-late" style="width: {{ $lateWidth }}%;"></span>@endif
                        @if($excusedWidth > 0)<span class="tc-seg is-excused" style="width: {{ $excusedWidth }}%;"></span>@endif
                        @if($absentWidth > 0)<span class="tc-seg is-absent" style="width: {{ $absentWidth }}%;"></span>@endif
                    </div>
                @endif

                @if($recentClassRecords->isEmpty())
                    <p class="mb-0" style="color: #a49890; font-size: 0.88rem;">
                        No check-ins recorded for {{ $subject->code }} yet. Start a QR session or use Manual Roll Call under Attendance Sessions.
                    </p>
                @else
                    <div class="tc-overview-list">
                        @foreach($recentClassRecords as $recent)
                            @php
                                $recentLabel = $recent->excused ? 'Excused' : ucfirst(strtolower((string) ($recent->status ?? 'Absent')));
                                $recentTone = match (strtolower($recentLabel)) {
                                    'present' => 'present',
                                    'late' => 'late',
                                    'excused' => 'excused',
                                    default => 'absent',
                                };
                            @endphp
                            <div class="tc-overview-row">
                                <div>
                                    <div style="font-weight: 700; color: #faf8f5;">{{ $recent->user->name ?? 'Unknown Student' }}</div>
                                    <div style="font-size: 0.76rem; color: #a49890;" class="portal-tabular">
                                        {{ \Carbon\Carbon::parse($recent->date)->format('M d, Y') }}
                                        @if($recent->time_in) &middot; {{ \Carbon\Carbon::parse($recent->time_in)->format('g:i A') }} @endif
                                    </div>
                                </div>
                                <x-badge :type="$recentTone">{{ $recentLabel }}</x-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @include('teacher.classroom.partials.student-assistants')

        <div class="mt-3">
            <a href="{{ route('teacher.classroom.assistant-attendance.index', $subject->code) }}" class="ent-btn ent-btn-secondary">
                <i class="bi bi-clipboard-check" aria-hidden="true"></i> Review assistant submissions
            </a>
        </div>
    </section>

    <section
        id="roster-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-roster"
        class="tc-tab-panel {{ $activeTab === 'roster' ? 'active' : '' }}"
        @if($activeTab !== 'roster') hidden @endif>

        <div class="tc-panel">
            <div class="tc-panel-head">
                <div>
                    <h2 class="tc-panel-title">Student Roster ({{ $totalStudents }})</h2>
                    <p class="tc-panel-sub">Enrolled students and their {{ $subject->code }} attendance standing.</p>
                </div>
                @if($students->isNotEmpty())
                    <div style="min-width: 240px; flex: 0 1 300px;">
                        <input type="search" id="rosterQuickSearch" class="ds-input" placeholder="Search roster by name or ID..." aria-label="Search class student roster">
                    </div>
                @endif
            </div>

            <div class="tc-panel-body">
                @if($students->isEmpty())
                    <div class="empty-state tc-empty-state">
                        <i class="bi bi-people" aria-hidden="true"></i>
                        <h3>No Students Found</h3>
                        <p>No enrolled students match this class's cohort yet.</p>
                    </div>
                @else
                    <div class="tc-table-scroll">
                        <table class="tch-table tc-records-table">
                            <thead>
                                <tr>
                                    <th>Student Number</th>
                                    <th>Name</th>
                                    <th>Year / Section</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Present</th>
                                    <th class="text-center">Late</th>
                                    <th class="text-center">Absent</th>
                                    <th class="text-center">Excused</th>
                                    <th class="text-center">Rate</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($students as $student)
                                    @php
                                        $studRecords = $student->attendances;
                                        $studTotal = $studRecords->count();
                                        $studExcused = $studRecords->where('excused', true)->count();
                                        $studPresent = $studRecords->where('excused', false)->where('status', 'Present')->count();
                                        $studLate = $studRecords->where('excused', false)->where('status', 'Late')->count();
                                        $studAbsent = $studRecords->where('excused', false)->where('status', 'Absent')->count();
                                        $studRate = $studTotal > 0 ? round((($studPresent + $studLate) / $studTotal) * 100) : 0;
                                    @endphp
                                    <tr data-roster-row data-search-text="{{ strtolower($student->name . ' ' . ($student->student_number ?? '')) }}">
                                        <td data-label="Student Number">
                                            <span class="portal-mono">{{ $student->student_number ?: '—' }}</span>
                                        </td>
                                        <td data-label="Name" style="font-weight: 700; color: #faf8f5;">{{ $student->name }}</td>
                                        <td data-label="Year / Section">Year {{ $student->year_level }} &middot; {{ $student->section ?: 'All' }}</td>
                                        <td data-label="Total" class="text-center portal-mono">{{ $studTotal }}</td>
                                        <td data-label="Present" class="text-center"><x-badge type="present">{{ $studPresent }}</x-badge></td>
                                        <td data-label="Late" class="text-center"><x-badge type="late">{{ $studLate }}</x-badge></td>
                                        <td data-label="Absent" class="text-center"><x-badge type="absent">{{ $studAbsent }}</x-badge></td>
                                        <td data-label="Excused" class="text-center"><x-badge type="excused">{{ $studExcused }}</x-badge></td>
                                        <td data-label="Rate" class="text-center portal-mono"><strong>{{ $studRate }}%</strong></td>
                                        <td data-label="Actions" class="text-end">
                                            <div class="d-inline-flex gap-2 flex-wrap justify-content-end">
                                                <a href="{{ route('teacher.classroom.show', ['subjectCode' => $subject->code, 'tab' => 'records', 'student_id' => $student->id]) }}" class="ent-btn ent-btn-secondary btn-sm">
                                                    <i class="bi bi-clock-history" aria-hidden="true"></i> History
                                                </a>
                                                <a href="{{ route('teacher.student', $student->id) }}" class="ent-btn ent-btn-secondary btn-sm">
                                                    <i class="bi bi-person" aria-hidden="true"></i> Profile
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section
        id="records-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-records"
        class="tc-tab-panel {{ $activeTab === 'records' ? 'active' : '' }}"
        @if($activeTab !== 'records') hidden @endif>
        @include('teacher.classroom.partials.attendance-records-tab')
    </section>

    <section
        id="sessions-tab"
        role="tabpanel"
        aria-labelledby="tab-btn-sessions"
        class="tc-tab-panel {{ $activeTab === 'sessions' ? 'active' : '' }}"
        @if($activeTab !== 'sessions') hidden @endif>
        @include('teacher.classroom.partials.attendance-sessions-tab')
    </section>
</div>

<!-- Attendance Correction Modal -->
<div class="modal fade" id="classAttendanceCorrectionModal" tabindex="-1" aria-labelledby="correctionModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="classAttendanceCorrectionForm" class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="correctionModalTitle">Correct Attendance Record</h2>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="correctionRecordId">
                <div class="mb-3">
                    <div style="font-size: 0.78rem; color: #a49890;">Student &amp; Date</div>
                    <div id="correctionRecordSummary" style="font-weight: 700; color: #faf8f5;"></div>
                </div>
                <div class="mb-3">
                    <label class="ds-label" for="correctionStatusSelect">Attendance Status *</label>
                    <select id="correctionStatusSelect" class="ds-select" required>
                        <option value="Present">Present</option>
                        <option value="Late">Late</option>
                        <option value="Absent">Absent</option>
                        <option value="Excused">Excused</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="ds-label" for="correctionReasonInput">Correction Reason</label>
                    <input type="text" id="correctionReasonInput" class="ds-input" maxlength="255" placeholder="e.g. Verified medical note or manual adjustment">
                </div>
                <div id="correctionExcuseWrap" class="mb-2">
                    <label class="ds-label" for="correctionExcuseNoteInput">Excuse Note (shown when Excused)</label>
                    <input type="text" id="correctionExcuseNoteInput" class="ds-input" maxlength="255" placeholder="Optional excuse details">
                </div>
                <div id="correctionModalError" class="text-danger small mt-2" hidden></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="ent-btn ent-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" id="correctionSaveBtn" class="ent-btn ent-btn-primary">Save Correction</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script @cspNonce>
(function () {
    const workspace = document.getElementById('classWorkspace');
    if (!workspace) return;

    const tabButtons = Array.from(document.querySelectorAll('.tc-tab-btn[data-tab]'));
    const tabPanels = Array.from(document.querySelectorAll('.tc-tab-panel'));

    function activateTab(tabName, options = {}) {
        const targetPanel = document.getElementById(tabName + '-tab');
        if (!targetPanel) return;

        tabButtons.forEach((btn) => {
            const selected = btn.dataset.tab === tabName;
            btn.classList.toggle('active', selected);
            btn.setAttribute('aria-selected', selected ? 'true' : 'false');
        });

        tabPanels.forEach((panel) => {
            const isTarget = panel.id === tabName + '-tab';
            panel.classList.toggle('active', isTarget);
            panel.hidden = !isTarget;
        });

        if (options.updateHistory !== false) {
            const nextUrl = new URL(window.location.href);
            nextUrl.searchParams.set('tab', tabName);
            window.history.pushState({ tab: tabName }, '', nextUrl.toString());
        }
    }

    tabButtons.forEach((btn, idx) => {
        btn.addEventListener('click', () => activateTab(btn.dataset.tab));
        btn.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            const step = e.key === 'ArrowRight' ? 1 : -1;
            const next = tabButtons[(idx + step + tabButtons.length) % tabButtons.length];
            next.focus();
            activateTab(next.dataset.tab);
        });
    });

    document.querySelectorAll('[data-switch-tab]').forEach((trigger) => {
        trigger.addEventListener('click', () => activateTab(trigger.getAttribute('data-switch-tab')));
    });

    window.addEventListener('popstate', (event) => {
        const params = new URLSearchParams(window.location.search);
        const tabFromUrl = (event.state && event.state.tab) || params.get('tab') || 'overview';
        activateTab(tabFromUrl, { updateHistory: false });
    });

    const rosterSearch = document.getElementById('rosterQuickSearch');
    if (rosterSearch) {
        rosterSearch.addEventListener('input', () => {
            const term = rosterSearch.value.trim().toLowerCase();
            document.querySelectorAll('[data-roster-row]').forEach((row) => {
                const haystack = row.dataset.searchText || '';
                row.style.display = haystack.includes(term) ? '' : 'none';
            });
        });
    }

    const markAllBtn = document.getElementById('markAllPresentBtn');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', () => {
            document.querySelectorAll('#manualAttendanceForm input[type="radio"][value="Present"]').forEach((radio) => {
                radio.checked = true;
            });
            showRecordsFeedback('All listed students set to Present. Click Save Attendance to apply.', 'info');
        });
    }

    if (window.OfflineAttendance) {
        const manualForm = document.getElementById('manualAttendanceForm');
        if (manualForm) {
            OfflineAttendance.interceptClassroomForm(manualForm, @json($subject->code), @json($subject->name));
        }
    }

    function updateOfflineBanner() {
        const slot = document.getElementById('oaOfflineBannerSlot');
        if (!slot) return;
        if (window.OfflineAttendance && OfflineAttendance.isOffline()) {
            if (!slot.querySelector('.oa-offline-banner')) {
                const banner = document.createElement('div');
                banner.className = 'oa-offline-banner';
                banner.innerHTML = '<i class="bi bi-wifi-off"></i> You are offline. Attendance will be saved locally and synced when connection is restored.';
                slot.appendChild(banner);
            }
        } else {
            const existing = slot.querySelector('.oa-offline-banner:not(.success)');
            if (existing) existing.remove();
        }
    }
    updateOfflineBanner();
    window.addEventListener('offline-attendance-connection', updateOfflineBanner);

    const feedbackEl = document.getElementById('tcRecordsFeedback');
    function showRecordsFeedback(message, tone = 'success') {
        if (!feedbackEl) return;
        feedbackEl.textContent = message;
        feedbackEl.className = 'tc-inline-feedback is-' + tone;
        feedbackEl.hidden = !message;
    }

    const badgeToneStyles = {
        present: 'background: rgba(74, 222, 128, 0.15); color: #4ade80; border: 1px solid rgba(74, 222, 128, 0.25);',
        late: 'background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.25);',
        absent: 'background: rgba(248, 113, 113, 0.15); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.25);',
        excused: 'background: rgba(96, 165, 250, 0.15); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.25);',
    };
    const badgeBaseStyle = 'display:inline-flex;align-items:center;border-radius:9999px;font-weight:700;text-transform:uppercase;letter-spacing:0.3px;line-height:1.5;white-space:nowrap;user-select:none;font-variant-numeric:tabular-nums;padding:3px 10px;font-size:0.75rem;gap:5px;';

    function applyRecordUiUpdate(recordId, data) {
        const resolved = data.excused || data.status === 'Excused' ? 'Excused' : data.status;
        const toneKey = resolved.toLowerCase();
        const btnEl = document.getElementById('statusBtn_' + recordId);
        if (btnEl) {
            const span = document.createElement('span');
            span.className = 'ds-badge ds-badge-' + toneKey;
            span.style.cssText = badgeBaseStyle + (badgeToneStyles[toneKey] || badgeToneStyles.absent);
            span.textContent = resolved;
            btnEl.replaceChildren(span);
        }

        const excuseCell = document.getElementById('excuseCell_' + recordId);
        if (excuseCell) {
            if (resolved === 'Excused') {
                const label = document.createElement('span');
                label.className = 'text-success';
                label.style.cssText = 'font-size:0.82rem;font-weight:700;';
                label.innerHTML = '<i class="bi bi-check-lg" aria-hidden="true"></i> Excused';
                excuseCell.replaceChildren(label);
                if (data.excuse_note) {
                    const noteDiv = document.createElement('div');
                    noteDiv.style.cssText = 'font-size:0.74rem;color:#d6ccc6;margin-top:2px;';
                    noteDiv.textContent = data.excuse_note;
                    excuseCell.appendChild(noteDiv);
                }
            } else {
                const muted = document.createElement('span');
                muted.style.color = '#a49890';
                muted.textContent = '—';
                excuseCell.replaceChildren(muted);
            }
        }

        const editBtn = document.querySelector(`[data-correct-record][data-record-id="${recordId}"]`);
        if (editBtn) {
            editBtn.setAttribute('data-status', resolved);
            editBtn.setAttribute('data-excuse-note', data.excuse_note || '');
        }
    }

    async function submitAttendanceOverride(recordId, payload) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const response = await fetch(`/teacher/attendance/${recordId}/override`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await response.json().catch(() => ({ success: false, message: 'Unexpected server response.' }));
        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to update attendance record.');
        }
        applyRecordUiUpdate(recordId, data);
        return data;
    }

    document.querySelectorAll('.status-override').forEach((item) => {
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const recordId = item.getAttribute('data-id');
            const newStatus = item.getAttribute('data-status');
            const btnEl = document.getElementById('statusBtn_' + recordId);
            const previousHtml = btnEl ? btnEl.innerHTML : '';

            if (btnEl) {
                btnEl.disabled = true;
                btnEl.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
            }

            try {
                const data = await submitAttendanceOverride(recordId, {
                    status: newStatus,
                    reason: 'Manual override by instructor',
                });
                showRecordsFeedback(data.message || 'Attendance record updated.', 'success');
            } catch (err) {
                if (btnEl) btnEl.innerHTML = previousHtml;
                showRecordsFeedback(err.message, 'error');
            } finally {
                if (btnEl) btnEl.disabled = false;
            }
        });
    });

    const modalEl = document.getElementById('classAttendanceCorrectionModal');
    const correctionForm = document.getElementById('classAttendanceCorrectionForm');
    const statusSelect = document.getElementById('correctionStatusSelect');
    const excuseWrap = document.getElementById('correctionExcuseWrap');
    const excuseNoteInput = document.getElementById('correctionExcuseNoteInput');
    const reasonInput = document.getElementById('correctionReasonInput');
    const summaryEl = document.getElementById('correctionRecordSummary');
    const recordIdInput = document.getElementById('correctionRecordId');
    const modalErrorEl = document.getElementById('correctionModalError');
    const saveBtn = document.getElementById('correctionSaveBtn');

    function syncExcuseVisibility() {
        if (excuseWrap && statusSelect) {
            excuseWrap.style.display = statusSelect.value === 'Excused' ? '' : 'none';
        }
    }

    statusSelect?.addEventListener('change', syncExcuseVisibility);

    document.querySelectorAll('[data-correct-record]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!modalEl || !recordIdInput) return;
            recordIdInput.value = btn.getAttribute('data-record-id') || '';
            if (summaryEl) {
                summaryEl.textContent = (btn.getAttribute('data-student-name') || 'Student') + ' — ' + (btn.getAttribute('data-record-date') || '');
            }
            if (statusSelect) {
                statusSelect.value = btn.getAttribute('data-status') || 'Present';
            }
            if (reasonInput) reasonInput.value = '';
            if (excuseNoteInput) excuseNoteInput.value = btn.getAttribute('data-excuse-note') || '';
            if (modalErrorEl) modalErrorEl.hidden = true;
            syncExcuseVisibility();

            if (typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });
    });

    correctionForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const recordId = recordIdInput?.value;
        if (!recordId) return;
        if (modalErrorEl) modalErrorEl.hidden = true;
        if (saveBtn) saveBtn.disabled = true;

        try {
            const data = await submitAttendanceOverride(recordId, {
                status: statusSelect?.value || 'Present',
                reason: reasonInput?.value || 'Authorized class record correction',
                excuse_note: statusSelect?.value === 'Excused' ? (excuseNoteInput?.value || '') : null,
            });
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }
            showRecordsFeedback(data.message || 'Attendance correction saved.', 'success');
        } catch (err) {
            if (modalErrorEl) {
                modalErrorEl.textContent = err.message;
                modalErrorEl.hidden = false;
            }
        } finally {
            if (saveBtn) saveBtn.disabled = false;
        }
    });
})();
</script>
@endpush
