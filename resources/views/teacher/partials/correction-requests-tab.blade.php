<div class="tch-card">
    <div class="tch-card-head" style="flex-wrap: wrap; gap: 12px;">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: rgba(122,26,26,0.22); color: var(--portal-gold);">
                <i class="bi bi-pencil-square" aria-hidden="true"></i>
            </div>
            <div>
                <div>Student Correction Requests</div>
                <div style="font-size: 0.78rem; font-weight: 500; color: #a49890;">
                    Review and approve student requests for attendance adjustments
                </div>
            </div>
        </div>
        <form method="GET" action="{{ route('teacher.corrections') }}" class="d-flex gap-2 align-items-center m-0">
            <select name="status"
                    aria-label="Filter correction requests by status"
                    class="tch-input"
                    style="min-width: 170px; cursor: pointer;"
                    onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <option value="pending" {{ $correctionStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="approved" {{ $correctionStatus === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ $correctionStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </form>
    </div>

    <div style="overflow-x: auto;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Class / Date</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($corrections as $c)
                    <tr>
                        <td data-label="Student">
                            <div class="student-meta">
                                <div class="name">{{ $c->student->name ?? 'Unknown Student' }}</div>
                                <div class="subtext">{{ $c->student->student_number ?? 'No ID' }}</div>
                            </div>
                        </td>
                        <td data-label="Class / Date">
                            <div class="subject-meta">
                                <div class="title">{{ $c->attendance->subject->name ?? $c->attendance->subject_code ?? 'Class' }}</div>
                                <div class="subtext">
                                    {{ \Carbon\Carbon::parse($c->attendance->date)->format('M d, Y') }} (Marked {{ $c->attendance->status }})
                                </div>
                            </div>
                        </td>
                        <td data-label="Reason" style="max-width: 300px;">
                            <div class="excuse-summary">
                                <div class="reason" style="font-weight: 500;">{{ $c->reason }}</div>
                                @if($c->teacher_notes)
                                    <div class="description"><em>Note: {{ $c->teacher_notes }}</em></div>
                                @endif
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($c->status === 'approved')
                                <span class="status-badge status-approved">Approved</span>
                            @elseif($c->status === 'rejected')
                                <span class="status-badge status-rejected">Rejected</span>
                            @else
                                <span class="status-badge status-pending">Pending</span>
                            @endif
                        </td>
                        <td data-label="Actions" style="text-align: right;">
                            @if($c->status === 'pending')
                                <div class="action-group">
                                    <form action="{{ route('teacher.corrections.update', $c) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="action-approve">
                                            <i class="bi bi-check-lg" aria-hidden="true"></i> Approve
                                        </button>
                                    </form>
                                    <form action="{{ route('teacher.corrections.update', $c) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="action-reject">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size: 0.8rem; color: #a49890;">Processed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">
                            <i class="bi bi-check2-circle"></i>
                            <div>No correction requests found</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">No attendance adjustment requests match the current filter.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($corrections->hasPages())
        <div style="padding: 16px 22px; border-top: 1px solid rgba(255,255,255,0.08);">
            {{ $corrections->links() }}
        </div>
    @endif
</div>
