<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::with('teacher')->withCount(['students', 'sessions']);

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('code', 'like', "%{$term}%")
                  ->orWhere('categories', 'like', "%{$term}%")
                  ->orWhereHas('teacher', function ($tq) use ($term) {
                      $tq->where('name', 'like', "%{$term}%");
                  });
            });
        }

        $courses = $query->orderBy('name')->get();

        $totalCourses = Course::count();
        $totalEnrollments = \Illuminate\Support\Facades\DB::table('course_student')->count();
        $totalSessions = \App\Models\CourseSession::count();
        $unassignedCourses = Course::whereNull('teacher_id')->count();

        return view('courses.index', compact(
            'courses',
            'totalCourses',
            'totalEnrollments',
            'totalSessions',
            'unassignedCourses'
        ));
    }

    public function create()
    {
        $teachers = Teacher::orderBy('name')->get();

        return view('courses.create', compact('teachers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'categories' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:courses,code'],
            'teacher_id' => ['nullable', 'exists:teachers,id'],
        ]);

        $course = Course::create($data);

        return redirect()
            ->route('courses.show', $course)
            ->with('success', 'Course baru berhasil dibuat. Sekarang daftarkan siswa dan tambahkan sesi pertemuan.');
    }

    public function show(Course $course)
    {
        $course->load([
            'teacher',
            'students' => fn ($q) => $q->orderBy('name'),
            'sessions' => fn ($q) => $q->orderByDesc('session_date'),
        ]);

        // siswa yang belum terdaftar di course ini, untuk dropdown "Tambah siswa"
        $availableStudents = Student::whereDoesntHave('courses', function ($q) use ($course) {
            $q->where('courses.id', $course->id);
        })->orderBy('name')->get();

        return view('courses.show', compact('course', 'availableStudents'));
    }
}
