@extends('layouts.app')

@section('page-title', 'Excuse Reviews')

@section('content')
<style>
.student-info,
.subject-info {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
}
.student-avatar,
.subject-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid rgba(255,255,255,0.12);
}
.student-meta,
.subject-meta,
.excuse-summary,
.meta-cell {
    min-width: 0;
}
.student-meta .name,
.subject-info .title {
    font-weight: 700;
    color: #f8fafc;
}
.student-meta .subtext,
.subject-info .subtext,
.meta-cell .subtext {
    color: #cbd5e1;
    font-size: 0.8rem;
    line-height: 1.4;
}
.excuse-summary {
    max-width: 260px;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
.excuse-summary .reason {
    font-weight: 600;
    color: #f8fafc;
    line-height: 1.5;
}
.excuse-summary .description {
    margin-top: 6px;
    color: #b39b82;
    font-size: 0.82rem;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 700;
}
.status-pending {
    background: rgba(245,158,11,0.12);
    color: #fbbf24;
}
.status-approved {
    background: rgba(22,163,74,0.14);
    color: #a7f3d0;
}
.status-rejected {
    background: rgba(239,68,68,0.14);
    color: #fecaca;
}
.attachment-badge {
    background: rgba(255,255,255,0.08);
    color: #cbd5e1;
    font-size: 0.75rem;
    padding: 6px 10px;
    border-radius: 999px;
    font-weight: 700;
}
.action-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: flex-end;
    align-items: center;
}
.action-group button,
.action-group a {
    min-width: 108px;
    padding: 10px 14px;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    justify-content: center;
}
.action-view {
    background: #4b5563 !important;
    color: #e2e8f0 !important;
    border-color: #4b5563 !important;
}
.action-view:hover {
    background: #5a6572 !important;
}
.action-approve {
    background: rgba(16,185,129,0.16);
    color: #d1fae5;
    border-color: rgba(16,185,129,0.3);
}
.action-reject {
    background: rgba(239,68,68,0.18);
    color: #fecaca;
    border-color: rgba(239,68,68,0.28);
}
.action-group .status-badge {
    background: rgba(255,255,255,0.08);
    color: #e2e8f0;
    font-size: 0.75rem;
    padding: 6px 10px;
}
.action-view {
    background: #4b5563 !important;
    color: #e2e8f0 !important;
    border-color: #4b5563 !important;
}
.action-view:hover {
    background: #5a6572 !important;
}
.attachment-badge {
    background: #3f4750 !important;
    color: #e2e8f0 !important;
    border-color: rgba(255,255,255,0.08);
}
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
}
.empty-state i {
    display: block;
    font-size: 2rem;
    margin-bottom: 12px;
    color: #cbd5e1;
}
.alert-success {
    background: rgba(16,185,129,0.12);
    border: 1px solid rgba(16,185,129,0.2);
    color: #a7f3d0;
    border-radius: 12px;
    padding: 12px 16px;
    font-size: .875rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
#modalImage {
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.22);
    transition: transform 0.2s ease;
    max-height: calc(100vh - 220px);
}
#modalImage:hover {
    transform: scale(1.02);
}
.attachment-item {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.attachment-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 16px rgba(255, 255, 255, 0.06);
}
.modal-content {
    background: #2a2a2a !important;
    color: #e2e8f0 !important;
    display: flex;
    flex-direction: column;
    max-height: calc(100vh - 30px);
    min-height: 0;
}
.modal-content .btn-close {
    filter: invert(1);
}
.modal-header,
.modal-footer {
    background: transparent;
    border-color: rgba(255,255,255,0.12);
}
.modal-body {
    min-height: 0;
}
.modal-dialog {
    max-height: calc(100vh - 20px);
    min-height: 0;
}
.modal-dialog-scrollable {
    height: calc(100vh - 20px);
}
.modal-dialog-scrollable .modal-content {
    max-height: 100%;
    min-height: 0;
}
.image-modal-body {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    max-height: calc(100vh - 190px);
    overflow-y: auto;
    flex: 1 1 auto;
}
.modal-header .modal-title,
.modal-footer .tch-btn,
.modal-footer .tch-btn-ghost,
.modal-body {
    color: #e2e8f0;
}
@media (max-width: 1200px) {
    .tch-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .tch-stats {
        grid-template-columns: 1fr;
    }
    .tch-table thead {
        display: none;
    }
    .tch-table tbody tr {
        display: block;
        border-radius: 16px;
        margin-bottom: 16px;
        background: rgba(255,255,255,0.05);
        box-shadow: 0 8px 20px rgba(255, 255, 255, 0.06);
    }
    .tch-table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 8px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        font-size: 0.88rem;
        color: rgba(248,250,252,0.95);
    }
    .tch-table tbody td:last-child {
        border-bottom: none;
    }
    .tch-table tbody td::before {
        content: attr(data-label);
        width: 100%;
        font-size: 0.72rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 6px;
    }
    .tch-table tbody td > div,
    .tch-table tbody td > span {
        width: 100%;
    }
    .action-group {
        width: 100%;
        justify-content: flex-start;
    }
    .action-group button,
    .action-group a {
        width: auto;
        flex: 1 1 calc(50% - 8px);
    }
}
</style>

@if(session('success'))
<div class="alert-success">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<div class="tch-stats">
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#fef7f7;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-file-text-fill" style="color:var(--tch-primary);font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: var(--tch-primary);">{{ $totalSubmissions }}</div>
        <div class="tch-stat-lbl">Total Submissions</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-clock" style="color:#d97706;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #d97706;">{{ $pendingCount }}</div>
        <div class="tch-stat-lbl">Pending Review</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-check-circle" style="color:#16a34a;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #16a34a;">{{ $approvedCount }}</div>
        <div class="tch-stat-lbl">Approved</div>
    </div>
    <div class="tch-stat">
        <div style="width:38px;height:38px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;margin-bottom:12px;">
            <i class="bi bi-x-circle" style="color:#dc2626;font-size:1rem;"></i>
        </div>
        <div class="tch-stat-val" style="color: #dc2626;">{{ $rejectedCount }}</div>
        <div class="tch-stat-lbl">Rejected</div>
    </div>
</div>

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background: #fef7f7; color: var(--tch-primary);">
                <i class="bi bi-file-text-fill"></i>
            </div>
            Excuse Submissions
        </div>
    </div>

    <!-- Filters -->
    <div style="padding: 16px 22px; border-bottom: 1px solid rgba(255,255,255,0.08);">
        <form method="GET" class="filter-form">
            <div class="row g-3">
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #f8fafc; margin-bottom: 4px; display: block;">Status</label>
                    <select name="status" class="tch-input" style="width: 100%;">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #f8fafc; margin-bottom: 4px; display: block;">Subject</label>
                    <select name="subject" class="tch-input" style="width: 100%;">
                        <option value="">All Subjects</option>
                        @foreach($teacherSubjects as $subject)
                            <option value="{{ $subject->code }}" {{ request('subject') == $subject->code ? 'selected' : '' }}>
                                {{ $subject->code }} - {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label style="font-size: 0.8rem; font-weight: 600; color: #f8fafc; margin-bottom: 4px; display: block;">Student</label>
                    <select name="student_id" class="tch-input" style="width: 100%;">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->name }} ({{ $student->student_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2" style="display: flex; align-items: end; gap: 8px; flex-wrap: wrap;">
                    <button type="submit" class="tch-btn tch-btn-primary" style="white-space: nowrap;">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('teacher.excuse.reviews') }}" class="tch-btn tch-btn-ghost" style="white-space: nowrap;">
                        <i class="bi bi-arrow-clockwise"></i> Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Excuse Submissions Table -->
    <div style="overflow-x: auto; padding: 22px;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Subject</th>
                    <th>Absent Date</th>
                    <th>Reason</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($excuseSubmissions as $excuse)
                    <tr data-excuse-id="{{ $excuse->id }}">
                        <td data-label="Student">
                            <div class="student-info">
                                <img src="{{ $excuse->user->profile_image ? (str_starts_with($excuse->user->profile_image, 'http') ? $excuse->user->profile_image : asset('storage/'.$excuse->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($excuse->user->name).'&background=800000&color=fff' }}" 
                                     class="student-avatar" alt="Student avatar">
                                <div class="student-meta">
                                    <div class="name">{{ $excuse->user->name }}</div>
                                    <div class="subtext">{{ $excuse->user->student_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Subject">
                            <div class="subject-info">
                                <div class="subject-meta">
                                    <div class="title">{{ $excuse->attendance->subject_code }}</div>
                                    <div class="subtext">{{ $excuse->attendance->subject->name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>
                        <td data-label="Absent Date">{{ \Carbon\Carbon::parse($excuse->attendance->date)->format('M j, Y') }}</td>
                        <td data-label="Reason">
                            <div class="excuse-summary">
                                <div class="reason" title="{{ $excuse->reason }}">{{ Str::limit($excuse->reason, 80) }}</div>
                                @if($excuse->description)
                                <div class="description" title="{{ $excuse->description }}">{{ Str::limit($excuse->description, 60) }}</div>
                                @endif
                            </div>
                        </td>
                        <td data-label="Submitted">
                            <div class="meta-cell">
                                <div>{{ $excuse->created_at->format('M j, Y') }}</div>
                                <div class="subtext">{{ $excuse->created_at->format('g:i A') }}</div>
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($excuse->status === 'pending')
                                <span class="status-badge status-pending">Pending</span>
                            @elseif($excuse->status === 'approved')
                                <span class="status-badge status-approved">Approved</span>
                            @elseif($excuse->status === 'rejected')
                                <span class="status-badge status-rejected">Rejected</span>
                            @endif
                        </td>
                        <td data-label="Actions">
                            <div class="action-group excuse-actions">
                                <button type="button" onclick="viewExcuse({{ $excuse->id }})" class="action-view">
                                    <i class="bi bi-eye"></i> View
                                </button>
                                @if($excuse->attachments && count($excuse->attachments) > 0)
                                    <span class="attachment-badge">Attachment</span>
                                @endif
                                @if($excuse->status === 'pending')
                                    <button type="button" onclick="approveExcuse({{ $excuse->id }})" class="action-approve">
                                        <i class="bi bi-check"></i> Approve
                                    </button>
                                    <button type="button" onclick="rejectExcuse({{ $excuse->id }})" class="action-reject">
                                        <i class="bi bi-x"></i> Reject
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="bi bi-file-text"></i>
                            <div>No excuse submissions found</div>
                            <div style="font-size: 0.8rem; margin-top: 4px;">Students haven't submitted any excuse letters for your subjects yet.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($excuseSubmissions->hasPages())
        <div style="padding: 16px 22px; border-top: 1px solid rgba(255,255,255,0.08);">
            {{ $excuseSubmissions->links() }}
        </div>
    @endif
</div>

<!-- View Excuse Modal -->
<div class="modal fade" id="viewExcuseModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; border: none; background: #2a2a2a; display: flex; flex-direction: column; max-height: calc(100vh - 30px); min-height: 0;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.12);">
                <h5 class="modal-title" style="font-weight: 700; color: #f8fafc;">
                    <i class="bi bi-file-text" style="color: #94a3b8;"></i>
                    Excuse Letter Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding: 20px; color: #e2e8f0; overflow-y: auto; flex: 1 1 auto; min-height: 0;" id="excuseDetails">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.12); padding: 16px 20px;">
                <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Excuse Modal -->
<div class="modal fade" id="approveExcuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.12);">
                <h5 class="modal-title" style="font-weight: 700; color: #f8fafc;">
                    <i class="bi bi-check-circle" style="color: #34d399;"></i>
                    Approve Excuse
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="approveExcuseForm">
                @csrf
                <div class="modal-body" style="padding: 20px; color: #cbd5e1;">
                    <p style="color: #cbd5e1; margin-bottom: 16px;">Are you sure you want to approve this excuse? This will mark the attendance as excused.</p>
                    
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #f8fafc;">Approval Note (Optional)</label>
                        <textarea name="admin_notes" class="tch-input" rows="3" placeholder="Add any additional notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.12); padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: #16a34a; color: white;">
                        <i class="bi bi-check"></i> Approve Excuse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Excuse Modal -->
<div class="modal fade" id="rejectExcuseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: none;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.12);">
                <h5 class="modal-title" style="font-weight: 700; color: #f8fafc;">
                    <i class="bi bi-x-circle" style="color: #f87171;"></i>
                    Reject Excuse
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectExcuseForm">
                @csrf
                <div class="modal-body" style="padding: 20px; color: #cbd5e1;">
                    <div class="mb-3">
                        <label class="form-label" style="font-weight: 600; color: #f8fafc;">Reason for Rejection *</label>
                        <textarea name="admin_notes" class="tch-input" rows="4" placeholder="Please provide a reason for rejecting this excuse..." required></textarea>
                        <div style="font-size: 0.75rem; color: #a8b2c1; margin-top: 4px;">The student will see this feedback.</div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.12); padding: 16px 20px;">
                    <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="tch-btn" style="background: #dc2626; color: white;">
                        <i class="bi bi-x"></i> Reject Excuse
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Image Viewer Modal -->
<div class="modal fade" id="imageViewerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius: 14px; border: none; background: #2a2a2a;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.12); background: #2a2a2a;">
                <h5 class="modal-title" style="font-weight: 700; color: white;">
                    <i class="bi bi-image" style="color: #10b981;"></i>
                    <span id="imageTitle">Attachment Preview</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body image-modal-body" style="padding: 0; background: #2a2a2a; text-align: center;">
                <img id="modalImage" src="" alt="Attachment" style="max-width: 100%; max-height: calc(100vh - 240px); object-fit: contain;">
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.12); background: #2a2a2a; justify-content: center; flex-shrink: 0;">
                <button type="button" class="tch-btn tch-btn-ghost" data-bs-dismiss="modal" style="background: #374151; color: white; border-color: #4b5563;">
                    <i class="bi bi-x"></i> Close
                </button>
                <a id="downloadLink" href="" download class="tch-btn" style="background: #10b981; color: white;">
                    <i class="bi bi-download"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const teacherExcuseBaseUrl = "{{ url('teacher/excuse') }}";
let currentExcuseId = null;

function viewImage(imageUrl, imageName) {
    document.getElementById('modalImage').src = imageUrl;
    document.getElementById('imageTitle').textContent = imageName;
    document.getElementById('downloadLink').href = imageUrl;
    document.getElementById('downloadLink').download = imageName;
    new bootstrap.Modal(document.getElementById('imageViewerModal')).show();
}

function viewExcuse(excuseId) {
    fetch(`${teacherExcuseBaseUrl}/${excuseId}/detail`, {
        headers: {
            'Accept': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const excuse = data.excuse;
                document.getElementById('excuseDetails').innerHTML = `
                    <div style="display: grid; gap: 20px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Student</h6>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <img src="${excuse.user.profile_image ? (excuse.user.profile_image.startsWith('http') ? excuse.user.profile_image : '/storage/' + excuse.user.profile_image) : 'https://ui-avatars.com/api/?name=' + encodeURIComponent(excuse.user.name) + '&background=800000&color=fff'}" 
                                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    <div>
                                        <div style="font-weight: 600;">${excuse.user.name}</div>
                                        <div style="font-size: 0.8rem; color: #a8b2c1;">${excuse.user.student_number}</div>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Subject & Date</h6>
                                <div style="font-weight: 600;">${excuse.attendance.subject_code}</div>
                                <div style="font-size: 0.9rem; color: #a8b2c1;">${excuse.attendance.subject ? excuse.attendance.subject.name : ''}</div>
                                <div style="font-size: 0.85rem; color: #f472b6; margin-top: 4px;">Absent: ${new Date(excuse.attendance.date).toLocaleDateString()}</div>
                            </div>
                        </div>
                        
                        <div>
                            <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Excuse Reason</h6>
                            <div style="padding: 12px; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08);">
                                ${excuse.reason}
                            </div>
                        </div>
                        
                        <div>
                            <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Detailed Description</h6>
                            <div style="padding: 12px; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); white-space: pre-wrap; line-height: 1.6; min-height: 60px; color: #e2e8f0;">
                                ${excuse.description || 'No additional details provided.'}
                            </div>
                        </div>
                        
                        ${excuse.attachments && excuse.attachments.length > 0 ? `
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">
                                    Attachments <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 10px; font-size: 0.65rem; font-weight: 700;">${excuse.attachments.length}</span>
                                </h6>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 8px;">
                                    ${excuse.attachments.map(attachment => {
                                        const isImage = /\.(jpg|jpeg|png|gif|bmp|webp)$/i.test(attachment);
                                        const fileName = attachment.split('/').pop();
                                        return `
                                            <div class="attachment-item" style="display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; font-size: 0.8rem;">
                                                <div style="width: 32px; height: 32px; border-radius: 6px; background: ${isImage ? '#16a34a' : '#475569'}; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                                    <i class="bi ${isImage ? 'bi-image' : 'bi-paperclip'}" style="color: white; font-size: 0.9rem;"></i>
                                                </div>
                                                <div style="flex: 1; min-width: 0;">
                                                    <div style="font-weight: 600; color: #f8fafc; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${fileName}">
                                                        ${fileName}
                                                    </div>
                                                    <div style="font-size: 0.7rem; color: #a8b2c1; margin-top: 1px;">
                                                        ${isImage ? 'Image File' : 'Document'}
                                                    </div>
                                                </div>
                                                <div style="display: flex; gap: 4px;">
                                                    ${isImage ? `
                                                        <button onclick="viewImage('/storage/${attachment}', '${fileName}')" 
                                                                style="padding: 4px 8px; background: #16a34a; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 0.7rem; display: flex; align-items: center; gap: 2px;"
                                                                title="View Image">
                                                            <i class="bi bi-eye-fill"></i> View
                                                        </button>
                                                    ` : ''}
                                                    <a href="/storage/${attachment}" target="_blank" 
                                                       style="padding: 4px 8px; background: #6b7280; color: white; text-decoration: none; border-radius: 4px; font-size: 0.7rem; display: flex; align-items: center; gap: 2px;"
                                                       title="Download File">
                                                        <i class="bi bi-download"></i> Get
                                                    </a>
                                                </div>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Status</h6>
                                <span class="badge" style="background: ${excuse.status === 'approved' ? '#f0fdf4' : excuse.status === 'rejected' ? '#fef2f2' : '#fffbeb'}; color: ${excuse.status === 'approved' ? '#16a34a' : excuse.status === 'rejected' ? '#dc2626' : '#d97706'}; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">
                                    ${excuse.status.charAt(0).toUpperCase() + excuse.status.slice(1)}
                                </span>
                            </div>
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Submitted</h6>
                                <div style="font-size: 0.9rem; color: #a8b2c1;">${new Date(excuse.created_at).toLocaleString()}</div>
                            </div>
                        </div>
                        
                        ${excuse.reviewed_at ? `
                            <div>
                                <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Review Information</h6>
                                <div style="padding: 12px; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #e2e8f0;">
                                    <div style="margin-bottom: 8px;"><strong>Reviewed by:</strong> ${excuse.reviewer ? excuse.reviewer.name : 'Unknown'}</div>
                                    <div style="margin-bottom: 8px;"><strong>Reviewed at:</strong> ${new Date(excuse.reviewed_at).toLocaleString()}</div>
                                    ${excuse.admin_notes ? `<div><strong>Notes:</strong> ${excuse.admin_notes}</div>` : ''}
                                </div>
                            </div>
                        ` : ''}

                        <div>
                            <h6 style="font-weight: 600; color: #f8fafc; margin-bottom: 8px;">Communication Thread</h6>
                            <div style="padding: 12px; background: rgba(255,255,255,0.06); border-radius: 8px; border: 1px solid rgba(255,255,255,0.08); color: #e2e8f0; max-height: 250px; overflow-y: auto;">
                                ${excuse.comments && excuse.comments.length > 0 ? excuse.comments.map(comment => `
                                    <div style="margin-bottom: 12px; padding: 8px; background: rgba(0,0,0,0.2); border-radius: 6px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.8rem; color: #a8b2c1;">
                                            <strong>${comment.user.name}</strong>
                                            <span>${new Date(comment.created_at).toLocaleString()}</span>
                                        </div>
                                        <div style="margin-top: 4px; font-size: 0.9rem;">${comment.body}</div>
                                    </div>
                                `).join('') : '<div style="color: #a8b2c1; font-size: 0.9rem;">No comments yet.</div>'}
                            </div>
                            <form action="/teacher/excuse/${excuse.id}/comment" method="POST" style="margin-top: 12px; display: flex; gap: 8px;">
                                <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                                <input type="text" name="body" class="tch-input" style="flex: 1;" placeholder="Type a comment..." required>
                                <button type="submit" class="tch-btn tch-btn-primary">Send</button>
                            </form>
                        </div>
                    </div>
                `;
                new bootstrap.Modal(document.getElementById('viewExcuseModal')).show();
            }
        })
        .catch(error => console.error('Error:', error));
}

function approveExcuse(excuseId) {
    currentExcuseId = excuseId;
    document.getElementById('approveExcuseForm').action = `${teacherExcuseBaseUrl}/${excuseId}/approve`;
    new bootstrap.Modal(document.getElementById('approveExcuseModal')).show();
}

function rejectExcuse(excuseId) {
    currentExcuseId = excuseId;
    document.getElementById('rejectExcuseForm').action = `${teacherExcuseBaseUrl}/${excuseId}/reject`;
    new bootstrap.Modal(document.getElementById('rejectExcuseModal')).show();
}

// Handle approve form submission
document.getElementById('approveExcuseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('approveExcuseModal')).hide();
            
            // Update the excuse row immediately
            const excuseRow = document.querySelector(`[data-excuse-id="${currentExcuseId}"]`);
            if (excuseRow) {
                // Update status badge
                const statusBadge = excuseRow.querySelector('.status-badge');
                if (statusBadge) {
                    statusBadge.textContent = 'Approved';
                    statusBadge.className = 'status-badge status-approved';
                    statusBadge.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    statusBadge.style.color = 'white';
                }
                
                // Update action buttons
                const actionButtons = excuseRow.querySelector('.excuse-actions');
                if (actionButtons) {
                    actionButtons.innerHTML = '<span class="text-success"><i class="bi bi-check-circle-fill"></i> Approved</span>';
                }
            }
            
            // Show success message
            const successMsg = document.createElement('div');
            successMsg.innerHTML = `
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
                    <i class="bi bi-check-circle-fill"></i><span>${data.message}</span>
                </div>
            `;
            document.querySelector('.tch-stats').parentNode.insertBefore(successMsg, document.querySelector('.tch-stats'));
            
            // Remove success message after 3 seconds
            setTimeout(() => {
                if (successMsg.parentNode) {
                    successMsg.parentNode.removeChild(successMsg);
                }
            }, 3000);
        } else {
            if (typeof showPremiumToast === 'function') showPremiumToast('Error: ' + (data.message || 'Unknown error occurred'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showPremiumToast === 'function') showPremiumToast('An error occurred while approving the excuse: ' + error.message, 'error');
    });
});

// Handle reject form submission
document.getElementById('rejectExcuseForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData,
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectExcuseModal')).hide();
            location.reload();
        } else {
            if (typeof showPremiumToast === 'function') showPremiumToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (typeof showPremiumToast === 'function') showPremiumToast('An error occurred while rejecting the excuse.', 'error');
    });
});
</script>
@endsection
