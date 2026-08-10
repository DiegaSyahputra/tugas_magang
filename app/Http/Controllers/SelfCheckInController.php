<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CourseSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SelfCheckInController extends Controller
{
    // Halaman publik yang dibuka siswa lewat link/QR
    public function show(CourseSession $session)
    {
        $session->load('course');
        $students = $session->course->students()->orderBy('name')->get();

        return view('checkin.show', compact('session', 'students'));
    }

    // Siswa menekan "Saya Hadir"
    public function store(Request $request, CourseSession $session)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        // Pastikan siswa ini memang terdaftar di kelas dari sesi ini
        $enrolled = $session->course->students()
            ->where('students.id', $data['student_id'])
            ->exists();

        if (! $enrolled) {
            return back()->withErrors(['student_id' => 'Anda tidak terdaftar di kelas ini, tidak bisa check-in.']);
        }

        // Tentukan present vs late otomatis berdasarkan toleransi waktu
        $deadline = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time)
            ->addMinutes(config('attendance.late_tolerance_minutes', 15));

        $status = now()->greaterThan($deadline) ? 'late' : 'present';

        AttendanceRecord::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'course_session_id' => $session->id,
            ],
            [
                'status' => $status,
                'checked_in_at' => now(),
            ]
        );
        // AttendanceRecordObserver otomatis menghitung ulang attendance_percent siswa ini.

        $student = $session->course->students()->find($data['student_id']);

        return redirect()
            ->route('checkin.show', $session->checkin_code)
            ->with('checkedIn', ['name' => $student->name, 'status' => $status]);
    }
}
