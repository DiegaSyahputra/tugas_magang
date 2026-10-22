<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MeetingMessage>
 */
class MeetingMessageFactory extends Factory
{
    protected $model = MeetingMessage::class;

    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'student_id' => null,
            'sender_name' => fake()->name(),
            'message' => fake()->sentence(),
        ];
    }
}
