@extends('layouts.app')
@section('page-title', 'Excuse Submissions')

@section('content')
@php
    $pendingCount = $excuseSubmissions->where('status', 'pending')->count();
    $approvedCount = $excuseSubmissions->where('status', 'approved')->count();
    $rejectedCount = $excuseSubmissions->where('status', 'rejected')->count();
    $totalCount = $excuseSubmissions->count();
    $enrolledSubjects = $subjects ?? collect();
@endphp

<div>
    <header class="sf-page-head">
        <div>
            <h1>Excuse Submissions</h1>
            <p>Submit supporting documentation for missed classes and track faculty review status</p>
        </div>
        <div class="sf-page-actions">
            <button type="button" class="sf-btn sf-btn-primary" id="sfOpenExcuseModalBtn" onclick="window.openSfModal?.('sfSubmitExcuseModal')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
                <span>Submit an excuse</span>
            </button>
            <a href="{{ route('excuses.create_general') }}" class="sf-btn sf-btn-ghost sf-desktop-only" title="Open full multi-subject excuse form">
                Full form
            </a>
        </div>
    </header>

    @if(session('success'))
        <div class="sf-flash is-success" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="sf-flash is-error" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="sf-flash is-error" role="alert">{{ $errors->first() }}</div>
    @endif

    {{-- Action Required: Unexcused Absences (shown only when student has open absences) --}}
    @if($absentRecords->isNotEmpty())
        <section class="sf-card" style="margin-bottom:16px;border-color:var(--sf-danger-border);" aria-label="Absences awaiting excuse">
            <div class="sf-card-head">
                <div style="display:flex;align-items:center;gap:8px;">
                    <h2 class="sf-card-title">Needs an excuse</h2>
                    <span class="sf-pill is-danger sf-mono">{{ $absentRecords->count() }}</span>
                </div>
                <span style="font-size:11.5px;color:var(--sf-text-muted);">Official absences without a submitted excuse</span>
            </div>
            <div>
                @foreach($absentRecords as $record)
                    <div class="sf-record-item" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                        <div style="min-width:0;">
                            <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap;">
                                <span class="sf-code-chip is-rose">{{ $record->subject_code }}</span>
                                <strong style="font-size:13px;color:var(--sf-text);">{{ $record->subject->name ?? $record->subject_code }}</strong>
                            </div>
                            <div class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);margin-top:3px;">
                                Absent · {{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}
                            </div>
                        </div>
                        <a href="{{ route('excuses.create', $record) }}" class="sf-btn sf-btn-secondary sf-btn-sm">
                            Submit excuse
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Status Filter Pills --}}
    <div class="sf-toolbar">
        <div class="sf-filter-pills" role="tablist" aria-label="Filter excuse submissions by status">
            <button type="button" class="sf-filter-pill active" data-excuse-status="all" role="tab" aria-selected="true">
                All <span class="sf-mono" style="opacity:0.75;">{{ $totalCount }}</span>
            </button>
            <button type="button" class="sf-filter-pill" data-excuse-status="pending" role="tab" aria-selected="false">
                Pending <span class="sf-mono" style="opacity:0.75;">{{ $pendingCount }}</span>
            </button>
            <button type="button" class="sf-filter-pill" data-excuse-status="approved" role="tab" aria-selected="false">
                Approved <span class="sf-mono" style="opacity:0.75;">{{ $approvedCount }}</span>
            </button>
            <button type="button" class="sf-filter-pill" data-excuse-status="rejected" role="tab" aria-selected="false">
                Rejected <span class="sf-mono" style="opacity:0.75;">{{ $rejectedCount }}</span>
            </button>
        </div>
    </div>

    {{-- Submissions Card --}}
    <section class="sf-card" aria-label="Excuse submission history">
        @forelse($excuseSubmissions as $excuse)
            @php
                $pillTone = match ($excuse->status) {
                    'approved' => 'is-emerald',
                    'rejected' => 'is-danger',
                    default => 'is-amber',
                };
                $attachmentCount = is_array($excuse->attachments) ? count($excuse->attachments) : 0;
            @endphp
            <article class="sf-record-item sf-excuse-card-row" data-status="{{ $excuse->status }}">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <div style="min-width:0;flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:5px;">
                            @if($excuse->attendance?->subject_code)
                                <span class="sf-code-chip is-rose">{{ $excuse->attendance->subject_code }}</span>
                            @endif
                            <strong style="font-size:13.5px;color:var(--sf-text);">
                                {{ $excuse->attendance?->subject?->name ?? $excuse->attendance?->subject_code ?? 'Attendance Record' }}
                            </strong>
                            <span class="sf-pill {{ $pillTone }}">{{ ucfirst($excuse->status) }}</span>
                        </div>

                        <div class="sf-mono" style="font-size:11.5px;color:var(--sf-text-muted);margin-bottom:8px;">
                            Absent: {{ $excuse->attendance?->date ? \Carbon\Carbon::parse($excuse->attendance->date)->format('M j, Y') : 'Date unavailable' }}
                            · Submitted {{ $excuse->created_at->diffForHumans() }}
                        </div>

                        <div style="padding:10px 12px;border-radius:10px;background:var(--sf-surface-2);border:1px solid var(--sf-border);font-size:12.5px;">
                            <div style="font-weight:600;color:var(--sf-text);margin-bottom:3px;">{{ $excuse->reason }}</div>
                            @if(filled($excuse->description))
                                <div style="color:var(--sf-text-secondary);line-height:1.45;">{{ $excuse->description }}</div>
                            @endif
                        </div>

                        @if($attachmentCount > 0)
                            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px;">
                                @foreach($excuse->attachments ?? [] as $index => $attachment)
                                    <a href="{{ route('excuse-attachments.show', [$excuse->id, $index]) }}"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       class="sf-btn sf-btn-ghost sf-btn-sm">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                        <span>Attachment {{ $index + 1 }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if($excuse->status === 'rejected' && filled($excuse->admin_notes))
                            <div style="margin-top:8px;padding:9px 12px;border-radius:9px;background:var(--sf-danger-bg);border:1px solid var(--sf-danger-border);font-size:12px;">
                                <strong style="color:var(--sf-danger);display:block;margin-bottom:2px;">Reviewer Note</strong>
                                <span style="color:var(--sf-text-soft);">{{ $excuse->admin_notes }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="sf-empty-state">
                <span class="sf-empty-icon-box">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>
                </span>
                <h3>No excuse submissions</h3>
                <p>If you missed a class due to illness, family emergency, or an official campus activity, submit a documented excuse for review.</p>
                <button type="button" class="sf-btn sf-btn-primary" style="margin-top:8px;" onclick="window.openSfModal?.('sfSubmitExcuseModal')">
                    + Submit an excuse
                </button>
            </div>
        @endforelse
        <div class="sf-empty-state" id="sfExcuseFilterEmpty" hidden>
            <h3>No submissions in this category</h3>
            <p>Select another status filter above to view your excuse history.</p>
        </div>
    </section>
</div>

{{-- Submit Excuse Request Modal (Desktop & Mobile with circular bottom X close button) --}}
<div class="sf-modal-backdrop" id="sfSubmitExcuseModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="sfSubmitExcuseTitle">
    <div class="sf-modal-card is-wide">
        <div class="sf-modal-head">
            <div>
                <h3 id="sfSubmitExcuseTitle">Submit an Excuse Request</h3>
                <p>Attach supporting documentation for faculty review</p>
            </div>
            <button type="button" class="sf-modal-close" data-close-modal="sfSubmitExcuseModal" aria-label="Close">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('excuses.store_general') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="sf-modal-body" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                    <div>
                        <label class="sf-label" for="sfModalCourse">Course *</label>
                        <select name="subject_code" id="sfModalCourse" class="sf-select" required>
                            @forelse($enrolledSubjects as $subj)
                                <option value="{{ $subj->code }}" @selected(old('subject_code') === $subj->code)>{{ $subj->code }} — {{ $subj->name }}</option>
                            @empty
                                <option value="">No enrolled courses</option>
                            @endforelse
                        </select>
                    </div>
                    <div>
                        <label class="sf-label" for="sfModalDate">Absence Date *</label>
                        <input type="date" name="date" id="sfModalDate" class="sf-input" max="{{ today()->toDateString() }}" value="{{ old('date', date('Y-m-d')) }}" required>
                    </div>
                </div>

                <div>
                    <label class="sf-label" for="sfModalReason">Reason Category *</label>
                    <select name="reason" id="sfModalReason" class="sf-select" required>
                        <option value="Medical/Health Issues" @selected(old('reason') === 'Medical/Health Issues')>Medical / Illness</option>
                        <option value="Family Emergency" @selected(old('reason') === 'Family Emergency')>Family Emergency</option>
                        <option value="Official School Activity / Event" @selected(old('reason') === 'Official School Activity / Event')>Official School Activity / Event</option>
                        <option value="Personal Emergency" @selected(old('reason') === 'Personal Emergency')>Personal Emergency</option>
                        <option value="Transportation Issues" @selected(old('reason') === 'Transportation Issues')>Transportation Issues</option>
                        <option value="Weather Conditions" @selected(old('reason') === 'Weather Conditions')>Weather Conditions</option>
                        <option value="Academic Activity" @selected(old('reason') === 'Academic Activity')>Academic Activity</option>
                        <option value="Other" @selected(old('reason') === 'Other')>Other</option>
                    </select>
                </div>

                <div>
                    <label class="sf-label" for="sfModalAttachment">Supporting Document (Optional)</label>
                    <input type="file" name="attachments[]" id="sfModalAttachment" class="sf-input" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx">
                    <div style="font-size:11px;color:var(--sf-text-muted);margin-top:4px;">Medical certificate, letter, or proof (JPG, PNG, PDF, DOC · Max 5MB)</div>
                </div>

                <div>
                    <label class="sf-label" for="sfModalDesc">Explanation *</label>
                    <textarea name="description" id="sfModalDesc" class="sf-textarea" maxlength="500" placeholder="Briefly explain why you missed this class..." required>{{ old('description') }}</textarea>
                </div>
            </div>
            <div class="sf-modal-foot">
                <button type="button" class="sf-btn sf-btn-ghost" data-close-modal="sfSubmitExcuseModal">Cancel</button>
                <button type="submit" class="sf-btn sf-btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
    <button type="button" class="sf-modal-circle-close sf-mobile-only" data-close-modal="sfSubmitExcuseModal" aria-label="Close modal">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
    </button>
</div>
@endsection

@section('scripts')
<script @cspNonce>
(function () {
    const pills = document.querySelectorAll('[data-excuse-status]');
    const rows = Array.from(document.querySelectorAll('.sf-excuse-card-row'));
    const emptyEl = document.getElementById('sfExcuseFilterEmpty');

    pills.forEach((pill) => {
        pill.addEventListener('click', () => {
            const status = pill.dataset.excuseStatus || 'all';
            pills.forEach((p) => {
                const active = p === pill;
                p.classList.toggle('active', active);
                p.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            let shown = 0;
            rows.forEach((row) => {
                const match = status === 'all' || row.dataset.status === status;
                row.hidden = !match;
                if (match) shown++;
            });
            if (emptyEl) emptyEl.hidden = rows.length === 0 || shown > 0;
        });
    });
})();
</script>
@endsection
