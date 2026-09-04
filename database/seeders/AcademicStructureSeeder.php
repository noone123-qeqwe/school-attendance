<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Models\Department;
use App\Models\Course;
use App\Models\Section;

class AcademicStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Academic Year & Semester
        AcademicYear::query()->update(['is_current' => false]);
        AcademicYear::updateOrCreate(
            ['name' => '2026-2027', 'semester' => 1],
            [
                'start_date' => '2026-08-01',
                'end_date'   => '2026-12-20',
                'is_current' => true,
            ]
        );

        // 2. Core Settings
        Setting::updateOrCreate(['key' => 'late_threshold'], ['value' => '15']);
        Setting::updateOrCreate(['key' => 'academic_year'], ['value' => '2026-2027']);
        Setting::updateOrCreate(['key' => 'current_semester'], ['value' => '1']);

        // 3. Departments
        $departments = [
            [
                'name' => 'College of Computer Studies',
                'code' => 'CCS',
                'description' => 'Department of Computing, Information Technology, and Computer Science.',
            ],
            [
                'name' => 'College of Business and Accountancy',
                'code' => 'CBA',
                'description' => 'Department of Business Administration, Marketing, and Accountancy.',
            ],
            [
                'name' => 'College of Arts and Sciences',
                'code' => 'CAS',
                'description' => 'Department of Liberal Arts, Humanities, and General Education.',
            ],
            [
                'name' => 'College of Education',
                'code' => 'CED',
                'description' => 'Department of Teacher Education and Pedagogical Studies.',
            ],
            [
                'name' => 'College of Criminal Justice Education',
                'code' => 'CCJE',
                'description' => 'Department of Criminology and Law Enforcement Administration.',
            ],
        ];

        $deptModels = [];
        foreach ($departments as $dept) {
            $deptModels[$dept['code']] = Department::updateOrCreate(
                ['code' => $dept['code']],
                ['name' => $dept['name'], 'description' => $dept['description']]
            );
        }

        // 4. Courses
        $ccsId = $deptModels['CCS']->id;
        $courses = [
            [
                'department_id' => $ccsId,
                'name' => 'Bachelor of Science in Computer Science',
                'code' => 'BSCS',
                'description' => 'Study of computing concepts, algorithms, data structures, and software development.',
            ],
            [
                'department_id' => $ccsId,
                'name' => 'Bachelor of Science in Information Technology',
                'code' => 'BSIT',
                'description' => 'Focus on software utilization, systems administration, and computer networks.',
            ],
            [
                'department_id' => $ccsId,
                'name' => 'Bachelor of Science in Information Systems',
                'code' => 'BSIS',
                'description' => 'Integration of information technology solutions with enterprise business processes.',
            ],
        ];

        $courseModels = [];
        foreach ($courses as $c) {
            $courseModels[$c['code']] = Course::updateOrCreate(
                ['code' => $c['code']],
                [
                    'department_id' => $c['department_id'],
                    'name' => $c['name'],
                    'description' => $c['description'],
                ]
            );
        }

        // 5. Sections
        $sections = [
            // BSCS
            ['course_id' => $courseModels['BSCS']->id, 'name' => 'BSCS-1A', 'year_level' => 1],
            ['course_id' => $courseModels['BSCS']->id, 'name' => 'BSCS-1B', 'year_level' => 1],
            ['course_id' => $courseModels['BSCS']->id, 'name' => 'BSCS-2A', 'year_level' => 2],
            ['course_id' => $courseModels['BSCS']->id, 'name' => 'BSCS-3A', 'year_level' => 3],
            ['course_id' => $courseModels['BSCS']->id, 'name' => 'BSCS-4A', 'year_level' => 4],

            // BSIT
            ['course_id' => $courseModels['BSIT']->id, 'name' => 'BSIT-1A', 'year_level' => 1],
            ['course_id' => $courseModels['BSIT']->id, 'name' => 'BSIT-2A', 'year_level' => 2],
            ['course_id' => $courseModels['BSIT']->id, 'name' => 'BSIT-3A', 'year_level' => 3],
            ['course_id' => $courseModels['BSIT']->id, 'name' => 'BSIT-4A', 'year_level' => 4],

            // BSIS
            ['course_id' => $courseModels['BSIS']->id, 'name' => 'BSIS-1A', 'year_level' => 1],
            ['course_id' => $courseModels['BSIS']->id, 'name' => 'BSIS-2A', 'year_level' => 2],
            ['course_id' => $courseModels['BSIS']->id, 'name' => 'BSIS-3A', 'year_level' => 3],
            ['course_id' => $courseModels['BSIS']->id, 'name' => 'BSIS-4A', 'year_level' => 4],
        ];

        foreach ($sections as $sec) {
            Section::updateOrCreate(
                ['course_id' => $sec['course_id'], 'name' => $sec['name']],
                ['year_level' => $sec['year_level']]
            );
        }

        $this->command->info('AcademicStructureSeeder finished: Academic Year, Settings, Departments, Courses, and Sections seeded.');
    }
}
