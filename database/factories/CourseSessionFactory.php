<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseSession>
 */
class CourseSessionFactory extends Factory
{
    protected $model = CourseSession::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => 'Pertemuan ' . fake()->numberBetween(1, 16) . ' - ' . fake()->sentence(3),
            'session_date' => now()->format('Y-m-d'),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'checkin_code' => Str::random(10),
        ];
    }
}
