<?php

namespace Database\Factories;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->lexify('SUBJ-???'),
            'name' => $this->faker->words(3, true),
            'year_level' => $this->faker->numberBetween(1, 4) . 'th Year',
            'semester' => '1st',
            'units' => 3,
            'section' => 'A',
            'instructor_id' => User::factory()->create(['role' => 'teacher'])->id,
        ];
    }
}
