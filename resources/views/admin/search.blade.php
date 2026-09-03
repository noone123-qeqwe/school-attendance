@extends('layouts.app')

@section('title', 'Search Results')

@section('content')
<div style="margin-bottom: 24px;">
    <h1 class="saas-heading saas-heading-lg" style="margin-bottom: 4px;">Search Results</h1>
    <p class="saas-text-muted" style="margin: 0;">Showing results for <strong style="color: #f3e7cd;">"{{ $q }}"</strong></p>
</div>

<div class="row g-4">
    <!-- Users Result -->
    <div class="col-lg-6">
        <div class="saas-card">
            <div class="saas-card-header">
                <div class="saas-heading saas-heading-sm"><i class="bi bi-people me-2"></i> Users ({{ $users->count() }})</div>
            </div>
            <div class="saas-card-body p-0">
                <ul class="list-group list-group-flush" style="background: transparent;">
                    @forelse($users as $user)
                        <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border-color: rgba(212,175,55,0.15); color: #f3e7cd;">
                            <div>
                                <div style="font-weight: 600;">{{ $user->name }}</div>
                                <div class="small text-muted">{{ $user->email }} &bull; {{ $user->student_number ?? $user->employee_id ?? 'No ID' }}</div>
                            </div>
                            <span class="badge" style="background: rgba(207,164,111,0.15); color: #cfa46f; border: 1px solid rgba(207,164,111,0.3);">{{ ucfirst($user->role) }}</span>
                        </li>
                    @empty
                        <li class="list-group-item text-center py-4 text-muted" style="background: transparent; border: none;">No matching users found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Subjects Result -->
    <div class="col-lg-6">
        <div class="saas-card">
            <div class="saas-card-header">
                <div class="saas-heading saas-heading-sm"><i class="bi bi-journal-bookmark me-2"></i> Subjects ({{ $subjects->count() }})</div>
            </div>
            <div class="saas-card-body p-0">
                <ul class="list-group list-group-flush" style="background: transparent;">
                    @forelse($subjects as $subject)
                        <li class="list-group-item d-flex justify-content-between align-items-center" style="background: transparent; border-color: rgba(212,175,55,0.15); color: #f3e7cd;">
                            <div>
                                <div style="font-weight: 600;">{{ $subject->name }}</div>
                                <div class="small text-muted">{{ $subject->code }} &bull; Year {{ $subject->year_level }} &bull; Sem {{ $subject->semester }}</div>
                            </div>
                            <a href="{{ route('admin.enrollments.index', $subject) }}" class="saas-btn saas-btn-secondary" style="padding: 4px 8px; font-size: 0.8rem;">View Roster</a>
                        </li>
                    @empty
                        <li class="list-group-item text-center py-4 text-muted" style="background: transparent; border: none;">No matching subjects found.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
