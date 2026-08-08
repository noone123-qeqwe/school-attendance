@extends('layouts.app')

@section('page-title', 'Early Warnings')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1" style="font-weight: 700;">Chronic Absenteeism Warnings</h2>
            <p class="text-muted mb-0">Students flagged with 3 or more unexcused absences/lates in the last 30 days.</p>
        </div>
        <div>
            <a href="{{ route('admin.early-warnings.export') }}" class="btn modern-btn">
                <i class="bi bi-file-earmark-excel-fill me-2"></i> Export Excel
            </a>
        </div>
    </div>

    <div class="main-card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table modern-table mb-0">
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
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-circle">
                                        {{ substr($warning->user->name ?? '?', 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="fw-bold">{{ $warning->user->name ?? 'Unknown' }}</div>
                                        <div class="text-muted small">{{ $warning->user->student_number ?? 'N/A' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary);">
                                    {{ $warning->subject_code }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-exclamation-triangle-fill text-warning"></i>
                                    <span>{{ $warning->message }}</span>
                                </div>
                            </td>
                            <td>
                                <div>{{ $warning->created_at->format('M d, Y') }}</div>
                                <div class="text-muted small">{{ $warning->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.student', $warning->user_id) }}" class="btn btn-sm btn-outline-secondary">
                                    View Profile
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-5">
                                    <i class="bi bi-shield-check text-success mb-3" style="font-size: 3rem;"></i>
                                    <h5>No Early Warnings Found</h5>
                                    <p class="text-muted mb-0">No students meet the criteria for chronic absenteeism in the last 30 days.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="p-3 border-top">
                {{ $warnings->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
