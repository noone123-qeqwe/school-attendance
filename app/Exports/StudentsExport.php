<?php

namespace App\Exports;

use App\Traits\SanitizesExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    use SanitizesExport;

    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students->map(function ($student) {
            return [
                'name' => $this->sanitizeField($student->name),
                'student_id' => $this->sanitizeField($student->student_number ?? 'N/A'),
                'email' => $this->sanitizeField($student->email),
                'attendance_rate' => $student->attendance_rate . '%'
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Student ID',
            'Email',
            'Attendance Rate'
        ];
    }
}
