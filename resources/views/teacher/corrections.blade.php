@extends('layouts.app')
@section('page-title', 'Attendance Correction Requests')

@section('content')
<div class="animate-fade-up" style="max-width:1100px; margin:0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="saas-heading saas-heading-lg mb-1" style="font-weight: 800; color: #f3e7cd; font-size: clamp(1.4rem, 2.5vw, 1.85rem);">
                Student Correction Requests
            </h1>
            <p class="saas-text-muted m-0" style="color: #b39b82; font-size: 0.95rem;">
                Review and approve student requests for attendance adjustments
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center mb-4" style="background:rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.4); color:#4ade80; border-radius:12px; padding:12px 16px;">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    <x-card title="Requests for Your Classes" icon="bi bi-pencil-square">
        <x-slot name="headerActions">
            <form method="GET" class="d-flex gap-2 align-items-center m-0">
                <select name="status" class="form-select form-select-sm" style="background:#140d07; border:1px solid rgba(207,164,111,0.3); color:#f3ede4; border-radius:8px; padding:6px 28px 6px 12px; font-weight:600; cursor:pointer;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending"  {{ request('status')=='pending'  ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </form>
        </x-slot>

        <div class="table-responsive">
            <table class="table align-middle" style="color: #f3ede4; border-color: rgba(207,164,111,0.15);">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(207,164,111,0.25); color: #cfa46f; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">
                        <th>Student</th>
                        <th>Class / Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($corrections as $c)
                    <tr style="border-bottom: 1px solid rgba(207,164,111,0.1);">
                        <td>
                            <div style="font-weight: 600; color: #f3e7cd;">{{ $c->student->name ?? 'Unknown Student' }}</div>
                            <div class="small text-muted">{{ $c->student->student_number ?? 'No ID' }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 500;">{{ $c->attendance->subject->name ?? $c->attendance->subject_code ?? 'Class' }}</div>
                            <div class="small text-muted">{{ \Carbon\Carbon::parse($c->attendance->date)->format('M d, Y') }} (Marked {{ $c->attendance->status }})</div>
                        </td>
                        <td style="max-width: 280px;">
                            <div style="font-size: 0.9rem; color: #e2d1b3;">{{ $c->reason }}</div>
                            @if($c->teacher_notes)
                                <div class="small text-muted mt-1"><em>Note: {{ $c->teacher_notes }}</em></div>
                            @endif
                        </td>
                        <td>
                            @if($c->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($c->status === 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($c->status === 'pending')
                                <div class="d-inline-flex gap-2">
                                    <form action="{{ route('teacher.corrections.update', $c) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-sm" style="background: rgba(34,197,94,0.2); color: #86efac; border: 1px solid rgba(34,197,94,0.4); border-radius: 8px; font-weight: 600;">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('teacher.corrections.update', $c) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-sm" style="background: rgba(239,68,68,0.2); color: #fca5a5; border: 1px solid rgba(239,68,68,0.4); border-radius: 8px; font-weight: 600;">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted small">Processed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle mb-2 fs-2 d-block" style="opacity: 0.4;"></i>
                            No correction requests found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($corrections->hasPages())
        <div class="mt-3">
            {{ $corrections->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
