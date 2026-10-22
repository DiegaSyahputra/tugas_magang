<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'categories' => fake()->randomElement(['Web Development', 'Design', 'Data', 'Mobile']),
            'name' => fake()->words(3, true) . ' Masterclass',
            'code' => 'CRS-' . fake()->unique()->numerify('####'),
        ];
    }
}
