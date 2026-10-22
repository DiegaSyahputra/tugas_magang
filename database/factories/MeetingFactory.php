<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'course_session_id' => null,
            'title' => 'Live Class: ' . fake()->sentence(3),
            'room_code' => 'skillset-' . Str::random(10),
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => 60,
            'started_at' => null,
            'ended_at' => null,
        ];
    }

    public function live(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subMinutes(15),
            'ended_at' => null,
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'started_at' => now()->subHours(2),
            'ended_at' => now()->subHour(),
        ]);
    }
}
