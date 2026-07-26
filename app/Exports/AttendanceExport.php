<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Attendance::with(['user', 'subject']);

        if (!empty($this->filters['subject_code'])) {
            $query->where('subject_code', $this->filters['subject_code']);
        }

        if (!empty($this->filters['date'])) {
            $query->whereDate('date', $this->filters['date']);
        }

        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'Student ID',
            'Subject Code',
            'Subject Name',
            'Date',
            'Status',
            'Excused',
        ];
    }

    public function map($row): array
    {
        return [
            $row->user?->name ?? 'N/A',
            $row->user?->student_number ?? 'N/A',
            $row->subject_code,
            $row->subject?->name ?? 'N/A',
            $row->date,
            $row->status,
            $row->excused ? 'Yes' : 'No',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
