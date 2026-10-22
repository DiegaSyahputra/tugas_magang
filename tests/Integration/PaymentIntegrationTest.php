<?php

namespace Tests\Integration;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $studentUser;
    protected Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->studentUser = User::factory()->student()->create();
        $this->student = Student::factory()->create(['user_id' => $this->studentUser->id]);
    }

    public function test_user_can_view_payments_and_filter_by_status(): void
    {
        $paidPayment = Payment::factory()->paid()->create([
            'student_id' => $this->student->id,
            'amount' => 750000,
        ]);

        $pendingPayment = Payment::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'pending',
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('payments.index'));
        $response->assertOk();
        $response->assertSee($paidPayment->invoice_id);
        $response->assertSee($pendingPayment->invoice_id);

        // Filter by paid
        $filterPaid = $this->actingAs($this->studentUser)->get(route('payments.index', ['status' => 'paid']));
        $filterPaid->assertOk();
        $filterPaid->assertSee($paidPayment->invoice_id);
        $filterPaid->assertDontSee($pendingPayment->invoice_id);
    }

    public function test_user_can_create_new_payment_invoice(): void
    {
        $course = Course::factory()->create();

        $payload = [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'amount' => 850000,
            'payment_method' => 'BCA Virtual Account',
        ];

        $response = $this->actingAs($this->studentUser)->post(route('payments.store'), $payload);

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'student_id' => $this->student->id,
            'course_id' => $course->id,
            'amount' => 850000,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_upload_payment_proof_and_status_becomes_paid(): void
    {
        Storage::fake('public');

        $payment = Payment::factory()->create([
            'student_id' => $this->student->id,
            'status' => 'pending',
        ]);

        $fakeProofFile = UploadedFile::fake()->image('transfer_receipt.jpg');

        $response = $this->actingAs($this->studentUser)->post(
            route('payments.upload-proof', $payment),
            ['proof_file' => $fakeProofFile]
        );

        $response->assertRedirect(route('payments.index'));
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($payment->proof_path);

        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_user_can_export_and_download_invoice(): void
    {
        Storage::fake('public');

        $payment = Payment::factory()->paid()->create([
            'student_id' => $this->student->id,
        ]);

        $response = $this->actingAs($this->studentUser)->get(route('payments.export-invoice', $payment));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }
}
