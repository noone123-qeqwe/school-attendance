<?php

namespace App\Services;

use App\Exports\AttendanceReportWorkbook;
use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use App\Traits\SanitizesExport;
use Carbon\Carbon;
use Generator;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportService
{
    use SanitizesExport;

    /**
     * Generate structured attendance report based on request filters.
     */
    public function generate(array $params, ?array $scopedSubjectCodes = null): array
    {
        $type = $params['type'] ?? 'daily';
        $today = today()->toDateString();
        $date = $params['date'] ?? $today;
        $month = $params['month'] ?? today()->format('Y-m');
        $startDate = $params['start_date'] ?? today()->subDays(7)->toDateString();
        $endDate = $params['end_date'] ?? $today;

        // Base attendance query
        $baseQuery = Attendance::with(['user', 'subject', 'academicYear']);

        if ($scopedSubjectCodes !== null) {
            $baseQuery->whereIn('attendances.subject_code', $scopedSubjectCodes);
        }

        if (!empty($params['subject'])) {
            $baseQuery->where('attendances.subject_code', $params['subject']);
        }

        if (!empty($params['course'])) {
            $baseQuery->whereHas('user', fn($q) => $q->where('course', $params['course']));
        }

        if (!empty($params['year_level'])) {
            $baseQuery->whereHas('user', fn($q) => $q->where('year_level', $params['year_level']));
        }

        if (!empty($params['section'])) {
            $baseQuery->whereHas('user', fn($q) => $q->where('section', $params['section']));
        }

        $records = collect();
        $percentageData = collect();
        $summaryData = collect();
        $title = 'Attendance Report';

        switch ($type) {
            case 'daily':
                $title = 'Daily Attendance Report - ' . Carbon::parse($date)->format('F d, Y');
                $records = (clone $baseQuery)
                    ->whereDate('date', $date)
                    ->orderBy('time_in', 'asc')
                    ->get();
                break;

            case 'weekly':
                $carbonDate = Carbon::parse($date);
                $weekStart = $carbonDate->copy()->startOfWeek();
                $weekEnd = $carbonDate->copy()->endOfWeek();
                $title = 'Weekly Attendance Report - ' . $weekStart->format('M d') . ' to ' . $weekEnd->format('M d, Y');
                $records = (clone $baseQuery)
                    ->whereDate('date', '>=', $weekStart->toDateString())
                    ->whereDate('date', '<=', $weekEnd->toDateString())
                    ->orderBy('date', 'asc')
                    ->orderBy('time_in', 'asc')
                    ->get();
                break;

            case 'monthly':
                $parts = explode('-', $month);
                $y = (int) $parts[0];
                $m = (int) ($parts[1] ?? now()->month);
                $title = 'Monthly Attendance Report - ' . Carbon::create($y, $m, 1)->format('F Y');
                $records = (clone $baseQuery)
                    ->whereYear('date', $y)
                    ->whereMonth('date', $m)
                    ->orderBy('date', 'asc')
                    ->get();
                break;

            case 'range':
                $title = 'Date Range Report - ' . Carbon::parse($startDate)->format('M d, Y') . ' to ' . Carbon::parse($endDate)->format('M d, Y');
                $records = (clone $baseQuery)
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->orderBy('date', 'asc')
                    ->get();
                break;

            case 'late':
                $title = 'Late Students Report - ' . Carbon::parse($startDate)->format('M d, Y') . ' to ' . Carbon::parse($endDate)->format('M d, Y');
                $records = (clone $baseQuery)
                    ->where('status', 'Late')
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->orderBy('date', 'desc')
                    ->get();
                break;

            case 'absent':
                $title = 'Absent Students Report - ' . Carbon::parse($startDate)->format('M d, Y') . ' to ' . Carbon::parse($endDate)->format('M d, Y');
                $records = (clone $baseQuery)
                    ->where('status', 'Absent')
                    ->whereDate('date', '>=', $startDate)
                    ->whereDate('date', '<=', $endDate)
                    ->orderBy('date', 'desc')
                    ->get();
                break;

            case 'class_summary':
                $title = 'Class & Section Attendance Summary';
                $summaryData = (clone $baseQuery)->toBase()
                    ->whereDate('attendances.date', '>=', $startDate)
                    ->whereDate('attendances.date', '<=', $endDate)
                    ->join('users', 'attendances.user_id', '=', 'users.id')
                    ->selectRaw("
                        COALESCE(users.course, 'Unassigned') as course,
                        COALESCE(users.year_level, 0) as year_level,
                        COALESCE(users.section, 'General') as section,
                        COUNT(attendances.id) as total_records,
                        SUM(CASE WHEN attendances.status = 'Present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN attendances.status = 'Late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN attendances.status = 'Absent' AND (attendances.excused IS FALSE OR attendances.excused IS NULL) THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN attendances.excused IS TRUE THEN 1 ELSE 0 END) as excused_count
                    ")
                    ->groupBy('course', 'year_level', 'section')
                    ->orderBy('course')
                    ->orderBy('year_level')
                    ->orderBy('section')
                    ->get()
                    ->map(function ($row) {
                        $p = (int) $row->present_count;
                        $l = (int) $row->late_count;
                        $t = (int) $row->total_records;
                        $row->rate = $t > 0 ? round((($p + $l) / $t) * 100, 1) : 0;
                        return $row;
                    });
                break;

            case 'subject_summary':
                $title = 'Subject Attendance Summary';
                $summaryData = (clone $baseQuery)->toBase()
                    ->whereDate('attendances.date', '>=', $startDate)
                    ->whereDate('attendances.date', '<=', $endDate)
                    ->selectRaw("
                        subject_code,
                        COUNT(id) as total_records,
                        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present_count,
                        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late_count,
                        SUM(CASE WHEN status = 'Absent' AND (excused IS FALSE OR excused IS NULL) THEN 1 ELSE 0 END) as absent_count,
                        SUM(CASE WHEN excused IS TRUE THEN 1 ELSE 0 END) as excused_count
                    ")
                    ->groupBy('subject_code')
                    ->orderBy('subject_code')
                    ->get()
                    ->map(function ($row) {
                        $p = (int) $row->present_count;
                        $l = (int) $row->late_count;
                        $t = (int) $row->total_records;
                        $row->rate = $t > 0 ? round((($p + $l) / $t) * 100, 1) : 0;
                        $row->subject_name = Subject::where('code', $row->subject_code)->value('name') ?? $row->subject_code;
                        return $row;
                    });
                break;

            case 'percentage':
            default:
                $type = 'percentage';
                $title = 'Student Attendance Ranking & Percentages';
                $studentQuery = User::where('role', 'student');

                if (!empty($params['course'])) {
                    $studentQuery->where('course', $params['course']);
                }
                if (!empty($params['year_level'])) {
                    $studentQuery->where('year_level', $params['year_level']);
                }
                if (!empty($params['section'])) {
                    $studentQuery->where('section', $params['section']);
                }

                if ($scopedSubjectCodes !== null) {
                    $scopedSubjects = Subject::whereIn('code', $scopedSubjectCodes);
                    if (!empty($params['subject'])) {
                        $scopedSubjects->where('code', $params['subject']);
                    }

                    $studentIds = SubjectRosterService::studentIds($scopedSubjects->get());
                    $studentQuery->whereIn('id', $studentIds);
                }

                $students = $studentQuery->with(['attendances' => function ($q) use ($scopedSubjectCodes, $startDate, $endDate, $params) {
                    if ($scopedSubjectCodes !== null) {
                        $q->whereIn('subject_code', $scopedSubjectCodes);
                    }
                    if (!empty($params['subject'])) {
                        $q->where('subject_code', $params['subject']);
                    }
                    if (!empty($params['start_date']) && !empty($params['end_date'])) {
                        $q->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate);
                    }
                }])->get();

                $percentageData = $students->map(function ($student) {
                    $total = $student->attendances->count();
                    $present = $student->attendances->where('status', 'Present')->count();
                    $late = $student->attendances->where('status', 'Late')->count();
                    $absent = $student->attendances->where('status', 'Absent')->where('excused', false)->count();
                    $excused = $student->attendances->where('excused', true)->count();
                    $attended = $present + $late;
                    $rate = $total > 0 ? round(($attended / $total) * 100, 1) : 0;

                    return [
                        'student' => $student,
                        'total' => $total,
                        'present' => $present,
                        'late' => $late,
                        'absent' => $absent,
                        'excused' => $excused,
                        'rate' => $rate,
                    ];
                })->sortByDesc('rate')->values();
                break;
        }

        // Aggregate statistics for overview cards
        $totalRecords = $records->count();
        $presentCount = $records->where('status', 'Present')->count();
        $lateCount = $records->where('status', 'Late')->count();
        $absentCount = $records->where('status', 'Absent')->where('excused', false)->count();
        $excusedCount = $records->where('excused', true)->count();
        $overallRate = $totalRecords > 0 ? round((($presentCount + $lateCount) / $totalRecords) * 100, 1) : 0;

        $data = match ($type) {
            'percentage' => $percentageData,
            'class_summary', 'subject_summary' => $summaryData,
            default => $records,
        };

        return [
            'type' => $type,
            'title' => $title,
            'date' => $date,
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'data' => $data,
            'records' => $records,
            'percentageData' => $percentageData,
            'summaryData' => $summaryData,
            'stats' => [
                'total' => $totalRecords,
                'present' => $presentCount,
                'late' => $lateCount,
                'absent' => $absentCount,
                'excused' => $excusedCount,
                'rate' => $overallRate,
            ],
            'params' => $params,
        ];
    }

    public function downloadCsv(array $report): StreamedResponse
    {
        $filename = $this->exportFilename($report, 'csv');

        return response()->stream(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            $headings = $this->exportHeadings($report['type']);
            $rateColumn = array_search(AttendanceReportWorkbook::RATE_HEADING, $headings, true);
            fputcsv($handle, $headings, escape: '');
            foreach ($this->exportRows($report) as $row) {
                if ($rateColumn !== false) {
                    $row[$rateColumn] .= '%';
                }

                fputcsv($handle, array_map($this->sanitizeField(...), $row), escape: '');
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadExcel(array $report): BinaryFileResponse
    {
        $previousBinder = Cell::getValueBinder();

        try {
            $response = Spreadsheet::download(
                new AttendanceReportWorkbook($this->exportHeadings($report['type']), $this->exportRows($report)),
                $this->exportFilename($report, 'xlsx'),
                Excel::XLSX,
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'no-store',
                ]
            );
            $response->setPrivate();

            return $response;
        } finally {
            // Laravel Excel sets a static binder while it builds the file.
            Cell::setValueBinder($previousBinder);
        }
    }

    private function exportFilename(array $report, string $extension): string
    {
        $type = preg_replace('/[^a-z0-9_-]+/i', '-', (string) $report['type']) ?: 'daily';

        return 'attendance-report-' . $type . '-' . now()->format('Y-m-d-His') . '.' . $extension;
    }

    private function exportHeadings(string $type): array
    {
        $totals = ['Present', 'Late', 'Absent', 'Excused', AttendanceReportWorkbook::RATE_HEADING];

        return match ($type) {
            'percentage' => ['Student Name', 'Student Number', 'Course', 'Year Level', 'Section', 'Total Classes', ...$totals],
            'class_summary' => ['Course', 'Year Level', 'Section', 'Total Records', ...$totals],
            'subject_summary' => ['Subject Code', 'Subject Name', 'Total Records', ...$totals],
            default => ['Date', 'Time In', 'Student Name', 'Student Number', 'Subject Code', 'Subject Name', 'Status', 'Excused', 'Method'],
        };
    }

    /** @return Generator<int, array> */
    private function exportRows(array $report): Generator
    {
        foreach ($report['data'] as $row) {
            yield match ($report['type']) {
                'percentage' => [
                    $row['student']->name, $row['student']->student_number ?? 'N/A', $row['student']->course ?? 'N/A',
                    $row['student']->year_level ?? 'N/A', $row['student']->section ?? 'N/A',
                    $row['total'], $row['present'], $row['late'], $row['absent'], $row['excused'], $row['rate'],
                ],
                'class_summary' => [
                    $row->course, $row->year_level, $row->section, (int) $row->total_records,
                    (int) $row->present_count, (int) $row->late_count, (int) $row->absent_count, (int) $row->excused_count, $row->rate,
                ],
                'subject_summary' => [
                    $row->subject_code, $row->subject_name, (int) $row->total_records,
                    (int) $row->present_count, (int) $row->late_count, (int) $row->absent_count, (int) $row->excused_count, $row->rate,
                ],
                default => [
                    $row->date?->format('Y-m-d') ?? 'N/A', $row->time_in ?? 'N/A',
                    $row->user->name ?? 'Unknown', $row->user->student_number ?? 'N/A',
                    $row->subject_code, $row->subject->name ?? $row->subject_code, $row->status,
                    $row->excused ? 'Yes' : 'No', strtoupper($row->method ?? 'QR'),
                ],
            };
        }
    }
}
