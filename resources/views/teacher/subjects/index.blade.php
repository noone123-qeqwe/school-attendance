@extends('layouts.app')
@section('page-title', 'My Subjects')

@section('content')
<style>
    /* Subjects table: keep year/sem readable and aligned */
    .tch-subjects-table thead th,
    .tch-subjects-table tbody td { vertical-align: middle; }
    .tch-subjects-table .tch-col-meta { width: 1%; white-space: nowrap; text-align: center; }
    .tch-level-pair {
        display: inline-flex;
        align-items: stretch;
        gap: 6px;
        justify-content: center;
    }
    .tch-level-chip {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-width: 52px;
        padding: 8px 10px;
        border-radius: 10px;
        border: 1px solid rgba(207,164,111,0.24);
        background: linear-gradient(180deg, rgba(207,164,111,0.08) 0%, rgba(56,30,22,0.14) 100%);
        line-height: 1.05;
    }
    .tch-level-chip--muted {
        border-color: rgba(183,128,100,0.18);
        background: linear-gradient(180deg, rgba(55,30,22,0.12) 0%, rgba(24,12,8,0.16) 100%);
    }
    .tch-level-chip-num {
        font-size: 1.05rem;
        font-weight: 800;
        color: #d6b67b;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
    .tch-level-chip--muted .tch-level-chip-num { color: #b39b82; }
    .tch-level-chip-lbl {
        font-size: 0.58rem;
        font-weight: 700;
        color: #b39b82;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-top: 4px;
    }
    @media (max-width: 768px) {
        .tch-table thead { display: none; }
        .tch-table tbody { display: block; }
        .tch-table tbody tr { 
            display: block; 
            border: 1px solid rgba(207,164,111,0.18);
            border-radius: 12px; 
            margin-bottom: 12px; 
            background: rgba(32,20,15,0.95); 
            box-shadow: 0 1px 8px rgba(0,0,0,.2);
            padding: 0;
        }
        
        .tch-table tbody td { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 10px 14px; 
            border-bottom: 1px solid rgba(207,164,111,0.12); 
            font-size: .82rem;
        }
        .tch-table tbody td:last-child { 
            border-bottom: none; 
        }
        .tch-table tbody td::before { 
            content: attr(data-label); 
            font-size: .7rem; 
            font-weight: 700; 
            color: #b39b82; 
            text-transform: uppercase; 
            letter-spacing: .5px; 
            margin-right: 10px; 
            flex-shrink: 0;
        }
        
        /* Hide some columns on mobile to reduce clutter */
        .mobile-hide {
            display: none;
        }
        
        /* Adjust level chips for mobile */
        .tch-level-pair {
            gap: 4px;
        }
        .tch-level-chip {
            min-width: 40px;
            padding: 6px 8px;
        }
        .tch-level-chip-num {
            font-size: 0.9rem;
        }
        .tch-level-chip-lbl {
            font-size: 0.55rem;
        }
        
        /* Mobile actions */
        .view-btn {
            padding: 6px 8px;
            font-size: 0.75rem;
        }
    }
</style>

@if(session('success'))
<div style="background:rgba(207,164,111,0.14);border:1px solid rgba(183,128,100,0.25);color:#e7d4b8;border-radius:12px;padding:12px 16px;font-size:.875rem;margin-bottom:20px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span>
</div>
@endif

<div class="tch-card">
    <div class="tch-card-head">
        <div class="tch-card-title">
            <div class="tch-card-icon" style="background:rgba(128,0,0,0.14);color:#800000;"><i class="bi bi-book-fill"></i></div>
            My Subjects
        </div>
        <a href="{{ route('teacher.subjects.create') }}" class="tch-btn tch-btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-plus-lg"></i> Add Subject
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="{{ route('teacher.subjects') }}" data-live-search style="padding:14px 22px;border-bottom:1px solid rgba(207,164,111,0.18);display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
        <input type="text" name="search" class="tch-input" placeholder="Code or name" value="{{ request('search') }}" style="width:220px;" autocomplete="off" oninput="window.liveSearchTimer && clearTimeout(window.liveSearchTimer); window.liveSearchTimer = setTimeout(() => this.form.submit(), 0);">
        <select name="year_level" class="tch-input">
            <option value="">All Years</option>
            @foreach([1,2,3,4] as $y)
            <option value="{{ $y }}" {{ request('year_level')==$y?'selected':'' }}>Year {{ $y }}</option>
            @endforeach
        </select>
        <select name="semester" class="tch-input">
            <option value="">All Semesters</option>
            <option value="1" {{ request('semester')=='1'?'selected':'' }}>1st Sem</option>
            <option value="2" {{ request('semester')=='2'?'selected':'' }}>2nd Sem</option>
        </select>
        <button type="submit" class="tch-btn tch-btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        @if(request()->hasAny(['search','year_level','semester']))
        <a href="{{ route('teacher.subjects') }}" class="tch-btn tch-btn-ghost">Clear</a>
        @endif
    </form>

    <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="tch-table tch-subjects-table">
            <thead>
                <tr><th>Code</th><th>Subject Name</th><th>Year / Sem</th><th>Days</th><th>Time</th><th>Units</th><th>Section</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr data-code="{{ $subject->code }}" data-name="{{ $subject->name }}">
                    <td data-label="Code" style="font-family:monospace;font-weight:700;color:#cfa46f;">{{ $subject->code }}</td>
                    <td data-label="Subject Name" style="font-weight:600;color:#f3e7cd;">{{ $subject->name }}</td>
                    <td data-label="Year / Sem" class="tch-col-meta">
                        <div class="tch-level-pair">
                            <div class="tch-level-chip" title="Year level">
                                <span class="tch-level-chip-num">{{ (int) $subject->year_level }}</span>
                                <span class="tch-level-chip-lbl">Year</span>
                            </div>
                            <div class="tch-level-chip tch-level-chip--muted" title="Semester">
                                <span class="tch-level-chip-num">{{ (int) $subject->semester }}</span>
                                <span class="tch-level-chip-lbl">Sem</span>
                            </div>
                        </div>
                    </td>
                    <td data-label="Days" style="font-weight:600;color:#d6b67b;">{{ $subject->days ?? 'â€”' }}</td>
                    <td data-label="Time" style="font-size:.82rem;color:#b39b82;">
                        @if($subject->start_time && $subject->end_time)
                            {{ \Carbon\Carbon::parse($subject->start_time)->format('h:i A') }} â€“ {{ \Carbon\Carbon::parse($subject->end_time)->format('h:i A') }}
                        @else â€”
                        @endif
                    </td>
                    <td data-label="Units" style="color:#b39b82;" class="mobile-hide">{{ $subject->units ?? 'â€”' }}</td>
                    <td data-label="Units" style="color:#b39b82;" class="mobile-hide">{{ $subject->units ?? '—' }}</td>
                    <td data-label="Section" style="color:#b39b82;" class="mobile-hide">{{ $subject->section ?? '—' }}</td>
                    <td data-label="Actions">
                        <div style="display:flex;gap:5px;">
                            <a href="{{ route('teacher.qr', $subject->code) }}" class="view-btn" style="color:#cfa46f;border-color:rgba(207,164,111,0.32);background:rgba(207,164,111,0.12);" title="Start QR Attendance">
                                <i class="bi bi-qr-code"></i>
                            </a>
                            <a href="{{ route('teacher.subjects.students', $subject->code) }}" class="view-btn" style="color:#60a5fa;border-color:rgba(96,165,250,0.32);background:rgba(96,165,250,0.12);" title="Students">
                                <i class="bi bi-people"></i>
                            </a>
                            <a href="{{ route('teacher.subjects.edit', $subject->id) }}" class="view-btn" style="color:#d6b67b;border-color:rgba(207,164,111,0.28);background:rgba(207,164,111,0.1);"><i class="bi bi-pencil-fill"></i></a>
                            <form action="{{ route('teacher.subjects.destroy', $subject->id) }}" method="POST" onsubmit="return confirm('Delete {{ addslashes($subject->name) }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="view-btn" style="color:#8a3b2e;border-color:rgba(138,59,46,0.25);background:rgba(138,59,46,0.12);"><i class="bi bi-trash3-fill"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8"><div class="empty-state"><i class="bi bi-book"></i><p>No subjects found.</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const debounce = (fn, delay = 450) => {
            let timer;
            return (...args) => {
                clearTimeout(timer);
                timer = setTimeout(() => fn(...args), delay);
            };
        };

        document.querySelectorAll('form[data-live-search] input[name="search"]').forEach(input => {
            const form = input.closest('form');
            if (!form) return;
            const submit = debounce(() => form.submit(), 450);
            input.addEventListener('input', submit);
        });
    });
</script>

@endsection
