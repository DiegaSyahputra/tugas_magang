<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        Course::updateOrCreate(['code' => 'MLR-101'], ['name' => 'Analisis Data & Statistik']);
        Course::updateOrCreate(['code' => 'WEB-201'], ['name' => 'Pemrograman Web Lanjut']);
    }
}
