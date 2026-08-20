@extends('layouts.app')

@section('page-title', 'Early Warnings')

@section('content')
<div class="animate-fade-up">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="saas-heading saas-heading-lg mb-1" style="font-weight: 800; color: #f3e7cd; font-size: clamp(1.4rem, 2.5vw, 1.85rem);">
                Chronic Absenteeism Warnings
            </h1>
            <p class="saas-text-muted m-0" style="color: #b39b82; font-size: 0.95rem;">
                Students flagged with 3 or more unexcused absences/lates in the last 30 days.
            </p>
        </div>
        <div>
            <a href="{{ route('admin.early-warnings.export') }}" class="btn btn-outline hover-lift btn-press" style="border-color: rgba(207,164,111,0.3); color: var(--gold); border-radius: 12px; padding: 10px 20px; font-weight: 600;">
                <i class="bi bi-file-earmark-excel me-2"></i> Export Excel
            </a>
        </div>
    </div>

    <x-card title="Active Warning Logs" icon="bi bi-exclamation-triangle-fill">
        <div class="table-responsive">
            <table class="adm-table table-responsive-card w-100" style="margin: 0;">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Warning Message</th>
                        <th>Date Flagged</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warnings as $warning)
                    <tr>
                        <td data-label="Student">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar-premium" style="width: 38px; height: 38px; border-radius: 50%; font-size: 0.8rem; background: linear-gradient(135deg, #7A1A1A, #3a1010); border: 1.5px solid rgba(207,164,111,0.4);">
                                    {{ substr($warning->user->name ?? '?', 0, 2) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #f3e7cd; font-size: 0.9rem;">{{ $warning->user->name ?? 'Unknown' }}</div>
                                    <div style="font-size: 0.75rem; color: #b39b82; font-family: monospace;">{{ $warning->user->student_number ?? '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Subject">
                            <span class="badge" style="background: rgba(207,164,111,0.12); color: var(--gold); border: 1px solid rgba(207,164,111,0.25); font-weight: 600; padding: 5px 10px; border-radius: 8px;">
                                {{ $warning->subject_code }}
                            </span>
                        </td>
                        <td data-label="Warning Message">
                            <div class="d-flex align-items-center gap-2">
                                <span class="status-dot pulse" style="background: #f87171; color: #f87171;"></span>
                                <span style="color: #f3e7cd; font-size: 0.88rem;">{{ $warning->message }}</span>
                            </div>
                        </td>
                        <td data-label="Date Flagged">
                            <div style="color: #f3ede4; font-weight: 500; font-size: 0.88rem;">{{ $warning->created_at->format('M d, Y') }}</div>
                            <div style="font-size: 0.75rem; color: #b39b82;">{{ $warning->created_at->format('h:i A') }}</div>
                        </td>
                        <td data-label="Actions" class="text-end">
                            <a href="{{ route('admin.student', $warning->user_id) }}" class="btn btn-sm btn-outline hover-lift" style="border-color: rgba(207,164,111,0.3); color: var(--gold); border-radius: 8px; padding: 6px 14px; font-weight: 600; font-size: 0.82rem;">
                                <i class="bi bi-person me-1"></i> View Profile
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-0 border-0">
                            <x-empty-state 
                                icon="shield-check"
                                title="No Early Warnings Found"
                                message="Great job! No students meet the criteria for chronic absenteeism in the last 30 days."
                            />
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($warnings->hasPages())
        <div class="p-3 border-top" style="border-color: rgba(255,255,255,0.06) !important;">
            {{ $warnings->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
