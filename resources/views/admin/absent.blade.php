@extends('layouts.app')
@section('page-title', 'Absent Report')

@section('content')


@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill fs-5"></i><span>{{ session('success') }}</span>
</div>
@endif

<style>
    .badge-yearsem {
        display: inline-flex;
        flex-direction: column;
        gap: 3px;
    }
    .badge-yearsem .yr {
        font-size: .78rem;
        font-weight: 700;
        color: #800000;
        background: #fef2f2;
        border: 1px solid #fecaca;
        border-radius: 6px;
        padding: 2px 8px;
        line-height: 1.4;
        white-space: nowrap;
    }
    .badge-yearsem .sem {
        font-size: .72rem;
        font-weight: 600;
        color: #64748b;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 2px 8px;
        line-height: 1.4;
        white-space: nowrap;
    }
</style>

<!-- Filter bar -->
<div class="adm-card" style="margin-bottom:20px;">
    <div style="padding:18px 22px;">
        <form method="GET" action="{{ route('admin.absent') }}" style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
            <div style="display:flex;flex-direction:column;">
                <label style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Date</label>
                <input type="date" name="date" class="adm-input" value="{{ $date }}">
            </div>
            <div style="display:flex;flex-direction:column;">
                <label style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Year Level</label>
                <select name="year_level" class="adm-input">
                    <option value="">All Years</option>
                    @foreach([1,2,3,4] as $y)
                    <option value="{{ $y }}" {{ request('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;flex-direction:column;">
                <label style="font-size:.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Semester</label>
                <select name="semester" class="adm-input">
                    <option value="">All Semesters</option>
                    <option value="1" {{ request('semester')=='1'?'selected':'' }}>1st Semester</option>
                    <option value="2" {{ request('semester')=='2'?'selected':'' }}>2nd Semester</option>
                </select>
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <button type="submit" class="adm-btn adm-btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admin.absent') }}" class="adm-btn adm-btn-ghost">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Results -->
<div class="adm-card">
    <div class="adm-card-head">
        <div class="adm-card-title">
            <div class="adm-card-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-person-x-fill"></i></div>
            Absent Students â€” {{ \Carbon\Carbon::parse($date)->format('F j, Y') }}
        </div>
        <span style="background:#fef2f2;color:#dc2626;padding:4px 12px;border-radius:99px;font-size:.78rem;font-weight:700;border:1px solid #fecaca;">
            {{ $absentRecords->count() }} absent
        </span>
    </div>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="adm-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Year / Sem</th>
                    <th>Subject</th>
                    <th>Total Absences</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($absentRecords as $i => $record)
                @php
                    $totalAbsences = $record->user
                        ? \App\Models\Attendance::where('user_id', $record->user->id)
                            ->where('subject_code', $record->subject_code)
                            ->where('status', 'Absent')->count()
                        : 0;
                @endphp
                <tr>
                    <td data-label="#" style="color:#d4b5a0;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td data-label="Student">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <img src="{{ $record->user && $record->user->profile_image ? (str_starts_with($record->user->profile_image, 'http') ? $record->user->profile_image : asset('storage/'.$record->user->profile_image)) : 'https://ui-avatars.com/api/?name='.urlencode($record->user->name ?? 'N').'&background=800000&color=fff&size=80' }}"
                                 style="width:32px;height:32px;border-radius:50%;object-fit:cover;border:2px solid #f1f5f9;">
                            <div style="font-weight:600;color:#f5e6d3;font-size:.875rem;">{{ $record->user->name ?? 'â€”' }}</div>
                        </div>
                    </td>
                    <td data-label="Student ID" style="font-family:monospace;font-weight:600;color:#d4b5a0;">{{ $record->user->student_number ?? 'â€”' }}</td>
                    <td data-label="Course"><span class="badge-course">{{ $record->user->course ?? 'â€”' }}</span></td>
                    <td data-label="Year / Sem">
                        <div class="badge-yearsem">
                            <span class="yr">Year {{ $record->user->year_level ?? 'â€”' }}</span>
                            <span class="sem">Sem {{ $record->user->semester ?? 'â€”' }}</span>
                        </div>
                    </td>
                    <td data-label="Subject" style="font-weight:600;color:#f5e6d3;">{{ $record->subject->name ?? $record->subject_code }}</td>
                    <td data-label="Total Absences">
                        @if($totalAbsences >= 3)
                            <span class="badge-absent">{{ $totalAbsences }}x â€” ðŸš¨ Critical</span>
                        @elseif($totalAbsences == 2)
                            <span class="badge-late">{{ $totalAbsences }}x â€” âš ï¸ Warning</span>
                        @else
                            <span style="color:#d4b5a0;font-size:.82rem;">{{ $totalAbsences }}x</span>
                        @endif
                    </td>
                    <td data-label="Action">
                        <div style="display:flex;gap:6px;">
                            @if($record->user)
                            <a href="{{ route('admin.student', $record->user->id) }}" class="view-btn adm-btn-ghost" style="border:none;">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <button onclick="openWarnModal({{ $record->user->id }}, '{{ addslashes($record->user->name) }}', '{{ $record->subject_code }}', '{{ addslashes($record->subject->name ?? $record->subject_code) }}', {{ $totalAbsences }})"
                                    style="display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:7px;
                                           background:{{ $totalAbsences >= 3 ? '#fef2f2' : ($totalAbsences == 2 ? '#fffbeb' : '#f8fafc') }};
                                           color:{{ $totalAbsences >= 3 ? '#dc2626' : ($totalAbsences == 2 ? '#d97706' : '#475569') }};
                                           border:1px solid {{ $totalAbsences >= 3 ? '#fecaca' : ($totalAbsences == 2 ? '#fde68a' : '#e2e8f0') }};
                                           font-size:.78rem;font-weight:600;cursor:pointer;transition:all .2s;"
                                    onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                                <i class="bi bi-bell-fill"></i> Warn
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-check-circle" style="color:#16a34a;opacity:1;font-size:2.5rem;display:block;margin-bottom:10px;"></i>
                            <p style="color:#16a34a;font-weight:600;">No absences recorded for this date.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Warning Modal -->
<div id="warnModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);backdrop-filter:blur(3px);align-items:center;justify-content:center;">
    <div style="background:white;border-radius:20px;padding:32px;max-width:460px;width:90%;box-shadow:0 24px 60px rgba(0,0,0,0.2);position:relative;">
        <button onclick="closeWarnModal()" style="position:absolute;top:16px;right:16px;background:#f1f5f9;border:none;width:32px;height:32px;border-radius:50%;cursor:pointer;font-size:1rem;color:#64748b;display:flex;align-items:center;justify-content:center;">
            <i class="bi bi-x"></i>
        </button>

        <div id="warnIcon" style="width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:16px;"></div>
        <h5 id="warnTitle" style="font-size:1.1rem;font-weight:800;color:#1e293b;margin-bottom:4px;"></h5>
        <p id="warnSubtitle" style="font-size:.85rem;color:#64748b;margin-bottom:20px;"></p>

        <div id="warnPreview" style="border-radius:12px;padding:14px 16px;font-size:.85rem;line-height:1.5;margin-bottom:20px;border:1px solid;"></div>

        <form id="warnForm" method="POST">
            @csrf
            <input type="hidden" name="subject_code" id="warnSubjectCode">
            <input type="hidden" name="type" id="warnType">
            <input type="hidden" name="message" id="warnMessage">

            <div style="display:flex;gap:10px;">
                <button type="button" onclick="closeWarnModal()"
                        style="flex:1;padding:11px;background:#f1f5f9;color:#475569;border:1.5px solid #e2e8f0;border-radius:10px;font-weight:600;font-size:.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button type="submit" id="warnSubmitBtn"
                        style="flex:1;padding:11px;color:white;border:none;border-radius:10px;font-weight:700;font-size:.875rem;cursor:pointer;transition:all .2s;">
                    <i class="bi bi-bell-fill me-1"></i> Send Warning
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openWarnModal(userId, name, subjectCode, subjectName, absenceCount) {
    const modal    = document.getElementById('warnModal');
    const icon     = document.getElementById('warnIcon');
    const title    = document.getElementById('warnTitle');
    const subtitle = document.getElementById('warnSubtitle');
    const preview  = document.getElementById('warnPreview');
    const form     = document.getElementById('warnForm');
    const submitBtn = document.getElementById('warnSubmitBtn');

    form.action = `/admin/student/${userId}/warn`;
    document.getElementById('warnSubjectCode').value = subjectCode;

    if (absenceCount >= 3) {
        document.getElementById('warnType').value = 'warning_3';
        const msg = `ðŸš¨ Final Notice: You have been absent ${absenceCount} or more times in ${subjectName}. You are required to report to the office to speak with your teacher.`;
        document.getElementById('warnMessage').value = msg;
        icon.style.cssText = 'width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:16px;background:#fef2f2;color:#dc2626;';
        icon.innerHTML = '<i class="bi bi-exclamation-octagon-fill"></i>';
        title.textContent = `Final Notice â€” ${name}`;
        subtitle.textContent = `${absenceCount} absences in ${subjectName}. This requires an office visit.`;
        preview.style.cssText = 'border-radius:12px;padding:14px 16px;font-size:.85rem;line-height:1.5;margin-bottom:20px;border:1px solid #fecaca;background:#fef2f2;color:#dc2626;';
        preview.textContent = msg;
        submitBtn.style.background = 'linear-gradient(135deg,#dc2626,#ef4444)';
        submitBtn.style.boxShadow = '0 4px 14px rgba(220,38,38,.3)';
    } else if (absenceCount == 2) {
        document.getElementById('warnType').value = 'warning_2';
        const msg = `âš ï¸ Warning: You have been absent for 2 consecutive sessions in ${subjectName}. Please attend your next class to avoid further action.`;
        document.getElementById('warnMessage').value = msg;
        icon.style.cssText = 'width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:16px;background:#fffbeb;color:#d97706;';
        icon.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i>';
        title.textContent = `Absence Warning â€” ${name}`;
        subtitle.textContent = `2 absences in ${subjectName}. Send a warning notification.`;
        preview.style.cssText = 'border-radius:12px;padding:14px 16px;font-size:.85rem;line-height:1.5;margin-bottom:20px;border:1px solid #fde68a;background:#fffbeb;color:#92400e;';
        preview.textContent = msg;
        submitBtn.style.background = 'linear-gradient(135deg,#d97706,#f59e0b)';
        submitBtn.style.boxShadow = '0 4px 14px rgba(217,119,6,.3)';
    } else {
        document.getElementById('warnType').value = 'custom';
        const msg = `ðŸ“‹ Notice: You were marked absent in ${subjectName}. Please make sure to attend your next class.`;
        document.getElementById('warnMessage').value = msg;
        icon.style.cssText = 'width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:16px;background:#fef2f2;color:#800000;';
        icon.innerHTML = '<i class="bi bi-info-circle-fill"></i>';
        title.textContent = `Absence Notice â€” ${name}`;
        subtitle.textContent = `1 absence in ${subjectName}. Send a reminder notification.`;
        preview.style.cssText = 'border-radius:12px;padding:14px 16px;font-size:.85rem;line-height:1.5;margin-bottom:20px;border:1px solid #fecaca;background:#fef2f2;color:#800000;';
        preview.textContent = msg;
        submitBtn.style.background = 'linear-gradient(135deg,#800000,#a00000)';
        submitBtn.style.boxShadow = '0 4px 14px rgba(128,0,0,.3)';
    }

    modal.style.display = 'flex';
}

function closeWarnModal() {
    document.getElementById('warnModal').style.display = 'none';
}

document.getElementById('warnModal').addEventListener('click', function(e) {
    if (e.target === this) closeWarnModal();
});
</script>
@endsection
