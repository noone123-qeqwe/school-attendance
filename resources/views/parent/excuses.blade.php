@extends('layouts.app')
@section('page-title', 'Excuse Letters')

@section('content')
<style>
    .mobile-excuse-card {
        background: linear-gradient(145deg, rgba(32,20,15,0.6) 0%, rgba(20,10,5,0.8) 100%);
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 16px; padding: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .filter-group { flex: 1; min-width: 150px; }
    @media (max-width: 768px) {
        .filter-group { flex-basis: 100%; min-width: 100%; }
    }
</style>
<div class="p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4" style="background: rgba(207,164,111,0.05); padding: 20px; border-radius: 16px; border: 1px solid rgba(207,164,111,0.15);">
        <h2 style="color: #f3e7cd; font-weight: 800; margin: 0; display: flex; align-items: center; font-size: clamp(1.2rem, 3vw, 1.8rem);">
            <div style="width: 40px; height: 40px; background: rgba(207,164,111,0.1); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 12px;">
                <i class="bi bi-file-earmark-text" style="color: var(--gold); font-size: 1.2rem;"></i>
            </div>
            Excuse Letters
        </h2>
        <a href="{{ route('parent.excuses.create_general') }}" class="ent-btn" style="background: var(--gold); color: #1a1d24; font-weight: 600; padding: 10px 20px;">
            <i class="bi bi-plus-lg"></i> Submit Excuse
        </a>
    </div>

    {{-- Filters --}}
    <x-card type="section" class="ent-mb-lg">
        <form method="GET" action="{{ route('parent.excuses') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div class="filter-group">
                <label class="ent-label">Child</label>
                <select name="child_id" class="ent-input">
                    <option value="">All Children</option>
                    @foreach($children as $child)
                        <option value="{{ $child->id }}" {{ request('child_id') == $child->id ? 'selected' : '' }}>{{ $child->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <label class="ent-label">Status</label>
                <select name="status" class="ent-input">
                    <option value="">All</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="ent-btn ent-btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                @if(request()->hasAny(['child_id', 'status']))
                    <a href="{{ route('parent.excuses') }}" class="ent-btn ent-btn-ghost text-danger">Clear</a>
                @endif
            </div>
        </form>
    </x-card>

    {{-- Excuses List --}}
    <x-card type="section" title="Submission History">
        <x-slot:headerActions>
            <span style="color: var(--ent-text-muted); font-size: 0.85rem;">{{ $excuses->total() }} submissions</span>
        </x-slot:headerActions>
        
        @if($excuses->count() > 0)
        <!-- Desktop Table -->
        <div class="ent-scroll-x d-none d-md-block" style="margin: -20px;">
            <table class="ent-table" style="min-width: 800px; margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Submitted On</th>
                        <th>Child</th>
                        <th>Class Missed</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($excuses as $excuse)
                    <tr>
                        <td data-label="Submitted On">
                            <div style="font-weight: 500;">{{ $excuse->created_at->format('M d, Y') }}</div>
                            <div class="ent-text-muted" style="font-size: 0.8rem;">{{ $excuse->created_at->format('h:i A') }}</div>
                        </td>
                        <td data-label="Child">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="{{ $excuse->user->profile_image ? (str_starts_with($excuse->user->profile_image, 'http') ? $excuse->user->profile_image : asset('storage/'.$excuse->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($excuse->user->name).'&background=800000&color=fff' }}"
                                     class="rounded-circle" style="width: 28px; height: 28px; object-fit: cover;">
                                <span style="font-weight: 600;">{{ $excuse->user->name }}</span>
                            </div>
                        </td>
                        <td data-label="Class Missed">
                            <div style="font-weight: 500;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</div>
                            <div class="ent-text-muted" style="font-size: 0.8rem;">Absence: {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }}</div>
                        </td>
                        <td data-label="Reason">
                            <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $excuse->reason }}
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($excuse->status === 'pending')
                                <span class="ent-badge ent-badge-warning"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                            @elseif($excuse->status === 'approved')
                                <span class="ent-badge ent-badge-success"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                            @else
                                <span class="ent-badge ent-badge-danger"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                            @endif
                        </td>
                        <td data-label="Action">
                            <a href="{{ route('parent.excuse.show', $excuse->id) }}" class="ent-btn ent-btn-sm ent-btn-primary" style="padding: 6px 12px;">
                                <i class="bi bi-chat-dots"></i> Details
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile Cards -->
        <div class="d-block d-md-none">
            <div class="d-flex flex-column gap-3">
                @foreach($excuses as $excuse)
                <div class="mobile-excuse-card">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <img src="{{ $excuse->user->profile_image ? (str_starts_with($excuse->user->profile_image, 'http') ? $excuse->user->profile_image : asset('storage/'.$excuse->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($excuse->user->name).'&background=800000&color=fff' }}"
                             class="rounded-circle flex-shrink-0" style="width: 48px; height: 48px; object-fit: cover; border: 2px solid rgba(255,255,255,0.1);">
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="font-weight: 700; color: #f3e7cd; font-size: 1.05rem;" class="text-truncate">{{ $excuse->user->name }}</div>
                                <div>
                                    @if($excuse->status === 'pending')
                                        <span class="ent-badge ent-badge-warning" style="font-size: 0.65rem; padding: 2px 6px;">Pending</span>
                                    @elseif($excuse->status === 'approved')
                                        <span class="ent-badge ent-badge-success" style="font-size: 0.65rem; padding: 2px 6px;">Approved</span>
                                    @else
                                        <span class="ent-badge ent-badge-danger" style="font-size: 0.65rem; padding: 2px 6px;">Rejected</span>
                                    @endif
                                </div>
                            </div>
                            <div style="font-size: 0.75rem; color: #b39b82;">
                                Missed: {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d') }} &bull; {{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 8px; font-size: 0.85rem; color: #e7dcc8; line-height: 1.5; margin-bottom: 12px; border-left: 3px solid rgba(207,164,111,0.4);">
                        <div style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $excuse->reason }}
                        </div>
                    </div>
                    
                    <a href="{{ route('parent.excuse.show', $excuse->id) }}" class="ent-btn ent-btn-sm w-100" style="background: rgba(255,255,255,0.05); color: #f3e7cd; border: 1px solid rgba(255,255,255,0.1);">
                        View Details
                    </a>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Pagination --}}
        <div style="border-top:1px solid var(--ent-border); padding-top:16px; margin-top:16px;">
            {{ $excuses->links() }}
        </div>
        @else
        <div class="ent-empty" style="padding: 40px;">
            <div class="ent-empty-icon" style="width:64px;height:64px;font-size:2rem; margin-bottom:16px;">
                <i class="bi bi-file-earmark-x"></i>
            </div>
            <div class="ent-empty-text">No excuse letters submitted yet.</div>
        </div>
        @endif
    </x-card>
</div>
@endsection
