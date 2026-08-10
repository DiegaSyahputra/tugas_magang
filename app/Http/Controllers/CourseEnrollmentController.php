<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    // Daftarkan 1 atau lebih siswa ke course
    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            'student_id' => ['required', 'array', 'min:1'],
            'student_id.*' => ['exists:students,id'],
        ]);

        $attachData = collect($data['student_id'])->mapWithKeys(function ($id) {
            return [$id => ['enrolled_at' => now()]];
        })->toArray();

        // Attach siswa beserta nilai kolom enrolled_at
        $course->students()->syncWithoutDetaching($attachData);

        // $course->students()->syncWithoutDetaching($data['student_id']);

        // Hitung ulang attendance semua siswa yang baru ditambahkan,
        // karena total sesi yang relevan buat mereka baru saja berubah.
        Student::whereIn('id', $data['student_id'])
            ->get()
            ->each->recalculateAttendance();

        return back()->with('success', 'Siswa berhasil didaftarkan ke kelas ini.');
    }

    // Keluarkan siswa dari course
    public function destroy(Course $course, Student $student)
    {
        $course->students()->detach($student->id);

        // Total sesi relevan buat siswa ini berubah, hitung ulang.
        $student->recalculateAttendance();

        return back()->with('success', 'Siswa dikeluarkan dari kelas ini.');
    }
}
