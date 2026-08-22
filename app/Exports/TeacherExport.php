<?php

namespace App\Exports;

use App\Models\User;
use App\Traits\SanitizesExport;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use SanitizesExport;

    public function query()
    {
        return User::where('role', 'teacher')->orderBy('name');
    }

    public function headings(): array
    {
        return [
            'Name',
            'Employee ID',
            'Email',
            'Department',
            'Position',
            'Specialization',
            'Registered At',
        ];
    }

    public function map($row): array
    {
        return [
            $this->sanitizeField($row->name),
            $this->sanitizeField($row->employee_id ?? 'N/A'),
            $this->sanitizeField($row->email),
            $this->sanitizeField($row->department ?? 'N/A'),
            $this->sanitizeField($row->position ?? 'N/A'),
            $this->sanitizeField($row->specialization ?? 'N/A'),
            $row->created_at->format('Y-m-d'),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '7f432e']],
            ],
        ];
    }
}
