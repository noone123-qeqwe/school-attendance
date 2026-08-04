@extends('layouts.portal')
@section('page-title', 'Excuse Submissions')

@section('content')
<div class="saas-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 class="saas-heading saas-heading-lg" style="margin-bottom:4px;">Excuse Submissions</h1>
            <p class="saas-text-muted" style="margin:0;">Manage and track your absence excuses</p>
        </div>
    </div>

    @if(session('success'))
    <div style="background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.2); color:#4ade80; border-radius:var(--saas-radius-md); padding:16px; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-check-circle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.2); color:#f87171; border-radius:var(--saas-radius-md); padding:16px; margin-bottom:24px; display:flex; align-items:center; gap:12px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('error') }}</span>
    </div>
    @endif

    <div class="row">
        <!-- Absent Records Requiring Excuses -->
        <div class="col-12 col-xl-6" style="margin-bottom:24px;">
            <div class="saas-card" style="height:100%;">
                <div class="saas-card-header" style="border-bottom:1px solid var(--saas-border);">
                    <h2 class="saas-heading saas-heading-sm" style="margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-person-x" style="color:var(--saas-danger);"></i> Action Required: Absences
                    </h2>
                </div>
                
                <div class="saas-card-body" style="padding:0;">
                    @forelse($absentRecords as $record)
                        <div style="padding:16px 20px; border-bottom:1px solid var(--saas-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; transition:background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <div>
                                <h3 class="saas-heading" style="font-size:0.95rem; margin-bottom:4px;">{{ $record->subject->name ?? $record->subject_code }}</h3>
                                <div style="font-size:0.8rem; color:var(--saas-text-muted);">
                                    <i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}
                                </div>
                            </div>
                            <div>
                                @if($record->excuseSubmission)
                                    @if($record->excuseSubmission->status === 'pending')
                                        <span class="saas-badge saas-badge-warning">Pending Review</span>
                                    @elseif($record->excuseSubmission->status === 'approved')
                                        <span class="saas-badge saas-badge-success">Approved</span>
                                    @else
                                        <span class="saas-badge saas-badge-danger">Rejected</span>
                                    @endif
                                @else
                                    <a href="{{ route('excuses.create', $record) }}" class="saas-btn saas-btn-primary" style="padding:6px 14px; font-size:0.8rem;">
                                        <i class="bi bi-plus-circle"></i> Submit Excuse
                                    </a>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div style="padding:48px 20px; text-align:center;">
                            <i class="bi bi-check2-circle" style="font-size:3rem; color:var(--saas-success); opacity:0.6; margin-bottom:12px; display:block;"></i>
                            <h4 class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">All caught up!</h4>
                            <p class="saas-text-muted" style="margin:0; font-size:0.875rem;">You don't have any unexcused absences.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Submitted Excuses History -->
        <div class="col-12 col-xl-6" style="margin-bottom:24px;">
            <div class="saas-card" style="height:100%;">
                <div class="saas-card-header" style="border-bottom:1px solid var(--saas-border);">
                    <h2 class="saas-heading saas-heading-sm" style="margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-file-text text-primary"></i> Submission History
                    </h2>
                </div>
                
                <div class="saas-card-body" style="padding:0; max-height: 600px; overflow-y: auto;">
                    @forelse($excuseSubmissions as $excuse)
                        <div style="padding:20px; border-bottom:1px solid var(--saas-border);">
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
                                <div>
                                    <h3 class="saas-heading" style="font-size:0.95rem; margin-bottom:4px;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</h3>
                                    <div style="font-size:0.75rem; color:var(--saas-text-muted);">
                                        <span style="color:var(--saas-text);">Absent:</span> {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }} 
                                        <span style="margin:0 4px;">•</span> 
                                        Submitted {{ $excuse->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                
                                @if($excuse->status === 'pending')
                                    <span class="saas-badge saas-badge-warning" style="font-size:0.7rem;">Pending</span>
                                @elseif($excuse->status === 'approved')
                                    <span class="saas-badge saas-badge-success" style="font-size:0.7rem;">Approved</span>
                                @else
                                    <span class="saas-badge saas-badge-danger" style="font-size:0.7rem;">Rejected</span>
                                @endif
                            </div>
                            
                            <div style="background:rgba(255,255,255,0.02); border-radius:var(--saas-radius-md); padding:12px; margin-bottom:12px;">
                                <div style="font-size:0.8rem; font-weight:600; color:var(--saas-text); margin-bottom:4px;">Reason: {{ $excuse->reason }}</div>
                                <p style="font-size:0.8rem; color:var(--saas-text-muted); margin:0; line-height:1.5;">{{ $excuse->description }}</p>
                            </div>

                            @if($excuse->attachments && count($excuse->attachments) > 0)
                                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px;">
                                    @foreach($excuse->attachments as $attachment)
                                        <a href="{{ asset('storage/' . $attachment) }}" target="_blank" 
                                           style="display:inline-flex; align-items:center; gap:6px; padding:4px 10px; background:rgba(255,255,255,0.05); border:1px solid var(--saas-border); border-radius:999px; font-size:0.75rem; color:var(--saas-text); text-decoration:none; transition:all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                                            <i class="bi bi-paperclip text-primary"></i>
                                            {{ Str::limit(basename($attachment), 20) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($excuse->status === 'rejected' && $excuse->admin_notes)
                                <div style="padding:10px 12px; background:rgba(239,68,68,0.05); border-left:3px solid var(--saas-danger); border-radius:0 var(--saas-radius-sm) var(--saas-radius-sm) 0;">
                                    <div style="font-size:0.75rem; font-weight:700; color:var(--saas-danger); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Reviewer Note</div>
                                    <p style="font-size:0.8rem; color:rgba(248,231,211,0.9); margin:0;">{{ $excuse->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="padding:48px 20px; text-align:center;">
                            <i class="bi bi-inbox" style="font-size:3rem; color:var(--saas-text-muted); opacity:0.3; margin-bottom:12px; display:block;"></i>
                            <h4 class="saas-heading" style="font-size:1.1rem; margin-bottom:8px;">No Submissions Yet</h4>
                            <p class="saas-text-muted" style="margin:0; font-size:0.875rem;">You haven't submitted any excuses.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection