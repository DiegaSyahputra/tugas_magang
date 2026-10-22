<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityAndEdgeCaseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;
    protected User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->teacher = User::factory()->teacher()->create();
        $this->studentUser = User::factory()->student()->create();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 1. Register dengan role=admin harus ditolak.
     * Celah keamanan: Form publik tidak boleh mengizinkan eskalasi hak akses ke admin.
     */
    public function test_public_registration_with_admin_role_must_be_rejected(): void
    {
        $response = $this->post(route('register.post'), [
            'name' => 'Hacker Admin',
            'email' => 'hacker@skillset.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        // Harus gagal validasi pada field role, bukan berhasil terdaftar sebagai admin
        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'hacker@skillset.com']);
    }

    /**
     * 2. attendance.take.store dengan ID siswa yang tidak terdaftar tidak boleh melempar 500 error.
     * Bug: $processedStudentIds undefined jika semua ID di-skip.
     */
    public function test_attendance_take_store_with_unregistered_student_id_does_not_throw_500(): void
    {
        $course = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);

        $unrelatedStudent = Student::factory()->create();

        $response = $this->actingAs($this->teacher)->post(route('attendance.take.store', $session), [
            'attendance' => [
                $unrelatedStudent->id => 'present',
            ],
        ]);

        // Tidak boleh ErrorException 500
        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect(route('attendance.index'));
        $this->assertDatabaseMissing('attendance_records', [
            'student_id' => $unrelatedStudent->id,
            'course_session_id' => $session->id,
        ]);
    }

    /**
     * 3. Registrasi siswa setelah ada siswa yang dihapus tidak boleh bentrok kode STU.
     * Konsistensi penomoran student_code dan transaksi atomik.
     */
    public function test_student_code_generation_remains_unique_after_deletions(): void
    {
        // Buat 1 siswa dengan student_code = 'STU-000011'
        // Pada kode lama (Student::count() + 10 = 1 + 10 = 11), akan bentrok dengan 'STU-000011'!
        // Kode baru dengan scanning max prefix angka akan menghasilkan 'STU-000012' tanpa bentrok unik!
        Student::factory()->create(['student_code' => 'STU-000011']);

        // Registrasi siswa baru via AuthController
        $response = $this->post(route('register.post'), [
            'name' => 'Siswa Baru Unik',
            'email' => 'siswabaru.unik@skillset.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'tel' => '081234567890',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('students', [
            'name' => 'Siswa Baru Unik',
            'student_code' => 'STU-000012',
        ]);
    }

    /**
     * 4. Urutan pesan chat pada live-class messages.index harus kronologis (oldest first).
     * Bug: latest() di relasi + oldest() di controller membuat konflik ORDER BY di SQL.
     */
    public function test_meeting_messages_polling_returns_in_chronological_order(): void
    {
        $meeting = Meeting::factory()->create();

        Carbon::setTestNow('2026-10-22 10:00:00');
        $msg1 = MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'sender_name' => 'Budi',
            'message' => 'Pesan Pertama (10:00)',
            'created_at' => Carbon::parse('2026-10-22 10:00:00'),
        ]);

        Carbon::setTestNow('2026-10-22 10:05:00');
        $msg2 = MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'sender_name' => 'Siti',
            'message' => 'Pesan Kedua (10:05)',
            'created_at' => Carbon::parse('2026-10-22 10:05:00'),
        ]);

        $response = $this->actingAs($this->studentUser)->getJson(route('live-class.messages.index', $meeting));
        $response->assertOk();

        $data = $response->json();
        $this->assertCount(2, $data);
        // Pesan pertama harus berada di indeks 0 untuk tampilan chat stream
        $this->assertEquals('Pesan Pertama (10:00)', $data[0]['message']);
        $this->assertEquals('Pesan Kedua (10:05)', $data[1]['message']);
    }

    /**
     * 5. Validasi MasterStudent harus menolak email dengan format tidak valid.
     * Celah: MasterStudent belum memiliki rule 'email'.
     */
    public function test_student_creation_rejects_invalid_email_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('students.store'), [
            'name' => 'Siswa Email Salah',
            'email' => 'bukan-sebuah-email',
            'tel' => '081234567890',
            'date_birth' => '2004-01-01',
            'status' => 'regular',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    /**
     * 6. Hapus siswa lalu buat ulang dengan email yang sama tidak boleh terblokir duplicate email User.
     * Bug: Student::delete() sebelumnya meninggalkan akun User yatim.
     */
    public function test_deleted_student_email_can_be_reused_without_conflict(): void
    {
        $user = User::factory()->student()->create(['email' => 'reusable@skillset.com']);
        $student = Student::factory()->create([
            'user_id' => $user->id,
            'name' => 'Siswa Pertama',
        ]);

        // Hapus siswa
        $this->actingAs($this->admin)->delete(route('students.destroy', $student->id));

        // Buat siswa baru dengan email yang sama
        $response = $this->actingAs($this->admin)->post(route('students.store'), [
            'name' => 'Siswa Baru Email Sama',
            'email' => 'reusable@skillset.com',
            'tel' => '081234567890',
            'date_birth' => '2003-01-01',
            'status' => 'regular',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('students', ['name' => 'Siswa Baru Email Sama']);
    }

    /**
     * 7. PUT /courses/{A}/sessions/{sesi milik course B} harus ditolak (Scoped Route Binding).
     * Celah BOLA: Sesi milik course B tidak boleh diupdate lewat route course A.
     */
    public function test_updating_session_belonging_to_another_course_is_rejected(): void
    {
        $courseA = Course::factory()->create();
        $courseB = Course::factory()->create();
        $sessionOfCourseB = CourseSession::factory()->create(['course_id' => $courseB->id]);

        $response = $this->actingAs($this->teacher)->put(
            route('courses.sessions.update', ['course' => $courseA, 'session' => $sessionOfCourseB]),
            [
                'title' => 'Bajak Sesi Course B',
                'session_date' => now()->format('Y-m-d'),
                'start_time' => '08:00',
                'end_time' => '10:00',
            ]
        );

        // Harus 404 (tidak ditemukan di scope Course A) atau 403
        $this->assertTrue(in_array($response->status(), [404, 403]));
    }

    /**
     * 8. Live-class check-in tidak boleh mencatat siswa yang tidak terdaftar di course meeting,
     * dan tidak boleh menimpa status 'late' yang sudah tercatat menjadi 'present'.
     */
    public function test_live_class_checkin_validates_enrollment_and_does_not_overwrite_late_status(): void
    {
        $course = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);
        $meeting = Meeting::factory()->create([
            'course_id' => $course->id,
            'course_session_id' => $session->id,
        ]);

        $enrolledStudent = Student::factory()->create();
        $nonEnrolledStudent = Student::factory()->create();
        $course->students()->attach($enrolledStudent->id);

        // Siswa non-enrolled mencoba check-in
        $respNonEnrolled = $this->actingAs($this->studentUser)->postJson(
            route('live-class.check-in', $meeting),
            ['student_id' => $nonEnrolledStudent->id]
        );
        $this->assertFalse($respNonEnrolled->json('recorded'));

        // Siswa enrolled sudah berstatus late
        AttendanceRecord::create([
            'student_id' => $enrolledStudent->id,
            'course_session_id' => $session->id,
            'status' => 'late',
            'checked_in_at' => now()->subHour(),
        ]);

        // Check-in ulang via video call tidak boleh mengubah late jadi present
        $respEnrolled = $this->actingAs($this->studentUser)->postJson(
            route('live-class.check-in', $meeting),
            ['student_id' => $enrolledStudent->id]
        );

        $record = AttendanceRecord::where('student_id', $enrolledStudent->id)
            ->where('course_session_id', $session->id)
            ->first();
        $this->assertEquals('late', $record->status);
    }

    /**
     * 9. Self check-in untuk sesi lampau atau masa depan harus ditolak jika di luar jendela waktu.
     */
    public function test_self_checkin_rejected_for_sessions_outside_valid_date(): void
    {
        Carbon::setTestNow('2026-10-22 10:00:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        // Sesi bulan lalu
        $pastSession = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'PASTSESSION',
            'session_date' => Carbon::parse('2026-09-01'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $responsePast = $this->post(route('checkin.store', $pastSession->checkin_code), [
            'student_id' => $student->id,
        ]);

        $responsePast->assertSessionHasErrors(['student_id']);

        // Sesi minggu depan
        $futureSession = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'FUTURESESSION',
            'session_date' => Carbon::parse('2026-10-30'),
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);

        $responseFuture = $this->post(route('checkin.store', $futureSession->checkin_code), [
            'student_id' => $student->id,
        ]);

        $responseFuture->assertSessionHasErrors(['student_id']);
    }

    /**
     * 10. Siswa yang login hanya boleh melihat riwayat pembayarannya sendiri di payments.index.
     */
    public function test_student_only_sees_their_own_payments(): void
    {
        $studentA = Student::factory()->create(['user_id' => $this->studentUser->id]);

        $otherUser = User::factory()->student()->create();
        $studentB = Student::factory()->create(['user_id' => $otherUser->id]);

        $paymentA = Payment::factory()->create([
            'student_id' => $studentA->id,
            'invoice_id' => 'INV-STUDENT-A-001',
        ]);

        $paymentB = Payment::factory()->create([
            'student_id' => $studentB->id,
            'invoice_id' => 'INV-STUDENT-B-002',
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('payments.index'));

        $response->assertOk();
        $response->assertSee('INV-STUDENT-A-001');
        $response->assertDontSee('INV-STUDENT-B-002');
    }

    /**
     * 11. DELETE /attendance/{student} dapat menghapus riwayat kehadiran siswa.
     */
    public function test_admin_can_delete_attendance_history_for_student(): void
    {
        $student = Student::factory()->create(['attendance_percent' => 100, 'status' => 'regular']);
        $record = AttendanceRecord::factory()->create(['student_id' => $student->id]);

        $response = $this->actingAs($this->admin)->delete(route('attendance.destroy', $student));

        $response->assertRedirect();
        // Riwayat record presensi harus terhapus
        $this->assertDatabaseMissing('attendance_records', ['id' => $record->id]);
        
        // Persentase dan status siswa direset ke 0 & irregular
        $student->refresh();
        $this->assertEquals(0, $student->attendance_percent);
        $this->assertEquals('irregular', $student->status);

        // Akun siswa & data profil siswa TETAP ADA di database (bukan terhapus akunnya)
        $this->assertDatabaseHas('students', ['id' => $student->id]);
    }
}
