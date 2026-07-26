<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if there is at least one student
        $student = User::where('role', 'student')->first();

        if (!$student) {
            $this->command->info('No student found. Create a student first.');
            return;
        }

        // Create a test parent
        $parent = User::firstOrCreate(
            ['email' => 'parent@example.com'],
            [
                'name' => 'John Doe (Parent)',
                'password' => Hash::make('password'),
                'role' => 'parent',
            ]
        );

        // Link the parent to the student
        if (!$parent->children()->where('student_id', $student->id)->exists()) {
            $parent->children()->attach($student->id);
            $this->command->info("Parent (parent@example.com) linked to Student ({$student->name}).");
        } else {
            $this->command->info("Parent already linked to Student ({$student->name}).");
        }
        
        $this->command->info("Parent credentials: parent@example.com / password");
    }
}
