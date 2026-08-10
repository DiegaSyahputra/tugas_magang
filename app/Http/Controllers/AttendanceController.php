<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\CourseSession;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $students = Student::query()
            ->with('courses')
            ->search($request->input('q'))
            ->filterStatus($request->input('status'))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        // Riwayat kehadiran terbaru = catatan check-in nyata (present/late),
        // bukan lagi tabel statis.
        $histories = AttendanceRecord::with(['student', 'session'])
            ->whereIn('status', ['present', 'late'])
            ->whereNotNull('checked_in_at')
            ->latest('checked_in_at')
            ->take(5)
            ->get();

        // Overall = rata-rata attendance_percent siswa, yang sudah otomatis
        // ter-update oleh AttendanceRecordObserver setiap ada absensi baru.
        $overallPercent = round(Student::avg('attendance_percent'), 2);

        // Sesi terbaru untuk tombol "Create Now" -> ambil absensi
        $upcomingSessions = CourseSession::with('course')
            ->orderByDesc('session_date')
            ->take(5)
            ->get();

        $totalStudents = Student::count();
        $regularCount = Student::where('status', 'regular')->count();
        $irregularCount = Student::where('status', 'irregular')->count();

        return view('attendance.index', compact(
            'students',
            'histories',
            'overallPercent',
            'upcomingSessions',
            'totalStudents',
            'regularCount',
            'irregularCount'
        ));
    }

    public function destroy(Student $student)
    {
        $student->delete();

        return back()->with('success', 'Data siswa berhasil dihapus.');
    }

    // Detail riwayat & tren kehadiran satu siswa
    public function show(Student $student)
    {
        $student->load('courses');

        $records = $student->attendanceRecords()
            ->with('session.course')
            ->get()
            ->filter(fn ($r) => $r->session) // jaga-jaga kalau sesi sudah terhapus
            ->sortByDesc(fn ($r) => $r->session->session_date)
            ->values();

        $total = $records->count();
        $present = $records->where('status', 'present')->count();
        $late = $records->where('status', 'late')->count();
        $absent = $records->where('status', 'absent')->count();

        // Tren kehadiran 8 minggu terakhir, dipakai untuk grafik garis
        $weeklyTrend = collect(range(7, 0))->map(function ($weeksAgo) use ($records) {
            $start = now()->subWeeks($weeksAgo)->startOfWeek();
            $end = now()->subWeeks($weeksAgo)->endOfWeek();

            $weekRecords = $records->filter(
                fn ($r) => $r->session->session_date->between($start, $end)
            );

            $weekTotal = $weekRecords->count();
            $weekAttended = $weekRecords->whereIn('status', ['present', 'late'])->count();

            return [
                'label' => $start->translatedFormat('d M'),
                'percent' => $weekTotal > 0 ? round($weekAttended / $weekTotal * 100) : null,
            ];
        });

        return view('attendance.show', compact(
            'student', 'records', 'total', 'present', 'late', 'absent', 'weeklyTrend'
        ));
    }
}
