<?php

namespace Tests\Integration;

use App\Models\AttendanceRecord;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseEnrollmentIntegrationTest extends TestCase
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

    public function test_teacher_or_admin_can_enroll_students_into_course(): void
    {
        $course = Course::factory()->create();
        $students = Student::factory()->count(2)->create();

        $response = $this->actingAs($this->teacher)->post(route('courses.enroll', $course), [
            'student_id' => $students->pluck('id')->toArray(),
        ]);

        $response->assertSessionHas('success');

        foreach ($students as $student) {
            $this->assertDatabaseHas('course_student', [
                'course_id' => $course->id,
                'student_id' => $student->id,
            ]);
        }
    }

    public function test_enrolling_student_recalculates_attendance_automatically(): void
    {
        $course = Course::factory()->create();
        $session1 = CourseSession::factory()->create(['course_id' => $course->id]);
        $session2 = CourseSession::factory()->create(['course_id' => $course->id]);

        $student = Student::factory()->create([
            'attendance_percent' => 100,
            'status' => 'regular',
        ]);

        // Student is enrolled in a course with 2 sessions but has 0 attendances recorded yet
        $this->actingAs($this->teacher)->post(route('courses.enroll', $course), [
            'student_id' => [$student->id],
        ]);

        $student->refresh();
        // Since there are 2 sessions and 0 attendance records, attendance_percent drops to 0% and status becomes irregular
        $this->assertEquals(0, $student->attendance_percent);
        $this->assertEquals('irregular', $student->status);
    }

    public function test_admin_can_remove_student_from_course(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($this->admin)->delete(
            route('courses.students.destroy', ['course' => $course, 'student' => $student])
        );

        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('course_student', [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
    }

    public function test_non_admin_cannot_remove_student_from_course(): void
    {
        $course = Course::factory()->create();
        $student = Student::factory()->create();
        $course->students()->attach($student->id, ['enrolled_at' => now()]);

        // Teacher is forbidden from deleting enrollments
        $response = $this->actingAs($this->teacher)->delete(
            route('courses.students.destroy', ['course' => $course, 'student' => $student])
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('course_student', [
            'course_id' => $course->id,
            'student_id' => $student->id,
        ]);
    }
}
