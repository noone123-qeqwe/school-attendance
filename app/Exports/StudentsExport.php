<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    protected $students;

    public function __construct($students)
    {
        $this->students = $students;
    }

    public function collection()
    {
        return $this->students->map(function ($student) {
            return [
                'name' => $student->name,
                'student_id' => $student->student_number ?? 'N/A',
                'email' => $student->email,
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
