@extends('layouts.app')
@section('page-title', 'Excuse Submissions')

@section('content')
<div class="ent-section ent-fade-up student-excuse-page">
    <header class="student-page-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: clamp(1.4rem, 3.5vw, 1.9rem); font-weight: 800; color: var(--cs-ink, #f3e7cd); margin-bottom:4px; letter-spacing: -0.02em;">Excuse Submissions</h1>
            <p style="color: var(--cs-muted, #b39b82); font-size: 0.92rem; margin:0;">Manage and track your absence excuses</p>
        </div>
        <a href="{{ route('excuses.create_general') }}" class="ent-btn ent-btn-primary cs-excuse-new-btn" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #140f0a; border: none; font-weight: 700;">
            <i class="bi bi-plus-circle-fill" style="margin-right: 6px;"></i> Submit Excuse Letter
        </a>
    </header>

    <div class="sd-excuse-summary cs-desktop-only" aria-label="Excuse statistics">
        <div class="sd-excuse-stat">
            <strong>{{ $absentRecords->count() }}</strong>
            <span>Needs an excuse</span>
        </div>
        <div class="sd-excuse-stat">
            <strong>{{ $excuseSubmissions->where('status', 'pending')->count() }}</strong>
            <span>Under review</span>
        </div>
        <div class="sd-excuse-stat">
            <strong style="color: var(--cs-present);">{{ $excuseSubmissions->where('status', 'approved')->count() }}</strong>
            <span>Approved</span>
        </div>
        <div class="sd-excuse-stat">
            <strong>{{ $excuseSubmissions->count() }}</strong>
            <span>Total submitted</span>
        </div>
    </div>

    @if(session('success'))
    <div class="cs-flash-success" style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: var(--cs-present, #4ade80); border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-check-circle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="cs-flash-error" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: var(--cs-absent, #f87171); border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('error') }}</span>
    </div>
    @endif

    <div class="student-view-switcher" role="group" aria-label="Excuse views">
        <button type="button" class="d-none d-lg-inline-flex" data-excuse-view="all" aria-controls="excuseNeedsPanel excuseHistoryPanel" aria-pressed="false">Side by side</button>
        <button type="button" data-excuse-view="needs" aria-controls="excuseNeedsPanel" aria-pressed="true">Needs an excuse ({{ $absentRecords->count() }})</button>
        <button type="button" data-excuse-view="history" aria-controls="excuseHistoryPanel" aria-pressed="false">My submissions ({{ $excuseSubmissions->count() }})</button>
    </div>
    <div class="row g-4">
        <!-- Absent Records Requiring Excuses -->
        <div class="col-12 col-lg-5" id="excuseNeedsPanel" data-excuse-panel="needs">
            <div class="excuse-panel" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2); height: 100%;">
                <div class="excuse-panel-head" style="padding: 20px 24px; border-bottom: 1px solid var(--cs-border, rgba(255,255,255,0.06)); background: var(--cs-surface-2, rgba(0,0,0,0.2));">
                    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--cs-ink, var(--gold)); margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-person-x" style="color: var(--cs-absent, #f87171);"></i> Action Required: Absences
                    </h2>
                </div>
                
                <div class="excuse-panel-list" style="padding: 0;">
                    @forelse($absentRecords as $record)
                        <div class="excuse-absence-row" style="padding: 18px 22px; border-bottom: 1px solid var(--cs-border, rgba(255,255,255,0.06)); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; transition: background 0.2s ease;">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 700; color: var(--cs-ink, #f3e7cd); margin-bottom: 4px;">{{ $record->subject->name ?? $record->subject_code }}</h3>
                                <div style="font-size: 0.85rem; color: var(--cs-muted, #b39b82);">
                                    <i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('excuses.create', $record) }}" class="ent-btn ent-btn-primary cs-excuse-row-btn" style="padding: 8px 16px; font-size: 0.85rem; background: linear-gradient(135deg, var(--gold), #b88a44); color: #140f0a; border: none; font-weight: 700;">
                                    <i class="bi bi-plus-circle-fill" style="margin-right: 4px;"></i> Submit Excuse
                                </a>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 48px 24px; text-align: center;">
                            <i class="bi bi-check2-circle" style="font-size: 3.5rem; color: var(--cs-present, #4ade80); opacity: 0.8; margin-bottom: 16px; display: block;"></i>
                            <h4 style="font-size: 1.25rem; font-weight: 700; color: var(--cs-ink, #f3e7cd); margin-bottom: 8px;">All caught up!</h4>
                            <p style="color: var(--cs-muted, #b39b82); margin: 0; font-size: 0.95rem;">You don't have any unexcused absences.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Submitted Excuses History -->
        <div class="col-12 col-lg-7" id="excuseHistoryPanel" data-excuse-panel="history">
            <div class="excuse-panel" style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2); height: 100%;">
                <div class="excuse-panel-head" style="padding: 20px 24px; border-bottom: 1px solid var(--cs-border, rgba(255,255,255,0.06)); background: var(--cs-surface-2, rgba(0,0,0,0.2));">
                    <h2 style="font-size: 1.2rem; font-weight: 700; color: var(--cs-ink, #60a5fa); margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-file-text-fill" style="color: var(--cs-accent, #60a5fa);"></i> Submission History
                    </h2>
                </div>
                
                <div class="excuse-history-scroll" style="padding: 0; max-height: 600px; overflow-y: auto;">
                    @forelse($excuseSubmissions as $excuse)
                        <div class="excuse-history-item" style="padding: 20px 24px; border-bottom: 1px solid var(--cs-border, rgba(255,255,255,0.06));">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 700; color: var(--cs-ink, #f3e7cd); margin-bottom: 4px;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</h3>
                                    <div style="font-size: 0.8rem; color: var(--cs-muted, #b39b82);">
                                        <span style="color: var(--cs-absent, #fca5a5); font-weight: 600;">Absent:</span> {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }} 
                                        <span style="margin:0 6px; opacity: 0.4;">•</span> 
                                        Submitted {{ $excuse->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                
                                @if($excuse->status === 'pending')
                                    <span class="excuse-status-pill is-pending" style="background: var(--cs-late-soft, rgba(245,158,11,0.1)); border: 1px solid var(--cs-late-border, rgba(245,158,11,0.2)); color: var(--cs-late, #fbbf24); padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Pending</span>
                                @elseif($excuse->status === 'approved')
                                    <span class="excuse-status-pill is-approved" style="background: var(--cs-present-soft, rgba(34,197,94,0.1)); border: 1px solid var(--cs-present-border, rgba(34,197,94,0.2)); color: var(--cs-present, #4ade80); padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Approved</span>
                                @else
                                    <span class="excuse-status-pill is-rejected" style="background: var(--cs-absent-soft, rgba(239,68,68,0.1)); border: 1px solid var(--cs-absent-border, rgba(239,68,68,0.2)); color: var(--cs-absent, #f87171); padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Rejected</span>
                                @endif
                            </div>
                            
                            <div class="excuse-reason-box" style="background: var(--cs-surface-2, rgba(0,0,0,0.3)); border-radius: 10px; padding: 12px 14px; border: 1px solid var(--cs-border, rgba(255,255,255,0.03));">
                                <div style="font-size: 0.85rem; font-weight: 700; color: var(--cs-ink, var(--gold)); margin-bottom: 4px;">Reason: {{ $excuse->reason }}</div>
                                <div style="font-size: 0.9rem; color: var(--cs-ink-2, #d6b67b); line-height: 1.5;">{{ $excuse->description }}</div>
                            </div>
                            
                            @if($excuse->attachments)
                                <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                                    @foreach($excuse->attachments ?? [] as $index => $attachment)
                                        <a href="{{ route('excuse-attachments.show', [$excuse->id, $index]) }}" target="_blank" rel="noopener noreferrer" class="excuse-attach-chip" style="background: var(--cs-surface-2, rgba(255,255,255,0.06)); border: 1px solid var(--cs-border-strong, rgba(255,255,255,0.12)); color: var(--cs-ink, #f3e7cd); min-height: 40px; padding: 8px 14px; border-radius: 10px; font-size: 0.78rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s;">
                                            <i class="bi bi-paperclip" aria-hidden="true"></i> View Attachment
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($excuse->status === 'rejected' && $excuse->admin_notes)
                                <div class="excuse-reviewer-note" style="margin-top: 12px; padding:10px 12px; background:var(--cs-absent-soft, rgba(239,68,68,0.05)); border-left:3px solid var(--cs-absent, #f87171); border-radius: 0 8px 8px 0;">
                                    <div style="font-size:0.75rem; font-weight:700; color: var(--cs-absent, #f87171); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Reviewer Note</div>
                                    <p style="font-size:0.8rem; color:var(--cs-ink, rgba(248,231,211,0.9)); margin:0;">{{ $excuse->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="padding: 48px 24px; text-align: center;">
                            <i class="bi bi-inbox" style="font-size: 3.5rem; color: var(--cs-muted, rgba(255,255,255,0.1)); margin-bottom: 16px; display: block;"></i>
                            <h4 style="font-size: 1.1rem; font-weight: 600; color: var(--cs-muted, #b39b82);">No Submissions Yet</h4>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
<script @cspNonce>
(function () {
    const buttons = document.querySelectorAll('[data-excuse-view]');
    const panels = document.querySelectorAll('[data-excuse-panel]');
    const needsPanel = document.getElementById('excuseNeedsPanel');
    const historyPanel = document.getElementById('excuseHistoryPanel');
    function showExcuseView(view) {
        buttons.forEach(button => button.setAttribute('aria-pressed', button.dataset.excuseView === view ? 'true' : 'false'));
        panels.forEach(panel => panel.hidden = (view !== 'all' && panel.dataset.excusePanel !== view));
        if (needsPanel && historyPanel) {
            if (view === 'all') {
                needsPanel.className = 'col-12 col-lg-5';
                historyPanel.className = 'col-12 col-lg-7';
            } else {
                needsPanel.className = 'col-12';
                historyPanel.className = 'col-12';
            }
        }
    }
    buttons.forEach(button => button.addEventListener('click', () => showExcuseView(button.dataset.excuseView)));
    const initialView = window.innerWidth >= 1024 ? 'all' : @json($absentRecords->isNotEmpty() ? 'needs' : 'history');
    showExcuseView(initialView);
})();
</script>
@endsection
