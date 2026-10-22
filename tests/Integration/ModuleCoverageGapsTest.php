<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Library;
use App\Models\Meeting;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ModuleCoverageGapsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | 1. Attendance Threshold Boundary Tests
    |--------------------------------------------------------------------------
    */

    public function test_attendance_threshold_boundary_69_percent_is_irregular(): void
    {
        // Default threshold is 70%
        // Student in a course with 13 sessions, attending 9 sessions:
        // 9 / 13 = 0.6923... => round(69.23%) = 69%
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $sessions = CourseSession::factory()->count(13)->create([
            'course_id' => $course->id,
        ]);

        // Attend 9 sessions
        foreach ($sessions->take(9) as $session) {
            $student->attendanceRecords()->create([
                'course_session_id' => $session->id,
                'status' => 'present',
            ]);
        }

        $student->refresh();
        $this->assertEquals(69, $student->attendance_percent);
        $this->assertEquals('irregular', $student->status);
    }

    public function test_attendance_threshold_boundary_70_percent_is_regular(): void
    {
        // Exactly 70% attendance gives regular status
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $sessions = CourseSession::factory()->count(10)->create([
            'course_id' => $course->id,
        ]);

        // Attend 7 of 10 sessions = 70%
        foreach ($sessions->take(7) as $session) {
            $student->attendanceRecords()->create([
                'course_session_id' => $session->id,
                'status' => 'present',
            ]);
        }

        $student->refresh();
        $this->assertEquals(70, $student->attendance_percent);
        $this->assertEquals('regular', $student->status);
    }

    public function test_attendance_threshold_respects_custom_config_override(): void
    {
        // Override threshold to 80%
        config(['attendance.regular_threshold' => 80]);

        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $course->students()->attach($student->id);

        $sessions = CourseSession::factory()->count(20)->create([
            'course_id' => $course->id,
        ]);

        // Attend 15 of 20 = 75%
        foreach ($sessions->take(15) as $session) {
            $student->attendanceRecords()->create([
                'course_session_id' => $session->id,
                'status' => 'present',
            ]);
        }

        $student->refresh();
        $this->assertEquals(75, $student->attendance_percent);
        // Under 80% threshold, 75% should be irregular
        $this->assertEquals('irregular', $student->status);

        // Add 1 more present record => 16 of 20 = 80%
        $student->attendanceRecords()->create([
            'course_session_id' => $sessions[15]->id,
            'status' => 'present',
        ]);

        $student->refresh();
        $this->assertEquals(80, $student->attendance_percent);
        $this->assertEquals('regular', $student->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 2. Dashboard Metrics Verification
    |--------------------------------------------------------------------------
    */

    public function test_dashboard_computes_exact_counts_and_overall_percentage(): void
    {
        $admin = User::factory()->admin()->create();

        // 3 regular students, 1 irregular student (total 4 students)
        Student::factory()->count(3)->create([
            'status' => 'regular',
        ]);
        Student::factory()->create([
            'status' => 'irregular',
        ]);

        $teachers = Teacher::factory()->count(2)->create();
        $course = Course::factory()->create(['teacher_id' => $teachers[0]->id]);
        Course::factory()->count(4)->create(['teacher_id' => $teachers[1]->id]);

        // Active live meeting: started_at set, ended_at null
        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'started_at' => now()->subMinutes(10),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalStudents', 4);
        $response->assertViewHas('totalTeachers', 2);
        $response->assertViewHas('totalCourses', 5);
        $response->assertViewHas('regularCount', 3);
        $response->assertViewHas('irregularCount', 1);
        $response->assertViewHas('overallPercent', 75);
        $response->assertViewHas('liveMeeting', function ($viewMeeting) use ($meeting) {
            return $viewMeeting !== null && $viewMeeting->id === $meeting->id;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 3. Auth Validation & Session Redirects
    |--------------------------------------------------------------------------
    */

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('register.post'), [
            'name' => 'Duplicate User',
            'email' => 'existing@example.com',
            'password' => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role' => 'student',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_password_confirmation_mismatch(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Mismatch User',
            'email' => 'mismatch@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
            'role' => 'student',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_fails_with_password_under_eight_characters(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Short Pass',
            'email' => 'short@example.com',
            'password' => '1234567',
            'password_confirmation' => '1234567',
            'role' => 'student',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_login_fails_with_wrong_password_and_flashes_only_email(): void
    {
        $user = User::factory()->create([
            'email' => 'validuser@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        $response = $this->from(route('login'))->post(route('login.post'), [
            'email' => 'validuser@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_authenticated_user_accessing_login_page_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->get(route('login'));
        $response->assertRedirect(route('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | 4. Payment Edge Cases, Ownership & Security
    |--------------------------------------------------------------------------
    */

    public function test_payment_creation_fails_with_negative_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => -50000,
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_payment_creation_fails_with_zero_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 0,
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_payment_creation_fails_with_overflow_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create();

        // 100,000,000 exceeds maximum allowable decimal bounds
        $response = $this->actingAs($admin)->post(route('payments.store'), [
            'student_id' => $student->id,
            'amount' => 100000000000,
            'payment_method' => 'Bank Transfer',
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_student_cannot_create_payment_for_another_student(): void
    {
        $studentUserA = User::factory()->student()->create();
        $studentA = Student::factory()->create(['user_id' => $studentUserA->id]);

        $studentUserB = User::factory()->student()->create();
        $studentB = Student::factory()->create(['user_id' => $studentUserB->id]);

        // Student A tries to pass Student B's ID in payload
        $this->actingAs($studentUserA)->post(route('payments.store'), [
            'student_id' => $studentB->id,
            'amount' => 250000,
            'payment_method' => 'Gopay',
        ]);

        // Controller overrides student_id with Student A's own ID
        $this->assertDatabaseHas('payments', [
            'student_id' => $studentA->id,
            'amount' => 250000,
        ]);
        $this->assertDatabaseMissing('payments', [
            'student_id' => $studentB->id,
            'amount' => 250000,
        ]);
    }

    public function test_student_cannot_upload_proof_to_another_students_payment(): void
    {
        Storage::fake('public');

        $studentUserA = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $studentUserA->id]);

        $studentUserB = User::factory()->student()->create();
        $studentB = Student::factory()->create(['user_id' => $studentUserB->id]);

        $paymentB = Payment::factory()->create(['student_id' => $studentB->id]);
        $proof = UploadedFile::fake()->create('proof.png', 500);

        // Student A attempts to upload proof to Student B's payment
        $response = $this->actingAs($studentUserA)->post(route('payments.upload-proof', $paymentB), [
            'proof_file' => $proof,
        ]);

        $response->assertForbidden();
    }

    public function test_student_cannot_export_invoice_belonging_to_another_student(): void
    {
        Storage::fake('public');

        $studentUserA = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $studentUserA->id]);

        $studentUserB = User::factory()->student()->create();
        $studentB = Student::factory()->create(['user_id' => $studentUserB->id]);

        $paymentB = Payment::factory()->create(['student_id' => $studentB->id]);

        // Student A attempts to export Student B's invoice
        $response = $this->actingAs($studentUserA)->get(route('payments.export-invoice', $paymentB));
        $response->assertForbidden();
    }

    public function test_uploading_proof_to_already_paid_payment_is_rejected(): void
    {
        Storage::fake('public');

        $studentUser = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'status' => 'paid',
        ]);

        $proof = UploadedFile::fake()->create('second-proof.png', 500);

        $response = $this->actingAs($studentUser)->post(route('payments.upload-proof', $payment), [
            'proof_file' => $proof,
        ]);

        $response->assertStatus(422);
    }

    public function test_export_invoice_escapes_xss_in_all_interpolated_fields(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();
        $student = Student::factory()->create([
            'name' => '<script>alert("XSS-STUDENT")</script>',
        ]);
        $course = Course::factory()->create([
            'name' => '<img src=x onerror=alert("XSS-COURSE")>',
        ]);
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'course_id' => $course->id,
            'payment_method' => '<svg/onload=alert("XSS-PAY")>',
            'amount' => 750000,
        ]);

        $response = $this->actingAs($admin)->get(route('payments.export-invoice', $payment));

        $this->assertEquals(200, $response->getStatusCode());
        $payment->refresh();
        $this->assertNotNull($payment->invoice_path);

        $storedInvoiceContent = Storage::disk('public')->get($payment->invoice_path);
        
        // Assert malicious tags are HTML-escaped
        $this->assertStringNotContainsString('<script>alert("XSS-STUDENT")</script>', $storedInvoiceContent);
        $this->assertStringNotContainsString('<img src=x onerror=alert("XSS-COURSE")>', $storedInvoiceContent);
        $this->assertStringNotContainsString('<svg/onload=alert("XSS-PAY")>', $storedInvoiceContent);

        $this->assertStringContainsString('&lt;script&gt;alert(&quot;XSS-STUDENT&quot;)&lt;/script&gt;', $storedInvoiceContent);
    }

    /*
    |--------------------------------------------------------------------------
    | 5. Library Edge Cases
    |--------------------------------------------------------------------------
    */

    public function test_library_upload_fails_without_file(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('library.store'), [
            'title' => 'Missing File Book',
            'category' => 'Tutorial',
            'file_format' => 'PDF',
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_library_upload_fails_when_file_exceeds_20mb(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create();

        $oversizedFile = UploadedFile::fake()->create('huge-manual.pdf', 25600);

        $response = $this->actingAs($admin)->post(route('library.store'), [
            'title' => 'Too Big Material',
            'category' => 'Documentation',
            'file_format' => 'PDF',
            'file' => $oversizedFile,
        ]);

        $response->assertSessionHasErrors(['file']);
    }

    public function test_library_download_redirects_with_error_when_file_is_missing_from_disk(): void
    {
        Storage::fake('public');
        $student = User::factory()->student()->create();

        $library = Library::factory()->create([
            'file_path' => 'library_files/ghost-file.pdf',
            'downloads' => 0,
        ]);

        $response = $this->actingAs($student)->get(route('library.download', $library));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $library->refresh();
        $this->assertEquals(0, $library->downloads);
    }

    public function test_library_download_increments_count_and_downloads_file(): void
    {
        Storage::fake('public');
        $student = User::factory()->student()->create();

        Storage::disk('public')->put('library_files/valid-ebook.pdf', 'dummy content');

        $library = Library::factory()->create([
            'title' => 'Mastering Laravel',
            'file_path' => 'library_files/valid-ebook.pdf',
            'downloads' => 5,
        ]);

        $response = $this->actingAs($student)->get(route('library.download', $library));

        $this->assertEquals(200, $response->getStatusCode());
        $library->refresh();
        $this->assertEquals(6, $library->downloads);
    }

    /*
    |--------------------------------------------------------------------------
    | 6. Report Content & XSS Protection
    |--------------------------------------------------------------------------
    */

    public function test_reports_export_pdf_escapes_xss_payloads(): void
    {
        Storage::fake('public');
        $admin = User::factory()->admin()->create([
            'name' => '<script>alert("ADMIN-XSS")</script>',
        ]);

        $student = Student::factory()->create([
            'student_code' => 'STU-XSS',
            'name' => '<b onmouseover=alert("STUDENT-XSS")>Bob</b>',
        ]);

        $course = Course::factory()->create([
            'code' => 'CRS-XSS',
            'name' => '<script>alert("CRS")</script>',
        ]);
        $course->students()->attach($student->id);

        $response = $this->actingAs($admin)->get(route('reports.export-pdf'));
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->streamedContent();

        $this->assertStringNotContainsString('<script>alert("ADMIN-XSS")</script>', $content);
        $this->assertStringNotContainsString('<script>alert("CRS")</script>', $content);
        $this->assertStringNotContainsString('<b onmouseover=alert("STUDENT-XSS")>Bob</b>', $content);
    }

    /*
    |--------------------------------------------------------------------------
    | 7. Cascading Deletion & Foreign Key Behavior
    |--------------------------------------------------------------------------
    */

    public function test_deleting_teacher_sets_course_teacher_id_null_and_removes_user(): void
    {
        $admin = User::factory()->admin()->create();
        $teacherUser = User::factory()->teacher()->create();
        $teacher = Teacher::factory()->create(['user_id' => $teacherUser->id]);

        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $this->actingAs($admin)->delete(route('teachers.destroy', $teacher->id))
            ->assertRedirect(route('teachers.index'));

        // Teacher deleted
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
        // User account deleted
        $this->assertDatabaseMissing('users', ['id' => $teacherUser->id]);
        // Course teacher_id set to null (or foreign key set null)
        $course->refresh();
        $this->assertNull($course->teacher_id);
    }

    public function test_deleting_student_removes_attendance_and_payments_records(): void
    {
        $admin = User::factory()->admin()->create();
        $studentUser = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $record = AttendanceRecord::factory()->create(['student_id' => $student->id]);
        $payment = Payment::factory()->create(['student_id' => $student->id]);

        $this->actingAs($admin)->delete(route('students.destroy', $student->id))
            ->assertRedirect(route('students.index'));

        $this->assertDatabaseMissing('students', ['id' => $student->id]);
        $this->assertDatabaseMissing('users', ['id' => $studentUser->id]);
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_teacher_role_forbidden_from_attendance_destroy(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($teacher)->delete(route('attendance.destroy', $student));
        $response->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | 8. Attendance Ripple Effects
    |--------------------------------------------------------------------------
    */

    public function test_adding_new_session_lowers_student_attendance_percentage(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session1 = CourseSession::factory()->create(['course_id' => $course->id]);

        // Hadir di sesi 1 -> 100%
        $student->attendanceRecords()->create([
            'course_session_id' => $session1->id,
            'status' => 'present',
        ]);

        $student->refresh();
        $this->assertEquals(100, $student->attendance_percent);
        $this->assertEquals('regular', $student->status);

        // Tambah sesi ke-2 di course ini -> siswa sekarang baru hadir 1 dari 2 sesi (50%)
        CourseSession::factory()->create(['course_id' => $course->id]);
        $student->recalculateAttendance();

        $student->refresh();
        $this->assertEquals(50, $student->attendance_percent);
        $this->assertEquals('irregular', $student->status);
    }

    public function test_removing_student_from_course_recalculates_attendance(): void
    {
        $admin = User::factory()->admin()->create();

        $course1 = Course::factory()->create();
        $course2 = Course::factory()->create();
        $student = Student::factory()->create();

        $course1->students()->attach($student->id);
        $course2->students()->attach($student->id);

        $session1 = CourseSession::factory()->create(['course_id' => $course1->id]);
        $session2 = CourseSession::factory()->create(['course_id' => $course2->id]);

        // Hadir hanya di sesi course 1
        $student->attendanceRecords()->create([
            'course_session_id' => $session1->id,
            'status' => 'present',
        ]);

        $student->recalculateAttendance();
        $this->assertEquals(50, $student->attendance_percent); // 1 dari 2 sesi = 50%

        // Admin mengeluarkan siswa dari course 2
        $this->actingAs($admin)->delete(route('courses.students.destroy', [
            'course' => $course2,
            'student' => $student,
        ]));

        $student->refresh();
        // Sekarang siswa hanya terdaftar di course 1 (1 sesi, 1 hadir = 100%)
        $this->assertEquals(100, $student->attendance_percent);
        $this->assertEquals('regular', $student->status);
    }

    /*
    |--------------------------------------------------------------------------
    | 9. Live Class & Message Edge Cases
    |--------------------------------------------------------------------------
    */

    public function test_live_class_checkin_rejects_student_id_spoofing(): void
    {
        $studentUserA = User::factory()->student()->create();
        Student::factory()->create(['user_id' => $studentUserA->id]);

        $studentUserB = User::factory()->student()->create();
        $studentB = Student::factory()->create(['user_id' => $studentUserB->id]);

        $course = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);
        $course->students()->attach($studentB->id);

        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'course_session_id' => $session->id,
            'started_at' => now(),
        ]);

        // Student A login tapi mengirim student_id milik Student B
        $response = $this->actingAs($studentUserA)->postJson(route('live-class.check-in', $meeting), [
            'student_id' => $studentB->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_live_class_checkout_for_non_existent_record_returns_unrecorded(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = Student::factory()->create();
        $course = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);

        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'course_session_id' => $session->id,
        ]);

        $response = $this->actingAs($teacher)->postJson(route('live-class.check-out', $meeting), [
            'student_id' => $student->id,
        ]);

        $response->assertJson(['recorded' => false]);
    }

    public function test_live_class_start_is_idempotent(): void
    {
        $teacher = User::factory()->teacher()->create();
        $originalStartedAt = Carbon::parse('2026-10-22 09:00:00');

        $meeting = Meeting::factory()->create([
            'started_at' => $originalStartedAt,
        ]);

        Carbon::setTestNow('2026-10-22 10:00:00');
        $this->actingAs($teacher)->post(route('live-class.start', $meeting));

        $meeting->refresh();
        // Started_at tidak berubah / tidak tertimpa
        $this->assertEquals($originalStartedAt->toDateTimeString(), $meeting->started_at->toDateTimeString());
    }

    public function test_meeting_messages_store_validates_empty_and_max_length(): void
    {
        $teacher = User::factory()->teacher()->create();
        $meeting = Meeting::factory()->create();

        // 1. Pesan kosong
        $response = $this->actingAs($teacher)->post(route('live-class.messages.store', $meeting), [
            'sender_name' => 'Guru',
            'message' => '',
        ]);
        $response->assertSessionHasErrors(['message']);

        // 2. Pesan lebih dari 1000 karakter
        $response2 = $this->actingAs($teacher)->post(route('live-class.messages.store', $meeting), [
            'sender_name' => 'Guru',
            'message' => str_repeat('A', 1001),
        ]);
        $response2->assertSessionHasErrors(['message']);
    }

    /*
    |--------------------------------------------------------------------------
    | 10. GET Views Coverage
    |--------------------------------------------------------------------------
    */

    public function test_all_uncovered_views_can_be_rendered(): void
    {
        $admin = User::factory()->admin()->create();
        $teacher = Teacher::factory()->create();
        $student = Student::factory()->create();
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);
        $course->students()->attach($student->id);
        $session = CourseSession::factory()->create(['course_id' => $course->id]);
        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'course_session_id' => $session->id,
        ]);

        // Buat record presensi untuk attendance.show & take
        AttendanceRecord::factory()->create([
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);

        // 1. live-class.show
        $this->actingAs($admin)->get(route('live-class.show', $meeting))->assertOk();

        // 2. attendance.take
        $this->actingAs($admin)->get(route('attendance.take', $session))->assertOk();

        // 3. attendance.show (detail & tren)
        $this->actingAs($admin)->get(route('attendance.show', $student))->assertOk();

        // 4. courses.sessions.edit
        $this->actingAs($admin)->get(route('courses.sessions.edit', ['course' => $course, 'session' => $session]))->assertOk();

        // 5. courses.sessions.checkin-info
        $this->actingAs($admin)->get(route('courses.sessions.checkin-info', ['course' => $course, 'session' => $session]))->assertOk();

        // 6. students.edit
        $this->actingAs($admin)->get(route('students.edit', $student))->assertOk();

        // 7. teachers.edit
        $this->actingAs($admin)->get(route('teachers.edit', $teacher))->assertOk();
    }
}
