<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => 'INV-' . date('Ymd') . '-' . fake()->unique()->numerify('#####'),
            'student_id' => Student::factory(),
            'course_id' => Course::factory(),
            'amount' => fake()->randomElement([500000, 750000, 1000000, 1250000]),
            'payment_method' => fake()->randomElement(['BCA Virtual Account', 'Mandiri VA', 'QRIS GoPay', 'OVO']),
            'status' => 'pending',
            'proof_path' => null,
            'invoice_path' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => 'paid',
            'paid_at' => now(),
            'proof_path' => 'payment_proofs/sample.jpg',
        ]);
    }
}
