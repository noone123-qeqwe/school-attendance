<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local')) {
            throw new \LogicException('DatabaseSeeder contains demo and roster data and can only be run locally.');
        }

        $this->call([
            AcademicStructureSeeder::class,
            AdminSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            ParentSeeder::class,
            SubjectSeeder::class,
            AttendanceSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
