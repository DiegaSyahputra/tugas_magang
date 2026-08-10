<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teacherUser = User::where('role', 'teacher')->first();

        Teacher::updateOrCreate(
            ['name' => 'Dr. Edwin Kurniawan'],
            [
                'user_id' => $teacherUser?->id,
                'specialization' => 'Web Development',
                'tel' => '081298765432',
            ]
        );

        Teacher::updateOrCreate(
            ['name' => 'Jane Cooper, M.Kom'],
            [
                'specialization' => 'UI/UX Design',
                'tel' => '081298765433',
            ]
        );
    }
}
