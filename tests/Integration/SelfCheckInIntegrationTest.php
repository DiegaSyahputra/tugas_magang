<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelfCheckInIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_public_self_checkin_page_can_be_accessed_via_checkin_code(): void
    {
        $session = CourseSession::factory()->create([
            'checkin_code' => 'QRCODE1234',
        ]);

        $response = $this->get(route('checkin.show', 'QRCODE1234'));

        $response->assertOk();
        $response->assertSee($session->title);
    }

    public function test_enrolled_student_can_self_checkin_and_is_marked_present(): void
    {
        Carbon::setTestNow('2026-10-22 08:05:00'); // 5 menit setelah mulai sesi (08:00)

        $course = Course::factory()->create();
        $student = Student::factory()->create(['name' => 'Siswa Mandiri']);
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'CHECKINNOW',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $response = $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $response->assertRedirect(route('checkin.show', $session->checkin_code));
        $response->assertSessionHas('checkedIn');

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);

        $student->refresh();
        $this->assertEquals(100, $student->attendance_percent);
    }

    public function test_checkin_at_exact_15_minute_tolerance_is_present(): void
    {
        // Sesi mulai 08:00, toleransi default 15 menit => deadline 08:15:00
        Carbon::setTestNow('2026-10-22 08:15:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'TOLERANCE15',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);
    }

    public function test_checkin_at_16_minutes_past_start_is_marked_late(): void
    {
        // Sesi mulai 08:00, check in 08:16:00 (1 menit melewati toleransi) => late
        Carbon::setTestNow('2026-10-22 08:16:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'LATECODE16',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'late',
        ]);
    }

    public function test_repeat_checkin_does_not_downgrade_present_status_to_late(): void
    {
        // 1. Siswa check in tepat waktu jam 08:10 -> status 'present'
        Carbon::setTestNow('2026-10-22 08:10:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'DOUBLECHECK',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $record = AttendanceRecord::where('student_id', $student->id)
            ->where('course_session_id', $session->id)
            ->first();
        $this->assertEquals('present', $record->status);
        $originalCheckinTime = $record->checked_in_at;

        // 2. Siswa menekan check in kedua kalinya jam 08:30 (lewat batas)
        Carbon::setTestNow('2026-10-22 08:30:00');

        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $record->refresh();
        // Status harus TETAP 'present', tidak boleh turun menjadi 'late'
        $this->assertEquals('present', $record->status);
        // Timestamp checkin pertama tidak tertimpa
        $this->assertEquals($originalCheckinTime->toDateTimeString(), $record->checked_in_at->toDateTimeString());
    }

    public function test_self_checkin_upgrades_teacher_premarked_absent_record(): void
    {
        Carbon::setTestNow('2026-10-22 08:05:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'UPGRADEABSENT',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        // Guru sebelumnya menandai absent
        AttendanceRecord::create([
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'absent',
        ]);

        // Siswa melakukan self check in
        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $record = AttendanceRecord::where('student_id', $student->id)
            ->where('course_session_id', $session->id)
            ->first();

        $this->assertEquals('present', $record->status);
    }

    public function test_custom_late_tolerance_config_override(): void
    {
        // Override batas toleransi menjadi 30 menit
        config(['attendance.late_tolerance_minutes' => 30]);
        // Jam 08:25 (lewat 25 menit, tapi masih di bawah 30 menit)
        Carbon::setTestNow('2026-10-22 08:25:00');

        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id);

        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'CONFIGTOLERANCE',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $student->id,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $student->id,
            'course_session_id' => $session->id,
            'status' => 'present',
        ]);
    }

    public function test_non_enrolled_student_cannot_self_checkin(): void
    {
        Carbon::setTestNow('2026-10-22 08:05:00');

        $course = Course::factory()->create();
        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'checkin_code' => 'REJECTCODE',
            'session_date' => '2026-10-22',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $nonEnrolledStudent = Student::factory()->create();

        $response = $this->post(route('checkin.store', $session->checkin_code), [
            'student_id' => $nonEnrolledStudent->id,
        ]);

        $response->assertSessionHasErrors(['student_id']);
        $this->assertDatabaseMissing('attendance_records', [
            'student_id' => $nonEnrolledStudent->id,
            'course_session_id' => $session->id,
        ]);
    }
}
