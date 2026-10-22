<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CourseSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    // Form roster: tampilkan siswa yang terdaftar di course dari sesi ini
    public function create(CourseSession $session)
    {
        $students = $session->course->students()->orderBy('name')->get();

        $existing = AttendanceRecord::where('course_session_id', $session->id)
            ->pluck('status', 'student_id');

        return view('attendance.take', compact('session', 'students', 'existing'));
    }

    // Simpan absensi seluruh siswa untuk 1 sesi sekaligus
    public function store(Request $request, CourseSession $session)
    {
        $data = $request->validate([
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:present,late,absent'],
        ]);

        // Hanya siswa yang benar-benar terdaftar di course sesi ini yang boleh diproses
        $enrolledStudentIds = $session->course->students()->pluck('students.id')->all();

        $processedStudentIds = [];

        foreach ($data['attendance'] as $studentId => $status) {
            if (! in_array((int) $studentId, $enrolledStudentIds, true)) {
                continue;
            }

            AttendanceRecord::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'course_session_id' => $session->id,
                ],
                [
                    'status' => $status,
                    'checked_in_at' => in_array($status, ['present', 'late']) ? now() : null,
                    'duration_minutes' => in_array($status, ['present', 'late'])
                        ? Carbon::parse($session->start_time)->diffInMinutes(Carbon::parse($session->end_time))
                        : 0,
                ]
            );
            $processedStudentIds[] = $studentId;
        }

        if (! empty($processedStudentIds)) {
            Student::whereIn('id', $processedStudentIds)
                ->get()
                ->each->recalculateAttendance();
        }

        // recalculateAttendance() pada tiap siswa terpicu otomatis lewat Observer
        // saat AttendanceRecord di atas disimpan (event "saved").

        return redirect()
            ->route('attendance.index')
            ->with('success', "Absensi untuk sesi \"{$session->title}\" berhasil disimpan.");
    }
}
