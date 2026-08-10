<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Course;
use App\Models\Payment;
use App\Models\Library;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed 3 Main Role Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@skillset.com'],
            [
                'name' => 'Admin SkillSet',
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        $teacherUser = User::firstOrCreate(
            ['email' => 'teacher@skillset.com'],
            [
                'name' => 'Dr. Edwin Kurniawan',
                'password' => Hash::make('password123'),
                'role' => 'teacher',
            ]
        );

        $studentUser = User::firstOrCreate(
            ['email' => 'student@skillset.com'],
            [
                'name' => 'Jacob Jones',
                'password' => Hash::make('password123'),
                'role' => 'student',
            ]
        );

        // 2. Call domain seeders
        $this->call([
            StudentSeeder::class,
            TeacherSeeder::class,
            CourseSeeder::class,
            EnrollmentSeeder::class,
            CourseSessionSeeder::class,
            AttendanceRecordSeeder::class,
            MeetingSeeder::class,
        ]);

        // Link studentUser to first student
        $student = Student::first();
        if ($student) {
            $student->update(['user_id' => $studentUser->id]);
        }

        // 3. Seed Payment Records
        $course = Course::first();
        if ($student) {
            Payment::firstOrCreate(
                ['invoice_id' => 'INV-20260810-01'],
                [
                    'student_id' => $student->id,
                    'course_id' => $course?->id,
                    'amount' => 750000,
                    'payment_method' => 'QRIS GoPay',
                    'status' => 'paid',
                    'paid_at' => now(),
                ]
            );

            Payment::firstOrCreate(
                ['invoice_id' => 'INV-20260809-02'],
                [
                    'student_id' => $student->id,
                    'course_id' => $course?->id,
                    'amount' => 850000,
                    'payment_method' => 'BCA Virtual Account',
                    'status' => 'pending',
                ]
            );
        }

        // 4. Seed Library Digital Materials
        Library::firstOrCreate(
            ['title' => 'Panduan Lengkap Laravel 11 Framework'],
            [
                'category' => 'Web Development',
                'file_format' => 'PDF E-Book',
                'file_size' => '14.2 MB',
                'cover_icon' => '📚',
                'downloads' => 342,
                'rating' => 4.9,
            ]
        );

        Library::firstOrCreate(
            ['title' => 'Mastering UI/UX Design with Figma'],
            [
                'category' => 'UI/UX Design',
                'file_format' => 'Video Course & Assets',
                'file_size' => '1.2 GB',
                'cover_icon' => '🎨',
                'downloads' => 520,
                'rating' => 4.8,
            ]
        );

        Library::firstOrCreate(
            ['title' => 'Flutter & Dart: Cross-Platform Mobile Apps'],
            [
                'category' => 'Mobile Apps',
                'file_format' => 'PDF E-Book & Code Repo',
                'file_size' => '28.5 MB',
                'cover_icon' => '📱',
                'downloads' => 210,
                'rating' => 4.9,
            ]
        );
    }
}
