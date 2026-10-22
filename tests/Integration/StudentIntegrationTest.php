<?php

namespace Tests\Integration;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_student_list_and_search_filter(): void
    {
        $studentA = Student::factory()->create([
            'name' => 'Aditya Pratama',
            'student_code' => 'STU-000101',
            'status' => 'regular',
        ]);

        $studentB = Student::factory()->create([
            'name' => 'Bambang Pamungkas',
            'student_code' => 'STU-000102',
            'status' => 'irregular',
        ]);

        // Access index
        $response = $this->actingAs($this->admin)->get(route('students.index'));
        $response->assertOk();
        $response->assertSee('Aditya Pratama');
        $response->assertSee('Bambang Pamungkas');

        // Search by keyword
        $searchResponse = $this->actingAs($this->admin)->get(route('students.index', ['q' => 'Aditya']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Aditya Pratama');
        $searchResponse->assertDontSee('Bambang Pamungkas');

        // Filter by status
        $filterResponse = $this->actingAs($this->admin)->get(route('students.index', ['status' => 'irregular']));
        $filterResponse->assertOk();
        $filterResponse->assertSee('Bambang Pamungkas');
        $filterResponse->assertDontSee('Aditya Pratama');
    }

    public function test_admin_can_create_student_and_user_account_is_auto_generated(): void
    {
        $payload = [
            'name' => 'Citra Dewi',
            'email' => 'citra@example.com',
            'tel' => '081234567890',
            'date_birth' => '2004-05-15',
            'status' => 'regular',
        ];

        $response = $this->actingAs($this->admin)->post(route('students.store'), $payload);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('status', 'success');

        // Verify User was created
        $this->assertDatabaseHas('users', [
            'name' => 'Citra Dewi',
            'email' => 'citra@example.com',
            'role' => 'student',
        ]);

        // Verify Student was created with STU code format
        $this->assertDatabaseHas('students', [
            'name' => 'Citra Dewi',
            'tel' => '081234567890',
            'status' => 'regular',
            'student_code' => 'STU-000001',
        ]);

        $createdStudent = Student::where('name', 'Citra Dewi')->first();
        $this->assertNotNull($createdStudent->user_id);
    }

    public function test_student_creation_fails_with_invalid_data(): void
    {
        // 1. Missing required fields
        $response = $this->actingAs($this->admin)->post(route('students.store'), []);
        $response->assertSessionHasErrors(['name', 'email', 'tel', 'date_birth', 'status']);

        // 2. Duplicate email
        User::factory()->create(['email' => 'existing@example.com']);

        $responseDup = $this->actingAs($this->admin)->post(route('students.store'), [
            'name' => 'Duplicated User',
            'email' => 'existing@example.com',
            'tel' => '081234567890',
            'date_birth' => '2003-01-01',
            'status' => 'regular',
        ]);
        $responseDup->assertSessionHasErrors(['email']);

        // 3. Invalid phone number format
        $responsePhone = $this->actingAs($this->admin)->post(route('students.store'), [
            'name' => 'Invalid Phone User',
            'email' => 'valid@example.com',
            'tel' => 'NOT_A_PHONE_NUMBER!@#',
            'date_birth' => '2003-01-01',
            'status' => 'regular',
        ]);
        $responsePhone->assertSessionHasErrors(['tel']);
    }

    public function test_admin_can_update_student_and_sync_with_user_record(): void
    {
        $user = User::factory()->student()->create([
            'name' => 'Original Student Name',
            'email' => 'original@example.com',
        ]);

        $student = Student::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Student Name',
            'status' => 'regular',
        ]);

        $updatePayload = [
            'name' => 'Updated Student Name',
            'email' => 'updated@example.com',
            'tel' => '089876543210',
            'date_birth' => '2002-12-25',
            'status' => 'irregular',
            'new_password' => 'newsecretpassword123',
        ];

        $response = $this->actingAs($this->admin)->put(route('students.update', $student->id), $updatePayload);

        $response->assertRedirect(route('students.index'));
        $response->assertSessionHas('status', 'success');

        // Check Student updated
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name' => 'Updated Student Name',
            'tel' => '089876543210',
            'status' => 'irregular',
        ]);

        // Check linked User synchronized
        $user->refresh();
        $this->assertEquals('Updated Student Name', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertTrue(Hash::check('newsecretpassword123', $user->password));
    }

    public function test_admin_can_delete_student(): void
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('students.destroy', $student->id));

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('students', ['id' => $student->id]);
    }
}
