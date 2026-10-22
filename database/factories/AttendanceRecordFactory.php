<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\CourseSession;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'course_session_id' => CourseSession::factory(),
            'status' => 'present',
            'checked_in_at' => now(),
            'duration_minutes' => 90,
        ];
    }

    public function late(): static
    {
        return $this->state(fn () => [
            'status' => 'late',
            'checked_in_at' => now(),
            'duration_minutes' => 75,
        ]);
    }

    public function absent(): static
    {
        return $this->state(fn () => [
            'status' => 'absent',
            'checked_in_at' => null,
            'duration_minutes' => 0,
        ]);
    }
}
