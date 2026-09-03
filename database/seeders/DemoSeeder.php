<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Subject;
use App\Models\Attendance;
use App\Models\Holiday;
use App\Models\ExcuseSubmission;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Seed default active Academic Year & Terms
        \App\Models\AcademicYear::firstOrCreate(
            ['name' => '2026-2027', 'semester' => 1],
            [
                'start_date' => '2026-08-01',
                'end_date' => '2026-12-20',
                'is_current' => true,
            ]
        );

        // Seed core school attendance settings
        \App\Models\Setting::firstOrCreate(['key' => 'late_threshold'], ['value' => '15']);
        \App\Models\Setting::firstOrCreate(['key' => 'academic_year'], ['value' => '2026-2027']);
        \App\Models\Setting::firstOrCreate(['key' => 'current_semester'], ['value' => '1']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Create Teacher User
        $teacher = User::firstOrCreate(
            ['email' => 'jovelyn.patalinghug@teacher.com'],
            [
                'name' => 'Jovelyn Patalinghug',
                'password' => Hash::make('password'),
                'role' => 'teacher',
                'employee_id' => 'T-2024-001',
                'department' => 'Computer Science',
                'position' => 'Instructor',
                'specialization' => 'Software Development'
            ]
        );

        // Create Parent User
        $parent = User::firstOrCreate(
            ['email' => 'parent@example.com'],
            [
                'name' => 'Demo Parent',
                'password' => Hash::make('password'),
                'role' => 'parent',
                'phone' => '09123456789'
            ]
        );

        User::firstOrCreate(
            ['email' => 'joel.salidaga@school.edu'],
            [
                'name' => 'Joel Salidaga',
                'password' => Hash::make('teacher123'),
                'role' => 'teacher',
                'employee_id' => 'T-2024-004',
                'department' => 'Information System',
                'position' => 'Teacher',
                'specialization' => 'Attendance Management'
            ]
        );

        // Create Demo Students
        $students = [
            [
                'name' => 'Mark Angelo Santos',
                'email' => 'student@example.com',
                'student_number' => '2310843',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'password' => 'password'
            ],
            [
                'name' => 'Maria Isabel Garcia',
                'email' => 'maria.garcia@student.edu', 
                'student_number' => '2024002',
                'course' => 'BSIT',
                'year_level' => 2,
                'semester' => 1
            ],
            [
                'name' => 'Carlos David Cruz',
                'email' => 'carlos.cruz@student.edu',
                'student_number' => '2024003',
                'course' => 'BSIS',
                'year_level' => 4,
                'semester' => 1
            ],
            [
                'name' => 'Ana Marie Reyes',
                'email' => 'ana.reyes@student.edu',
                'student_number' => '2024004',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 1
            ],
            [
                'name' => 'Robert James Dela Cruz',
                'email' => 'robert.delacruz@student.edu',
                'student_number' => '2024005',
                'course' => 'BSIT',
                'year_level' => 3,
                'semester' => 1
            ]
        ];

        foreach ($students as $studentData) {
            $student = User::where('student_number', $studentData['student_number'])->first();
            
            if ($student) {
                // If student exists, just ensure they have the demo password so you can log in,
                // but preserve their real name and other details from the database!
                $student->update([
                    'password' => Hash::make($studentData['password'] ?? 'student123')
                ]);
            } else {
                // If student doesn't exist, create them
                User::create([
                    'name' => $studentData['name'],
                    'email' => $studentData['email'],
                    'student_number' => $studentData['student_number'],
                    'course' => $studentData['course'],
                    'year_level' => $studentData['year_level'],
                    'semester' => $studentData['semester'],
                    'password' => Hash::make($studentData['password'] ?? 'student123'),
                    'role' => 'student'
                ]);
            }
        }

        // Create Demo Subjects
        $subjects = [
            [
                'code' => 'CS301',
                'name' => 'Data Structures and Algorithms',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor' => 'Ma. Sandy Mae Santa Rosa',
                'section' => 'CS3-1'
            ],
            [
                'code' => 'IT201',
                'name' => 'Web Development Fundamentals',
                'course' => 'BSIT',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor' => 'Ma. Sandy Mae Santa Rosa',
                'section' => 'IT2-1'
            ],
            [
                'code' => 'IS401',
                'name' => 'Systems Analysis and Design',
                'course' => 'BSIS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor' => 'Ma. Sandy Mae Santa Rosa',
                'section' => 'IS4-1'
            ],
            [
                'code' => 'GE101',
                'name' => 'Introduction to Computing',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'instructor' => 'Ma. Sandy Mae Santa Rosa',
                'section' => 'CS1-1'
            ]
        ];

        foreach ($subjects as $subjectData) {
            Subject::firstOrCreate(
                ['code' => $subjectData['code']],
                $subjectData
            );
        }

        // Create Demo Attendance Records (last 5 days) for student subjects
        $studentUsers = User::where('role', 'student')->get();

        foreach ($studentUsers as $student) {
            $studentSubjects = $student->getAllSubjects();
            if ($studentSubjects->isEmpty()) {
                $studentSubjects = Subject::limit(3)->get();
            }

            for ($i = 4; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                
                // Skip weekends for demo
                if ($date->isWeekend()) continue;

                foreach ($studentSubjects as $subject) {
                    $statuses = ['Present', 'Present', 'Present', 'Late', 'Absent'];
                    $status = $statuses[array_rand($statuses)];
                    
                    $timeIn = null;
                    if ($status !== 'Absent') {
                        $baseTime = $date->copy()->setTime(8, 0, 0);
                        if ($status === 'Late') {
                            $baseTime->addMinutes(rand(10, 30));
                        } else {
                            $baseTime->addMinutes(rand(-5, 10));
                        }
                        $timeIn = $baseTime;
                    }

                    $dateStr = $date->format('Y-m-d');

                    $exists = Attendance::where('user_id', $student->id)
                        ->where('subject_id', $subject->id)
                        ->whereDate('date', $dateStr)
                        ->exists();

                    if (!$exists) {
                        Attendance::create([
                            'user_id'      => $student->id,
                            'subject_id'   => $subject->id,
                            'subject_code' => $subject->code,
                            'date'         => $dateStr,
                            'status'       => $status,
                            'time_in'      => $timeIn,
                            'created_at'   => $date,
                            'updated_at'   => $date,
                        ]);
                    }
                }
            }
        }

        // Create Demo Holidays
        $holidays = [
            [
                'name' => 'Independence Day',
                'date' => Carbon::create(2024, 6, 12),
                'type' => 'national',
                'description' => 'Philippine Independence Day'
            ],
            [
                'name' => 'Christmas Day',
                'date' => Carbon::create(2024, 12, 25),
                'type' => 'national',
                'description' => 'Christmas Holiday'
            ],
            [
                'name' => 'Founder\'s Day',
                'date' => Carbon::create(2024, 9, 15),
                'type' => 'school',
                'description' => 'School Foundation Day'
            ]
        ];

        foreach ($holidays as $holidayData) {
            $holidayDateStr = $holidayData['date']->format('Y-m-d');
            $existingHoliday = Holiday::whereDate('date', $holidayDateStr)->first();

            if (!$existingHoliday) {
                Holiday::create([
                    'date'        => $holidayDateStr,
                    'name'        => $holidayData['name'],
                    'type'        => $holidayData['type'],
                    'description' => $holidayData['description'],
                    'is_active'   => true,
                    'created_by'  => $admin->id
                ]);
            }
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Admin: admin@gmail.com / password');
        $this->command->info('Teacher: jovelyn.patalinghug@teacher.com / password');
        $this->command->info('Parent: parent@example.com / password');
        $this->command->info('Student ID: 2310843 / password');

        // Create Demo Excuse Submissions
        $this->createDemoExcuses();
    }

    private function createDemoExcuses()
    {
        $students = User::where('role', 'student')->get();
        $absentAttendances = Attendance::where('status', 'Absent')->limit(5)->get();

        $excuses = [
            'Medical appointment',
            'Family emergency', 
            'Severe illness',
            'Transportation issues',
            'Personal matters'
        ];

        $descriptions = [
            'I had a scheduled medical check-up that I could not reschedule.',
            'There was an urgent family matter that required my immediate attention.',
            'I was suffering from high fever and could not attend class.',
            'Due to heavy rain, transportation services were suspended.',
            'I had to attend to important personal matters.'
        ];

        foreach ($absentAttendances as $index => $attendance) {
            if ($index < count($excuses)) {
                ExcuseSubmission::create([
                    'user_id' => $attendance->user_id,
                    'attendance_id' => $attendance->id,
                    'reason' => $excuses[$index],
                    'description' => $descriptions[$index],
                    'status' => $index === 0 ? 'pending' : ($index === 1 ? 'approved' : 'pending'),
                    'created_at' => $attendance->date,
                    'updated_at' => $attendance->date
                ]);
            }
        }

        $this->command->info('Demo excuse submissions created!');
    }
}