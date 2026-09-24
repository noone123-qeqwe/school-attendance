@extends('layouts.pdf')

@section('title', 'Certificate of Registration & Class Schedule')
@section('report-title', 'Certificate of Registration ( COR )')
@section('footer-title', 'Academic Schedule Slip')
@section('footer-details', 'Student: ' . $user->name . ' • ' . ($user->student_number ?? 'ID: ' . $user->id) . ' • Year ' . $user->year_level . ' • Semester ' . $user->semester)

@section('content')
@php
    $yearLevelMap = [
        1 => 'First Year',
        2 => 'Second Year',
        3 => 'Third Year',
        4 => 'Fourth Year',
        5 => 'Fifth Year',
    ];
    $yearText = $yearLevelMap[(int) $user->year_level] ?? ('Year ' . ($user->year_level ?? '1'));

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

    $studentNumber = !empty($user->student_number) ? $user->student_number : ('ST-' . str_pad((string)$user->id, 6, '0', STR_PAD_LEFT));
    $sectionDisplay = $user->section ?: ($courseCode . ' ' . ($user->year_level ?? '1') . 'A');

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

    $groupedSchedules = [];
    $rowNumber = 1;

    foreach ($subjects as $subject) {
        $subjectSchedules = $subject->schedules ?? collect();
        $instructorName = $subject->instructorUser?->name ?? $subject->instructor ?? 'TBA';
        $units = (float) ($subject->units ?: 3.0);
        $rawSection = $subject->section ?: ($user->section ?: '1420');

        if ($subjectSchedules->count() > 0) {
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
                $classNum = str_pad((string) (20 + (($subject->id * 3 + $slotIndex) % 70)), 2, '0', STR_PAD_LEFT);
                $currentRow = $rowNumber++;
                $sectionLabel = preg_match('/^\d+\s+/', $rawSection) ? $rawSection : ($currentRow . ' ' . $rawSection);

                $groupedSchedules[] = (object) [
                    'row_num'         => $currentRow,
                    'section'         => $sectionLabel,
                    'code'            => $subject->code,
                    'name'            => $subject->name,
                    'class_number'    => $classNum,
                    'units'           => $slotIndex === 0 ? number_format($units, 1) : '—',
                    'start_time'      => $slot['start_time'],
                    'end_time'        => $slot['end_time'],
                    'days'            => $daysAbbr,
                    'room'            => !empty($slot['room']) ? $slot['room'] : 'TBA',
                    'teacher'         => $instructorName,
                ];
                $slotIndex++;
            }
        } else {
            $currentRow = $rowNumber++;
            $sectionLabel = preg_match('/^\d+\s+/', $rawSection) ? $rawSection : ($currentRow . ' ' . $rawSection);

            $groupedSchedules[] = (object) [
                'row_num'         => $currentRow,
                'section'         => $sectionLabel,
                'code'            => $subject->code,
                'name'            => $subject->name,
                'class_number'    => str_pad((string) (20 + (($subject->id * 3) % 70)), 2, '0', STR_PAD_LEFT),
                'units'           => number_format($units, 1),
                'start_time'      => null,
                'end_time'        => null,
                'days'            => 'TBA',
                'room'            => 'TBA',
                'teacher'         => $instructorName,
            ];
        }
    }

    $totalUnits = $subjects->sum(fn($s) => (float) ($s->units ?: 3.0));
@endphp

<style>
    .cor-pdf-grid {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .cor-pdf-grid td {
        border: 1px solid #333333;
        padding: 5px 8px;
        font-size: 10.5px;
    }
    .cor-pdf-label {
        background-color: #f2f2f2;
        font-weight: bold;
        color: #800000;
        width: 18%;
        text-transform: uppercase;
        font-size: 9.5px;
    }
    .cor-pdf-val {
        color: #111111;
        font-weight: 600;
    }
    .cor-pdf-mono {
        font-family: 'Courier New', monospace;
        font-weight: bold;
    }
    .cor-sched-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .cor-sched-table th {
        background-color: #f5ede0;
        border: 1px solid #333333;
        padding: 6px 7px;
        font-size: 9.5px;
        font-weight: bold;
        text-transform: uppercase;
        color: #800000;
        text-align: left;
    }
    .cor-sched-table th.center,
    .cor-sched-table td.center {
        text-align: center;
    }
    .cor-sched-table td {
        border: 1px solid #444444;
        padding: 5px 7px;
        font-size: 10px;
        color: #222222;
    }
    .cor-sched-table tfoot td {
        background-color: #f8f8f8;
        font-weight: bold;
        border-top: 2px solid #333333;
    }
    .cor-footer-box {
        width: 100%;
        margin-top: 20px;
    }
    .cor-sig-block {
        text-align: right;
        margin-top: 15px;
    }
</style>

<!-- 1. Student Demographic Table (Inspired by Reference Document Layout) -->
<table class="cor-pdf-grid">
    <tr>
        <td class="cor-pdf-label">Student Number :</td>
        <td class="cor-pdf-val cor-pdf-mono" style="width: 32%;">{{ $studentNumber }}</td>
        <td class="cor-pdf-label">Year Level :</td>
        <td class="cor-pdf-val" style="width: 32%;">{{ $yearText }}</td>
    </tr>
    <tr>
        <td class="cor-pdf-label">Course :</td>
        <td class="cor-pdf-val" colspan="3">{{ $courseFull }} ({{ $courseCode }})</td>
    </tr>
    <tr>
        <td class="cor-pdf-label">Name :</td>
        <td class="cor-pdf-val" style="font-weight: bold;">{{ $user->name }}</td>
        <td class="cor-pdf-label">Section :</td>
        <td class="cor-pdf-val">{{ $sectionDisplay }}</td>
    </tr>
</table>

@if(count($groupedSchedules) > 0)
<!-- 2. Class & Schedule Unified Matrix Table (Inspired by Reference Document Columns) -->
<table class="cor-sched-table">
    <thead>
        <tr>
            <th style="width: 10%;">Section</th>
            <th style="width: 30%;">Subject Code & Description</th>
            <th class="center" style="width: 9%;">Class No.</th>
            <th class="center" style="width: 7%;">Units</th>
            <th style="width: 17%;">Time</th>
            <th class="center" style="width: 8%;">Day</th>
            <th class="center" style="width: 7%;">Room</th>
            <th style="width: 12%;">Teacher's Name</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groupedSchedules as $sched)
        <tr>
            <td style="font-weight: bold; font-family: 'Courier New', monospace;">{{ $sched->section }}</td>
            <td>
                <strong style="color: #800000; font-family: 'Courier New', monospace;">{{ $sched->code }}</strong> - {{ $sched->name }}
            </td>
            <td class="center font-monospace">{{ $sched->class_number }}</td>
            <td class="center font-bold">{{ $sched->units }}</td>
            <td style="font-family: 'Courier New', monospace;">
                @if($sched->start_time && $sched->end_time)
                    {{ \Carbon\Carbon::parse($sched->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($sched->end_time)->format('h:i A') }}
                @else
                    TBA
                @endif
            </td>
            <td class="center" style="font-weight: bold; color: #800000;">{{ $sched->days }}</td>
            <td class="center font-monospace">{{ $sched->room }}</td>
            <td>{{ $sched->teacher }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align: right; text-transform: uppercase;">Total Units :</td>
            <td class="center" style="font-size: 11px; font-weight: bold;">{{ number_format($totalUnits, 1) }}</td>
            <td colspan="4" style="color: #666; font-size: 9px;">
                {{ $subjects->count() }} Total Registered Subjects • Officially Validated
            </td>
        </tr>
    </tfoot>
</table>


@else
<div style="text-align: center; padding: 30px; border: 1px dashed #999; margin: 20px 0;">
    <h3>No Enrolled Subjects</h3>
    <p>You are currently not registered for any classes this semester.</p>
</div>
@endif

@endsection
