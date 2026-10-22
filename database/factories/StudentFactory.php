<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'user_id' => User::factory()->student(),
            'student_code' => 'STU-' . fake()->unique()->numerify('######'),
            'date_birth' => fake()->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            'tel' => '08' . fake()->numerify('##########'),
            'attendance_percent' => 0,
            'status' => 'regular',
        ];
    }

    public function irregular(): static
    {
        return $this->state(fn () => [
            'status' => 'irregular',
        ]);
    }
}
