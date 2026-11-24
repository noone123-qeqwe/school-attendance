<?php

namespace App\Exports;

use Generator;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportWorkbook extends DefaultValueBinder implements FromGenerator, ShouldAutoSize, WithColumnFormatting,
    WithCustomValueBinder, WithHeadings, WithStrictNullComparison, WithStyles, WithTitle
{
    public const RATE_HEADING = 'Attendance Rate (%)';

    private int|false $rateColumn;

    public function __construct(private array $columns, private Generator $rows)
    {
        $this->rateColumn = array_search(self::RATE_HEADING, $columns, true);
    }

    public function title(): string
    {
        return 'Attendance report';
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function generator(): Generator
    {
        foreach ($this->rows as $row) {
            if ($this->rateColumn !== false) {
                $row[$this->rateColumn] /= 100;
            }

            yield $row;
        }
    }

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value)) {
            // Preserve identifiers and treat names and labels as plain text.
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return $this->rateColumn === false
            ? []
            : [Coordinate::stringFromColumnIndex($this->rateColumn + 1) => '0.0%'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDataDimension());

        return [1 => ['font' => ['bold' => true]]];
    }
}
