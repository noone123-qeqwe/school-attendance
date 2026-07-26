@extends('layouts.app')

@section('content')
<style>
    .excuse-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        padding: 20px;
        margin-bottom: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .excuse-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    .excuse-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        gap: 20px;
    }
    .excuse-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .excuse-date {
        font-size: 0.875rem;
        color: #64748b;
        margin: 4px 0 0 0;
    }
    .excuse-status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .status-pending {
        background: #fef3c7;
        color: #d97706;
    }
    .status-approved {
        background: #f0fdf4;
        color: #16a34a;
    }
    .status-rejected {
        background: #fef2f2;
        color: #dc2626;
    }
    .excuse-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: #800000 !important;
        color: white !important;
        border: none;
        border-radius: 10px;
        font-size: 0.875rem;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s;
        margin-top: 8px;
    }
    .excuse-btn:hover {
        background: #600000 !important;
        color: white !important;
        transform: translateY(-1px);
        text-decoration: none;
    }
    .excuse-btn:focus {
        background: #800000 !important;
        color: white !important;
        box-shadow: 0 0 0 3px rgba(128, 0, 0, 0.2);
        text-decoration: none;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #9ca3af;
    }
    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    .section-title {
        font-size: 1.25rem;
        font-weight: 800;
        color:  #fff5f5;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .section-icon {
        width: 32px;
        height: 32px;
        background: #fff5f5;
        color: #800000;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 style="font-size: 1.5rem; font-weight: 800; color: #fff5f5; margin: 0;">Excuse Submissions</h1>
                    <p style="color:  #fff5f5; margin: 4px 0 0 0;">Submit excuses for your absences</p>
                </div>
            </div>

            @if(session('success'))
                <div style="background: #f0fdf4; color: #16a34a; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #bbf7d0;">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div style="background: #fef2f2; color: #dc2626; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca;">
                    <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                </div>
            @endif

            <!-- Absent Records That Can Be Excused -->
            <div class="section-title">
                <div class="section-icon">
                    <i class="bi bi-person-x"></i>
                </div>
                Absent Records
            </div>

            @forelse($absentRecords as $record)
                <div class="excuse-card">
                    <div class="excuse-header">
                        <div>
                            <h3 class="excuse-title">{{ $record->subject->name ?? $record->subject_code }}</h3>
                            <p class="excuse-date">{{ \Carbon\Carbon::parse($record->date)->format('F j, Y') }}</p>
                        </div>
                        <div>
                            @if($record->excuseSubmission)
                                <span class="excuse-status status-{{ $record->excuseSubmission->status }}">
                                    {{ ucfirst($record->excuseSubmission->status) }}
                                </span>
                            @else
                                <a href="{{ route('excuses.create', $record) }}" class="excuse-btn">
                                    <i class="bi bi-plus-circle"></i> Submit Excuse
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <h4>No Absent Records</h4>
                    <p>You don't have any absent records that need excuses.</p>
                </div>
            @endforelse

            <!-- Submitted Excuses -->
            @if($excuseSubmissions->count() > 0)
                <div class="section-title" style="margin-top: 40px;">
                    <div class="section-icon">
                        <i class="bi bi-file-text"></i>
                    </div>
                    Submitted Excuses
                </div>

                @foreach($excuseSubmissions as $excuse)
                    <div class="excuse-card">
                        <div class="excuse-header">
                            <div>
                                <h3 class="excuse-title">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</h3>
                                <p class="excuse-date">
                                    Absent on {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('F j, Y') }} • 
                                    Submitted {{ $excuse->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <span class="excuse-status status-{{ $excuse->status }}">
                                {{ ucfirst($excuse->status) }}
                            </span>
                        </div>
                        
                        <div style="margin-top: 12px;">
                            <strong style="color: #374151;">Reason:</strong> {{ $excuse->reason }}
                        </div>
                        
                        <div style="margin-top: 8px;">
                            <strong style="color: #374151;">Description:</strong>
                            <p style="margin: 4px 0 0 0; color: #64748b;">{{ $excuse->description }}</p>
                        </div>

                        @if($excuse->attachments && count($excuse->attachments) > 0)
                            <div style="margin-top: 12px;">
                                <strong style="color: #374151;">Attachments:</strong>
                                <div style="margin-top: 4px;">
                                    @foreach($excuse->attachments as $attachment)
                                        <a href="{{ asset('storage/' . $attachment) }}" target="_blank" 
                                           style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.75rem; color: #475569; text-decoration: none; margin-right: 8px; margin-bottom: 4px;">
                                            <i class="bi bi-paperclip"></i>
                                            {{ basename($attachment) }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($excuse->status === 'rejected' && $excuse->admin_notes)
                            <div style="margin-top: 12px; padding: 12px; background: #fef2f2; border-radius: 8px; border: 1px solid #fecaca;">
                                <strong style="color: #dc2626;">Admin Notes:</strong>
                                <p style="margin: 4px 0 0 0; color: #dc2626;">{{ $excuse->admin_notes }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection