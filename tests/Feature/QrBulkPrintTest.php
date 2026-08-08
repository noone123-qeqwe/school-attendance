<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrBulkPrintTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_bulk_print_qr_codes()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $students = User::factory(3)->create(['role' => 'student']);

        $response = $this->actingAs($admin)->withSession(['admin_2fa_verified' => true])->post(route('admin.qr.bulk-print'), [
            'student_ids' => $students->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
