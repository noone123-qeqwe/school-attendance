@extends('layouts.app')
@section('page-title', $student->name)

@section('content')


<a href="{{ route('admin.students') }}" class="student-back-link">
    <i class="bi bi-arrow-left"></i> Back to Students
</a>

<!-- Profile header -->
<div class="adm-card" style="margin-bottom:20px;">
    <div style="padding:24px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
        <img src="{{ $student->profile_image ? (str_starts_with($student->profile_image, 'http') ? $student->profile_image : asset('storage/'.$student->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($student->name).'&background=800000&color=fff&size=200' }}"
             style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #fef3c7;box-shadow:0 4px 16px rgba(128,0,0,.12);">
        <div style="flex:1;">
            <div class="student-name">{{ $student->name }}</div>
            <div class="student-email">{{ $student->email }}</div>
            <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;">
                <span class="badge-course">{{ $student->course }}</span>
                <span class="badge-year">Year {{ $student->year_level }}</span>
                <span style="background:#f8fafc;color:#475569;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #e2e8f0;">
                    {{ $student->semester }}{{ (int)$student->semester===1?'st':'nd' }} Semester
                </span>
                <span style="background:#f8fafc;color:#475569;padding:3px 10px;border-radius:99px;font-size:.72rem;font-weight:700;border:1px solid #e2e8f0;font-family:monospace;">
                    {{ $student->student_number }}
                </span>
            </div>
        </div>
        <!-- Mini stats -->
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="text-align:center;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:12px 18px;">
                <div class="student-stat-value">{{ $total }}</div>
                <div class="student-stat-label">Total</div>
            </div>
            <div style="text-align:center;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">{{ $totalPresent }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#16a34a;text-transform:uppercase;">Present</div>
            </div>
            <div style="text-align:center;background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#d97706;">{{ $totalLate }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#d97706;text-transform:uppercase;">Late</div>
            </div>
            <div style="text-align:center;background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:#dc2626;">{{ $totalAbsent }}</div>
                <div style="font-size:.68rem;font-weight:600;color:#dc2626;text-transform:uppercase;">Absent</div>
            </div>
            <div style="text-align:center;background:#f8fafc;border:1px solid #f1f5f9;border-radius:12px;padding:12px 18px;">
                <div style="font-size:1.4rem;font-weight:800;color:{{ $rate>=75?'#16a34a':'#dc2626' }};">{{ $rate }}%</div>
                <div style="font-size:.68rem;font-weight:600;color:#94a3b8;text-transform:uppercase;">Rate</div>
            </div>
        </div>
        <!-- Warning Button -->
        <button onclick="openWarningModal({{ $student->id }}, '{{ $student->name }}')" 
                class="adm-btn" style="background: #dc2626; color: white; display: flex; align-items: center; gap: 8px;">
            <i class="bi bi-exclamation-triangle"></i> Send Warning
        </button>
    </div>
    <!-- Progress bar -->
    <div style="padding:0 24px 20px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-size:.75rem;font-weight:600;color:#b39b82;">Attendance Rate</span>
            <span style="font-size:.75rem;font-weight:700;color:{{ $rate>=75?'#16a34a':'#dc2626' }};">{{ $rate }}%</span>
        </div>
        <div style="height:8px;background:#f1f5f9;border-radius:99px;overflow:hidden;">
            <div style="height:100%;width:{{ $rate }}%;background:{{ $rate>=75?'linear-gradient(90deg,#16a34a,#22c55e)':'linear-gradient(90deg,#dc2626,#ef4444)' }};border-radius:99px;transition:width 1s;"></div>
        </div>
    </div>
</div>

<!-- Device Binding & Anti-Proxy Security Panel -->
<div class="adm-card" style="margin-bottom:24px;">
    <div class="adm-card-head" style="display:flex;justify-content:space-between;align-items:center;">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-shield-lock-fill"></i></div>
            Bound Attendance Device
        </div>
        @if($student->deviceBinding)
            <span class="badge" style="background:rgba(22,163,74,0.12);color:#16a34a;border:1px solid rgba(22,163,74,0.25);font-size:0.75rem;padding:5px 10px;border-radius:8px;">
                <i class="bi bi-check-circle me-1"></i>Active & Bound
            </span>
        @else
            <span class="badge" style="background:rgba(148,163,184,0.12);color:#64748b;border:1px solid rgba(148,163,184,0.25);font-size:0.75rem;padding:5px 10px;border-radius:8px;">
                <i class="bi bi-phone me-1"></i>Not Bound
            </span>
        @endif
    </div>
    <div style="padding:16px 24px;">
        @if($student->deviceBinding)
        <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:44px;height:44px;border-radius:10px;background:#f0fdf4;color:#16a34a;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                    <i class="bi {{ $student->deviceBinding->getDeviceIcon() }}"></i>
                </div>
                <div>
                    <div style="font-weight:700;font-size:0.95rem;color:#1e293b;">{{ $student->deviceBinding->device_name ?: 'Bound Mobile Device' }}</div>
                    <div style="font-size:0.78rem;color:#64748b;">
                        Bound: {{ $student->deviceBinding->created_at?->format('M d, Y g:i A') ?? 'N/A' }} •
                        Last Active: <span style="font-weight:600;color:#0f172a;">{{ $student->deviceBinding->last_seen_at?->diffForHumans() ?? 'Never' }}</span>
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="text-align:right;font-size:0.78rem;color:#64748b;">
                    <div>IP: <span style="font-family:monospace;color:#0f172a;">{{ $student->deviceBinding->ip_address ?: 'Unknown' }}</span></div>
                    <div>Changes: <span style="font-weight:600;color:{{ $student->deviceBinding->change_count > 2 ? '#dc2626' : '#16a34a' }};">{{ $student->deviceBinding->change_count ?? 0 }}</span></div>
                </div>
                <form action="{{ route('admin.student.reset_device', $student->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to reset the device binding for {{ addslashes($student->name) }}? The student will be prompted to bind their current device upon next sign in.')" style="margin:0;">
                    @csrf
                    <button type="submit" class="adm-btn" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:0.8rem;padding:7px 12px;">
                        <i class="bi bi-phone-flip me-1"></i>Reset Binding
                    </button>
                </form>
            </div>
        </div>
        @else
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;color:#64748b;font-size:0.85rem;">
                <i class="bi bi-phone" style="font-size:1.3rem;"></i>
                <span>This student has not yet bound a personal device for attendance clock-ins.</span>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Linked Parents / Guardians Management -->
<div class="adm-card" style="margin-bottom:24px;">
    <div class="adm-card-head" style="display:flex;justify-content:space-between;align-items:center;">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#fef3c7;color:#b45309;"><i class="bi bi-people-fill"></i></div>
            Linked Parents &amp; Guardians
        </div>
        <div style="display:flex;align-items:center;gap:10px;">
            <span class="badge" style="background:rgba(207,164,111,0.15);color:#cfa46f;border:1px solid rgba(207,164,111,0.3);font-size:0.75rem;padding:5px 10px;border-radius:8px;">
                {{ $student->parents->count() }} Connected
            </span>
            <button type="button" onclick="openLinkParentModal()" class="adm-btn adm-btn-primary" style="font-size:0.8rem;padding:6px 14px;background:linear-gradient(135deg, #cfa46f 0%, #a67c43 100%);color:#140703;font-weight:700;border:none;">
                <i class="bi bi-person-plus-fill me-1"></i> Link Parent Account
            </button>
        </div>
    </div>
    <div style="padding:16px 24px;">
        @if($student->parents->isNotEmpty())
        <div style="display:flex;flex-direction:column;gap:12px;">
            @foreach($student->parents as $parent)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;gap:12px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:12px;min-width:0;flex:1;">
                    <img src="{{ $parent->profile_photo_url }}" style="width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #e2e8f0;">
                    <div style="min-width:0;flex:1;">
                        <div style="font-weight:700;color:#1e293b;font-size:0.95rem;">{{ $parent->name }}</div>
                        <div style="font-size:0.78rem;color:#64748b;display:flex;gap:10px;flex-wrap:wrap;margin-top:2px;">
                            <span><i class="bi bi-envelope me-1"></i>{{ $parent->email }}</span>
                            @if($parent->phone)
                                <span><i class="bi bi-phone me-1"></i>{{ $parent->phone }}</span>
                            @endif
                            <span class="badge" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;font-size:0.7rem;padding:2px 8px;border-radius:99px;">
                                <i class="bi bi-check-circle-fill me-1"></i>Authorized
                            </span>
                        </div>
                    </div>
                </div>
                <form action="{{ route('admin.student.unlink_parent', [$student, $parent]) }}" method="POST" onsubmit="return confirm('Are you sure you want to unlink parent {{ addslashes($parent->name) }} from this student?')" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="adm-btn" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;font-size:0.8rem;padding:6px 12px;">
                        <i class="bi bi-link-45deg me-1"></i>Unlink
                    </button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align:center;padding:24px 16px;color:#64748b;font-size:0.88rem;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;">
            <i class="bi bi-person-x" style="font-size:2rem;color:#94a3b8;display:block;margin-bottom:6px;"></i>
            <div style="font-weight:700;color:#334155;margin-bottom:4px;">No Parents / Guardians Linked</div>
            <p style="font-size:0.8rem;color:#64748b;margin-bottom:0;">
                Click "Link Parent Account" above to associate a registered parent account with this student.
            </p>
        </div>
        @endif
    </div>
</div>

<!-- Attendance Records -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-shield-check-fill"></i></div>
            Attendance History
        </div>
        <span style="font-size:.78rem;color:#94a3b8;">{{ $total }} total records</span>
    </div>
    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Time In</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $i => $record)
                <tr>
                    <td data-label="#" style="color:#cbd5e1;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td data-label="Date">
                        <div class="attendance-date">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</div>
                        <div class="attendance-day">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</div>
                    </td>
                    <td data-label="Subject" class="attendance-subject">{{ $record->subject->name ?? $record->subject_code }}</td>
                    <td data-label="Status">
                        @if($record->status === 'Present')
                            <span class="badge-present">Present</span>
                        @elseif($record->status === 'Late')
                            <span class="badge-late">Late</span>
                        @else
                            <span class="badge-absent">Absent</span>
                        @endif
                    </td>
                    <td data-label="Time In" class="attendance-time">{{ $record->time_in ? \Carbon\Carbon::parse($record->time_in)->format('h:i A') : 'â€”' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5">
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>No attendance records for this student.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="warningModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); background: rgba(26,14,11,0.95); box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h5 class="modal-title" style="color: #f3e7cd;">
                    <i class="bi bi-exclamation-triangle" style="color: #dc2626;"></i>
                    Send Warning to {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="warningForm" method="POST" action="{{ route('admin.student.warn', $student) }}">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Subject</label>
                        <select name="subject_code" class="adm-input" required>
                            <option value="">Select Subject</option>
                            @php
                                $subjects = \App\Models\Subject::orderBy('name')->get();
                            @endphp
                            @foreach($subjects as $subject)
                                <option value="{{ $subject->code }}">{{ $subject->code }} - {{ $subject->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Warning Type</label>
                        <select name="type" class="adm-input" required onchange="toggleCustomMessage()">
                            <option value="warning_2">2 Consecutive Absences</option>
                            <option value="warning_3">3+ Absences (Final Notice)</option>
                            <option value="warning_consecutive_3">3 Consecutive Absences (OSAS Readmission Required)</option>
                            <option value="custom">Custom Message</option>
                        </select>
                    </div>
                    
                    <div class="mb-3" id="customMessageDiv" style="display: none;">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">Custom Message</label>
                        <textarea name="message" class="adm-input" rows="3" placeholder="Enter your custom warning message..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 16px 20px;">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="adm-btn adm-btn-primary" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-color: #b91c1c;">
                        <i class="bi bi-send"></i> Send Warning
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Link Parent Account Modal -->
<div class="modal fade" id="linkParentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 14px; border: 1px solid rgba(255,255,255,0.1); background: rgba(26,14,11,0.95); box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                <h5 class="modal-title" style="color: #f3e7cd;">
                    <i class="bi bi-person-plus-fill text-gold me-2"></i>
                    Link Parent to {{ $student->name }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="linkParentForm" method="POST" action="{{ route('admin.student.link_parent', $student) }}">
                @csrf
                <div class="modal-body" style="padding: 20px;">
                    <div class="mb-3">
                        <label class="form-label" style="color: #b39b82; font-size: 0.85rem; font-weight: 600;">
                            Select Parent / Guardian Account
                        </label>
                        @if(isset($availableParents) && $availableParents->isNotEmpty())
                            <select name="parent_id" class="adm-input" required style="width:100%;">
                                <option value="">-- Choose Registered Parent --</option>
                                @foreach($availableParents as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->email }}{{ $p->phone ? ' • ' . $p->phone : '' }})</option>
                                @endforeach
                            </select>
                        @else
                            <div style="padding:14px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);border-radius:10px;color:#fca5a5;font-size:0.85rem;">
                                No other active parent accounts found to link. Create a parent account first if needed.
                            </div>
                        @endif
                    </div>
                    <div style="background:rgba(207,164,111,0.06);border:1px solid rgba(207,164,111,0.15);border-radius:10px;padding:12px;font-size:0.78rem;color:#e6dbce;line-height:1.45;">
                        <i class="bi bi-info-circle me-1 text-gold"></i>
                        Linking directly as an Administrator binds the accounts immediately and updates the student's primary guardian email address for automated attendance notifications.
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.05); padding: 16px 20px;">
                    <button type="button" class="adm-btn adm-btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    @if(isset($availableParents) && $availableParents->isNotEmpty())
                    <button type="submit" class="adm-btn adm-btn-primary" style="background: linear-gradient(135deg, #cfa46f 0%, #a67c43 100%); color:#140703; font-weight:700; border:none;">
                        <i class="bi bi-link-45deg me-1"></i> Confirm &amp; Link Parent
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openWarningModal(studentId, studentName) {
    new bootstrap.Modal(document.getElementById('warningModal')).show();
}

function openLinkParentModal() {
    new bootstrap.Modal(document.getElementById('linkParentModal')).show();
}

function toggleCustomMessage() {
    const type = document.querySelector('select[name="type"]').value;
    const customDiv = document.getElementById('customMessageDiv');
    customDiv.style.display = type === 'custom' ? 'block' : 'none';
}
</script>

@endsection
