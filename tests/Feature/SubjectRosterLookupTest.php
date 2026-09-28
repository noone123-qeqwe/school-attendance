<?php

namespace Tests\Feature;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectRosterLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_roster_lookup_accepts_explicit_and_exact_cohort_members_only(): void
    {
        $subject = Subject::factory()->create([
            'year_level' => 2,
            'semester' => 1,
            'course' => 'BSCS',
            'section' => 'A',
        ]);
        $implicit = User::factory()->create([
            'role' => 'student', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'A',
        ]);
        $explicit = User::factory()->create([
            'role' => 'student', 'year_level' => 3, 'semester' => 2,
            'course' => 'BSIT', 'section' => 'B',
        ]);
        $otherSection = User::factory()->create([
            'role' => 'student', 'year_level' => 2, 'semester' => 1,
            'course' => 'BSCS', 'section' => 'B',
        ]);
        $subject->enrolledStudents()->attach($explicit->id);

        $this->assertTrue($subject->hasStudent($implicit));
        $this->assertTrue($subject->hasStudent($explicit));
        $this->assertFalse($subject->hasStudent($otherSection));
    }
}
