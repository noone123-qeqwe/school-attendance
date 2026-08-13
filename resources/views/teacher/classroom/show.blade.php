@extends('layouts.app')
@section('page-title', 'Classroom - ' . $subject->name)

@section('content')

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<!-- Class Header -->
<div class="mb-4" style="background: linear-gradient(135deg, rgba(32,20,15,0.9) 0%, rgba(20,10,5,0.95) 100%); border: 1px solid rgba(207,164,111,0.25); border-radius: 24px; padding: 30px; position: relative; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.3);">
    <div style="position: absolute; top: 0; left: 0; width: 6px; height: 100%; background: linear-gradient(180deg, var(--gold) 0%, #8f6e4a 100%);"></div>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-4">
        <div>
            <div style="font-family: monospace; color: var(--gold); font-weight: 700; margin-bottom: 6px;">{{ $subject->code }}</div>
            <h1 style="color: #f3e7cd; font-weight: 800; margin: 0 0 8px 0; font-size: 2rem;">{{ $subject->name }}</h1>
            <div style="color: #b39b82; font-size: 0.95rem; display: flex; gap: 16px; flex-wrap: wrap;">
                <span><i class="bi bi-mortarboard me-1"></i> Year {{ $subject->year_level }} - Sem {{ $subject->semester }}</span>
                <span><i class="bi bi-clock me-1"></i> {{ $subject->days ?: 'TBA' }} ({{ $subject->start_time ? \Carbon\Carbon::parse($subject->start_time)->format('h:i A') : 'TBA' }})</span>
                @if($subject->room)<span><i class="bi bi-geo-alt me-1"></i> Room {{ $subject->room }}</span>@endif
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('teacher.qr', $subject->code) }}" class="btn btn-primary" style="background: var(--gold); color: #fff; border: none; font-weight: 600;">
                <i class="bi bi-qr-code-scan"></i> Start QR Attendance
            </a>
            <form action="{{ route('teacher.classroom.markAllPresent', $subject->code) }}" method="POST" style="display:inline;" onsubmit="return confirm('Mark all students present for today?');">
                @csrf
                <button type="submit" class="btn btn-primary" style="background: #10b981; color: #fff; border: none; font-weight: 600;">
                    <i class="bi bi-check2-all"></i> Mark All Present (Today)
                </button>
            </form>
            <a href="{{ route('teacher.subjects.edit', $subject->id) }}" class="btn btn-outline" style="border-color: rgba(207,164,111,0.3); color: var(--gold);">
                <i class="bi bi-pencil-square"></i> Edit
            </a>
            <form action="{{ route('teacher.subjects.destroy', $subject->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this subject? All associated data will be removed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline" style="color: #f87171; border-color: rgba(239,68,68,0.3); background: rgba(239,68,68,0.1);">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
        </div>
    </div>
</div>

<ul class="nav nav-pills mb-4" id="classroom-tabs" role="tablist" style="gap: 10px; border-bottom: 1px solid rgba(207,164,111,0.15); padding-bottom: 10px;">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="students-tab" data-bs-toggle="pill" data-bs-target="#students" type="button" role="tab" style="color: #b39b82; font-weight: 700;">
            <i class="bi bi-people-fill me-2"></i> Enrolled Students ({{ $students->count() }})
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="pill" data-bs-target="#history" type="button" role="tab" style="color: #b39b82; font-weight: 700;">
            <i class="bi bi-clock-history me-2"></i> Attendance History
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="manual-tab" data-bs-toggle="pill" data-bs-target="#manual" type="button" role="tab" style="color: #b39b82; font-weight: 700;">
            <i class="bi bi-journal-check me-2"></i> Manual Entry
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="materials-tab" data-bs-toggle="pill" data-bs-target="#materials" type="button" role="tab" style="color: #b39b82; font-weight: 700;">
            <i class="bi bi-folder-fill me-2"></i> Materials
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Tab 1: Students Roster -->
    <div class="tab-pane fade show active" id="students" role="tabpanel">
        <x-card title="Enrolled Students" icon="bi bi-people">
            <x-data-table :headers="['#', 'Student Name', 'Course / Year', 'Present', 'Absent', 'Rate']">
                @forelse($students as $i => $student)
                    @php
                        $total = $student->attendances->count();
                        $present = $student->attendances->whereIn('status', ['Present', 'Late'])->count();
                        $absent = $student->attendances->where('status', 'Absent')->count();
                        $rate = $total > 0 ? round(($present / $total) * 100) : 0;
                    @endphp
                    <tr>
                        <td data-label="#">{{ $i + 1 }}</td>
                        <td data-label="Student Name">
                            <div style="font-weight: 700; color: #f3e7cd;">{{ $student->name }}</div>
                            <div style="font-size: 0.75rem; color: #b39b82;">{{ $student->student_number }}</div>
                        </td>
                        <td data-label="Course / Year">{{ $student->course }} - Y{{ $student->year_level }}</td>
                        <td data-label="Present" style="color: #4ade80; font-weight: 600;">{{ $present }}</td>
                        <td data-label="Absent" style="color: #f87171; font-weight: 600;">{{ $absent }}</td>
                        <td data-label="Rate">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; width: 60px;">
                                    <div style="height: 100%; border-radius: 10px; width: {{ $rate }}%; background: {{ $rate >= 75 ? 'var(--gold)' : '#f87171' }}"></div>
                                </div>
                                <span style="font-weight: 700; font-size: 0.8rem; color: {{ $rate >= 75 ? 'var(--gold)' : '#f87171' }}">{{ $rate }}%</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center" style="padding: 40px; color: #b39b82;">No students enrolled yet.</td></tr>
                @endforelse
            </x-data-table>
        </x-card>
    </div>

    <!-- Tab 2: Attendance History -->
    <div class="tab-pane fade" id="history" role="tabpanel">
        <x-card title="Attendance History" icon="bi bi-clock-history">
            <x-data-table :headers="['Date', 'Student Name', 'Status', 'Recorded At']">
                @forelse($attendanceRecords as $record)
                    <tr>
                        <td data-label="Date" style="font-weight: 600;">{{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}</td>
                        <td data-label="Student Name">{{ $record->user->name ?? 'Unknown' }}</td>
                        <td data-label="Status">
                            @php $recordStatus = strtolower($record->status ?? ''); @endphp
                            @if($recordStatus === 'present') <x-badge type="present">Present</x-badge>
                            @elseif($recordStatus === 'late')  <x-badge type="late">Late</x-badge>
                            @else <x-badge type="absent">Absent</x-badge>
                            @endif
                        </td>
                        <td data-label="Recorded At" style="color:#b39b82; font-size: 0.8rem;">
                            {{ $record->created_at->format('h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center" style="padding: 40px; color: #b39b82;">No attendance records found for this class.</td></tr>
                @endforelse
            </x-data-table>
        </x-card>
    </div>

    <!-- Tab 3: Manual Entry -->
    <div class="tab-pane fade" id="manual" role="tabpanel">
        <x-card title="Manual Entry" icon="bi bi-journal-check">
            <form action="{{ route('teacher.classroom.attendance.store', $subject->code) }}" method="POST">
                @csrf
                <div style="margin-bottom: 24px; max-width: 300px;">
                    <label style="display: block; color: #b39b82; font-weight: 700; margin-bottom: 8px; font-size: 0.85rem; text-transform: uppercase;">Select Date</label>
                    <input type="date" name="date" value="{{ date('Y-m-d') }}" required class="form-control" style="background: rgba(0,0,0,0.2); border: 1px solid rgba(207,164,111,0.3); color: #f3e7cd; padding: 12px; border-radius: 12px;">
                </div>

                <x-data-table :headers="['Student', 'Mark Attendance']">
                    <style>
                        /* Custom Radio for Manual Attendance */
                        .att-radio-group { display: flex; gap: 10px; }
                        .att-radio { display: none; }
                        .att-label {
                            padding: 6px 14px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1);
                            cursor: pointer; font-size: 0.8rem; font-weight: 600; color: #b39b82; transition: all 0.2s;
                        }
                        .att-radio[value="Present"]:checked + .att-label { background: rgba(34,197,94,0.2); border-color: #4ade80; color: #4ade80; }
                        .att-radio[value="Absent"]:checked + .att-label { background: rgba(239,68,68,0.2); border-color: #f87171; color: #f87171; }
                        .att-radio[value="Late"]:checked + .att-label { background: rgba(245,158,11,0.2); border-color: #fbbf24; color: #fbbf24; }
                    </style>
                    @foreach($students as $student)
                    <tr>
                        <td data-label="Student">
                            <div style="font-weight: 700; color: #f3e7cd;">{{ $student->name }}</div>
                        </td>
                        <td data-label="Mark Attendance">
                            <div class="att-radio-group">
                                <input type="radio" name="attendance[{{ $student->id }}]" value="Present" id="p_{{ $student->id }}" class="att-radio" checked>
                                <label for="p_{{ $student->id }}" class="att-label">Present</label>
                                
                                <input type="radio" name="attendance[{{ $student->id }}]" value="Late" id="l_{{ $student->id }}" class="att-radio">
                                <label for="l_{{ $student->id }}" class="att-label">Late</label>

                                <input type="radio" name="attendance[{{ $student->id }}]" value="Absent" id="a_{{ $student->id }}" class="att-radio">
                                <label for="a_{{ $student->id }}" class="att-label">Absent</label>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </x-data-table>

                <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary" style="background: var(--gold); border: none; padding: 10px 24px; font-weight: 600; color: #fff;">
                        <i class="bi bi-save"></i> Save Attendance
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    <!-- Tab 4: Materials -->
    <div class="tab-pane fade" id="materials" role="tabpanel">
        <div class="row g-4">
            <div class="col-lg-8">
                <x-card title="Course Materials" icon="bi bi-folder2-open">
                    <div class="d-flex flex-column gap-3">
                        @forelse($subject->materials as $material)
                            <div style="background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); padding: 16px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width: 40px; height: 40px; border-radius: 10px; background: rgba(207,164,111,0.1); display: flex; align-items: center; justify-content: center; color: var(--gold); font-size: 1.2rem;">
                                        @if(in_array(strtolower($material->file_type), ['pdf']))
                                            <i class="bi bi-file-earmark-pdf-fill" style="color: #ef4444;"></i>
                                        @elseif(in_array(strtolower($material->file_type), ['doc', 'docx']))
                                            <i class="bi bi-file-earmark-word-fill" style="color: #3b82f6;"></i>
                                        @elseif(in_array(strtolower($material->file_type), ['xls', 'xlsx']))
                                            <i class="bi bi-file-earmark-excel-fill" style="color: #10b981;"></i>
                                        @else
                                            <i class="bi bi-file-earmark-text-fill"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div style="font-weight: 700; color: #f3e7cd;">{{ $material->title }}</div>
                                        <div style="font-size: 0.8rem; color: #b39b82;">{{ $material->original_filename }} &bull; {{ $material->file_size }} &bull; Uploaded {{ $material->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ Storage::url($material->file_path) }}" target="_blank" class="btn btn-sm btn-outline" style="border-color: rgba(255,255,255,0.1); color: #f3e7cd;">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <form action="{{ route('teacher.materials.destroy', [$subject->code, $material->id]) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this material?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline" style="border-color: rgba(239,68,68,0.2); color: #f87171;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="text-center p-4" style="color: #b39b82;">
                                <i class="bi bi-folder-x" style="font-size: 2rem; opacity: 0.5;"></i>
                                <div class="mt-2" style="font-weight: 600;">No materials uploaded yet</div>
                            </div>
                        @endforelse
                    </div>
                </x-card>
            </div>
            <div class="col-lg-4">
                <x-card title="Upload Material" icon="bi bi-cloud-upload">
                    <form action="{{ route('teacher.materials.store', $subject->code) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; color: #b39b82;">Title *</label>
                            <input type="text" name="title" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-weight: 600; color: #b39b82;">Description</label>
                            <textarea name="description" class="form-control" rows="2" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;"></textarea>
                        </div>
                        <div class="mb-4">
                            <label class="form-label" style="font-weight: 600; color: #b39b82;">File *</label>
                            <input type="file" name="file" class="form-control" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff;" required>
                            <div class="form-text" style="color: rgba(179,155,130,0.7); font-size: 0.75rem;">Max 10MB</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" style="background: var(--gold); border: none; font-weight: 600; color: #fff;">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </form>
                </x-card>
            </div>
        </div>
    </div>
</div>

<style>
    .nav-pills .nav-link.active, .nav-pills .show>.nav-link {
        background-color: rgba(207,164,111,0.15) !important;
        border: 1px solid rgba(207,164,111,0.3) !important;
        color: #f3e7cd !important;
    }
</style>

@endsection
