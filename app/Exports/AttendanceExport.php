<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $logs;

    public function __construct($logs)
    {
        $this->logs = $logs;
    }

    public function collection()
    {
        return $this->logs;
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
