@extends('layouts.app')
@section('portal-title', 'Classes & Schedule')
@section('page-title', 'Classes & Schedule')
@section('page-sub', 'Certificate of Registration (COR) & Unified Academic Timetable')

@section('content')
@php
    /** @var \App\Models\User $user */
    $user = $user ?? auth()->user();

    // ── ACADEMIC YEAR & SEMESTER MAPPINGS ──
    $yearLevelMap = [
        1 => 'First Year',
        2 => 'Second Year',
        3 => 'Third Year',
        4 => 'Fourth Year',
        5 => 'Fifth Year',
    ];
    $yearText = $yearLevelMap[(int) $user->year_level] ?? ('Year ' . ($user->year_level ?? '1'));

    $semesterMap = [
        1 => 'First Semester',
        2 => 'Second Semester',
        3 => 'Summer Term',
    ];
    $semesterText = $semesterMap[(int) $user->semester] ?? ('Semester ' . ($user->semester ?? '1'));

    $currentYear = (int) date('Y');
    // If current month is Jan-May (second sem), academic year is (Y-1) - Y; if Jun-Dec, Y - (Y+1)
    $academicYear = $currentYear . ' - ' . ($currentYear + 1);

    // ── COURSE FULL NAME EXPANSION ──
    $courseNames = [
        'BSCS'  => 'Bachelor of Science in Computer Science',
        'BSIT'  => 'Bachelor of Science in Information Technology',
        'BSIS'  => 'Bachelor of Science in Information Systems',
        'BSEMC' => 'Bachelor of Science in Entertainment and Multimedia Computing',
        'BSEd'  => 'Bachelor of Secondary Education',
        'BEEd'  => 'Bachelor of Elementary Education',
        'BSBA'  => 'Bachelor of Science in Business Administration',
        'BSCrim'=> 'Bachelor of Science in Criminology',
        'BSHM'  => 'Bachelor of Science in Hospitality Management',
        'BSTM'  => 'Bachelor of Science in Tourism Management',
        'BSN'   => 'Bachelor of Science in Nursing',
    ];
    $courseCode = $user->course ?? 'BSCS';
    $courseFull = $courseNames[$courseCode] ?? ($courseCode ?: 'Bachelor of Science in Computer Science');

    $studentNumber = $user->student_number ?? '2312215';
    $sectionDisplay = $user->section ?: 'BSCS 4A';

    // ── SCHEDULE DAY HELPER ──
    $todayName = now()->format('l'); // e.g. "Monday"

    $formatDaysAbbr = function (array $days): string {
        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $presentDays = array_values(array_intersect($order, $days));
        if (empty($presentDays)) return 'TBA';

        if ($presentDays === ['Tuesday', 'Thursday']) return 'TTH';
        if ($presentDays === ['Monday', 'Wednesday', 'Friday']) return 'MWF';
        if ($presentDays === ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) return 'M-F';
        if ($presentDays === ['Monday', 'Wednesday']) return 'MW';
        if ($presentDays === ['Tuesday', 'Wednesday', 'Thursday']) return 'TWTH';
        if ($presentDays === ['Saturday']) return 'SAT';
        if ($presentDays === ['Sunday']) return 'SUN';
        if ($presentDays === ['Monday']) return 'MON';
        if ($presentDays === ['Tuesday']) return 'TUE';
        if ($presentDays === ['Wednesday']) return 'WED';
        if ($presentDays === ['Thursday']) return 'THU';
        if ($presentDays === ['Friday']) return 'FRI';

        $short = [
            'Monday'=>'M', 'Tuesday'=>'T', 'Wednesday'=>'W',
            'Thursday'=>'TH', 'Friday'=>'F', 'Saturday'=>'SAT', 'Sunday'=>'SUN'
        ];
        return implode('', array_map(fn($d) => $short[$d] ?? $d, $presentDays));
    };

    // ── FLATTEN / GROUP SUBJECTS & SCHEDULES ──
    $groupedSchedules = [];
    $rowNumber = 1;

    foreach ($subjects as $subject) {
        $subjectSchedules = $subject->schedules ?? collect();
        $instructorName = $subject->instructorUser?->name ?? $subject->instructor ?? 'TBA';
        $units = (float) ($subject->units ?: 3.0);
        $section = $subject->section ?: ($user->section ?: '1 1423');

        if ($subjectSchedules->count() > 0) {
            // Group slots by time and room
            $slots = [];
            foreach ($subjectSchedules as $sched) {
                $key = ($sched->start_time ?? '') . '|' . ($sched->end_time ?? '') . '|' . ($sched->room ?? '');
                if (!isset($slots[$key])) {
                    $slots[$key] = [
                        'days'       => [],
                        'start_time' => $sched->start_time,
                        'end_time'   => $sched->end_time,
                        'room'       => $sched->room ?? null,
                    ];
                }
                $slots[$key]['days'][] = $sched->day;
            }

            $slotIndex = 0;
            foreach ($slots as $slot) {
                $daysAbbr = $formatDaysAbbr($slot['days']);
                $isToday = in_array($todayName, $slot['days']);
                $classNum = str_pad((string) ($subject->id * 10 + $slotIndex), 2, '0', STR_PAD_LEFT);

                $groupedSchedules[] = (object) [
                    'row_num'         => $rowNumber++,
                    'subject_id'      => $subject->id,
                    'section'         => $section,
                    'code'            => $subject->code,
                    'name'            => $subject->name,
                    'class_number'    => $classNum,
                    'units'           => $slotIndex === 0 ? number_format($units, 1) : '—',
                    'raw_units'       => $slotIndex === 0 ? $units : 0,
                    'start_time'      => $slot['start_time'],
                    'end_time'        => $slot['end_time'],
                    'days'            => $daysAbbr,
                    'raw_days'        => $slot['days'],
                    'is_today'        => $isToday,
                    'room'            => !empty($slot['room']) ? $slot['room'] : 'TBA',
                    'teacher'         => $instructorName,
                ];
                $slotIndex++;
            }
        } else {
            // Subject without specified schedule
            $groupedSchedules[] = (object) [
                'row_num'         => $rowNumber++,
                'subject_id'      => $subject->id,
                'section'         => $section,
                'code'            => $subject->code,
                'name'            => $subject->name,
                'class_number'    => str_pad((string) $subject->id, 2, '0', STR_PAD_LEFT),
                'units'           => number_format($units, 1),
                'raw_units'       => $units,
                'start_time'      => null,
                'end_time'        => null,
                'days'            => 'TBA',
                'raw_days'        => [],
                'is_today'        => false,
                'room'            => 'TBA',
                'teacher'         => $instructorName,
            ];
        }
    }

    $totalUnits = $subjects->sum(fn($s) => (float) ($s->units ?: 3.0));
    $todayClassesCount = collect($groupedSchedules)->where('is_today', true)->count();
@endphp

<style>
/* ── CERTIFICATE OF REGISTRATION (COR) DIGITAL THEME ── */
:root {
    --cor-gold: #cfa46f;
    --cor-gold-bright: #dfb784;
    --cor-gold-amber: #ffd166;
    --cor-maroon: #800000;
    --cor-maroon-dark: #4a0000;
    --cor-paper-bg: #140b08;
    --cor-card-bg: rgba(26, 16, 12, 0.94);
    --cor-border-gold: rgba(207, 164, 111, 0.38);
    --cor-border-subtle: rgba(207, 164, 111, 0.18);
    --cor-text-main: #f8e7d3;
    --cor-text-muted: #b39b82;
}

.cor-page-wrapper {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 90px;
}

/* Page action header banner */
.cor-actions-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 22px;
}

.cor-page-title-group h1 {
    font-size: 1.65rem;
    font-weight: 800;
    color: var(--cor-text-main);
    letter-spacing: -0.3px;
    margin: 0 0 4px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.cor-page-title-group p {
    font-size: 0.88rem;
    color: var(--cor-text-muted);
    margin: 0;
}

.cor-btn-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.cor-btn-action {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: 700;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
    text-decoration: none;
    border: 1px solid transparent;
}

.cor-btn-pdf {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.22), rgba(184, 134, 56, 0.12));
    border-color: rgba(207, 164, 111, 0.45);
    color: var(--cor-gold-bright);
}

.cor-btn-pdf:hover {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.36), rgba(184, 134, 56, 0.22));
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.3);
}

.cor-btn-print {
    background: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.14);
    color: var(--cor-text-main);
}

.cor-btn-print:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    transform: translateY(-1px);
}

/* ── THE OFFICIAL COR SHEET CARD ── */
.cor-sheet {
    background: var(--cor-card-bg);
    border: 2px solid var(--cor-border-gold);
    border-radius: 20px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    position: relative;
    padding: 36px;
    overflow: hidden;
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
}

/* Subtle academic watermark overlay */
.cor-sheet::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 440px;
    height: 440px;
    background: radial-gradient(circle, rgba(207, 164, 111, 0.04) 0%, transparent 70%);
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 0;
}

/* Decorative inner diploma frame line */
.cor-sheet::after {
    content: '';
    position: absolute;
    inset: 10px;
    border: 1px dashed rgba(207, 164, 111, 0.2);
    border-radius: 12px;
    pointer-events: none;
    z-index: 1;
}

.cor-content-relative {
    position: relative;
    z-index: 2;
}

/* ── INSTITUTIONAL HEADER ── */
.cor-header-block {
    text-align: center;
    padding-bottom: 22px;
    margin-bottom: 24px;
    border-bottom: 2px solid var(--cor-border-gold);
}

.cor-seal-wrapper {
    width: 68px;
    height: 68px;
    margin: 0 auto 12px;
    border-radius: 50%;
    padding: 3px;
    background: linear-gradient(135deg, var(--cor-gold), #800000);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
}

.cor-seal-img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    background: #fff;
}

.cor-college-name {
    font-size: 1.55rem;
    font-weight: 900;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #fcecd7;
    margin: 0 0 2px;
    font-family: serif, 'Times New Roman', Georgia;
}

.cor-college-city {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--cor-gold);
    letter-spacing: 1px;
    margin: 0 0 8px;
}

.cor-doc-title {
    font-size: 1.18rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--cor-gold-amber);
    margin: 0 0 4px;
}

.cor-doc-sem {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--cor-text-main);
    margin: 0;
}

.cor-status-badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(16, 185, 129, 0.12);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #34d399;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    padding: 4px 12px;
    border-radius: 99px;
    margin-top: 10px;
}

/* ── STUDENT INFORMATION DEMOGRAPHIC GRID (COR Box) ── */
.cor-student-info-grid {
    width: 100%;
    border: 1.5px solid var(--cor-border-gold);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 24px;
    background: rgba(0, 0, 0, 0.25);
}

.cor-info-row {
    display: grid;
    grid-template-columns: 180px 1fr 150px 1fr;
    border-bottom: 1px solid var(--cor-border-subtle);
}

.cor-info-row:last-child {
    border-bottom: none;
}

.cor-info-row.two-col {
    grid-template-columns: 180px 1fr;
}

.cor-info-label {
    padding: 12px 16px;
    background: rgba(207, 164, 111, 0.08);
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    color: var(--cor-gold);
    border-right: 1px solid var(--cor-border-subtle);
    display: flex;
    align-items: center;
}

.cor-info-value {
    padding: 12px 18px;
    font-size: 0.94rem;
    font-weight: 700;
    color: var(--cor-text-main);
    display: flex;
    align-items: center;
    border-right: 1px solid var(--cor-border-subtle);
    word-break: break-word;
}

.cor-info-row .cor-info-value:last-child {
    border-right: none;
}

.cor-student-num-val {
    font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
    color: var(--cor-gold-amber);
    font-size: 1.05rem;
    letter-spacing: 0.5px;
}

/* ── CONTROLS & FILTER TOOLBAR (Screen Only) ── */
.cor-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 18px;
}

.cor-search-box {
    position: relative;
    flex: 1;
    min-width: 240px;
    max-width: 380px;
}

.cor-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--cor-gold);
    font-size: 0.9rem;
    pointer-events: none;
}

.cor-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    background: rgba(0, 0, 0, 0.35);
    border: 1px solid var(--cor-border-gold);
    border-radius: 10px;
    color: var(--cor-text-main);
    font-size: 0.85rem;
    outline: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.cor-search-input:focus {
    border-color: var(--cor-gold-bright);
    box-shadow: 0 0 0 3px rgba(207, 164, 111, 0.2);
}

.cor-day-pills-rail {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow-x: auto;
    scrollbar-width: none;
    padding: 2px;
}

.cor-day-pills-rail::-webkit-scrollbar {
    display: none;
}

.cor-day-btn {
    padding: 6px 14px;
    border-radius: 99px;
    font-size: 0.78rem;
    font-weight: 700;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.04);
    color: var(--cor-text-muted);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
}

.cor-day-btn:hover {
    background: rgba(207, 164, 111, 0.12);
    color: var(--cor-text-main);
    border-color: var(--cor-border-gold);
}

.cor-day-btn.active {
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.32), rgba(184, 134, 56, 0.18));
    border-color: var(--cor-gold);
    color: var(--cor-gold-amber);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.cor-view-toggle-btns {
    display: flex;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--cor-border-gold);
    border-radius: 8px;
    padding: 2px;
}

.cor-toggle-btn {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    border: none;
    background: transparent;
    color: var(--cor-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.cor-toggle-btn.active {
    background: var(--cor-gold);
    color: #120804;
}

/* ── SCHEDULE TABLE (Official COR Format) ── */
.cor-table-scroll-wrapper {
    width: 100%;
    overflow-x: auto;
    border: 1.5px solid var(--cor-border-gold);
    border-radius: 12px;
    background: rgba(0, 0, 0, 0.2);
    margin-bottom: 24px;
    -webkit-overflow-scrolling: touch;
}

.cor-table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    font-size: 0.88rem;
    color: var(--cor-text-main);
}

.cor-table thead th {
    background: rgba(207, 164, 111, 0.12);
    color: var(--cor-gold-bright);
    font-size: 0.74rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    padding: 14px 16px;
    border-bottom: 2px solid var(--cor-border-gold);
    border-right: 1px solid var(--cor-border-subtle);
    white-space: nowrap;
    text-align: left;
}

.cor-table thead th:last-child {
    border-right: none;
}

.cor-table thead th.center,
.cor-table tbody td.center {
    text-align: center;
}

.cor-table tbody tr {
    border-bottom: 1px solid var(--cor-border-subtle);
    transition: background-color 0.15s ease;
}

.cor-table tbody tr:hover {
    background-color: rgba(207, 164, 111, 0.08);
}

.cor-table tbody tr.is-today {
    background-color: rgba(207, 164, 111, 0.07);
}

.cor-table tbody td {
    padding: 13px 16px;
    border-right: 1px solid var(--cor-border-subtle);
    vertical-align: middle;
}

.cor-table tbody td:last-child {
    border-right: none;
}

/* Specific Table Column Styling */
.cor-code-badge {
    font-family: 'JetBrains Mono', monospace;
    font-weight: 800;
    font-size: 0.82rem;
    color: var(--cor-gold-amber);
    background: rgba(207, 164, 111, 0.12);
    border: 1px solid rgba(207, 164, 111, 0.25);
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-block;
}

.cor-subject-name {
    font-weight: 600;
    color: var(--cor-text-main);
    font-size: 0.85rem;
    margin-top: 3px;
    line-height: 1.3;
}

.cor-units-cell {
    font-weight: 800;
    color: var(--cor-text-main);
    font-size: 0.92rem;
}

.cor-time-text {
    font-size: 0.82rem;
    font-weight: 600;
    color: #e5e5e5;
    white-space: nowrap;
}

.cor-day-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: rgba(255, 209, 102, 0.14);
    border: 1px solid rgba(255, 209, 102, 0.3);
    color: var(--cor-gold-amber);
}

.cor-room-badge {
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.82rem;
    font-weight: 700;
    color: #38bdf8;
    background: rgba(14, 165, 233, 0.12);
    border: 1px solid rgba(14, 165, 233, 0.25);
    padding: 3px 9px;
    border-radius: 6px;
    display: inline-block;
}

.cor-teacher-name {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--cor-text-main);
}

.cor-today-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.68rem;
    font-weight: 800;
    color: #10b981;
    background: rgba(16, 185, 129, 0.14);
    border: 1px solid rgba(16, 185, 129, 0.3);
    padding: 2px 6px;
    border-radius: 4px;
    margin-left: 6px;
    text-transform: uppercase;
}

/* Table Footer Row for Total Units */
.cor-table tfoot td {
    background: rgba(207, 164, 111, 0.12);
    padding: 14px 16px;
    border-top: 2px solid var(--cor-border-gold);
    border-right: 1px solid var(--cor-border-subtle);
    font-weight: 800;
    color: var(--cor-gold-amber);
    vertical-align: middle;
}

.cor-table tfoot td:last-child {
    border-right: none;
}

.cor-total-units-label {
    text-align: right;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--cor-gold-bright);
}

.cor-total-units-val {
    font-size: 1.05rem;
    font-weight: 900;
    color: var(--cor-gold-amber);
    font-family: 'JetBrains Mono', monospace;
}

/* ── MOBILE CARD LAYOUT (Alternative to Table) ── */
.cor-mobile-cards-view {
    display: none;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 24px;
}

.cor-card-item {
    background: rgba(0, 0, 0, 0.3);
    border: 1.5px solid var(--cor-border-subtle);
    border-radius: 12px;
    padding: 16px;
    transition: border-color 0.2s;
    position: relative;
}

.cor-card-item.is-today {
    border-color: rgba(207, 164, 111, 0.5);
    background: linear-gradient(135deg, rgba(207, 164, 111, 0.08), rgba(0, 0, 0, 0.35));
}

.cor-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 12px;
    border-bottom: 1px solid var(--cor-border-subtle);
    padding-bottom: 10px;
}

.cor-card-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 14px;
    font-size: 0.82rem;
}

.cor-card-field-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    font-weight: 700;
    color: var(--cor-gold);
    margin-bottom: 2px;
}

.cor-card-field-val {
    font-weight: 600;
    color: var(--cor-text-main);
}

/* ── REGISTRATION CERTIFICATION & FOOTER SECTION ── */
.cor-footer-section {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 24px;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1.5px solid var(--cor-border-gold);
}

.cor-assessment-box {
    border: 1.5px solid var(--cor-border-subtle);
    border-radius: 10px;
    overflow: hidden;
    background: rgba(0, 0, 0, 0.25);
}

.cor-assessment-row {
    display: grid;
    grid-template-columns: 170px 1fr;
    border-bottom: 1px solid var(--cor-border-subtle);
    font-size: 0.85rem;
}

.cor-assessment-row:last-child {
    border-bottom: none;
}

.cor-assessment-label {
    padding: 10px 14px;
    background: rgba(207, 164, 111, 0.06);
    color: var(--cor-gold);
    font-weight: 700;
    border-right: 1px solid var(--cor-border-subtle);
}

.cor-assessment-val {
    padding: 10px 14px;
    color: var(--cor-text-main);
    font-weight: 600;
}

.cor-signature-box {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-end;
    padding: 10px;
}

.cor-signature-line {
    width: 220px;
    height: 1px;
    background: var(--cor-border-gold);
    margin: 0 auto 8px;
}

.cor-registrar-name {
    font-size: 0.98rem;
    font-weight: 800;
    color: var(--cor-text-main);
    letter-spacing: 0.5px;
    margin: 0 0 2px;
}

.cor-registrar-title {
    font-size: 0.8rem;
    font-weight: 700;
    color: var(--cor-gold);
    text-transform: uppercase;
    letter-spacing: 1px;
    margin: 0 0 4px;
}

.cor-registrar-dept {
    font-size: 0.72rem;
    color: var(--cor-text-muted);
    margin: 0;
}

/* ── EMPTY STATE ── */
.cor-empty-state {
    text-align: center;
    padding: 48px 20px;
    color: var(--cor-text-muted);
}

.cor-empty-icon {
    font-size: 2.8rem;
    color: var(--cor-gold);
    opacity: 0.4;
    margin-bottom: 12px;
}

/* ── RESPONSIVE STYLES ── */
@media (max-width: 991px) {
    .cor-sheet {
        padding: 24px;
    }
    .cor-student-info-grid .cor-info-row {
        grid-template-columns: 140px 1fr;
    }
    .cor-student-info-grid .cor-info-row.four-col {
        grid-template-columns: 140px 1fr;
    }
    .cor-footer-section {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

@media (max-width: 767px) {
    .cor-page-wrapper {
        padding-bottom: 110px;
    }
    .cor-sheet {
        padding: 18px 14px;
        border-radius: 16px;
    }
    .cor-sheet::after {
        display: none;
    }
    .cor-college-name {
        font-size: 1.25rem;
    }
    .cor-doc-title {
        font-size: 1.02rem;
    }
    .cor-doc-sem {
        font-size: 0.85rem;
    }
    .cor-actions-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    .cor-btn-group {
        width: 100%;
    }
    .cor-btn-action {
        flex: 1;
        justify-content: center;
    }

    .cor-student-info-grid .cor-info-row {
        grid-template-columns: 1fr;
    }
    .cor-info-label {
        border-right: none;
        border-bottom: 1px solid var(--cor-border-subtle);
        padding: 8px 12px;
    }
    .cor-info-value {
        border-right: none;
        padding: 10px 12px;
    }

    /* Mobile toggle defaults */
    .cor-table-scroll-wrapper {
        display: none;
    }
    .cor-mobile-cards-view {
        display: flex;
    }
    .cor-table-scroll-wrapper.force-show {
        display: block;
    }
    .cor-mobile-cards-view.force-hide {
        display: none;
    }
}

/* ── PRINT MEDIA STYLES (Clean Paper-Authentic COR) ── */
@media print {
    @page {
        size: A4 portrait;
        margin: 12mm 10mm;
    }
    body {
        background: #ffffff !important;
        color: #000000 !important;
        font-family: 'Times New Roman', serif !important;
    }
    .sidebar,
    .top-header,
    .mobile-bottom-nav,
    .cor-actions-bar,
    .cor-toolbar,
    .mobile-back-btn,
    .no-print {
        display: none !important;
    }
    .cor-page-wrapper {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .cor-sheet {
        background: #ffffff !important;
        color: #000000 !important;
        border: 2px solid #000000 !important;
        box-shadow: none !important;
        padding: 16px !important;
        border-radius: 0 !important;
    }
    .cor-sheet::before,
    .cor-sheet::after {
        display: none !important;
    }
    .cor-header-block {
        border-bottom: 2px solid #000000 !important;
    }
    .cor-college-name,
    .cor-doc-title,
    .cor-college-city,
    .cor-doc-sem {
        color: #000000 !important;
    }
    .cor-status-badge-chip {
        border-color: #000000 !important;
        color: #000000 !important;
        background: transparent !important;
    }
    .cor-student-info-grid {
        border-color: #000000 !important;
        background: transparent !important;
    }
    .cor-info-row {
        border-bottom-color: #000000 !important;
    }
    .cor-info-label,
    .cor-info-value {
        color: #000000 !important;
        border-right-color: #000000 !important;
        background: transparent !important;
    }
    .cor-table-scroll-wrapper {
        display: block !important;
        border-color: #000000 !important;
        background: transparent !important;
    }
    .cor-mobile-cards-view {
        display: none !important;
    }
    .cor-table {
        min-width: 100% !important;
        color: #000000 !important;
    }
    .cor-table thead th {
        background: #f4f4f4 !important;
        color: #000000 !important;
        border-bottom-color: #000000 !important;
        border-right-color: #000000 !important;
    }
    .cor-table tbody tr {
        border-bottom-color: #000000 !important;
    }
    .cor-table tbody td {
        color: #000000 !important;
        border-right-color: #000000 !important;
    }
    .cor-code-badge,
    .cor-day-badge,
    .cor-room-badge {
        background: transparent !important;
        border: 1px solid #000000 !important;
        color: #000000 !important;
    }
    .cor-today-indicator {
        display: none !important;
    }
    .cor-table tfoot td {
        background: #f4f4f4 !important;
        color: #000000 !important;
        border-top-color: #000000 !important;
    }
    .cor-footer-section {
        border-top-color: #000000 !important;
    }
    .cor-assessment-box {
        border-color: #000000 !important;
    }
    .cor-assessment-label,
    .cor-assessment-val {
        color: #000000 !important;
        border-right-color: #000000 !important;
    }
    .cor-registrar-name,
    .cor-registrar-title,
    .cor-registrar-dept {
        color: #000000 !important;
    }
    .cor-signature-line {
        background: #000000 !important;
    }
}
</style>

<div class="container-fluid p-3 p-md-4 cor-page-wrapper">

    <!-- Screen Action Header -->
    <div class="cor-actions-bar no-print">
        <div class="cor-page-title-group">
            <h1>
                <i class="bi bi-file-earmark-text text-gold"></i>
                Classes & Schedule
            </h1>
            <p>Official Certificate of Registration (COR) & Academic Timetable</p>
        </div>

        <div class="cor-btn-group">
            <a href="{{ route('student.classes.pdf') }}" class="cor-btn-action cor-btn-pdf" id="downloadPdfBtn" title="Download official COR PDF document">
                <i class="bi bi-file-earmark-pdf-fill"></i>
                <span>Download PDF</span>
            </a>
            <button type="button" class="cor-btn-action cor-btn-print" onclick="window.print()" id="printCorBtn" title="Print Certificate of Registration">
                <i class="bi bi-printer-fill"></i>
                <span>Print Official COR</span>
            </button>
        </div>
    </div>

    <!-- ── THE CERTIFICATE OF REGISTRATION (COR) SHEET ── -->
    <div class="cor-sheet" id="corCertificateSheet">
        <div class="cor-content-relative">

            <!-- 1. Institutional Header Block -->
            <div class="cor-header-block">
                <div class="cor-seal-wrapper">
                    <img src="{{ asset('images/logo.png') }}" alt="Osmeña Colleges Seal" class="cor-seal-img" onerror="this.src='/favicon.ico';">
                </div>
                <div class="cor-college-name">Osmeña Colleges</div>
                <div class="cor-college-city">Masbate City, Philippines</div>
                <div class="cor-doc-title">Certificate of Registration ( COR )</div>
                <div class="cor-doc-sem">{{ $semesterText }} {{ $academicYear }}</div>
                <div>
                    <span class="cor-status-badge-chip">
                        <i class="bi bi-patch-check-fill"></i> OFFICIALLY ENROLLED • REGULAR
                    </span>
                </div>
            </div>

            <!-- 2. Student Demographic Profile (COR Table Header) -->
            <div class="cor-student-info-grid">
                <!-- Row 1 -->
                <div class="cor-info-row four-col">
                    <div class="cor-info-label">Student Number :</div>
                    <div class="cor-info-value cor-student-num-val">{{ $studentNumber }}</div>
                    <div class="cor-info-label">Year Level :</div>
                    <div class="cor-info-value">{{ $yearText }}</div>
                </div>

                <!-- Row 2 -->
                <div class="cor-info-row two-col">
                    <div class="cor-info-label">Course / Program :</div>
                    <div class="cor-info-value">{{ $courseFull }} ({{ $courseCode }})</div>
                </div>

                <!-- Row 3 -->
                <div class="cor-info-row four-col">
                    <div class="cor-info-label">Student Name :</div>
                    <div class="cor-info-value">{{ $user->name }}</div>
                    <div class="cor-info-label">Section :</div>
                    <div class="cor-info-value">
                        <span class="badge" style="background:rgba(207,164,111,0.18); border:1px solid rgba(207,164,111,0.4); color:var(--cor-gold-bright); font-weight:700;">
                            {{ $sectionDisplay }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Screen Controls Toolbar (Search, Filter, Mobile View Toggle) -->
            <div class="cor-toolbar no-print">
                <div class="cor-search-box">
                    <i class="bi bi-search cor-search-icon"></i>
                    <input type="text" 
                           id="scheduleSearchInput" 
                           class="cor-search-input" 
                           placeholder="Search subject, code, instructor, room..." 
                           aria-label="Filter schedule records">
                </div>

                <div class="cor-day-pills-rail" id="dayFilterRail">
                    <button type="button" class="cor-day-btn active" data-day="all">All Days ({{ count($groupedSchedules) }})</button>
                    @if($todayClassesCount > 0)
                        <button type="button" class="cor-day-btn" data-day="today">
                            <i class="bi bi-stars text-warning me-1"></i> Today ({{ $todayClassesCount }})
                        </button>
                    @endif
                    <button type="button" class="cor-day-btn" data-day="Monday">Mon</button>
                    <button type="button" class="cor-day-btn" data-day="Tuesday">Tue</button>
                    <button type="button" class="cor-day-btn" data-day="Wednesday">Wed</button>
                    <button type="button" class="cor-day-btn" data-day="Thursday">Thu</button>
                    <button type="button" class="cor-day-btn" data-day="Friday">Fri</button>
                    <button type="button" class="cor-day-btn" data-day="Saturday">Sat</button>
                </div>

                <!-- Mobile view switcher (Visible only on small screens) -->
                <div class="cor-view-toggle-btns d-md-none">
                    <button type="button" class="cor-toggle-btn active" id="viewCardsBtn" onclick="setMobileView('cards')" title="Cards layout">
                        <i class="bi bi-grid-fill"></i> Cards
                    </button>
                    <button type="button" class="cor-toggle-btn" id="viewTableBtn" onclick="setMobileView('table')" title="Official table layout">
                        <i class="bi bi-table"></i> Table
                    </button>
                </div>
            </div>

            <!-- 4. Class Schedule Table (Official Full-Width COR Layout) -->
            <div class="cor-table-scroll-wrapper" id="corTableScrollWrapper">
                <table class="cor-table" id="corScheduleTable">
                    <thead>
                        <tr>
                            <th style="width: 10%;">Section</th>
                            <th style="width: 28%;">Subject Code & Description</th>
                            <th class="center" style="width: 10%;">Class Number</th>
                            <th class="center" style="width: 8%;">Units</th>
                            <th style="width: 16%;">Time</th>
                            <th class="center" style="width: 8%;">Day</th>
                            <th class="center" style="width: 8%;">Room</th>
                            <th style="width: 16%;">Teacher's Name</th>
                        </tr>
                    </thead>
                    <tbody id="corTableBody">
                        @forelse($groupedSchedules as $sched)
                            <tr class="cor-schedule-row {{ $sched->is_today ? 'is-today' : '' }}" 
                                data-days="{{ implode(',', $sched->raw_days) }}" 
                                data-is-today="{{ $sched->is_today ? '1' : '0' }}">
                                <!-- Section -->
                                <td>
                                    <strong>{{ $sched->section }}</strong>
                                </td>

                                <!-- Subject Code & Name -->
                                <td>
                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                        <span class="cor-code-badge">{{ $sched->code }}</span>
                                        @if($sched->is_today)
                                            <span class="cor-today-indicator no-print"><i class="bi bi-dot"></i> Today</span>
                                        @endif
                                    </div>
                                    <div class="cor-subject-name">{{ $sched->name }}</div>
                                </td>

                                <!-- Class Number -->
                                <td class="center font-monospace" style="color: var(--cor-text-muted);">
                                    {{ $sched->class_number }}
                                </td>

                                <!-- Units -->
                                <td class="center cor-units-cell">
                                    {{ $sched->units }}
                                </td>

                                <!-- Time -->
                                <td>
                                    @if($sched->start_time && $sched->end_time)
                                        <div class="cor-time-text">
                                            <i class="bi bi-clock me-1 text-gold no-print"></i>
                                            {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                        </div>
                                    @else
                                        <span class="text-muted">TBA</span>
                                    @endif
                                </td>

                                <!-- Day -->
                                <td class="center">
                                    <span class="cor-day-badge">{{ $sched->days }}</span>
                                </td>

                                <!-- Room -->
                                <td class="center">
                                    @if($sched->room && $sched->room !== 'TBA')
                                        <span class="cor-room-badge">{{ $sched->room }}</span>
                                    @else
                                        <span class="text-muted">TBA</span>
                                    @endif
                                </td>

                                <!-- Teacher's Name -->
                                <td>
                                    <div class="cor-teacher-name">
                                        <i class="bi bi-person-fill text-gold me-1 no-print"></i>
                                        {{ $sched->teacher }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8">
                                    <div class="cor-empty-state">
                                        <i class="bi bi-journal-x cor-empty-icon"></i>
                                        <h5>No Enrolled Subjects Found</h5>
                                        <p class="mb-0">You are currently not registered for any classes this semester.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="cor-total-units-label">
                                Total Units :
                            </td>
                            <td class="center cor-total-units-val">
                                {{ number_format($totalUnits, 1) }}
                            </td>
                            <td colspan="4" style="color: var(--cor-text-muted); font-size: 0.8rem;">
                                {{ $subjects->count() }} Registered Subject{{ $subjects->count() === 1 ? '' : 's' }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- 5. Mobile Responsive Cards View (Alternative to scrollable table on mobile) -->
            <div class="cor-mobile-cards-view" id="corMobileCardsView">
                @forelse($groupedSchedules as $sched)
                    <div class="cor-card-item {{ $sched->is_today ? 'is-today' : '' }}" 
                         data-days="{{ implode(',', $sched->raw_days) }}" 
                         data-is-today="{{ $sched->is_today ? '1' : '0' }}">
                        <div class="cor-card-header">
                            <div>
                                <div class="d-flex align-items-center gap-1 flex-wrap">
                                    <span class="cor-code-badge">{{ $sched->code }}</span>
                                    <span class="badge" style="background:rgba(207,164,111,0.15); color:var(--cor-gold); font-size:0.72rem;">Sec {{ $sched->section }}</span>
                                    @if($sched->is_today)
                                        <span class="cor-today-indicator"><i class="bi bi-dot"></i> Today</span>
                                    @endif
                                </div>
                                <div class="cor-subject-name" style="font-size: 0.98rem; font-weight: 700; margin-top: 4px;">
                                    {{ $sched->name }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="badge" style="background: rgba(207,164,111,0.22); color: var(--cor-gold-bright); font-size: 0.82rem; font-weight: 800;">
                                    {{ $sched->units !== '—' ? $sched->units . ' Units' : 'Lab/Lec' }}
                                </div>
                            </div>
                        </div>

                        <div class="cor-card-grid">
                            <div>
                                <div class="cor-card-field-label"><i class="bi bi-clock me-1"></i> Time</div>
                                <div class="cor-card-field-val">
                                    @if($sched->start_time && $sched->end_time)
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                                    @else
                                        TBA
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="cor-card-field-label"><i class="bi bi-calendar-event me-1"></i> Day</div>
                                <div class="cor-card-field-val">
                                    <span class="cor-day-badge">{{ $sched->days }}</span>
                                </div>
                            </div>

                            <div>
                                <div class="cor-card-field-label"><i class="bi bi-geo-alt me-1"></i> Room</div>
                                <div class="cor-card-field-val">
                                    @if($sched->room && $sched->room !== 'TBA')
                                        <span class="cor-room-badge">{{ $sched->room }}</span>
                                    @else
                                        <span class="text-muted">TBA</span>
                                    @endif
                                </div>
                            </div>

                            <div>
                                <div class="cor-card-field-label"><i class="bi bi-person me-1"></i> Instructor</div>
                                <div class="cor-card-field-val text-truncate" title="{{ $sched->teacher }}">
                                    {{ $sched->teacher }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="cor-empty-state">
                        <i class="bi bi-journal-x cor-empty-icon"></i>
                        <h5>No Enrolled Subjects</h5>
                        <p class="mb-0">You are currently not registered for any classes this semester.</p>
                    </div>
                @endforelse
            </div>

            <!-- 6. Official Academic Verification & Certification Block (COR Footer) -->
            <div class="cor-footer-section">
                <!-- Assessment & Scholarship Box (Matches Physical COR) -->
                <div class="cor-assessment-box">
                    <div class="cor-assessment-row">
                        <div class="cor-assessment-label">Total Amount Paid :</div>
                        <div class="cor-assessment-val" style="font-family: 'JetBrains Mono', monospace; color: #34d399; font-weight: 800;">
                            ₱ 0.00 (Fully Covered)
                        </div>
                    </div>
                    <div class="cor-assessment-row">
                        <div class="cor-assessment-label">Scholarship / Subsidy :</div>
                        <div class="cor-assessment-val">
                            TES Batch 10 • CHED UniFAST Free Higher Education (RA 10931)
                        </div>
                    </div>
                    <div class="cor-assessment-row">
                        <div class="cor-assessment-label">Total Academic Load :</div>
                        <div class="cor-assessment-val" style="color: var(--cor-gold-amber);">
                            {{ $subjects->count() }} Subjects • {{ number_format($totalUnits, 1) }} Total Units Validated
                        </div>
                    </div>
                </div>

                <!-- Official Registrar Seal Block -->
                <div class="cor-signature-box">
                    <div class="mb-2" style="font-family: 'Times New Roman', serif; font-style: italic; color: var(--cor-gold); font-size: 1.15rem;">
                        Susan I. Aguilar
                    </div>
                    <div class="cor-signature-line"></div>
                    <div class="cor-registrar-name">SUSAN I. AGUILAR</div>
                    <div class="cor-registrar-title">College Registrar</div>
                    <div class="cor-registrar-dept">Osmeña Colleges • Office of the Registrar</div>
                </div>
            </div>

        </div>
    </div>

</div>

<script @cspNonce>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('scheduleSearchInput');
    const dayButtons = document.querySelectorAll('.cor-day-btn');
    const tableRows = document.querySelectorAll('.cor-schedule-row');
    const cards = document.querySelectorAll('.cor-card-item');

    let activeDay = 'all';

    function applyFilter() {
        const query = (searchInput ? searchInput.value : '').trim().toLowerCase();

        // 1. Filter Table Rows
        tableRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const daysAttr = row.getAttribute('data-days') || '';
            const isTodayAttr = row.getAttribute('data-is-today') === '1';

            let matchesDay = true;
            if (activeDay === 'all') {
                matchesDay = true;
            } else if (activeDay === 'today') {
                matchesDay = isTodayAttr;
            } else {
                matchesDay = daysAttr.split(',').includes(activeDay);
            }

            const matchesSearch = query === '' || rowText.includes(query);

            if (matchesDay && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });

        // 2. Filter Mobile Cards
        cards.forEach(card => {
            const cardText = card.textContent.toLowerCase();
            const daysAttr = card.getAttribute('data-days') || '';
            const isTodayAttr = card.getAttribute('data-is-today') === '1';

            let matchesDay = true;
            if (activeDay === 'all') {
                matchesDay = true;
            } else if (activeDay === 'today') {
                matchesDay = isTodayAttr;
            } else {
                matchesDay = daysAttr.split(',').includes(activeDay);
            }

            const matchesSearch = query === '' || cardText.includes(query);

            if (matchesDay && matchesSearch) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilter);
    }

    dayButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            dayButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            activeDay = this.getAttribute('data-day');
            applyFilter();
        });
    });

    // Mobile view switch function
    window.setMobileView = function(view) {
        const table = document.getElementById('corTableScrollWrapper');
        const cardsView = document.getElementById('corMobileCardsView');
        const btnCards = document.getElementById('viewCardsBtn');
        const btnTable = document.getElementById('viewTableBtn');

        if (view === 'table') {
            if (table) table.classList.add('force-show');
            if (cardsView) cardsView.classList.add('force-hide');
            if (btnTable) btnTable.classList.add('active');
            if (btnCards) btnCards.classList.remove('active');
        } else {
            if (table) table.classList.remove('force-show');
            if (cardsView) cardsView.classList.remove('force-hide');
            if (btnCards) btnCards.classList.add('active');
            if (btnTable) btnTable.classList.remove('active');
        }
    };
});
</script>
@endsection