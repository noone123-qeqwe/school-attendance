<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::withTrashed()->where('email', 'admin@osmena.edu')->first();

        if ($admin) {
            $this->command?->info('Administrator account already exists; credentials and status preserved.');
            return;
        }

        $configuredPassword = config('auth.seed_admin_password');
        $password = $configuredPassword;
        if (!$password && app()->environment('production')) {
            throw new \RuntimeException('Set SEED_ADMIN_PASSWORD before creating the initial administrator.');
        }
        $password ??= Str::random(32);

        $attributes = [
            'name'              => 'System Administrator',
            'email'             => 'admin@osmena.edu',
            'role'              => 'admin',
            'admin_sub_role'    => 'super_admin',
            'department'        => 'College of Computer Studies',
            'phone'             => '09171234567',
            'password'          => Hash::make($password),
            'must_change_password' => true,
            'email_verified_at' => now(),
            'is_active'         => true,
        ];

        User::create($attributes);
        $this->command?->info('Initial administrator created: admin@osmena.edu');
        if (!$configuredPassword) {
            $this->command?->warn('One-time local administrator password: '.$password);
        }
    }
}
