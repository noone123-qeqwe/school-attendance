<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing schedules, subjects, and enrollments
        Schema::disableForeignKeyConstraints();
        DB::table('enrollments')->truncate();
        Schedule::truncate();
        Subject::truncate();
        Schema::enableForeignKeyConstraints();

        // Retrieve instructors from the database
        $jovelyn  = User::where('email', 'jovelyn.patalinghug@teacher.com')->first();
        $sandyMae = User::where('email', 'sandymae.santarosa@school.edu')->first();
        $joel     = User::where('email', 'joel.salidaga@school.edu')->first();
        $fallback = User::where('role', 'teacher')->first();

        $tJovelyn  = $jovelyn ?? $fallback;
        $tSandyMae = $sandyMae ?? $fallback;
        $tJoel     = $joel ?? $fallback;

        $subjects = [
            // ══════════════════════════════════════════════════════════════
            // 🔵 1ST YEAR - 1ST SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'CC101',
                'name' => 'Introduction to Computing',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'CC102',
                'name' => 'Fundamentals of Programming',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'CSE1',
                'name' => 'Productivity Tools and Office Applications',
                'course' => null, // common
                'year_level' => 1,
                'semester' => 1,
                'units' => 2,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'GE01',
                'name' => 'Understanding the Self',
                'course' => null, // common GE
                'year_level' => 1,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'NSTP01',
                'name' => 'National Service Training Program 1 (CWTS)',
                'course' => null,
                'year_level' => 1,
                'semester' => 1,
                'units' => 1,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '08:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'PE03',
                'name' => 'Individual and Dual Sports',
                'course' => null,
                'year_level' => 1,
                'semester' => 1,
                'units' => 2,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🔵 1ST YEAR - 2ND SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'CN101',
                'name' => 'Computer Networks & Telecommunications',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'DS102',
                'name' => 'Data Structures and Algorithms',
                'course' => 'BSCS',
                'year_level' => 1,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'GE07',
                'name' => 'Ethics in Information Technology',
                'course' => null,
                'year_level' => 1,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'PE04',
                'name' => 'Fitness and Wellness Exercise',
                'course' => null,
                'year_level' => 1,
                'semester' => 2,
                'units' => 2,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],
            [
                'code' => 'GE04',
                'name' => 'Art Appreciation and Philippine Culture',
                'course' => null,
                'year_level' => 1,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'NSTP03',
                'name' => 'National Service Training Program 2',
                'course' => null,
                'year_level' => 1,
                'semester' => 2,
                'units' => 1,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '08:00', 'end' => '12:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🟢 2ND YEAR - 1ST SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'AL101',
                'name' => 'Design and Analysis of Algorithms',
                'course' => 'BSCS',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'CC105',
                'name' => 'Information Management & Databases',
                'course' => 'BSCS',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'IT201',
                'name' => 'Web Development Fundamentals',
                'course' => 'BSIT',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Wednesday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'DS101',
                'name' => 'Discrete Structures 1',
                'course' => 'BSCS',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'GE02',
                'name' => 'Readings in Philippine History',
                'course' => null,
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'GE03',
                'name' => 'The Contemporary World',
                'course' => null,
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],
            [
                'code' => 'PE01',
                'name' => 'Physical Fitness and Movement Activities',
                'course' => null,
                'year_level' => 2,
                'semester' => 1,
                'units' => 2,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'SDF104',
                'name' => 'Object-Oriented Programming (Java/C++)',
                'course' => 'BSCS',
                'year_level' => 2,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🟢 2ND YEAR - 2ND SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'CN201',
                'name' => 'Advanced Routing and Switching',
                'course' => 'BSIT',
                'year_level' => 2,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'DS202',
                'name' => 'Advanced Data Structures & Algorithms',
                'course' => 'BSCS',
                'year_level' => 2,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🟣 3RD YEAR - 1ST SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'CS301',
                'name' => 'Data Structures and Algorithms Analysis',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'AL102',
                'name' => 'Automata Theory and Formal Languages',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Wednesday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'AR101',
                'name' => 'Computer Architecture and Organization',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'CC106',
                'name' => 'Application Development and Emerging Technologies',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'GE4E',
                'name' => 'Mathematics in the Modern World',
                'course' => null,
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'MS101',
                'name' => 'Modeling and Simulation',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],
            [
                'code' => 'SE101',
                'name' => 'Software Engineering 1',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'WPD',
                'name' => 'Web Programming & Design Frameworks',
                'course' => 'BSIT',
                'year_level' => 3,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🟣 3RD YEAR - 2ND SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'APS',
                'name' => 'Applied Statistics for Computing',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'GE11',
                'name' => 'Masining na Pagpapahayag at Panitikan',
                'course' => null,
                'year_level' => 3,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'GV101',
                'name' => 'Computer Graphics & Visual Computing',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tSandyMae,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'OS101',
                'name' => 'Operating Systems Concepts & Architecture',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],
            [
                'code' => 'SE102',
                'name' => 'Software Engineering 2 (Project Management)',
                'course' => 'BSCS',
                'year_level' => 3,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🔴 4TH YEAR - 1ST SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'IS401',
                'name' => 'Systems Analysis and Design',
                'course' => 'BSIS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'GE08',
                'name' => 'Professional Ethics & Accountability',
                'course' => null,
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Wednesday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'IAS101',
                'name' => 'Information Assurance and Security',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'NC101',
                'name' => 'Network Administration and Security',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'SDM',
                'name' => 'Software Deployment & CI/CD Pipelines',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'THS103',
                'name' => 'Capstone Project & Thesis 2',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 1,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']
                ]
            ],

            // ══════════════════════════════════════════════════════════════
            // 🔴 4TH YEAR - 2ND SEMESTER
            // ══════════════════════════════════════════════════════════════
            [
                'code' => 'GE10',
                'name' => 'Retorika at Masining na Pagpapahayag',
                'course' => null,
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'],
                    ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']
                ]
            ],
            [
                'code' => 'GE09',
                'name' => 'Life and Works of Dr. Jose Rizal',
                'course' => null,
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'],
                    ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']
                ]
            ],
            [
                'code' => 'IS101',
                'name' => 'Intelligent Systems and Machine Learning',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'PD101',
                'name' => 'Parallel and Distributed Computing',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'],
                    ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']
                ]
            ],
            [
                'code' => 'SP101',
                'name' => 'Social and Professional Issues in Computing',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJoel,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']
                ]
            ],
            [
                'code' => 'THS102',
                'name' => 'Thesis Writing & Research Colloquium',
                'course' => 'BSCS',
                'year_level' => 4,
                'semester' => 2,
                'units' => 3,
                'instructor_user' => $tJovelyn,
                'section' => 'A',
                'schedules' => [
                    ['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'],
                    ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']
                ]
            ],
        ];

        $createdSubjects = collect();

        foreach ($subjects as $subjectData) {
            $schedules = $subjectData['schedules'];
            unset($subjectData['schedules']);

            $instructorUser = $subjectData['instructor_user'] ?? null;
            unset($subjectData['instructor_user']);

            $subjectData['instructor_id'] = $instructorUser ? $instructorUser->id : null;
            $subjectData['instructor']    = $instructorUser ? $instructorUser->name : 'Unassigned';

            $subject = Subject::create($subjectData);
            $createdSubjects->push($subject);

            foreach ($schedules as $schedule) {
                Schedule::create([
                    'subject_id' => $subject->id,
                    'day'        => $schedule['day'],
                    'start_time' => $schedule['start'],
                    'end_time'   => $schedule['end'],
                ]);
            }
        }

        // ══════════════════════════════════════════════════════════════
        // 🎓 STUDENT ENROLLMENT
        // ══════════════════════════════════════════════════════════════
        $students = User::where('role', 'student')->get();
        $enrollmentRows = [];
        $now = now();

        foreach ($students as $student) {
            $studentYear = (int)$student->year_level;
            $studentSem  = (int)$student->semester;

            // Find curriculum subjects matching year level and semester
            $matchingSubjects = $createdSubjects->filter(function ($s) use ($student, $studentYear, $studentSem) {
                $yearMatch = (int)$s->year_level === $studentYear;
                $semMatch  = (int)$s->semester === $studentSem;
                $courseMatch = empty($s->course) || $s->course === $student->course;
                $sectionMatch = empty($s->section) || empty($student->section) || $s->section === $student->section;

                return $yearMatch && $semMatch && $courseMatch && $sectionMatch;
            });

            foreach ($matchingSubjects as $subj) {
                $enrollmentRows[] = [
                    'user_id'    => $student->id,
                    'subject_id' => $subj->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($enrollmentRows)) {
            DB::table('enrollments')->insertOrIgnore($enrollmentRows);
        }

        $this->command->info("SubjectSeeder finished: " . count($subjects) . " subjects created with schedules and " . count($enrollmentRows) . " enrollments mapped.");
    }
}