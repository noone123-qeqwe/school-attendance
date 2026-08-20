@extends('layouts.app')
@section('page-title', 'Excuse Reviews')

@section('content')
<div class="animate-fade-up" style="max-width:1100px; margin:0 auto;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h1 class="saas-heading saas-heading-lg mb-1" style="font-weight: 800; color: #f3e7cd; font-size: clamp(1.4rem, 2.5vw, 1.85rem);">
                Excuse Reviews
            </h1>
            <p class="saas-text-muted m-0" style="color: #b39b82; font-size: 0.95rem;">
                Review and bulk-manage student excuse submissions
            </p>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success d-flex align-items-center mb-4" style="background:rgba(34,197,94,0.15); border:1px solid rgba(34,197,94,0.4); color:#4ade80; border-radius:12px; padding:12px 16px;">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        <div>{{ session('success') }}</div>
    </div>
    @endif

    <x-card title="Excuse Submissions" icon="bi bi-file-earmark-check-fill">
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

        <!-- Bulk Actions Form -->
        <form method="POST" id="bulkForm">
            @csrf
            <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                <button type="button" onclick="submitBulk('{{ route('admin.excuses.bulk.approve') }}')" class="btn btn-sm btn-press" style="padding:7px 16px; background:rgba(34,197,94,0.15); color:#86efac; border:1px solid rgba(34,197,94,0.3); border-radius:10px; font-size:0.82rem; font-weight:600; cursor:pointer;">
                    <i class="bi bi-check2-all me-1"></i> Bulk Approve
                </button>
                <button type="button" onclick="submitBulk('{{ route('admin.excuses.bulk.reject') }}')" class="btn btn-sm btn-press" style="padding:7px 16px; background:rgba(239,68,68,0.15); color:#fca5a5; border:1px solid rgba(239,68,68,0.3); border-radius:10px; font-size:0.82rem; font-weight:600; cursor:pointer;">
                    <i class="bi bi-x-lg me-1"></i> Bulk Reject
                </button>
                <label class="ms-2 d-flex align-items-center gap-2" style="font-size:0.85rem; color:#b39b82; cursor:pointer; user-select:none;">
                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="accent-color:var(--gold);"> Select All
                </label>
            </div>

            <div class="table-responsive">
                <table class="adm-table table-responsive-card w-100" style="margin:0;">
                    <thead>
                        <tr>
                            <th style="width:40px;"></th>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th style="text-align:center;">Status</th>
                            <th style="text-align:center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($excuses as $excuse)
                        <tr>
                            <td>
                                @if($excuse->status === 'pending')
                                <input type="checkbox" name="ids[]" value="{{ $excuse->id }}" class="excuse-cb" style="accent-color:var(--gold);">
                                @endif
                            </td>
                            <td data-label="Student">
                                <div style="font-weight:600; color:#f3e7cd;">{{ $excuse->user->name ?? '—' }}</div>
                                <div style="font-size:0.75rem; color:#b39b82; font-family:monospace;">{{ $excuse->user->student_number ?? '' }}</div>
                            </td>
                            <td data-label="Subject" style="color:#d4c5a9; font-weight:500;">
                                {{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code ?? '—' }}
                            </td>
                            <td data-label="Date" style="color:#d4c5a9;">
                                {{ $excuse->attendance ? $excuse->attendance->date->format('M d, Y') : '—' }}
                            </td>
                            <td data-label="Reason" style="max-width:220px; color:#b39b82; font-size:0.84rem;">
                                <div style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $excuse->reason }}">
                                    {{ $excuse->reason }}
                                </div>
                            </td>
                            <td data-label="Status" style="text-align:center;">
                                @if($excuse->status === 'pending')
                                    <span class="badge" style="background:rgba(245,158,11,0.15); color:#fbbf24; border:1px solid rgba(245,158,11,0.3); padding:4px 10px; border-radius:99px; font-weight:600; font-size:0.75rem;">Pending</span>
                                @elseif($excuse->status === 'approved')
                                    <span class="badge" style="background:rgba(34,197,94,0.15); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:4px 10px; border-radius:99px; font-weight:600; font-size:0.75rem;">Approved</span>
                                @else
                                    <span class="badge" style="background:rgba(239,68,68,0.15); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 10px; border-radius:99px; font-weight:600; font-size:0.75rem;">Rejected</span>
                                @endif
                            </td>
                            <td data-label="Actions" style="text-align:center;">
                                @if($excuse->status === 'pending')
                                <div class="d-flex justify-content-center gap-2">
                                    <form method="POST" action="{{ route('admin.excuse.approve', $excuse) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-press" style="padding:4px 12px; background:rgba(34,197,94,0.15); color:#86efac; border:1px solid rgba(34,197,94,0.3); border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer;" title="Approve Excuse">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.excuse.reject', $excuse) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-press" style="padding:4px 12px; background:rgba(239,68,68,0.15); color:#fca5a5; border:1px solid rgba(239,68,68,0.3); border-radius:8px; font-size:0.75rem; font-weight:600; cursor:pointer;" title="Reject Excuse">
                                            ✕ Reject
                                        </button>
                                    </form>
                                </div>
                                @else
                                    <span style="font-size:0.8rem; color:#8f826f;">Reviewed</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="p-0 border-0">
                                <x-empty-state 
                                    icon="file-earmark-check"
                                    title="No Excuse Submissions Found"
                                    message="There are currently no excuse letters matching your selected filter."
                                />
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($excuses, 'hasPages') && $excuses->hasPages())
            <div class="p-3 border-top" style="border-color:rgba(255,255,255,0.06) !important;">
                {{ $excuses->links() }}
            </div>
            @endif
        </form>
    </x-card>
</div>

<script>
function toggleAll(cb) {
    document.querySelectorAll('.excuse-cb').forEach(el => el.checked = cb.checked);
}
function submitBulk(url) {
    const checked = document.querySelectorAll('.excuse-cb:checked');
    if (checked.length === 0) { alert('Select at least one pending excuse.'); return; }
    const form = document.getElementById('bulkForm');
    form.action = url;
    form.submit();
}
</script>
@endsection
