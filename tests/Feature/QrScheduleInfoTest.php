<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrScheduleInfoTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_info_validates_input_and_teacher_ownership(): void
    {
        $owner = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        Subject::create([
            'code' => 'AUDIT101',
            'name' => 'Audit Subject',
            'instructor_id' => $owner->id,
            'year_level' => 1,
            'semester' => 1,
        ]);

        $this->actingAs($owner)->get(route('teacher.qr.schedule'))
            ->assertStatus(422)->assertJsonStructure(['error']);
        $this->get(route('teacher.qr.schedule', ['subject_code' => 'MISSING']))
            ->assertNotFound()->assertJsonStructure(['error']);
        $this->actingAs($otherTeacher)
            ->get(route('teacher.qr.schedule', ['subject_code' => 'AUDIT101']))
            ->assertForbidden()->assertJsonStructure(['error']);
        $this->actingAs($owner)
            ->get(route('teacher.qr.schedule', ['subject_code' => 'AUDIT101']))
            ->assertOk()->assertJson(['has_schedule' => false]);
    }
}
