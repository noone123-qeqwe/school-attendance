@extends('layouts.app')
@section('page-title', 'My Excuses')

@section('content')
<div class="ent-section ent-fade-up">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:16px;">
        <div>
            <h1 style="font-size: 2rem; font-weight: 800; color: #f3e7cd; margin-bottom:4px; letter-spacing: -0.02em;">My Excuses</h1>
            <p style="color: #b39b82; font-size: 0.95rem; margin:0;">Submit and track your absence excuses for your classes</p>
        </div>
        <a href="{{ route('teacher.excuses.create') }}" class="ent-btn ent-btn-primary" style="background: linear-gradient(135deg, var(--gold), #b88a44); color: #1a1a2e; border: none; font-weight: 700;">
            <i class="bi bi-plus-circle-fill" style="margin-right: 4px;"></i> Submit New Excuse
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

    <div class="row">
        <!-- Submitted Excuses History -->
        <div class="col-12">
            <div style="background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.06); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 32px rgba(0,0,0,0.2);">
                <div style="padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.06); background: rgba(0,0,0,0.2);">
                    <h2 style="font-size: 1.25rem; font-weight: 700; color: #60a5fa; margin:0; display:flex; align-items:center; gap:8px;">
                        <i class="bi bi-file-text-fill"></i> Submission History
                    </h2>
                </div>
                
                <div style="padding: 0;">
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
                            <x-empty-state 
                                icon="bi-inbox" 
                                title="No Submissions Yet" 
                                subtitle="You haven't submitted any excuses." 
                            />
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
