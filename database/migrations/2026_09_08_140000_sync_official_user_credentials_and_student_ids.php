<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Ensures all official student numbers (e.g. 0703250), emails, passwords,
     * and roles are fully synchronized in production databases across environments.
     */
    public function up(): void
    {
        if (app()->environment('testing')) {
            return;
        }

        // 1. Sync official students
        $csvPath = database_path('data/students.csv');
        if (File::exists($csvPath)) {
            $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($lines)) {
                $header = str_getcsv(array_shift($lines));
                $header = array_map('trim', $header);

                foreach ($lines as $line) {
                    $row = str_getcsv($line);
                    if (count($row) < count($header)) {
                        continue;
                    }

                    $data = array_combine($header, array_map('trim', $row));
                    if (empty($data['email'])) {
                        continue;
                    }

                    $email = strtolower($data['email']);
                    $studentNumber = !empty($data['student_number']) ? (string)$data['student_number'] : null;

                    $existing = User::withTrashed()
                        ->where(function ($q) use ($studentNumber, $email) {
                            if ($studentNumber !== null) {
                                $q->where('student_number', $studentNumber);
                            }
                            $q->orWhere('email', $email);
                        })
                        ->first();

                    $attributes = [
                        'name'              => $data['name'] ?? 'Student',
                        'email'             => $email,
                        'student_number'    => $studentNumber,
                        'course'            => $data['course'] ?? 'BSCS',
                        'year_level'        => !empty($data['year_level']) ? (int)$data['year_level'] : 4,
                        'semester'          => !empty($data['semester']) ? (int)$data['semester'] : 1,
                        'section'           => $data['section'] ?? 'A',
                        'role'              => 'student',
                        'is_active'         => true,
                        'email_verified_at' => now(),
                    ];

                    if (!empty($data['password'])) {
                        $attributes['password'] = Hash::make($data['password']);
                    }

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update($attributes);
                    } else {
                        if (empty($attributes['password'])) {
                            $attributes['password'] = Hash::make('student123');
                        }
                        User::create($attributes);
                    }
                }
            }
        }

        // 2. Sync official teachers
        $teachersCsv = database_path('data/teachers.csv');
        if (File::exists($teachersCsv)) {
            $lines = file($teachersCsv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (!empty($lines)) {
                $header = str_getcsv(array_shift($lines));
                $header = array_map('trim', $header);

                foreach ($lines as $line) {
                    $row = str_getcsv($line);
                    if (count($row) < count($header)) {
                        continue;
                    }

                    $data = array_combine($header, array_map('trim', $row));
                    if (empty($data['email'])) {
                        continue;
                    }

                    $email = strtolower($data['email']);
                    $empId = !empty($data['employee_id']) ? (string)$data['employee_id'] : null;

                    $existing = User::withTrashed()
                        ->where(function ($q) use ($empId, $email) {
                            if ($empId !== null) {
                                $q->where('employee_id', $empId);
                            }
                            $q->orWhere('email', $email);
                        })
                        ->first();

                    $attributes = [
                        'name'              => $data['name'] ?? 'Instructor',
                        'email'             => $email,
                        'employee_id'       => $empId,
                        'department'        => $data['department'] ?? 'Computer Science',
                        'position'          => $data['position'] ?? 'Instructor',
                        'specialization'    => $data['specialization'] ?? null,
                        'role'              => 'teacher',
                        'is_active'         => true,
                        'email_verified_at' => now(),
                    ];

                    if (!empty($data['password'])) {
                        $attributes['password'] = Hash::make($data['password']);
                    }

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $existing->update($attributes);
                    } else {
                        if (empty($attributes['password'])) {
                            $attributes['password'] = Hash::make('teacher123');
                        }
                        User::create($attributes);
                    }
                }
            }
        }

        // 3. Ensure official System Administrator account
        $admin = User::withTrashed()->where('email', 'admin@osmena.edu')->first();
        $adminAttrs = [
            'name'              => 'System Administrator',
            'email'             => 'admin@osmena.edu',
            'role'              => 'admin',
            'department'        => 'College of Computer Studies',
            'phone'             => '09171234567',
            'password'          => Hash::make('Admin@1234'),
            'email_verified_at' => now(),
            'is_active'         => true,
        ];

        if ($admin) {
            if ($admin->trashed()) {
                $admin->restore();
            }
            $admin->update($adminAttrs);
        } else {
            User::create($adminAttrs);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
