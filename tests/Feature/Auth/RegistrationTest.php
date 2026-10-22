<?php

namespace Tests\Feature\Auth;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_student_can_register_and_student_record_is_created(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'student',
            'tel' => '081234567890',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'role' => 'student',
        ]);

        $this->assertDatabaseHas('students', [
            'name' => 'Budi Santoso',
            'tel' => '081234567890',
            'status' => 'regular',
        ]);
    }

    public function test_new_teacher_can_register_and_teacher_record_is_created(): void
    {
        $response = $this->post('/register', [
            'name' => 'Prof. Haryanto',
            'email' => 'haryanto@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'teacher',
            'tel' => '081298765432',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'name' => 'Prof. Haryanto',
            'email' => 'haryanto@example.com',
            'role' => 'teacher',
        ]);

        $this->assertDatabaseHas('teachers', [
            'name' => 'Prof. Haryanto',
            'specialization' => 'General Lecturer',
            'tel' => '081298765432',
        ]);
    }

    public function test_registration_requires_valid_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Invalid Role User',
            'email' => 'invalid@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertGuest();
    }

    public function test_registration_rolls_back_user_if_student_creation_fails(): void
    {
        Student::creating(function () {
            throw new \Exception('Simulated student creation failure');
        });

        try {
            $this->post('/register', [
                'name' => 'Failing Student',
                'email' => 'failing.student@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'student',
                'tel' => '081234567890',
            ]);
        } catch (\Exception $e) {
            // Expected simulated exception
        }

        // Akun user tidak boleh tertinggal sebagai user yatim
        $this->assertDatabaseMissing('users', ['email' => 'failing.student@example.com']);
    }
}
