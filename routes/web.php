<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CourseEnrollmentController;
use App\Http\Controllers\CourseSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LiveClassController;
use App\Http\Controllers\MeetingMessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SelfCheckInController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Public Self Check-In
Route::get('/check-in/{session:checkin_code}', [SelfCheckInController::class, 'show'])->name('checkin.show');
Route::post('/check-in/{session:checkin_code}', [SelfCheckInController::class, 'store'])->name('checkin.store');

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Authenticated Routes (Requires Login)
Route::middleware(['auth'])->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard / Home Route
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // Courses
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

    // Live Class
    Route::get('/live-class', [LiveClassController::class, 'index'])->name('live-class.index');
    Route::get('/live-class/{meeting}', [LiveClassController::class, 'show'])->name('live-class.show');
    Route::post('/live-class/{meeting}/check-in', [LiveClassController::class, 'checkIn'])->name('live-class.check-in');
    Route::post('/live-class/{meeting}/check-out', [LiveClassController::class, 'checkOut'])->name('live-class.check-out');
    Route::get('/live-class/{meeting}/messages', [MeetingMessageController::class, 'index'])->name('live-class.messages.index');
    Route::post('/live-class/{meeting}/messages', [MeetingMessageController::class, 'store'])->name('live-class.messages.store');

    // Library Upload & Download
    Route::get('/library', [LibraryController::class, 'index'])->name('library.index');
    Route::post('/library', [LibraryController::class, 'store'])->name('library.store');
    Route::get('/library/{library}/download', [LibraryController::class, 'download'])->name('library.download');

    // Payments & Proof Upload / Export Invoice
    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{payment}/upload-proof', [PaymentController::class, 'uploadProof'])->name('payments.upload-proof');
    Route::get('/payments/{payment}/invoice', [PaymentController::class, 'exportInvoice'])->name('payments.export-invoice');

    // ACCESSIBLE BY ADMIN & TEACHER
    Route::middleware([CheckRole::class . ':admin,teacher'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/attendance/{student}', [AttendanceController::class, 'show'])->name('attendance.show');
        Route::get('/attendance/sessions/{session}/take', [AttendanceRecordController::class, 'create'])->name('attendance.take');
        Route::post('/attendance/sessions/{session}/take', [AttendanceRecordController::class, 'store'])->name('attendance.take.store');

        Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');
        Route::post('/courses/{course}/enroll', [CourseEnrollmentController::class, 'store'])->name('courses.enroll');
        Route::get('/courses/{course}/sessions/create', [CourseSessionController::class, 'create'])->name('courses.sessions.create');
        Route::post('/courses/{course}/sessions', [CourseSessionController::class, 'store'])->name('courses.sessions.store');
        Route::get('/courses/{course}/sessions/{session}/edit', [CourseSessionController::class, 'edit'])->name('courses.sessions.edit');
        Route::put('/courses/{course}/sessions/{session}', [CourseSessionController::class, 'update'])->name('courses.sessions.update');
        Route::get('/courses/{course}/sessions/{session}/checkin-info', [CourseSessionController::class, 'checkinInfo'])->name('courses.sessions.checkin-info');

        Route::get('/live-class/create', [LiveClassController::class, 'create'])->name('live-class.create');
        Route::post('/live-class', [LiveClassController::class, 'store'])->name('live-class.store');
        Route::post('/live-class/{meeting}/start', [LiveClassController::class, 'start'])->name('live-class.start');
        Route::post('/live-class/{meeting}/end', [LiveClassController::class, 'end'])->name('live-class.end');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export-pdf', [ReportController::class, 'exportPdf'])->name('reports.export-pdf');
        Route::get('/reports/export-excel', [ReportController::class, 'exportExcel'])->name('reports.export-excel');

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
    });

    // ACCESSIBLE EXCLUSIVELY BY ADMIN
    Route::middleware([CheckRole::class . ':admin'])->group(function () {
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        Route::delete('/attendance/{student}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');

        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::put('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

        Route::delete('/courses/{course}/students/{student}', [CourseEnrollmentController::class, 'destroy'])->name('courses.students.destroy');
        Route::delete('/courses/{course}/sessions/{session}', [CourseSessionController::class, 'destroy'])->name('courses.sessions.destroy');
    });
});
