<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Meeting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\AttendanceRecord;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $totalStudents = Student::count();
        $totalTeachers = Teacher::count();
        $totalCourses = Course::count();
        $liveMeeting = Meeting::whereNotNull('started_at')->whereNull('ended_at')->with('course')->first();
        
        $regularCount = Student::where('status', 'regular')->count();
        $irregularCount = Student::where('status', 'irregular')->count();
        $overallPercent = $totalStudents > 0 ? round(($regularCount / $totalStudents) * 100) : 0;

        $recentStudents = Student::latest()->take(5)->get();
        $recentCourses = Course::withCount('students')->latest()->take(4)->get();

        return view('home', compact(
            'totalStudents',
            'totalTeachers',
            'totalCourses',
            'liveMeeting',
            'regularCount',
            'irregularCount',
            'overallPercent',
            'recentStudents',
            'recentCourses'
        ));
    }
}
