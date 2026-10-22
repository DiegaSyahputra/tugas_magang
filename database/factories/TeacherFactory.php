<?php

namespace Database\Factories;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'user_id' => User::factory()->teacher(),
            'specialization' => fake()->randomElement(['Web Development', 'Data Science', 'UI/UX Design', 'Mobile Apps']),
            'tel' => '08' . fake()->numerify('##########'),
        ];
    }
}
