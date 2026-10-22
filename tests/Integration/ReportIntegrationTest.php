<?php

namespace Tests\Integration;

use App\Models\Course;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $teacherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacherUser = User::factory()->teacher()->create();
    }

    public function test_teacher_or_admin_can_view_reports_dashboard(): void
    {
        Student::factory()->create(['status' => 'regular']);
        Student::factory()->create(['status' => 'irregular']);
        Course::factory()->create();

        $response = $this->actingAs($this->teacherUser)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('totalStudents', 2);
        $response->assertViewHas('regularCount', 1);
        $response->assertViewHas('irregularCount', 1);
        $response->assertViewHas('attendanceRate', 50);
    }

    public function test_teacher_can_export_pdf_html_academic_report(): void
    {
        Storage::fake('public');

        Student::factory()->create();
        Course::factory()->create();

        $response = $this->actingAs($this->teacherUser)->get(route('reports.export-pdf'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_teacher_can_export_excel_csv_attendance_report(): void
    {
        Storage::fake('public');

        Student::factory()->create([
            'student_code' => 'STU-999888',
            'name' => 'Budi Santoso',
            'tel' => '081234567890',
        ]);

        $response = $this->actingAs($this->teacherUser)->get(route('reports.export-excel'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
