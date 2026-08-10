<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Meeting;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::orderBy('id')->get();

        if ($courses->isEmpty()) {
            return;
        }

        // Satu meeting yang sedang "live" untuk demo tampilan Meeting Room
        Meeting::updateOrCreate(
            ['title' => 'Sesi Live - ' . $courses->first()->name],
            [
                'course_id' => $courses->first()->id,
                'scheduled_at' => now()->subMinutes(10),
                'duration_minutes' => 60,
                'started_at' => now()->subMinutes(10),
            ]
        );

        // Beberapa jadwal mendatang untuk panel "Weekly Meetings"
        $labels = ['Kelas Minggu Ini', 'Review Tugas', 'Sesi Tanya Jawab'];

        foreach ($labels as $i => $label) {
            $course = $courses[$i % $courses->count()];

            Meeting::updateOrCreate(
                ['title' => "{$label} - {$course->name}"],
                [
                    'course_id' => $course->id,
                    'scheduled_at' => now()->addDays($i + 1)->setTime(9, 0),
                    'duration_minutes' => 60,
                ]
            );
        }
    }
}
