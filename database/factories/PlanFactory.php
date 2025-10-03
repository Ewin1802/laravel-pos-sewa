<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Basic Plan', 'Premium Plan', 'Enterprise Plan']),
            'code' => $this->faker->unique()->bothify('PLAN_???###'),
            'description' => $this->faker->sentence(10),
            'features' => [
                'Unlimited Transactions',
                'Basic Reports',
                'Customer Database',
                'Email Support',
            ],
            'price' => $this->faker->numberBetween(100000, 500000), // IDR 100,000 - 500,000
            'currency' => 'IDR',
            'duration_days' => $this->faker->randomElement([30, 90, 365]),
            'trial_days' => $this->faker->numberBetween(7, 14),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the plan has no trial period.
     */
    public function withoutTrial(): static
    {
        return $this->state(fn(array $attributes) => [
            'trial_days' => 0,
        ]);
    }

    /**
     * Indicate that the plan is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }
}
