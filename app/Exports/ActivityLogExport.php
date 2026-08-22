<?php

namespace App\Exports;

use App\Traits\SanitizesExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            'Time',
            'User Name',
            'User Email',
            'Action',
            'Resource',
            'IP Address',
            'Changes'
        ];
    }

    public function map($row): array
    {
        $changes = '';
        if (isset($row->properties['attributes'])) {
            $attr = $row->properties['attributes'];
            $old = $row->properties['old'] ?? [];
            $changeList = [];
            foreach ($attr as $key => $val) {
                if (in_array($key, ['password', 'remember_token'])) {
                    continue; // Skip sensitive fields
                }
                $oldVal = is_array($old) && isset($old[$key]) ? (is_array($old[$key]) ? json_encode($old[$key]) : $old[$key]) : 'N/A';
                $newVal = is_array($val) ? json_encode($val) : $val;
                $changeList[] = "$key: $oldVal -> $newVal";
            }
            $changes = implode(', ', $changeList);
        }

        return [
            $row->created_at->format('Y-m-d H:i:s'),
            $this->sanitizeField($row->causer->name ?? 'System'),
            $this->sanitizeField($row->causer->email ?? 'System Process'),
            $this->sanitizeField(ucfirst($row->description)),
            $this->sanitizeField($row->subject_type ? (class_basename($row->subject_type) . ($row->subject_id ? " #{$row->subject_id}" : '')) : 'N/A'),
            $this->sanitizeField($row->properties['ip'] ?? '127.0.0.1'),
            $this->sanitizeField($changes)
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
