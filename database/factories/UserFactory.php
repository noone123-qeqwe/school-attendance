<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state (student by default).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'student_number' => str_pad(fake()->unique()->numberBetween(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'course' => fake()->randomElement(['BSCS', 'BSIT', 'BSIS']),
            'year_level' => fake()->numberBetween(1, 4),
            'semester' => fake()->randomElement([1, 2]),
        ];
    }

    /**
     * Configure the model as a teacher.
     */
    public function teacher(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'teacher',
            'student_number' => null,
            'course' => null,
            'year_level' => null,
            'semester' => null,
            'employee_id' => 'EMP-' . fake()->unique()->numberBetween(1000, 9999),
            'department' => fake()->randomElement(['Computer Science', 'Information Technology', 'Mathematics']),
            'position' => 'Assistant Professor',
        ]);
    }

    /**
     * Configure the model as an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
            'student_number' => null,
            'course' => null,
            'year_level' => null,
            'semester' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
