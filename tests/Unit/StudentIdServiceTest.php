<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\StudentIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIdServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_sequential_unique_ids_using_the_two_digit_year_prefix(): void
    {
        User::factory()->create(['student_number' => '2600001']);
        $service = app(StudentIdService::class);

        $this->assertSame('2600002', $service->generateNextId('2026'));
        $this->assertSame('2600003', $service->generateNextId('2026'));
        $this->assertSame('2600012', $service->formatStudentId('2026', 12));
        $this->assertSame('2600004', $service->previewNextId('2026'));
    }
}
