<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Schedule;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data (disable foreign key checks temporarily)
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schedule::truncate();
        Subject::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $subjects = [
            // 🔵 1ST YEAR - 1ST SEM
            ['code' => 'CC101', 'name' => 'Intro to Computing', 'year_level' => 1, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'CC102', 'name' => 'Fundamentals of Programming', 'year_level' => 1, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'CSE1', 'name' => 'Productivity Tools', 'year_level' => 1, 'semester' => 1, 'units' => 2, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'GE01', 'name' => 'Understanding the Self', 'year_level' => 1, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'NSTP01', 'name' => 'CWTS 1', 'year_level' => 1, 'semester' => 1, 'units' => 1, 'instructor' => 'Sgt. Mendoza', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '08:00', 'end' => '12:00']]],
            ['code' => 'PE03', 'name' => 'Sports', 'year_level' => 1, 'semester' => 1, 'units' => 2, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],

            // 🔵 1ST YEAR - 2ND SEM
            ['code' => 'CN101', 'name' => 'Computer Networks', 'year_level' => 1, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'DS102', 'name' => 'Data Structures', 'year_level' => 1, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'GE07', 'name' => 'Ethics in IT', 'year_level' => 1, 'semester' => 2, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'PE04', 'name' => 'Fitness and Wellness', 'year_level' => 1, 'semester' => 2, 'units' => 2, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],
            ['code' => 'GE04', 'name' => 'Art and Culture', 'year_level' => 1, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'NSTP03', 'name' => 'CWTS 3', 'year_level' => 1, 'semester' => 2, 'units' => 1, 'instructor' => 'Sgt. Mendoza', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '08:00', 'end' => '12:00']]],

            // 🟢 2ND YEAR - 1ST SEM
            ['code' => 'AL101', 'name' => 'Algorithms', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'CC105', 'name' => 'Information Management', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'DS101', 'name' => 'Discrete Structures 1', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'GE02', 'name' => 'Philippine History', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'GE03', 'name' => 'Contemporary World', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],
            ['code' => 'PE01', 'name' => 'Physical Education', 'year_level' => 2, 'semester' => 1, 'units' => 2, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'SDF104', 'name' => 'Object-Oriented Programming', 'year_level' => 2, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']]],

            // � 2ND YEAR - 2ND SEM
            ['code' => 'CN201', 'name' => 'Computer Networks', 'year_level' => 2, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'DS202', 'name' => 'Data Structures', 'year_level' => 2, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'GE07', 'name' => 'Ethics in IT', 'year_level' => 2, 'semester' => 2, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'PE04', 'name' => 'Fitness and Wellness', 'year_level' => 2, 'semester' => 2, 'units' => 2, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],
            ['code' => 'GE04', 'name' => 'Art and Culture', 'year_level' => 2, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'NSTP03', 'name' => 'CWTS 3', 'year_level' => 2, 'semester' => 2, 'units' => 1, 'instructor' => 'Sgt. Mendoza', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '08:00', 'end' => '12:00']]],

            // �🟣 3RD YEAR - 1ST SEM
            ['code' => 'AL102', 'name' => 'Automata Theory', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'AR101', 'name' => 'Architecture and Organization', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'CC106', 'name' => 'Application Development', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'GE4E', 'name' => 'Mathematics', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'MS101', 'name' => 'Modeling and Simulation', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],
            ['code' => 'SE101', 'name' => 'Software Engineering 1', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'WPD', 'name' => 'Web Programming', 'year_level' => 3, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']]],

            // 🟣 3RD YEAR - 2ND SEM
            ['code' => 'APS', 'name' => 'Statistics', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'GE11', 'name' => 'Panitikan', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'GV101', 'name' => 'Graphics', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'GE12', 'name' => 'Contemporary Literature', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'OS101', 'name' => 'Operating System', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],
            ['code' => 'SE102', 'name' => 'Software Engineering 2', 'year_level' => 3, 'semester' => 2, 'units' => 3, 'instructor' => 'Coach Torres', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '08:00', 'end' => '10:00']]],

            // 🔴 4TH YEAR - 1ST SEM
            ['code' => 'GE08', 'name' => 'Ethics', 'year_level' => 4, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'IAS101', 'name' => 'Security', 'year_level' => 4, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'NC101', 'name' => 'Networks', 'year_level' => 4, 'semester' => 1, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'SDM', 'name' => 'Software Deployment', 'year_level' => 4, 'semester' => 1, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'THS103', 'name' => 'Thesis 2', 'year_level' => 4, 'semester' => 1, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']]],

            // 🔴 4TH YEAR - 2ND SEM
            ['code' => 'GE10', 'name' => 'Pagpapahayag', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Santos', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '08:00', 'end' => '10:00'], ['day' => 'Wednesday', 'start' => '08:00', 'end' => '10:00']]],
            ['code' => 'GE09', 'name' => 'Rizal Life', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Reyes', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '10:00', 'end' => '12:00'], ['day' => 'Thursday', 'start' => '10:00', 'end' => '12:00']]],
            ['code' => 'IS101', 'name' => 'Intelligent Systems', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Dr. Garcia', 'section' => 'A', 'schedules' => [['day' => 'Friday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'PD101', 'name' => 'Parallel and Distributed', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Ms. Cruz', 'section' => 'A', 'schedules' => [['day' => 'Monday', 'start' => '13:00', 'end' => '15:00'], ['day' => 'Wednesday', 'start' => '13:00', 'end' => '15:00']]],
            ['code' => 'SP101', 'name' => 'Social Issues', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Saturday', 'start' => '10:00', 'end' => '14:00']]],
            ['code' => 'THS102', 'name' => 'Thesis Writing 1', 'year_level' => 4, 'semester' => 2, 'units' => 3, 'instructor' => 'Prof. Lopez', 'section' => 'A', 'schedules' => [['day' => 'Tuesday', 'start' => '14:00', 'end' => '16:00'], ['day' => 'Thursday', 'start' => '14:00', 'end' => '16:00']]],

            // Add more subjects as needed...
        ];

        foreach ($subjects as $subjectData) {
            $schedules = $subjectData['schedules'];
            unset($subjectData['schedules']);

            $subject = Subject::create($subjectData);

            foreach ($schedules as $schedule) {
                Schedule::create([
                    'subject_id' => $subject->id,
                    'day' => $schedule['day'],
                    'start_time' => $schedule['start'],
                    'end_time' => $schedule['end'],
                ]);
            }
        }
    }
}