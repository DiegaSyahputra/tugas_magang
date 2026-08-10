<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\CourseSession;
use App\Models\Student;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    public function run(): void
    {
        $sessions = CourseSession::all();
        $students = Student::all();

        foreach ($students as $student) {
            // Pakai attendance_percent awal dari StudentSeeder sebagai target
            // "kecenderungan hadir" siswa ini, supaya hasilnya realistis & bervariasi.
            $reliability = max(10, min(95, $student->attendance_percent ?: 50)) / 100;

            // Hanya buat catatan untuk sesi milik course yang benar-benar
            // diikuti siswa ini (lihat EnrollmentSeeder / tabel course_student).
            $enrolledCourseIds = $student->courses()->pluck('courses.id');
            $sessionsForStudent = $sessions->whereIn('course_id', $enrolledCourseIds);

            foreach ($sessionsForStudent as $session) {
                $roll = mt_rand(1, 100) / 100;

                if ($roll <= $reliability * 0.85) {
                    $status = 'present';
                } elseif ($roll <= $reliability) {
                    $status = 'late';
                } else {
                    $status = 'absent';
                }

                $checkedIn = in_array($status, ['present', 'late'])
                    ? $session->session_date->copy()->setTimeFromTimeString($session->start_time)
                    : null;

                AttendanceRecord::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'course_session_id' => $session->id,
                    ],
                    [
                        'status' => $status,
                        'checked_in_at' => $checkedIn,
                        'duration_minutes' => $checkedIn
                            ? mt_rand(80, 150) // variasi durasi mengikuti sesi
                            : 0,
                    ]
                );
            }

            // Pastikan attendance_percent & status ter-update dari data riil di atas
            // (observer sudah otomatis memicu ini tiap AttendanceRecord disimpan,
            // baris ini hanya jaga-jaga / eksplisit).
            $student->recalculateAttendance();
        }
    }
}
