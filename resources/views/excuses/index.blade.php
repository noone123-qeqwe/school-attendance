@extends('layouts.app')
@section('page-title', 'Excuse Submissions')

@section('content')
<div class="ent-section ent-fade-up">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #f3e7cd; margin-bottom:4px; letter-spacing: -0.02em;">Excuse Submissions</h1>
            <p style="color: #b39b82; font-size: 0.95rem; margin:0;">Manage and track your absence excuses</p>
        </div>
        <a href="{{ route('excuses.create_general') }}" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; border: none; font-weight: 700;">
            <i class="bi bi-plus-circle-fill" style="margin-right: 4px;"></i> New Leave Request
        </a>
    </div>

    @if(session('success'))
    <div style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #4ade80; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-check-circle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #f87171; border-radius: 12px; padding: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size:1.25rem;"></i>
        <span style="font-size:0.875rem; font-weight:500;">{{ session('error') }}</span>
    </div>
    @endif

    <div class="row g-4">
        <!-- Absent Records Requiring Excuses -->
        <div class="col-12 col-xl-6">
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2); height: 100%;">
                <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--gold); margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-person-x" style="color: #f87171;"></i> Action Required: Absences
                    </h2>
                </div>
                
                <div style="padding: 0;">
                    @forelse($absentRecords as $record)
                        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.06); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; transition: background 0.2s ease;" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background='transparent'">
                            <div>
                                <h3 style="font-size: 1rem; font-weight: 700; color: #f3e7cd; margin-bottom: 4px;">{{ $record->subject->name ?? $record->subject_code }}</h3>
                                <div style="font-size: 0.85rem; color: #b39b82;">
                                    <i class="bi bi-calendar3 me-1"></i> {{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}
                                </div>
                            </div>
                            <div>
                                <a href="{{ route('excuses.create', $record) }}" class="ent-btn ent-btn-primary" style="padding: 8px 16px; font-size: 0.85rem; background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; border: none; font-weight: 700;">
                                    <i class="bi bi-plus-circle-fill" style="margin-right: 4px;"></i> Submit Excuse
                                </a>
                            </div>
                        </div>
                    @empty
                        <div style="padding: 48px 24px; text-align: center;">
                            <i class="bi bi-check2-circle" style="font-size: 3.5rem; color: #4ade80; opacity: 0.8; margin-bottom: 16px; display: block;"></i>
                            <h4 style="font-size: 1.25rem; font-weight: 700; color: #f3e7cd; margin-bottom: 8px;">All caught up!</h4>
                            <p style="color: #b39b82; margin: 0; font-size: 0.95rem;">You don't have any unexcused absences.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Submitted Excuses History -->
        <div class="col-12 col-xl-6">
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2); height: 100%;">
                <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #60a5fa; margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-file-text-fill"></i> Submission History
                    </h2>
                </div>
                
                <div style="padding: 0; max-height: 600px; overflow-y: auto;">
                    @forelse($excuseSubmissions as $excuse)
                        <div style="padding: 20px 24px; border-bottom: 1px solid rgba(255,255,255,0.06);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <h3 style="font-size: 1rem; font-weight: 700; color: #f3e7cd; margin-bottom: 4px;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</h3>
                                    <div style="font-size: 0.8rem; color: #b39b82;">
                                        <span style="color: #fca5a5; font-weight: 600;">Absent:</span> {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }} 
                                        <span style="margin:0 6px; opacity: 0.4;">•</span> 
                                        Submitted {{ $excuse->created_at->diffForHumans() }}
                                    </div>
                                </div>
                                
                                @if($excuse->status === 'pending')
                                    <span style="background: rgba(245,158,11,0.1); border: 1px solid rgba(245,158,11,0.2); color: #fbbf24; padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Pending</span>
                                @elseif($excuse->status === 'approved')
                                    <span style="background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #4ade80; padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Approved</span>
                                @else
                                    <span style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #f87171; padding: 4px 10px; border-radius: 99px; font-size: 0.75rem; font-weight: 700;">Rejected</span>
                                @endif
                            </div>
                            
                            <div style="background: rgba(0,0,0,0.3); border-radius: 8px; padding: 12px; border: 1px solid rgba(255,255,255,0.03);">
                                <div style="font-size: 0.85rem; font-weight: 700; color: var(--gold); margin-bottom: 4px;">Reason: {{ $excuse->reason }}</div>
                                <div style="font-size: 0.9rem; color: #d6b67b; line-height: 1.5;">{{ $excuse->description }}</div>
                            </div>
                            
                            @if($excuse->attachments)
                                <div style="margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap;">
                                    @foreach(json_decode($excuse->attachments, true) ?? [] as $attachment)
                                        <a href="{{ Storage::url($attachment) }}" target="_blank" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #f3e7cd; padding: 6px 12px; border-radius: 6px; font-size: 0.75rem; text-decoration: none; display: flex; align-items: center; gap: 6px; transition: all 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background='rgba(255,255,255,0.05)'">
                                            <i class="bi bi-paperclip"></i> View Attachment
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if($excuse->status === 'rejected' && $excuse->admin_notes)
                                <div style="margin-top: 12px; padding:10px 12px; background:rgba(239,68,68,0.05); border-left:3px solid #f87171; border-radius: 0 8px 8px 0;">
                                    <div style="font-size:0.75rem; font-weight:700; color: #f87171; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Reviewer Note</div>
                                    <p style="font-size:0.8rem; color:rgba(248,231,211,0.9); margin:0;">{{ $excuse->admin_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div style="padding: 48px 24px; text-align: center;">
                            <i class="bi bi-inbox" style="font-size: 3.5rem; color: rgba(255,255,255,0.1); margin-bottom: 16px; display: block;"></i>
                            <h4 style="font-size: 1.1rem; font-weight: 600; color: #b39b82;">No Submissions Yet</h4>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection