<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Avoid duplicate if already exists
        if (!User::where('email', 'admin@osmena.edu')->exists()) {
            User::create([
                'name'           => 'Admin Teacher',
                'student_number' => '0000000',
                'course'         => 'BSCS',
                'year_level'     => '1',
                'semester'       => '1',
                'email'          => 'admin@osmena.edu',
                'password'       => Hash::make('Admin@1234'),
                'role'           => 'admin',
            ]);

            echo "Admin account created.\n";
            echo "Email:    admin@osmena.edu\n";
            echo "Password: Admin@1234\n";
        } else {
            echo "Admin account already exists.\n";
        }
    }
}
