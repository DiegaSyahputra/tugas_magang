<?php

namespace Tests\Integration;

use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $protectedRoutes = [
            '/dashboard',
            '/courses',
            '/live-class',
            '/library',
            '/payments',
            '/attendance',
            '/reports',
            '/students',
            '/teachers',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    public function test_student_can_access_general_features_but_forbidden_from_academic_management(): void
    {
        $studentUser = User::factory()->student()->create();

        // Accessible by Student
        $this->actingAs($studentUser)->get('/dashboard')->assertOk();
        $this->actingAs($studentUser)->get('/courses')->assertOk();
        $this->actingAs($studentUser)->get('/live-class')->assertOk();
        $this->actingAs($studentUser)->get('/library')->assertOk();
        $this->actingAs($studentUser)->get('/payments')->assertOk();

        // Forbidden for Student (Admin / Teacher only)
        $this->actingAs($studentUser)->get('/attendance')->assertForbidden();
        $this->actingAs($studentUser)->get('/courses/create')->assertForbidden();
        $this->actingAs($studentUser)->get('/reports')->assertForbidden();
        $this->actingAs($studentUser)->get('/students')->assertForbidden();
        $this->actingAs($studentUser)->get('/teachers')->assertForbidden();
    }

    public function test_teacher_can_access_course_and_attendance_but_forbidden_from_master_crud(): void
    {
        $teacherUser = User::factory()->teacher()->create();

        // Accessible by Teacher
        $this->actingAs($teacherUser)->get('/dashboard')->assertOk();
        $this->actingAs($teacherUser)->get('/courses')->assertOk();
        $this->actingAs($teacherUser)->get('/courses/create')->assertOk();
        $this->actingAs($teacherUser)->get('/attendance')->assertOk();
        $this->actingAs($teacherUser)->get('/reports')->assertOk();
        $this->actingAs($teacherUser)->get('/students')->assertOk(); // View list is allowed for teachers
        $this->actingAs($teacherUser)->get('/teachers')->assertOk(); // View list is allowed for teachers

        // Forbidden for Teacher (Admin exclusively)
        $this->actingAs($teacherUser)->get('/students/create')->assertForbidden();
        $this->actingAs($teacherUser)->get('/teachers/create')->assertForbidden();
    }

    public function test_admin_has_full_access_to_all_master_and_management_routes(): void
    {
        $adminUser = User::factory()->admin()->create();

        $this->actingAs($adminUser)->get('/dashboard')->assertOk();
        $this->actingAs($adminUser)->get('/students')->assertOk();
        $this->actingAs($adminUser)->get('/students/create')->assertOk();
        $this->actingAs($adminUser)->get('/teachers')->assertOk();
        $this->actingAs($adminUser)->get('/teachers/create')->assertOk();
        $this->actingAs($adminUser)->get('/courses/create')->assertOk();
        $this->actingAs($adminUser)->get('/reports')->assertOk();
        $this->actingAs($adminUser)->get('/attendance')->assertOk();
    }
}
