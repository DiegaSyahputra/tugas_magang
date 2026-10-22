<?php

namespace Tests\Integration;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->teacherUser = User::factory()->teacher()->create();
    }

    public function test_user_can_view_courses_list_and_search_by_course_name_or_code(): void
    {
        $teacher = Teacher::factory()->create(['name' => 'Pak Dosen']);
        $course1 = Course::factory()->create([
            'name' => 'Mastering Laravel 12',
            'code' => 'LARA-101',
            'teacher_id' => $teacher->id,
        ]);
        $course2 = Course::factory()->create([
            'name' => 'Fullstack React Mastery',
            'code' => 'REACT-202',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('courses.index'));
        $response->assertOk();
        $response->assertSee('Mastering Laravel 12');
        $response->assertSee('Fullstack React Mastery');

        // Search by code
        $searchResponse = $this->actingAs($this->teacherUser)->get(route('courses.index', ['q' => 'LARA-101']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Mastering Laravel 12');
        $searchResponse->assertDontSee('Fullstack React Mastery');
    }

    public function test_teacher_or_admin_can_create_new_course(): void
    {
        $teacher = Teacher::factory()->create();

        $payload = [
            'name' => 'Data Structures and Algorithms',
            'categories' => 'Computer Science',
            'code' => 'CS-301',
            'teacher_id' => $teacher->id,
        ];

        $response = $this->actingAs($this->teacherUser)->post(route('courses.store'), $payload);

        $course = Course::where('code', 'CS-301')->first();
        $this->assertNotNull($course);
        $response->assertRedirect(route('courses.show', $course));

        $this->assertDatabaseHas('courses', [
            'name' => 'Data Structures and Algorithms',
            'code' => 'CS-301',
            'teacher_id' => $teacher->id,
        ]);
    }

    public function test_course_creation_requires_unique_code(): void
    {
        Course::factory()->create(['code' => 'DUPLICATE-CODE']);

        $response = $this->actingAs($this->admin)->post(route('courses.store'), [
            'name' => 'Duplicate Test Course',
            'code' => 'DUPLICATE-CODE',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_course_show_displays_enrolled_and_available_students(): void
    {
        $course = Course::factory()->create();
        $enrolledStudent = Student::factory()->create(['name' => 'Siswa Terdaftar']);
        $availableStudent = Student::factory()->create(['name' => 'Siswa Belum Terdaftar']);

        $course->students()->attach($enrolledStudent->id, ['enrolled_at' => now()]);

        $response = $this->actingAs($this->teacherUser)->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Siswa Terdaftar');
        // Both names should be in DOM: one in the enrolled table, one in the available dropdown
        $response->assertSee('Siswa Belum Terdaftar');
    }
}
