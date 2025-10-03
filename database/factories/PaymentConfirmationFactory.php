<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\PaymentConfirmation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentConfirmation>
 */
class PaymentConfirmationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'submitted_by' => $this->faker->name(),
            'amount' => $this->faker->randomFloat(2, 50000, 500000),
            'bank_name' => $this->faker->randomElement(['BCA', 'BNI', 'BRI', 'Mandiri']),
            'reference_no' => 'REF-' . $this->faker->unique()->randomNumber(8),
            'evidence_path' => $this->faker->optional()->filePath(),
            'status' => PaymentConfirmation::STATUS_SUBMITTED,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_note' => null,
        ];
    }

    /**
     * Indicate that the payment confirmation is submitted.
     */
    public function submitted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentConfirmation::STATUS_SUBMITTED,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'admin_note' => null,
        ]);
    }

    /**
     * Indicate that the payment confirmation is approved.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentConfirmation::STATUS_APPROVED,
            'reviewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'admin_note' => 'Payment confirmed and approved',
        ]);
    }

    /**
     * Indicate that the payment confirmation is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentConfirmation::STATUS_REJECTED,
            'reviewed_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'admin_note' => 'Payment confirmation rejected',
        ]);
    }
}