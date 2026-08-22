<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Traits\SanitizesExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use SanitizesExport;

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
            $this->sanitizeField($row->user?->name ?? 'N/A'),
            $this->sanitizeField($row->user?->student_number ?? 'N/A'),
            $this->sanitizeField($row->subject_code),
            $this->sanitizeField($row->subject?->name ?? 'N/A'),
            $this->sanitizeField($row->date),
            $this->sanitizeField($row->status),
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
