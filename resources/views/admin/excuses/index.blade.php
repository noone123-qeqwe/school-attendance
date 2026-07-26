@extends('layouts.admin_premium')
@section('page-title', 'Excuse Reviews')

@section('content')
<div style="max-width:1100px;margin:0 auto;">
    <div style="margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
        <div>
            <div style="font-size:1.5rem;font-weight:800;color:#f3e7cd;">Excuse Reviews</div>
            <div style="font-size:0.88rem;color:#b39b82;margin-top:2px;">Review and bulk-manage student excuse submissions</div>
        </div>
    </div>

    @if(session('success'))
    <div style="background:rgba(34,197,94,0.14);border:1px solid rgba(34,197,94,0.3);color:#bbf7d0;border-radius:12px;padding:12px 16px;margin-bottom:18px;font-size:0.88rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
    @endif

    <!-- Filter -->
    <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">
        <select name="status" style="padding:10px 14px;border-radius:10px;border:1.5px solid rgba(255,215,145,0.15);background:rgba(255,235,190,0.04);color:#f3e7cd;font-size:0.875rem;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"  {{ request('status')=='pending'  ? 'selected' : '' }}>Pending</option>
            <option value="approved" {{ request('status')=='approved' ? 'selected' : '' }}>Approved</option>
            <option value="rejected" {{ request('status')=='rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
    </form>

    <!-- Bulk Actions Form -->
    <form method="POST" id="bulkForm">
        @csrf
        <div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap;">
            <button type="button" onclick="submitBulk('{{ route('admin.excuses.bulk.approve') }}')" style="padding:9px 18px;background:rgba(34,197,94,0.15);color:#bbf7d0;border:1px solid rgba(34,197,94,0.25);border-radius:10px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-check2-all me-2"></i>Bulk Approve
            </button>
            <button type="button" onclick="submitBulk('{{ route('admin.excuses.bulk.reject') }}')" style="padding:9px 18px;background:rgba(220,38,38,0.14);color:#fca5a5;border:1px solid rgba(220,38,38,0.2);border-radius:10px;font-size:0.85rem;font-weight:600;cursor:pointer;">
                <i class="bi bi-x-lg me-2"></i>Bulk Reject
            </button>
            <label style="display:flex;align-items:center;gap:8px;font-size:0.85rem;color:#b39b82;cursor:pointer;">
                <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="accent-color:#cfa46f;"> Select All
            </label>
        </div>

        <div style="background:rgba(255,235,190,0.04);border:1px solid rgba(255,215,145,0.1);border-radius:14px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,215,145,0.12);">
                        <th style="padding:12px 16px;width:40px;"></th>
                        <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Student</th>
                        <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Subject</th>
                        <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Date</th>
                        <th style="padding:12px 16px;text-align:left;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Reason</th>
                        <th style="padding:12px 16px;text-align:center;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Status</th>
                        <th style="padding:12px 16px;text-align:center;font-size:0.72rem;font-weight:700;color:#b39b82;text-transform:uppercase;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($excuses as $excuse)
                    <tr style="border-bottom:1px solid rgba(255,215,145,0.06);" onmouseover="this.style.background='rgba(255,255,255,0.02)'" onmouseout="this.style.background=''">
                        <td style="padding:12px 16px;">
                            @if($excuse->status === 'pending')
                            <input type="checkbox" name="ids[]" value="{{ $excuse->id }}" class="excuse-cb" style="accent-color:#cfa46f;">
                            @endif
                        </td>
                        <td style="padding:12px 16px;font-size:0.875rem;color:#f3e7cd;">{{ $excuse->user->name ?? '—' }}</td>
                        <td style="padding:12px 16px;font-size:0.875rem;color:#d4c5a9;">{{ $excuse->attendance->subject->name ?? $excuse->attendance->subject_code ?? '—' }}</td>
                        <td style="padding:12px 16px;font-size:0.875rem;color:#d4c5a9;">{{ $excuse->attendance ? $excuse->attendance->date->format('M d, Y') : '—' }}</td>
                        <td style="padding:12px 16px;font-size:0.82rem;color:#b39b82;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="{{ $excuse->reason }}">{{ $excuse->reason }}</td>
                        <td style="padding:12px 16px;text-align:center;">
                            @if($excuse->status === 'pending')
                                <span style="background:rgba(245,158,11,0.15);color:#fde68a;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;">Pending</span>
                            @elseif($excuse->status === 'approved')
                                <span style="background:rgba(34,197,94,0.14);color:#bbf7d0;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;">Approved</span>
                            @else
                                <span style="background:rgba(220,38,38,0.14);color:#fca5a5;padding:3px 10px;border-radius:999px;font-size:0.72rem;font-weight:700;">Rejected</span>
                            @endif
                        </td>
                        <td style="padding:12px 16px;text-align:center;">
                            @if($excuse->status === 'pending')
                            <div style="display:flex;justify-content:center;gap:6px;">
                                <form method="POST" action="{{ route('admin.excuse.approve', $excuse) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="padding:5px 12px;background:rgba(34,197,94,0.15);color:#bbf7d0;border:1px solid rgba(34,197,94,0.25);border-radius:8px;font-size:0.75rem;font-weight:600;cursor:pointer;">✓ Approve</button>
                                </form>
                                <form method="POST" action="{{ route('admin.excuse.reject', $excuse) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" style="padding:5px 12px;background:rgba(220,38,38,0.14);color:#fca5a5;border:1px solid rgba(220,38,38,0.2);border-radius:8px;font-size:0.75rem;font-weight:600;cursor:pointer;">✕ Reject</button>
                                </form>
                            </div>
                            @else
                                <span style="font-size:0.78rem;color:#b39b82;">Reviewed</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="padding:32px;text-align:center;color:#b39b82;font-size:0.875rem;">No excuse submissions found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $excuses->links() }}
    </form>
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
