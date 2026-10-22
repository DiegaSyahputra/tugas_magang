<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSessionAndAttendanceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->teacher = User::factory()->teacher()->create();
    }

    public function test_teacher_can_create_session_with_valid_times(): void
    {
        $course = Course::factory()->create();

        $payload = [
            'title' => 'Pertemuan 1 - Konsep Dasar Database Relasional',
            'session_date' => now()->format('Y-m-d'),
            'start_time' => '08:00',
            'end_time' => '10:00',
        ];

        $response = $this->actingAs($this->teacher)->post(route('courses.sessions.store', $course), $payload);

        $response->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('course_sessions', [
            'course_id' => $course->id,
            'title' => 'Pertemuan 1 - Konsep Dasar Database Relasional',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
        ]);

        $session = CourseSession::where('title', 'Pertemuan 1 - Konsep Dasar Database Relasional')->first();
        $this->assertNotEmpty($session->checkin_code);
    }

    public function test_session_creation_fails_if_end_time_is_before_start_time(): void
    {
        $course = Course::factory()->create();

        $response = $this->actingAs($this->teacher)->post(route('courses.sessions.store', $course), [
            'title' => 'Invalid Time Session',
            'session_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '08:00', // Invalid: end time before start time
        ]);

        $response->assertSessionHasErrors(['end_time']);
    }

    public function test_teacher_can_take_manual_attendance_and_student_status_updates(): void
    {
        $course = Course::factory()->create();
        $session = CourseSession::factory()->create([
            'course_id' => $course->id,
            'start_time' => '08:00',
            'end_time' => '10:00',
        ]);

        $studentA = Student::factory()->create(['name' => 'Siswa Hadir']);
        $studentB = Student::factory()->create(['name' => 'Siswa Absen']);

        $course->students()->attach([$studentA->id, $studentB->id], ['enrolled_at' => now()]);

        // Submit manual attendance
        $payload = [
            'attendance' => [
                $studentA->id => 'present',
                $studentB->id => 'absent',
            ],
        ];

        $response = $this->actingAs($this->teacher)->post(route('attendance.take.store', $session), $payload);

        $response->assertRedirect(route('attendance.index'));
        $response->assertSessionHas('success');

        // Check Attendance Records
        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $studentA->id,
            'course_session_id' => $session->id,
            'status' => 'present',
            'duration_minutes' => 120,
        ]);

        $this->assertDatabaseHas('attendance_records', [
            'student_id' => $studentB->id,
            'course_session_id' => $session->id,
            'status' => 'absent',
            'duration_minutes' => 0,
        ]);

        // Check student attendance recalculation
        $studentA->refresh();
        $this->assertEquals(100, $studentA->attendance_percent);
        $this->assertEquals('regular', $studentA->status);

        $studentB->refresh();
        $this->assertEquals(0, $studentB->attendance_percent);
        $this->assertEquals('irregular', $studentB->status);
    }

    public function test_deleting_session_recalculates_attendance_for_students(): void
    {
        $course = Course::factory()->create();
        $session1 = CourseSession::factory()->create(['course_id' => $course->id]);
        $session2 = CourseSession::factory()->create(['course_id' => $course->id]);

        $student = Student::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        // Student attended session 1
        AttendanceRecord::create([
            'student_id' => $student->id,
            'course_session_id' => $session1->id,
            'status' => 'present',
        ]);

        $student->recalculateAttendance();
        $this->assertEquals(50, $student->fresh()->attendance_percent);

        // Delete session 2 (Admin exclusively)
        $response = $this->actingAs($this->admin)->delete(
            route('courses.sessions.destroy', ['course' => $course, 'session' => $session2])
        );

        $response->assertRedirect(route('courses.show', $course));
        $this->assertDatabaseMissing('course_sessions', ['id' => $session2->id]);

        // Now total sessions is 1, attended is 1 => attendance percent becomes 100%
        $student->refresh();
        $this->assertEquals(100, $student->attendance_percent);
    }

    public function test_teacher_can_view_student_attendance_detail_and_trend(): void
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->teacher)->get(route('attendance.show', $student));

        $response->assertOk();
        $response->assertSee($student->name);
    }
}
