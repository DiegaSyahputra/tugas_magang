<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\Payment;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $regularCount = Student::where('status', 'regular')->count();
        $irregularCount = Student::where('status', 'irregular')->count();
        
        $attendanceRate = $totalStudents > 0 ? round(($regularCount / $totalStudents) * 100) : 0;
        $completionRate = 88;

        $coursesSummary = Course::withCount(['students', 'sessions'])->get();

        return view('reports.index', compact(
            'totalStudents',
            'regularCount',
            'irregularCount',
            'attendanceRate',
            'completionRate',
            'coursesSummary'
        ));
    }

    public function exportPdf()
    {
        $filename = 'reports/Laporan_Akademik_' . date('Ymd_His') . '.html';

        $students = Student::all();
        $courses = Course::withCount(['students', 'sessions'])->get();
        $totalRevenue = Payment::where('status', 'paid')->sum('amount');
        $dateStr = date('d M Y, H:i');

        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <title>Laporan Eksekutif Akademik SkillSet</title>
            <style>
                body { font-family: sans-serif; padding: 30px; color: #1e293b; line-height: 1.5; }
                .header { border-bottom: 2px solid #6366f1; padding-bottom: 10px; margin-bottom: 20px; }
                .title { font-size: 22px; font-weight: bold; color: #6366f1; }
                .table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
                .table th, .table td { border: 1px solid #cbd5e1; padding: 8px 12px; text-align: left; }
                .table th { background: #f1f5f9; }
                .stats { display: flex; gap: 20px; margin-bottom: 20px; }
                .stat-card { border: 1px solid #cbd5e1; padding: 15px; border-radius: 8px; flex: 1; }
            </style>
        </head>
        <body>
            <div class='header'>
                <div class='title'>Laporan Akademik & Performa Platform SkillSet</div>
                <p>Tanggal Ekspor: {$dateStr} &middot; Pengunduh: " . (auth()->user()->name ?? 'Admin') . "</p>
            </div>

            <h3>Ringkasan Performa Per Kelas</h3>
            <table class='table'>
                <thead>
                    <tr><th>Kode</th><th>Nama Course</th><th>Siswa Terdaftar</th><th>Jumlah Sesi</th></tr>
                </thead>
                <tbody>";

        foreach ($courses as $c) {
            $htmlContent .= "<tr><td>{$c->code}</td><td>{$c->name}</td><td>{$c->students_count} Siswa</td><td>{$c->sessions_count} Sesi</td></tr>";
        }

        $htmlContent .= "
                </tbody>
            </table>

            <h3>Daftar Siswa & Persentase Kehadiran</h3>
            <table class='table'>
                <thead>
                    <tr><th>NIM / Code</th><th>Nama Siswa</th><th>Status</th><th>Kehadiran (%)</th></tr>
                </thead>
                <tbody>";

        foreach ($students as $s) {
            $htmlContent .= "<tr><td>{$s->student_code}</td><td>{$s->name}</td><td>" . strtoupper($s->status) . "</td><td>{$s->attendance_percent}%</td></tr>";
        }

        $htmlContent .= "
                </tbody>
            </table>
        </body>
        </html>
        ";

        // Save report file to project storage/app/public/reports
        Storage::disk('public')->put($filename, $htmlContent);

        return Storage::disk('public')->download($filename, 'Laporan_Akademik_' . date('Ymd') . '.html');
    }

    public function exportExcel()
    {
        $filename = 'reports/Laporan_Kehadiran_' . date('Ymd_His') . '.csv';

        $students = Student::all();
        $csvData = "ID Siswa,Nama Lengkap,Status Presensi,Persentase Kehadiran,Telepon\n";

        foreach ($students as $s) {
            $csvData .= "\"{$s->student_code}\",\"{$s->name}\",\"{$s->status}\",\"{$s->attendance_percent}%\",\"{$s->tel}\"\n";
        }

        // Save report file to project storage/app/public/reports
        Storage::disk('public')->put($filename, $csvData);

        return Storage::disk('public')->download($filename, 'Laporan_Kehadiran_' . date('Ymd') . '.csv');
    }
}
