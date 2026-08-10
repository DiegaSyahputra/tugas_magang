<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Meeting;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{
    public function index()
    {
        // Meeting yang sedang berlangsung (started tapi belum ended)
        $liveMeeting = Meeting::whereNotNull('started_at')
            ->whereNull('ended_at')
            ->with(['course'])
            ->latest('started_at')
            ->first();

        // Jadwal meeting mendatang
        $upcomingMeetings = Meeting::whereNull('started_at')
            ->with(['course'])
            ->orderBy('scheduled_at')
            ->get();

        $courses = Course::orderBy('name')->get();

        $liveCount = $liveMeeting ? 1 : 0;
        $upcomingCount = $upcomingMeetings->count();
        $completedCount = Meeting::whereNotNull('ended_at')->count();

        return view('live-class.index', compact(
            'liveMeeting',
            'upcomingMeetings',
            'courses',
            'liveCount',
            'upcomingCount',
            'completedCount'
        ));
    }

    public function create()
    {
        $courses = Course::orderBy('name')->get();
        $sessions = CourseSession::orderByDesc('session_date')->get();

        return view('live-class.create', compact('courses', 'sessions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'course_session_id' => ['nullable', 'exists:course_sessions,id'],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
        ]);

        $meeting = Meeting::create($data);

        return redirect()
            ->route('live-class.show', $meeting)
            ->with('success', 'Meeting berhasil dibuat.');
    }

    public function show(Meeting $meeting)
    {
        $meeting->load(['course', 'messages.student']);
        $participants = $meeting->participants();

        return view('live-class.show', compact('meeting', 'participants'));
    }

    // Tandai meeting sudah dimulai (host klik "Mulai Meeting")
    public function start(Meeting $meeting)
    {
        if (! $meeting->started_at) {
            $meeting->update(['started_at' => now()]);
        }

        return redirect()->route('live-class.show', $meeting);
    }

    // Tandai meeting selesai
    public function end(Meeting $meeting)
    {
        $meeting->update(['ended_at' => now()]);

        return redirect()
            ->route('live-class.index')
            ->with('success', 'Meeting telah diakhiri.');
    }

    // Dipanggil otomatis via JS saat siswa join video call (event videoConferenceJoined)
    public function checkIn(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        if (! $meeting->course_session_id) {
            // Meeting ini tidak terhubung ke sesi kelas manapun,
            // jadi tidak ada AttendanceRecord yang bisa diisi otomatis.
            return response()->json(['recorded' => false, 'reason' => 'no_session']);
        }

        AttendanceRecord::updateOrCreate(
            [
                'student_id' => $data['student_id'],
                'course_session_id' => $meeting->course_session_id,
            ],
            [
                'status' => 'present',
                'checked_in_at' => now(),
            ]
        );
        // AttendanceRecordObserver otomatis menghitung ulang attendance_percent
        // siswa ini begitu record di atas disimpan.

        return response()->json(['recorded' => true]);
    }

    // Dipanggil otomatis via JS saat siswa keluar video call (event videoConferenceLeft)
    public function checkOut(Request $request, Meeting $meeting)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        if (! $meeting->course_session_id) {
            return response()->json(['recorded' => false, 'reason' => 'no_session']);
        }

        $record = AttendanceRecord::where('student_id', $data['student_id'])
            ->where('course_session_id', $meeting->course_session_id)
            ->first();

        if ($record && $record->checked_in_at) {
            $record->update([
                'duration_minutes' => now()->diffInMinutes($record->checked_in_at),
            ]);
        }

        return response()->json(['recorded' => (bool) $record]);
    }
}
