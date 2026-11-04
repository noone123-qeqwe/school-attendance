<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentApiAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_attendance_api_returns_a_curated_paginated_resource(): void
    {
        [$parent, $child] = $this->linkedParentAndChild();

        Attendance::create([
            'user_id' => $child->id,
            'subject_code' => 'CS101',
            'subject_name' => 'Introduction to Computing',
            'status' => 'Present',
            'excused' => false,
            'date' => '2026-11-04',
            'time_in' => '08:15:00',
            'latitude' => 14.5995123,
            'longitude' => 120.9842221,
            'gps_accuracy' => 3.5,
        ]);

        $response = $this->actingAs($parent, 'sanctum')
            ->getJson("/api/parent/child/{$child->id}?per_page=1&date_to=2026-11-04");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.per_page', 1)
            ->assertJsonPath('data.data.0.subject_code', 'CS101')
            ->assertJsonPath('data.data.0.subject_name', 'Introduction to Computing')
            ->assertJsonPath('data.data.0.status', 'Present')
            ->assertJsonMissingPath('data.data.0.latitude')
            ->assertJsonMissingPath('data.data.0.longitude')
            ->assertJsonMissingPath('data.data.0.gps_accuracy')
            ->assertJsonMissingPath('data.data.0.user_id');
    }

    public function test_parent_attendance_api_rejects_invalid_filters_and_unbounded_pages(): void
    {
        [$parent, $child] = $this->linkedParentAndChild();

        $this->actingAs($parent, 'sanctum')
            ->getJson("/api/parent/child/{$child->id}?status=Unknown")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->actingAs($parent, 'sanctum')
            ->getJson("/api/parent/child/{$child->id}?per_page=101")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('per_page');

        $this->actingAs($parent, 'sanctum')
            ->getJson("/api/parent/child/{$child->id}?date_from=2026-11-10&date_to=2026-11-01")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('date_to');
    }

    public function test_parent_cannot_read_an_unlinked_child_attendance_resource(): void
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'student_number' => null,
            'course' => null,
            'year_level' => null,
            'semester' => null,
        ]);
        $child = User::factory()->create();

        $this->actingAs($parent, 'sanctum')
            ->getJson("/api/parent/child/{$child->id}")
            ->assertForbidden();
    }

    /** @return array{User, User} */
    private function linkedParentAndChild(): array
    {
        $parent = User::factory()->create([
            'role' => 'parent',
            'student_number' => null,
            'course' => null,
            'year_level' => null,
            'semester' => null,
        ]);
        $child = User::factory()->create();
        $parent->children()->attach($child->id);

        return [$parent, $child];
    }
}
