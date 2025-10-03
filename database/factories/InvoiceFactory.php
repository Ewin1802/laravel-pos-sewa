<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'subscription_id' => Subscription::factory(),
            'amount' => $this->faker->randomFloat(2, 50000, 500000),
            'currency' => 'IDR',
            'status' => $this->faker->randomElement([
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION,
                Invoice::STATUS_PAID,
                Invoice::STATUS_CANCELLED,
                Invoice::STATUS_EXPIRED,
            ]),
            'payment_method' => $this->faker->randomElement([
                Invoice::PAYMENT_METHOD_MANUAL_BANK,
                Invoice::PAYMENT_METHOD_MANUAL_QRIS,
                Invoice::PAYMENT_METHOD_OTHER,
            ]),
            'due_at' => $this->faker->dateTimeBetween('now', '+7 days'),
            'paid_at' => null,
            'note' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PAID,
            'paid_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is awaiting confirmation.
     */
    public function awaitingConfirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_AWAITING_CONFIRMATION,
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_EXPIRED,
            'due_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
            'paid_at' => null,
        ]);
    }

    /**
     * Indicate that the invoice is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::STATUS_CANCELLED,
            'paid_at' => null,
        ]);
    }
}