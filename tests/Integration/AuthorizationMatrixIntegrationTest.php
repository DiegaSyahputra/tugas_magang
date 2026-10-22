<?php

namespace Tests\Integration;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Meeting;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthorizationMatrixIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teacherUser;
    protected User $studentUser;

    protected Student $student;
    protected Teacher $teacher;
    protected Course $course;
    protected CourseSession $session;
    protected Meeting $meeting;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::factory()->admin()->create();
        $this->teacherUser = User::factory()->teacher()->create();
        $this->studentUser = User::factory()->student()->create();

        $this->teacher = Teacher::factory()->create();
        $this->student = Student::factory()->create();
        $this->course = Course::factory()->create(['teacher_id' => $this->teacher->id]);
        $this->course->students()->attach($this->student->id);
        $this->session = CourseSession::factory()->create(['course_id' => $this->course->id]);
        $this->meeting = Meeting::factory()->create([
            'course_id' => $this->course->id,
            'course_session_id' => $this->session->id,
        ]);
    }

    /**
     * Matriks Otorisasi untuk Operasi Penulisan/Mutasi Data (POST, PUT, DELETE).
     * Menguji setiap rute terhadap seluruh peran (guest, student, teacher, admin).
     */
    #[DataProvider('writeActionMatrix')]
    public function test_write_action_authorization_matrix(
        string $method,
        string $routeKey,
        array $roleExpectations
    ): void {
        $uri = $this->resolveRouteUri($routeKey);

        foreach ($roleExpectations as $role => $expectedStatus) {
            $request = match ($role) {
                'guest' => $this,
                'student' => $this->actingAs($this->studentUser),
                'teacher' => $this->actingAs($this->teacherUser),
                'admin' => $this->actingAs($this->admin),
            };

            $response = match ($method) {
                'GET' => $request->get($uri),
                'POST' => $request->post($uri, $this->samplePayload($routeKey)),
                'PUT' => $request->put($uri, $this->samplePayload($routeKey)),
                'DELETE' => $request->delete($uri),
            };

            if ($expectedStatus === 302 && $role === 'guest') {
                $response->assertRedirect('/login');
            } elseif ($expectedStatus === 403) {
                $response->assertForbidden();
            } else {
                $statusCode = $response->baseResponse->getStatusCode();
                $this->assertTrue(
                    in_array($statusCode, [200, 302]),
                    "Aksi {$method} {$uri} oleh role {$role} gagal dengan status {$statusCode}."
                );
                if ($statusCode === 302) {
                    $response->assertSessionHasNoErrors();
                }
            }
        }
    }

    public static function writeActionMatrix(): array
    {
        return [
            // Master Siswa: Hanya Admin
            ['POST', 'students.store', ['guest' => 302, 'student' => 403, 'teacher' => 403, 'admin' => 302]],
            ['PUT', 'students.update', ['student' => 403, 'teacher' => 403, 'admin' => 302]],
            ['DELETE', 'students.destroy', ['student' => 403, 'teacher' => 403, 'admin' => 302]],

            // Master Guru: Hanya Admin
            ['POST', 'teachers.store', ['guest' => 302, 'student' => 403, 'teacher' => 403, 'admin' => 302]],
            ['PUT', 'teachers.update', ['student' => 403, 'teacher' => 403, 'admin' => 302]],
            ['DELETE', 'teachers.destroy', ['student' => 403, 'teacher' => 403, 'admin' => 302]],

            // Course: Admin & Teacher
            ['POST', 'courses.store', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['POST', 'courses.enroll', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['DELETE', 'courses.students.destroy', ['student' => 403, 'teacher' => 403, 'admin' => 302]],

            // Sesi Pertemuan: Buat & Update (Admin & Teacher), Hapus (Hanya Admin)
            ['POST', 'courses.sessions.store', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['PUT', 'courses.sessions.update', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['DELETE', 'courses.sessions.destroy', ['student' => 403, 'teacher' => 403, 'admin' => 302]],

            // Absensi Manual: Admin & Teacher
            ['POST', 'attendance.take.store', ['student' => 403, 'teacher' => 302, 'admin' => 302]],

            // Live Class: Admin & Teacher
            ['POST', 'live-class.store', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['POST', 'live-class.start', ['student' => 403, 'teacher' => 302, 'admin' => 302]],
            ['POST', 'live-class.end', ['student' => 403, 'teacher' => 302, 'admin' => 302]],

            // Reports Export: Admin & Teacher
            ['GET', 'reports.export-pdf', ['student' => 403, 'teacher' => 200, 'admin' => 200]],
            ['GET', 'reports.export-excel', ['student' => 403, 'teacher' => 200, 'admin' => 200]],
        ];
    }

    private function resolveRouteUri(string $routeKey): string
    {
        return match ($routeKey) {
            'students.store' => route('students.store'),
            'students.update' => route('students.update', $this->student->id),
            'students.destroy' => route('students.destroy', $this->student->id),

            'teachers.store' => route('teachers.store'),
            'teachers.update' => route('teachers.update', $this->teacher->id),
            'teachers.destroy' => route('teachers.destroy', $this->teacher->id),

            'courses.store' => route('courses.store'),
            'courses.enroll' => route('courses.enroll', $this->course),
            'courses.students.destroy' => route('courses.students.destroy', ['course' => $this->course, 'student' => $this->student]),

            'courses.sessions.store' => route('courses.sessions.store', $this->course),
            'courses.sessions.update' => route('courses.sessions.update', ['course' => $this->course, 'session' => $this->session]),
            'courses.sessions.destroy' => route('courses.sessions.destroy', ['course' => $this->course, 'session' => $this->session]),

            'attendance.take.store' => route('attendance.take.store', $this->session),

            'live-class.store' => route('live-class.store'),
            'live-class.start' => route('live-class.start', $this->meeting),
            'live-class.end' => route('live-class.end', $this->meeting),

            'reports.export-pdf' => route('reports.export-pdf'),
            'reports.export-excel' => route('reports.export-excel'),
        };
    }

    private function samplePayload(string $routeKey): array
    {
        return match ($routeKey) {
            'students.store' => [
                'name' => 'Sample Student',
                'email' => 'sample' . uniqid() . '@skillset.com',
                'date_birth' => '2005-01-01',
                'status' => 'regular',
                'tel' => '081234567890',
            ],
            'students.update' => [
                'name' => 'Updated Student Name',
                'email' => 'updated' . uniqid() . '@skillset.com',
                'date_birth' => '2005-01-01',
                'status' => 'regular',
                'tel' => '081234567890',
            ],
            'teachers.store' => [
                'name' => 'Sample Teacher',
                'email' => 'teacher' . uniqid() . '@skillset.com',
                'specialization' => 'Web Development',
                'tel' => '081234567890',
            ],
            'teachers.update' => [
                'name' => 'Updated Teacher Name',
                'email' => 'updated_teacher' . uniqid() . '@skillset.com',
                'specialization' => 'Data Science',
                'tel' => '081234567890',
            ],
            'courses.store' => [
                'teacher_id' => $this->teacher->id,
                'categories' => 'Programming',
                'name' => 'Fullstack Laravel',
                'code' => 'CRS-' . uniqid(),
            ],
            'courses.enroll' => [
                'student_id' => [$this->student->id],
            ],
            'courses.sessions.store' => [
                'title' => 'Pertemuan Baru',
                'session_date' => now()->format('Y-m-d'),
                'start_time' => '09:00',
                'end_time' => '11:00',
            ],
            'courses.sessions.update' => [
                'title' => 'Pertemuan Diedit',
                'session_date' => now()->format('Y-m-d'),
                'start_time' => '09:30',
                'end_time' => '11:30',
            ],
            'attendance.take.store' => [
                'attendance' => [
                    $this->student->id => 'present',
                ],
            ],
            'live-class.store' => [
                'course_id' => $this->course->id,
                'course_session_id' => $this->session->id,
                'title' => 'Live Session Room',
                'scheduled_at' => now()->format('Y-m-d H:i:s'),
                'duration_minutes' => 60,
            ],
            default => [],
        };
    }
}
