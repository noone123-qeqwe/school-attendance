<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Subject;
use App\Models\User;
use App\Models\Section;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceReportService
{
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

        if (!empty($scopedSubjectCodes)) {
            $baseQuery->whereIn('subject_code', $scopedSubjectCodes);
        }

        if (!empty($params['subject'])) {
            $baseQuery->where('subject_code', $params['subject']);
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
                $y = (int) ($parts[0] ?? now()->year);
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
                $summaryQuery = Attendance::query();
                if (!empty($scopedSubjectCodes)) {
                    $summaryQuery->whereIn('subject_code', $scopedSubjectCodes);
                }
                if (!empty($params['subject'])) {
                    $summaryQuery->where('subject_code', $params['subject']);
                }
                $summaryQuery->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate);

                $summaryData = $summaryQuery->join('users', 'attendances.user_id', '=', 'users.id')
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
                $subjQuery = Attendance::query();
                if (!empty($scopedSubjectCodes)) {
                    $subjQuery->whereIn('subject_code', $scopedSubjectCodes);
                }
                $subjQuery->whereDate('date', '>=', $startDate)->whereDate('date', '<=', $endDate);

                $summaryData = $subjQuery->selectRaw("
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

                $students = $studentQuery->with(['attendances' => function ($q) use ($scopedSubjectCodes, $startDate, $endDate, $params) {
                    if (!empty($scopedSubjectCodes)) {
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

    /**
     * Generate a streaming CSV download for the report.
     */
    public function downloadCsv(array $report): StreamedResponse
    {
        $filename = 'attendance-report-' . $report['type'] . '-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel

            $type = $report['type'];

            if ($type === 'percentage') {
                fputcsv($handle, ['Student Name', 'Student Number', 'Course', 'Year Level', 'Section', 'Total Classes', 'Present', 'Late', 'Absent', 'Excused', 'Attendance Rate (%)']);
                foreach ($report['percentageData'] as $item) {
                    $s = $item['student'];
                    fputcsv($handle, [
                        $s->name,
                        $s->student_number ?? 'N/A',
                        $s->course ?? 'N/A',
                        $s->year_level ?? 'N/A',
                        $s->section ?? 'N/A',
                        $item['total'],
                        $item['present'],
                        $item['late'],
                        $item['absent'],
                        $item['excused'],
                        $item['rate'] . '%',
                    ]);
                }
            } elseif ($type === 'class_summary') {
                fputcsv($handle, ['Course', 'Year Level', 'Section', 'Total Records', 'Present', 'Late', 'Absent', 'Excused', 'Attendance Rate (%)']);
                foreach ($report['summaryData'] as $row) {
                    fputcsv($handle, [
                        $row->course,
                        $row->year_level,
                        $row->section,
                        $row->total_records,
                        $row->present_count,
                        $row->late_count,
                        $row->absent_count,
                        $row->excused_count,
                        $row->rate . '%',
                    ]);
                }
            } elseif ($type === 'subject_summary') {
                fputcsv($handle, ['Subject Code', 'Subject Name', 'Total Records', 'Present', 'Late', 'Absent', 'Excused', 'Attendance Rate (%)']);
                foreach ($report['summaryData'] as $row) {
                    fputcsv($handle, [
                        $row->subject_code,
                        $row->subject_name,
                        $row->total_records,
                        $row->present_count,
                        $row->late_count,
                        $row->absent_count,
                        $row->excused_count,
                        $row->rate . '%',
                    ]);
                }
            } else {
                fputcsv($handle, ['Date', 'Time In', 'Student Name', 'Student Number', 'Subject Code', 'Subject Name', 'Status', 'Excused', 'Method']);
                foreach ($report['records'] as $att) {
                    fputcsv($handle, [
                        $att->date ? (is_string($att->date) ? $att->date : $att->date->format('Y-m-d')) : 'N/A',
                        $att->time_in ?? 'N/A',
                        $att->user->name ?? 'Unknown',
                        $att->user->student_number ?? 'N/A',
                        $att->subject_code,
                        $att->subject->name ?? $att->subject_code,
                        $att->status,
                        $att->excused ? 'Yes' : 'No',
                        strtoupper($att->method ?? 'QR'),
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
