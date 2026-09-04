<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withTrashed()->where('email', 'admin@osmena.edu')->first();

        $attributes = [
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
            $admin->update($attributes);
            $this->command->info('Official Admin account synced: admin@osmena.edu / Admin@1234');
        } else {
            User::create($attributes);
            $this->command->info('Official Admin account created: admin@osmena.edu / Admin@1234');
        }
    }
}
