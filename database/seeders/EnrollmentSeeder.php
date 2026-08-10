<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Database\Seeder;

class EnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $courses = Course::orderBy('id')->get();
        $students = Student::orderBy('id')->get();

        if ($courses->count() < 2) {
            // fallback: kalau cuma ada 1 course, daftarkan semua siswa ke situ
            $students->each(fn ($s) => $s->courses()->syncWithoutDetaching($courses->pluck('id')));
            return;
        }

        [$courseA, $courseB] = [$courses[0], $courses[1]];

        foreach ($students as $index => $student) {
            // semua siswa ikut course A, separuh (index genap) juga ikut course B
            $courseIds = [$courseA->id];

            if ($index % 2 === 0) {
                $courseIds[] = $courseB->id;
            }

            $student->courses()->syncWithoutDetaching($courseIds);
        }
    }
}
