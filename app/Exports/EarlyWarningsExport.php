<?php

namespace App\Exports;

use App\Models\Warning;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EarlyWarningsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Warning::with('user')->orderBy('created_at', 'desc')->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Student Name',
            'Student Number',
            'Subject Code',
            'Message',
            'Date Flagged',
        ];
    }

    public function map($warning): array
    {
        return [
            $warning->id,
            $warning->user->name ?? 'N/A',
            $warning->user->student_number ?? 'N/A',
            $warning->subject_code,
            $warning->message,
            $warning->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
