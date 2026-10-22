<?php

namespace Tests\Integration;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TeacherIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_view_teachers_and_search_by_specialization(): void
    {
        $teacherA = Teacher::factory()->create([
            'name' => 'Dr. Edwin Kurniawan',
            'specialization' => 'Artificial Intelligence',
        ]);

        $teacherB = Teacher::factory()->create([
            'name' => 'Siti Nurhaliza, M.Kom',
            'specialization' => 'Cyber Security',
        ]);

        $response = $this->actingAs($this->admin)->get(route('teachers.index'));
        $response->assertOk();
        $response->assertSee('Dr. Edwin Kurniawan');
        $response->assertSee('Siti Nurhaliza, M.Kom');

        // Search by specialization
        $searchResponse = $this->actingAs($this->admin)->get(route('teachers.index', ['q' => 'Cyber Security']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Siti Nurhaliza, M.Kom');
        $searchResponse->assertDontSee('Dr. Edwin Kurniawan');
    }

    public function test_admin_can_create_teacher_and_user_account_is_auto_generated(): void
    {
        $payload = [
            'name' => 'Dr. Rahmat Hidayat',
            'email' => 'rahmat@skillset.com',
            'tel' => '081234567899',
            'specialization' => 'Machine Learning & Python',
        ];

        $response = $this->actingAs($this->admin)->post(route('teachers.store'), $payload);

        $response->assertRedirect(route('teachers.index'));
        $response->assertSessionHas('status', 'success');

        // Check user created with teacher role
        $this->assertDatabaseHas('users', [
            'name' => 'Dr. Rahmat Hidayat',
            'email' => 'rahmat@skillset.com',
            'role' => 'teacher',
        ]);

        // Check teacher profile created
        $this->assertDatabaseHas('teachers', [
            'name' => 'Dr. Rahmat Hidayat',
            'specialization' => 'Machine Learning & Python',
            'tel' => '081234567899',
        ]);
    }

    public function test_teacher_creation_validation_rules(): void
    {
        // 1. Empty fields
        $response = $this->actingAs($this->admin)->post(route('teachers.store'), []);
        $response->assertSessionHasErrors(['name', 'email', 'tel', 'specialization']);

        // 2. Duplicate email
        User::factory()->create(['email' => 'teacher.exists@skillset.com']);

        $dupResponse = $this->actingAs($this->admin)->post(route('teachers.store'), [
            'name' => 'Another Teacher',
            'email' => 'teacher.exists@skillset.com',
            'tel' => '081234567890',
            'specialization' => 'DevOps',
        ]);
        $dupResponse->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_update_teacher_and_sync_user(): void
    {
        $user = User::factory()->teacher()->create([
            'name' => 'Old Teacher Name',
            'email' => 'old.teacher@skillset.com',
        ]);

        $teacher = Teacher::factory()->create([
            'user_id' => $user->id,
            'name' => 'Old Teacher Name',
            'specialization' => 'PHP Basics',
        ]);

        $updatePayload = [
            'name' => 'New Teacher Name',
            'email' => 'new.teacher@skillset.com',
            'tel' => '081999888777',
            'specialization' => 'Fullstack Laravel & Vue',
            'new_password' => 'newpassword123',
        ];

        $response = $this->actingAs($this->admin)->put(route('teachers.update', $teacher->id), $updatePayload);

        $response->assertRedirect(route('teachers.index'));
        $response->assertSessionHas('status', 'success');

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => 'New Teacher Name',
            'specialization' => 'Fullstack Laravel & Vue',
            'tel' => '081999888777',
        ]);

        $user->refresh();
        $this->assertEquals('New Teacher Name', $user->name);
        $this->assertEquals('new.teacher@skillset.com', $user->email);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_admin_can_delete_teacher(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('teachers.destroy', $teacher->id));

        $response->assertRedirect(route('teachers.index'));
        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }
}
