<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Seeder;

class CourseSessionSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::all();

        foreach ($courses as $course) {
            for ($i = 9; $i >= 0; $i--) {
                CourseSession::updateOrCreate(
                    [
                        'course_id' => $course->id,
                        'title' => "Pertemuan " . (10 - $i) . " - {$course->name}",
                    ],
                    [
                        'session_date' => now()->subWeeks($i)->toDateString(),
                        'start_time' => '08:00:00',
                        'end_time' => '10:00:00',
                    ]
                );
            }
        }
    }
}
