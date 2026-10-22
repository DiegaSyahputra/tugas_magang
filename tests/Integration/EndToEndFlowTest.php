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
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon mock
        parent::tearDown();
    }

    /**
     * E2E Flow 1: Alur Lengkap Akademik & Presensi:
     * Admin buat Guru -> Guru buat Kursus & Sesi -> Admin buat & daftarkan Siswa ->
     * Siswa Self Check-In -> Statistik berubah di Dashboard, Attendance, dan Isi Dokumen Laporan CSV.
     */
    public function test_complete_academic_and_attendance_lifecycle(): void
    {
        Storage::fake('public');
        Carbon::setTestNow('2026-10-22 09:05:00'); // Sesi hari ini jam 09:00, check-in jam 09:05 (toleransi 15 mnt => present)

        $admin = User::factory()->admin()->create();

        // 1. Admin membuat Guru baru
        $teacherPayload = [
            'name' => 'Dr. Bambang Setiawan',
            'email' => 'bambang.setiawan@skillset.com',
            'tel' => '081299887766',
            'specialization' => 'Web Architecture & Cloud',
        ];
        $this->actingAs($admin)->post(route('teachers.store'), $teacherPayload)
            ->assertRedirect(route('teachers.index'));

        $teacherUser = User::where('email', 'bambang.setiawan@skillset.com')->first();
        $this->assertNotNull($teacherUser);
        $teacher = Teacher::where('user_id', $teacherUser->id)->first();
        $this->assertNotNull($teacher);

        // 2. Guru membuat Course baru
        $coursePayload = [
            'name' => 'Fullstack Laravel 12 Masterclass',
            'categories' => 'Web Development',
            'code' => 'FS-LARA-12',
            'teacher_id' => $teacher->id,
        ];
        $this->actingAs($teacherUser)->post(route('courses.store'), $coursePayload);
        $course = Course::where('code', 'FS-LARA-12')->first();
        $this->assertNotNull($course);

        // 3. Guru membuat Sesi Pertemuan pertama (hari ini 09:00 - 11:00)
        $sessionPayload = [
            'title' => 'Pertemuan 1: Instalasi & Arsitektur MVC',
            'session_date' => '2026-10-22',
            'start_time' => '09:00',
            'end_time' => '11:00',
        ];
        $this->actingAs($teacherUser)->post(route('courses.sessions.store', $course), $sessionPayload);
        $session = CourseSession::where('course_id', $course->id)->first();
        $this->assertNotNull($session);
        $this->assertNotEmpty($session->checkin_code);

        // 4. Admin membuat Siswa baru
        $studentPayload = [
            'name' => 'Rian Pratama',
            'email' => 'rian.pratama@skillset.com',
            'tel' => '081344556677',
            'date_birth' => '2004-03-20',
            'status' => 'irregular',
        ];
        $this->actingAs($admin)->post(route('students.store'), $studentPayload)
            ->assertRedirect(route('students.index'));

        $student = Student::where('name', 'Rian Pratama')->first();
        $this->assertNotNull($student);
        $studentUser = $student->user;
        $this->assertNotNull($studentUser);

        // 5. Admin mendaftarkan Siswa ke Course
        $this->actingAs($admin)->post(route('courses.enroll', $course), [
            'student_id' => [$student->id],
        ]);
        $this->assertDatabaseHas('course_student', [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);

        // Karena sudah terdaftar di 1 sesi tapi belum diabsen, kehadiran siswa harus 0%
        $student->refresh();
        $this->assertEquals(0, $student->attendance_percent);
        $this->assertEquals('irregular', $student->status);

        // 6. Siswa melakukan Self Check-In via kode sesi publik
        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ])->assertRedirect(route('checkin.show', $session->checkin_code));

        // 7. Verifikasi Perubahan Menyeluruh (Ripple Effect)
        // a. Catatan absensi tersimpan dengan status 'present'
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);

        // b. Siswa otomatis berubah menjadi 100% dan status 'regular'
        $student->refresh();
        $this->assertEquals(100, $student->attendance_percent);
        $this->assertEquals('regular', $student->status);

        // c. Dashboard mencerminkan statistik siswa regular
        $dashResponse = $this->actingAs($admin)->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertViewHas('regularCount', 1);
        $dashResponse->assertViewHas('irregularCount', 0);
        $dashResponse->assertViewHas('overallPercent', 100.0);

        // d. Laporan Kehadiran CSV memuat nama siswa, NIM, 100%, dan status REGULAR
        $csvResponse = $this->actingAs($admin)->get(route('reports.export-excel'));
        $csvResponse->assertOk();

        // Baca file CSV yang disimpan di storage
        $files = Storage::disk('public')->files('reports');
        $csvFile = collect($files)->first(fn ($f) => str_ends_with($f, '.csv'));
        $this->assertNotNull($csvFile);
        $csvContent = Storage::disk('public')->get($csvFile);

        $this->assertStringContainsString($student->student_code, $csvContent);
        $this->assertStringContainsString('Rian Pratama', $csvContent);
        $this->assertStringContainsString('regular', $csvContent);
        $this->assertStringContainsString('100%', $csvContent);
    }

    /**
     * E2E Flow 2: Live Class Meeting dengan Sesi Terhubung:
     * Jadwalkan Meeting -> Mulai Meeting -> Siswa Join & Check-In -> Chatting -> Siswa Check-Out -> Durasi Tercatat -> Selesai.
     */
    public function test_live_class_meeting_connected_to_session_lifecycle(): void
    {
        Carbon::setTestNow('2026-10-22 14:00:00');

        $teacherUser = User::factory()->teacher()->create();
        $studentUser = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $studentUser->id]);

        $course = Course::factory()->create();
        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'session_date' => '2026-10-22',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
        ]);
        $course->students()->attach($student->id);

        // 1. Guru menjadwalkan Live Class
        $meetingPayload = [
            'course_id' => $course->id,
            'course_session_id' => $session->id,
            'title' => 'Live Coding: RESTful API di Laravel 12',
            'scheduled_at' => '2026-10-22 14:00:00',
            'duration_minutes' => 60,
        ];
        $this->actingAs($teacherUser)->post(route('live-class.store'), $meetingPayload);
        $meeting = Meeting::where('title', 'Live Coding: RESTful API di Laravel 12')->first();
        $this->assertNotNull($meeting);

        // 2. Guru memulai meeting (jam 14:00)
        $this->actingAs($teacherUser)->post(route('live-class.start', $meeting));
        $meeting->refresh();
        $this->assertEquals('live', $meeting->status);

        // 3. Siswa bergabung ke video call (trigger check-in)
        $this->actingAs($studentUser)->postJson(route('live-class.check-in', $meeting), [
            'student_id' => $student->id,
        ])->assertJson(['recorded' => true]);

        $record = AttendanceRecord::where('student_id', $student->id)
            ->where('course_session_id', $session->id)
            ->first();
        $this->assertNotNull($record);
        $this->assertEquals('present', $record->status);

        // 4. Guru & Siswa mengirim pesan chat
        $this->actingAs($teacherUser)->postJson(route('live-class.messages.store', $meeting), [
            'sender_name' => $teacherUser->name,
            'message' => 'Selamat datang di live coding sesi ini!',
        ])->assertJson(['success' => true]);

        Carbon::setTestNow('2026-10-22 14:10:00');
        $this->actingAs($studentUser)->postJson(route('live-class.messages.store', $meeting), [
            'sender_name' => $student->name,
            'message' => 'Siap pak, suara dan layar terlihat jelas.',
        ])->assertJson(['success' => true]);

        $chatResponse = $this->actingAs($studentUser)->getJson(route('live-class.messages.index', $meeting));
        $chatData = $chatResponse->json();
        $this->assertEquals('Selamat datang di live coding sesi ini!', $chatData[0]['message']);
        $this->assertEquals('Siap pak, suara dan layar terlihat jelas.', $chatData[1]['message']);

        // 5. Siswa keluar video call setelah 50 menit (14:50) -> trigger check-out
        Carbon::setTestNow('2026-10-22 14:50:00');
        $this->actingAs($studentUser)->postJson(route('live-class.check-out', $meeting), [
            'student_id' => $student->id,
        ])->assertJson(['recorded' => true]);

        $record->refresh();
        $this->assertEquals(50, $record->duration_minutes);
        $this->assertEquals('0 hr 50 min', $record->duration_label);

        // 6. Guru mengakhiri meeting
        $this->actingAs($teacherUser)->post(route('live-class.end', $meeting));
        $meeting->refresh();
        $this->assertEquals('ended', $meeting->status);
    }

    /**
     * E2E Flow 3: Alur Lengkap Transaksi Pembayaran hingga Pelaporan Keuangan:
     * Siswa buat invoice -> Unggah bukti transfer -> Status berubah jadi 'paid' ->
     * Ekspor & unduh struk invoice -> Admin melihat penambahan pendapatan di Dashboard & Laporan Keuangan.
     */
    public function test_payment_lifecycle_and_financial_reporting(): void
    {
        Storage::fake('public');

        $admin = User::factory()->admin()->create();
        $studentUser = User::factory()->student()->create();
        $student = Student::factory()->create(['user_id' => $studentUser->id, 'name' => 'Aditya Wijaya']);

        $course = Course::factory()->create(['name' => 'DevOps & Kubernetes Masterclass']);

        // 1. Siswa membuat tagihan invoice pembayaran kelas Rp 1.500.000
        $paymentPayload = [
            'student_id' => $student->id,
            'course_id' => $course->id,
            'amount' => 1500000,
            'payment_method' => 'BCA Virtual Account',
        ];
        $this->actingAs($studentUser)->post(route('payments.store'), $paymentPayload)
            ->assertRedirect(route('payments.index'));

        $payment = Payment::where('student_id', $student->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(1500000, (float) $payment->amount);

        // 2. Siswa mengunggah bukti transfer
        $proofFile = \Illuminate\Http\UploadedFile::fake()->create('bukti_transfer.jpg', 600, 'image/jpeg');
        $this->actingAs($studentUser)->post(route('payments.upload-proof', $payment), [
            'proof_file' => $proofFile,
        ])->assertRedirect(route('payments.index'));

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->proof_path);
        Storage::disk('public')->assertExists($payment->proof_path);

        // 3. Siswa mengunduh berkas invoice resmi
        $invoiceResponse = $this->actingAs($studentUser)->get(route('payments.export-invoice', $payment));
        $this->assertEquals(200, $invoiceResponse->getStatusCode());

        $payment->refresh();
        $this->assertNotNull($payment->invoice_path);
        Storage::disk('public')->assertExists($payment->invoice_path);

        $invoiceContent = Storage::disk('public')->get($payment->invoice_path);
        $this->assertStringContainsString('Aditya Wijaya', $invoiceContent);
        $this->assertStringContainsString('1.500.000', $invoiceContent);
        $this->assertStringContainsString('BCA Virtual Account', $invoiceContent);
        $this->assertStringContainsString('PAID', $invoiceContent);

        // 4. Admin mengakses laporan keuangan eksekutif
        $reportResponse = $this->actingAs($admin)->get(route('reports.export-pdf'));
        $this->assertEquals(200, $reportResponse->getStatusCode());
        $pdfHtmlContent = $reportResponse->streamedContent();

        $this->assertStringContainsString('Laporan Akademik & Performa Platform SkillSet', $pdfHtmlContent);
        $this->assertStringContainsString('DevOps &amp; Kubernetes Masterclass', $pdfHtmlContent);
    }

    /**
     * E2E Flow 4: Alur Materi Digital Perpustakaan (Upload, Browse/Search, dan Download):
     * Guru mengunggah modul digital -> Siswa mencari materi di perpustakaan ->
     * Siswa mengunduh berkas -> Counter unduhan otomatis bertambah.
     */
    public function test_digital_library_resource_lifecycle(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();

        // 1. Guru mengunggah materi e-book digital
        $fakeEbook = \Illuminate\Http\UploadedFile::fake()->create('panduan_docker.pdf', 2048, 'application/pdf');
        $libraryPayload = [
            'title' => 'Panduan Komprehensif Docker dan Kontainer',
            'category' => 'Cloud & DevOps',
            'file_format' => 'PDF E-Book',
            'cover_icon' => '🐳',
            'file' => $fakeEbook,
        ];

        $this->actingAs($teacher)->post(route('library.store'), $libraryPayload)
            ->assertRedirect(route('library.index'));

        $resource = Library::where('title', 'Panduan Komprehensif Docker dan Kontainer')->first();
        $this->assertNotNull($resource);
        $this->assertEquals(0, $resource->downloads);
        $this->assertNotNull($resource->file_path);
        Storage::disk('public')->assertExists($resource->file_path);

        // 2. Siswa mencari materi di halaman library
        $searchResponse = $this->actingAs($student)->get(route('library.index', ['q' => 'Docker']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Panduan Komprehensif Docker dan Kontainer');

        // 3. Siswa mengunduh materi
        $downloadResponse = $this->actingAs($student)->get(route('library.download', $resource));
        $this->assertEquals(200, $downloadResponse->getStatusCode());

        // 4. Verifikasi counter download bertambah
        $resource->refresh();
        $this->assertEquals(1, $resource->downloads);
    }
}
