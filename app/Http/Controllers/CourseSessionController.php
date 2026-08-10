<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Student;
use Illuminate\Http\Request;

class CourseSessionController extends Controller
{
    public function create(Course $course)
    {
        return view('courses.sessions.create', compact('course'));
    }

    public function store(Request $request, Course $course)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $course->sessions()->create($data);

        // Sesi baru berarti total sesi relevan bertambah buat semua siswa
        // yang terdaftar di course ini -> persentase kehadiran mereka otomatis
        // turun sampai mereka diabsen di sesi baru itu. Hitung ulang supaya akurat.
        $course->students->each->recalculateAttendance();

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Sesi baru berhasil dibuat.');
    }

    public function edit(Course $course, CourseSession $session)
    {
        return view('courses.sessions.edit', compact('course', 'session'));
    }

    public function update(Request $request, Course $course, CourseSession $session)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $session->update($data);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Sesi berhasil diperbarui.');
    }

    public function destroy(Course $course, CourseSession $session)
    {
        // Menghapus sesi otomatis menghapus attendance_records terkait (cascade),
        // dan attendance_percent siswa yang terdampak akan terhitung ulang
        // lewat AttendanceRecordObserver (event "deleted") untuk tiap record yang hilang.
        $affectedStudentIds = $session->attendanceRecords()->pluck('student_id');

        $session->delete();

        Student::whereIn('id', $affectedStudentIds)
            ->get()
            ->each->recalculateAttendance();

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Sesi berhasil dihapus.');
    }

    // Tampilkan link & QR code self check-in untuk sesi ini (untuk ditunjukkan/diprint di kelas)
    public function checkinInfo(Course $course, CourseSession $session)
    {
        return view('courses.sessions.checkin-info', compact('course', 'session'));
    }
}
