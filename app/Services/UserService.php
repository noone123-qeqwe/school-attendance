<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Create a new student.
     */
    public function createStudent(array $data)
    {
        $studentNumber = !empty($data['student_number'])
            ? trim($data['student_number'])
            : User::generateStudentNumber();

        return User::create([
            'name' => trim($data['name']),
            'student_number' => $studentNumber,
            'course' => $data['course'],
            'year_level' => (int) $data['year_level'],
            'semester' => (int) $data['semester'],
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'email_verified_at' => now(),
        ]);
    }
}
