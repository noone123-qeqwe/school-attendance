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
        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@school.edu'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Create Teacher User
        $teacher = User::firstOrCreate(
            ['email' => 'sandy.rosa@school.edu'],
            [
                'name' => 'Ma. Sandy Mae Santa Rosa',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
                'employee_id' => 'T-2024-003',
                'department' => 'Computer Science',
                'position' => 'Instructor',
                'specialization' => 'Software Development'
            ]
        );

        User::firstOrCreate(
            ['email' => 'joel.salidaga@school.edu'],
            [
                'name' => 'Joel Salidaga',
                'password' => Hash::make('teacher123'),
                'role' => 'teacher',
                'employee_id' => 'T-2024-004',
                'department' => 'Education',
                'position' => 'Teacher',
                'specialization' => 'Attendance Management'
            ]
        );

        // Create Demo Students
        $students = [
            [
                'name' => 'John Michael Santos',
                'email' => 'john.santos@student.edu',
                'student_number' => '2024001',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1
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
            User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'name' => $studentData['name'],
                    'student_number' => $studentData['student_number'],
                    'course' => $studentData['course'],
                    'year_level' => $studentData['year_level'],
                    'semester' => $studentData['semester'],
                    'password' => Hash::make('student123'),
                    'role' => 'student'
                ]
            );
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

        // Create Demo Attendance Records (last 7 days)
        $studentUsers = User::where('role', 'student')->get();
        $subjectCodes = Subject::pluck('code');

        foreach ($studentUsers as $student) {
            for ($i = 6; $i >= 0; $i--) {
                $date = Carbon::today()->subDays($i);
                
                // Skip weekends for demo
                if ($date->isWeekend()) continue;

                foreach ($subjectCodes as $subjectCode) {
                    // Random attendance pattern for demo
                    $statuses = ['Present', 'Present', 'Present', 'Late', 'Absent']; // Mostly present
                    $status = $statuses[array_rand($statuses)];
                    
                    $timeIn = null;
                    if ($status !== 'Absent') {
                        $baseTime = $date->copy()->setTime(8, 0, 0); // 8:00 AM base
                        if ($status === 'Late') {
                            $baseTime->addMinutes(rand(10, 30)); // 10-30 minutes late
                        } else {
                            $baseTime->addMinutes(rand(-5, 10)); // On time or slightly early/late
                        }
                        $timeIn = $baseTime;
                    }

                    Attendance::firstOrCreate([
                        'user_id' => $student->id,
                        'subject_code' => $subjectCode,
                        'date' => $date->format('Y-m-d')
                    ], [
                        'status' => $status,
                        'time_in' => $timeIn,
                        'created_at' => $date,
                        'updated_at' => $date
                    ]);
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
            Holiday::firstOrCreate(
                ['date' => $holidayData['date']->format('Y-m-d')],
                [
                    'name' => $holidayData['name'],
                    'type' => $holidayData['type'],
                    'description' => $holidayData['description'],
                    'is_active' => true,
                    'created_by' => $admin->id
                ]
            );
        }

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Admin: admin@school.edu / admin123');
        $this->command->info('Teacher: sandy.rosa@school.edu / password123');
        $this->command->info('Students: john.santos@student.edu / student123 (and others)');

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