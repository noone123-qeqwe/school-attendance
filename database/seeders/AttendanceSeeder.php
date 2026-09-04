<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\AcademicYear;
use App\Models\ExcuseSubmission;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'student')->get();
        if ($students->isEmpty()) {
            $this->command->warn('No students found for AttendanceSeeder.');
            return;
        }

        $academicYear = AcademicYear::where('is_current', true)->first() 
            ?? AcademicYear::first();
        $academicYearId = $academicYear ? $academicYear->id : null;

        $admin = User::where('role', 'admin')->first();

        // Determine 10 recent school days (weekdays)
        $schoolDays = [];
        $cursor = Carbon::today();
        while (count($schoolDays) < 10) {
            $cursor = $cursor->copy()->subDay();
            if (!$cursor->isWeekend()) {
                $schoolDays[] = $cursor->copy();
            }
        }
        // Reverse so dates are chronological
        $schoolDays = array_reverse($schoolDays);

        $createdAttendanceCount = 0;
        $absentAttendances = [];

        foreach ($students as $student) {
            $subjects = $student->getAllSubjects();
            if ($subjects->isEmpty()) {
                $subjects = Subject::where('course', $student->course ?? 'BSCS')
                    ->where('year_level', $student->year_level ?? 4)
                    ->where('semester', $student->semester ?? 1)
                    ->get();
            }

            if ($subjects->isEmpty()) {
                $subjects = Subject::limit(4)->get();
            }

            foreach ($schoolDays as $day) {
                $dateStr = $day->format('Y-m-d');

                foreach ($subjects as $subject) {
                    // Realistic status distribution: 85% Present, 10% Late, 5% Absent
                    $roll = rand(1, 100);
                    if ($roll <= 85) {
                        $status = 'Present';
                        $timeIn = $day->copy()->setTime(7, rand(45, 59), rand(0, 59))->format('H:i:s');
                    } elseif ($roll <= 95) {
                        $status = 'Late';
                        $timeIn = $day->copy()->setTime(8, rand(12, 28), rand(0, 59))->format('H:i:s');
                    } else {
                        $status = 'Absent';
                        $timeIn = null;
                    }

                    $attendance = Attendance::updateOrCreate(
                        [
                            'user_id'      => $student->id,
                            'subject_code' => $subject->code,
                            'date'         => $dateStr,
                        ],
                        [
                            'subject_id'       => $subject->id,
                            'status'           => $status,
                            'time_in'          => $timeIn,
                            'academic_year_id' => $academicYearId,
                            'method'           => 'QR',
                            'created_at'       => $day,
                            'updated_at'       => $day,
                        ]
                    );

                    $createdAttendanceCount++;

                    if ($status === 'Absent') {
                        $absentAttendances[] = $attendance;
                    }
                }
            }
        }

        // Generate clean demo excuse submissions for up to 2 absences
        $excuseSamples = [
            [
                'reason'      => 'Medical Consultation',
                'description' => 'Underwent scheduled medical examination and consultation at Osmeña Municipal Hospital.',
                'status'      => 'approved',
                'notes'       => 'Medical certificate verified and approved.',
            ],
            [
                'reason'      => 'Urgent Family Matter',
                'description' => 'Required to attend and assist family with an emergency matter in Masbate City.',
                'status'      => 'pending',
                'notes'       => null,
            ],
        ];

        $excuseCount = 0;
        foreach ($excuseSamples as $idx => $sample) {
            if (isset($absentAttendances[$idx])) {
                $absentRecord = $absentAttendances[$idx];

                // Ensure no duplicate excuse
                ExcuseSubmission::updateOrCreate(
                    [
                        'user_id'       => $absentRecord->user_id,
                        'attendance_id' => $absentRecord->id,
                    ],
                    [
                        'reason'      => $sample['reason'],
                        'description' => $sample['description'],
                        'status'      => $sample['status'],
                        'admin_notes' => $sample['notes'],
                        'reviewed_at' => $sample['status'] === 'approved' ? Carbon::now() : null,
                        'reviewed_by' => $sample['status'] === 'approved' && $admin ? $admin->id : null,
                        'created_at'  => $absentRecord->date,
                        'updated_at'  => $absentRecord->date,
                    ]
                );

                if ($sample['status'] === 'approved') {
                    $absentRecord->update(['excused' => true, 'excuse_note' => $sample['reason']]);
                }

                $excuseCount++;
            }
        }

        $this->command->info("AttendanceSeeder finished: {$createdAttendanceCount} attendance records and {$excuseCount} excuse submissions created.");
    }
}
