<?php

namespace App\Exports;

use App\Models\Warning;
use App\Traits\SanitizesExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EarlyWarningsExport implements FromCollection, WithHeadings, WithMapping
{
    use SanitizesExport;

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
            $this->sanitizeField($warning->user->name ?? 'N/A'),
            $this->sanitizeField($warning->user->student_number ?? 'N/A'),
            $this->sanitizeField($warning->subject_code),
            $this->sanitizeField($warning->message),
            $warning->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
