<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_has_explicit_role_and_reseeding_preserves_credentials_and_status(): void
    {
        config()->set('auth.seed_admin_password', 'ConfiguredInitialPassword!');

        $this->seed(AdminSeeder::class);
        $admin = User::where('email', 'admin@osmena.edu')->firstOrFail();

        $this->assertSame('super_admin', $admin->admin_sub_role);
        $this->assertTrue((bool) $admin->must_change_password);
        $this->assertTrue(Hash::check('ConfiguredInitialPassword!', $admin->password));

        $admin->update([
            'password' => Hash::make('ChangedPassword!'),
            'is_active' => false,
            'admin_sub_role' => 'auditor',
        ]);
        $this->seed(AdminSeeder::class);

        $admin->refresh();
        $this->assertFalse($admin->is_active);
        $this->assertSame('auditor', $admin->admin_sub_role);
        $this->assertTrue(Hash::check('ChangedPassword!', $admin->password));
    }
}
