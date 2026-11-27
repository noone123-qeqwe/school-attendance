@extends('layouts.app')
@section('page-title', 'Excuse Submissions')
@section('page-sub', 'Manage and track your absence excuse letters')

@section('content')
@php
    $pendingCount = $excuseSubmissions->where('status', 'pending')->count();
    $approvedCount = $excuseSubmissions->where('status', 'approved')->count();
    $rejectedCount = $excuseSubmissions->where('status', 'rejected')->count();
@endphp
<div class="ent-section ent-fade-up student-excuse-page sd-excuse-page">
    <header class="sd-excuse-hero">
        <div>
            <h1>Excuse Submissions</h1>
            <p>Submit supporting documentation for absences and track reviewer decisions in real time.</p>
        </div>
        <a href="{{ route('excuses.create_general') }}" class="ent-btn ent-btn-primary">
            <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i> Submit Excuse Letter
        </a>
    </header>

    @if(session('success'))
    <x-alert-banner role="status" class="mb-4" data-alert-transient style="background: rgba(52,211,153,0.12); border: 1px solid rgba(52,211,153,0.32); color: #6ee7b7; border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-check-circle-fill fs-5" aria-hidden="true"></i>
        <span class="small fw-semibold">{{ session('success') }}</span>
    </x-alert-banner>
    @endif

    @if(session('error'))
    <x-alert-banner role="alert" class="mb-4" data-alert-transient style="background: rgba(255,107,122,0.12); border: 1px solid rgba(255,107,122,0.34); color: #ffb3bc; border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-exclamation-triangle-fill fs-5" aria-hidden="true"></i>
        <span class="small fw-semibold">{{ session('error') }}</span>
    </x-alert-banner>
    @endif

    <section class="sd-excuse-kpis" aria-label="Excuse summary">
        <div class="sd-excuse-kpi {{ $absentRecords->count() > 0 ? 'is-danger' : '' }}">
            <span class="sd-excuse-kpi-val">{{ $absentRecords->count() }}</span>
            <span class="sd-excuse-kpi-lbl">Needs Excuse</span>
        </div>
        <div class="sd-excuse-kpi is-warning">
            <span class="sd-excuse-kpi-val">{{ $pendingCount }}</span>
            <span class="sd-excuse-kpi-lbl">Pending Review</span>
        </div>
        <div class="sd-excuse-kpi is-success">
            <span class="sd-excuse-kpi-val">{{ $approvedCount }}</span>
            <span class="sd-excuse-kpi-lbl">Approved</span>
        </div>
        <div class="sd-excuse-kpi {{ $rejectedCount > 0 ? 'is-danger' : '' }}">
            <span class="sd-excuse-kpi-val">{{ $rejectedCount }}</span>
            <span class="sd-excuse-kpi-lbl">Rejected</span>
        </div>
    </section>

    <div class="mb-3">
        <div class="student-view-switcher" role="group" aria-label="Excuse views">
            <button type="button" class="d-none d-lg-inline-flex" data-excuse-view="all" aria-controls="excuseNeedsPanel excuseHistoryPanel" aria-pressed="false">Side by side</button>
            <button type="button" data-excuse-view="needs" aria-controls="excuseNeedsPanel" aria-pressed="true">Needs an excuse ({{ $absentRecords->count() }})</button>
            <button type="button" data-excuse-view="history" aria-controls="excuseHistoryPanel" aria-pressed="false">My submissions ({{ $excuseSubmissions->count() }})</button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Absent Records Requiring Excuses -->
        <div class="col-12 col-lg-5" id="excuseNeedsPanel" data-excuse-panel="needs">
            <div class="sd-excuse-panel">
                <div class="sd-excuse-panel-head">
                    <h2>
                        <i class="bi bi-person-x-fill text-danger" aria-hidden="true"></i>
                        Action Required: Absences
                    </h2>
                    <x-badge :variant="$absentRecords->count() > 0 ? 'danger' : 'success'" size="sm">
                        {{ $absentRecords->count() }} {{ \Illuminate\Support\Str::plural('item', $absentRecords->count()) }}
                    </x-badge>
                </div>

                <div>
                    @forelse($absentRecords as $record)
                        <div class="sd-excuse-row d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div class="min-w-0">
                                <h3 class="sd-excuse-row-title">{{ $record->subject->name ?? $record->subject_code }}</h3>
                                <div class="sd-excuse-row-meta">
                                    <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>{{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}
                                </div>
                            </div>
                            <a href="{{ route('excuses.create', $record) }}" class="ent-btn ent-btn-primary" style="padding: 8px 15px; font-size: 0.82rem;">
                                <i class="bi bi-plus-circle-fill me-1" aria-hidden="true"></i> Submit Excuse
                            </a>
                        </div>
                    @empty
                        <div class="sd-excuse-empty">
                            <i class="bi bi-check2-circle" style="font-size: 3rem; color: #34d399;" aria-hidden="true"></i>
                            <h4>All caught up!</h4>
                            <p>You don't have any unexcused absences right now.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Submitted Excuses History -->
        <div class="col-12 col-lg-7" id="excuseHistoryPanel" data-excuse-panel="history">
            <div class="sd-excuse-panel">
                <div class="sd-excuse-panel-head">
                    <h2>
                        <i class="bi bi-file-earmark-text-fill" style="color: #7dd3fc;" aria-hidden="true"></i>
                        Submission History
                    </h2>
                    <x-badge variant="info" size="sm">{{ $excuseSubmissions->count() }} total</x-badge>
                </div>

                <div class="excuse-history-scroll" style="max-height: 620px; overflow-y: auto;">
                    @forelse($excuseSubmissions as $excuse)
                        <div class="sd-excuse-row">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                <div class="min-w-0">
                                    <h3 class="sd-excuse-row-title">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</h3>
                                    <div class="sd-excuse-row-meta">
                                        <span style="color: #ff8e9b; font-weight: 700;">Absent:</span> {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }}
                                        <span class="mx-1 opacity-50" aria-hidden="true">&bull;</span>
                                        Submitted {{ $excuse->created_at->diffForHumans() }}
                                    </div>
                                </div>

                                @if($excuse->status === 'pending')
                                    <x-badge variant="warning" size="sm" :dot="true">Pending</x-badge>
                                @elseif($excuse->status === 'approved')
                                    <x-badge variant="success" size="sm" :dot="true">Approved</x-badge>
                                @else
                                    <x-badge variant="danger" size="sm" :dot="true">Rejected</x-badge>
                                @endif
                            </div>

                            <div class="sd-excuse-reason-box">
                                <div class="sd-excuse-reason-title">Reason: {{ $excuse->reason }}</div>
                                <div class="sd-excuse-reason-body">{{ $excuse->description }}</div>
                            </div>

                            @if($excuse->attachments)
                                <div class="mt-3 d-flex gap-2 flex-wrap">
                                    @foreach($excuse->attachments ?? [] as $index => $attachment)
                                        <a href="{{ route('excuse-attachments.show', [$excuse->id, $index]) }}" target="_blank" rel="noopener noreferrer" class="sd-excuse-attachment">
                                            <i class="bi bi-paperclip" aria-hidden="true"></i> View Attachment
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($excuse->status === 'rejected' && $excuse->admin_notes)
                                <div class="sd-excuse-reviewer-note">
                                    <div style="font-size:0.74rem;font-weight:800;color:#ff8e9b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:4px;">Reviewer Note</div>
                                    <p style="font-size:0.84rem;color:#FAF8F5;margin:0;">{{ $excuse->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="sd-excuse-empty">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: rgba(255, 255, 255,0.22);" aria-hidden="true"></i>
                            <h4>No Submissions Yet</h4>
                            <p>Submitted excuse letters and reviewer decisions will appear here.</p>
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
