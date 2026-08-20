<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/students.csv');

        if (!File::exists($csvPath)) {
            $this->command->warn("Students CSV file not found at: {$csvPath}");
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            $this->command->warn("Students CSV file is empty.");
            return;
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map('trim', $header);

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < count($header)) {
                continue;
            }

            $data = array_combine($header, array_map('trim', $row));

            if (empty($data['email'])) {
                continue;
            }

            $lookup = [];
            if (!empty($data['student_number'])) {
                $lookup['student_number'] = $data['student_number'];
            } else {
                $lookup['email'] = strtolower($data['email']);
            }

            $existing = User::withTrashed()
                ->where(function ($q) use ($data) {
                    if (!empty($data['student_number'])) {
                        $q->where('student_number', $data['student_number']);
                    }
                    if (!empty($data['email'])) {
                        $q->orWhere('email', strtolower($data['email']));
                    }
                })
                ->first();

            $attributes = [
                'name' => $data['name'] ?? 'Student',
                'email' => strtolower($data['email']),
                'student_number' => $data['student_number'] ?? null,
                'course' => $data['course'] ?? null,
                'year_level' => !empty($data['year_level']) ? (int)$data['year_level'] : null,
                'semester' => !empty($data['semester']) ? (int)$data['semester'] : null,
                'section' => $data['section'] ?? 'A',
                'role' => 'student',
                'email_verified_at' => now(),
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attributes);
                $updatedCount++;
            } else {
                $attributes['password'] = Hash::make($data['password'] ?? 'student123');
                User::create($attributes);
                $createdCount++;
            }
        }

        $this->command->info("StudentSeeder finished: {$createdCount} created, {$updatedCount} updated/synced.");
    }
}
