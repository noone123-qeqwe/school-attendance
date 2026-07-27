@extends('parent.layout')
@section('page-title', 'Excuse Letters')

@section('content')
<div class="p-4">
    <h2 style="color: #f3e7cd; font-weight: 800; margin-bottom: 24px;">
        <i class="bi bi-file-earmark-text" style="color: #cfa46f; margin-right: 8px;"></i>Excuse Letters
    </h2>

    {{-- Filters --}}
    <div class="adm-card" style="margin-bottom: 24px;">
        <div class="adm-card-body" style="padding: 16px 20px;">
            <form method="GET" action="{{ route('parent.excuses') }}" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Child</label>
                    <select name="child_id" class="tch-input" style="min-width: 160px;">
                        <option value="">All Children</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" {{ request('child_id') == $child->id ? 'selected' : '' }}>{{ $child->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 0.72rem; font-weight: 600; color: #b39b82; text-transform: uppercase;">Status</label>
                    <select name="status" class="tch-input" style="min-width: 120px;">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <button type="submit" class="adm-btn adm-btn-primary" style="font-size: 0.82rem;"><i class="bi bi-funnel"></i> Filter</button>
                <a href="{{ route('parent.excuses') }}" class="adm-btn adm-btn-ghost" style="font-size: 0.82rem;">Clear</a>
            </form>
        </div>
    </div>

    {{-- Excuses List --}}
    <div class="adm-card">
        <div class="adm-card-head">
            <div class="adm-card-title">
                <div class="adm-card-icon"><i class="bi bi-clock-history"></i></div>
                Submission History
            </div>
            <span style="color: #b39b82; font-size: 0.82rem;">{{ $excuses->total() }} submissions</span>
        </div>
        <div class="adm-card-body" style="padding: 0;">
            @if($excuses->count() > 0)
            <div class="table-responsive">
                <table class="adm-table">
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
                            <td>
                                <div style="color: #f3e7cd; font-weight: 600; font-size: 0.85rem;">{{ $excuse->created_at->format('M d, Y') }}</div>
                                <div style="color: #8f826f; font-size: 0.72rem;">{{ $excuse->created_at->format('h:i A') }}</div>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <img src="{{ $excuse->user->profile_image ? (str_starts_with($excuse->user->profile_image, 'http') ? $excuse->user->profile_image : asset('storage/'.$excuse->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($excuse->user->name).'&background=800000&color=fff' }}"
                                         class="rounded-circle" style="width: 24px; height: 24px;">
                                    <span style="color: #e7dcc8; font-weight: 600; font-size: 0.85rem;">{{ $excuse->user->name }}</span>
                                </div>
                            </td>
                            <td>
                                <div style="color: #f3e7cd; font-weight: 600; font-size: 0.85rem;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code }}</div>
                                <div style="color: #8f826f; font-size: 0.75rem;">Absence: {{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M d, Y') }}</div>
                            </td>
                            <td>
                                <div style="color: #e7dcc8; font-size: 0.85rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $excuse->reason }}
                                </div>
                            </td>
                            <td>
                                @if($excuse->status === 'pending')
                                    <span style="background: rgba(255,167,38,0.15); color: #ffa726; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                @elseif($excuse->status === 'approved')
                                    <span style="background: rgba(102,187,106,0.15); color: #66bb6a; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><i class="bi bi-check-circle-fill me-1"></i>Approved</span>
                                @else
                                    <span style="background: rgba(239,83,80,0.15); color: #ef5350; padding: 4px 10px; border-radius: 999px; font-size: 0.75rem; font-weight: 600;"><i class="bi bi-x-circle-fill me-1"></i>Rejected</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('parent.excuse.show', $excuse->id) }}" class="adm-btn adm-btn-primary" style="font-size: 0.75rem; padding: 6px 12px;">
                                    <i class="bi bi-chat-dots"></i> Details & Comments
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div style="padding: 16px 20px; border-top: 1px solid rgba(255,215,145,0.08);">
                {{ $excuses->links() }}
            </div>
            @else
            <div class="empty-state" style="padding: 40px;">
                <i class="bi bi-file-earmark-x" style="font-size: 2rem;"></i>
                <span>No excuse letters submitted yet.</span>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
