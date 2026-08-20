<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class TeacherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = database_path('data/teachers.csv');

        if (!File::exists($csvPath)) {
            $this->command->warn("Teachers CSV file not found at: {$csvPath}");
            return;
        }

        $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($lines)) {
            $this->command->warn("Teachers CSV file is empty.");
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

            $existing = User::withTrashed()
                ->where(function ($q) use ($data) {
                    if (!empty($data['employee_id'])) {
                        $q->where('employee_id', $data['employee_id']);
                    }
                    if (!empty($data['email'])) {
                        $q->orWhere('email', strtolower($data['email']));
                    }
                })
                ->first();

            $attributes = [
                'name'              => $data['name'] ?? 'Instructor',
                'email'             => strtolower($data['email']),
                'employee_id'       => $data['employee_id'] ?? null,
                'department'        => $data['department'] ?? null,
                'position'          => $data['position'] ?? 'Instructor',
                'specialization'    => $data['specialization'] ?? null,
                'role'              => 'teacher',
                'email_verified_at' => now(),
            ];

            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($attributes);
                $updatedCount++;
            } else {
                $attributes['password'] = Hash::make($data['password'] ?? 'teacher123');
                User::create($attributes);
                $createdCount++;
            }
        }

        $this->command->info("TeacherSeeder finished: {$createdCount} created, {$updatedCount} updated/synced.");
    }
}
