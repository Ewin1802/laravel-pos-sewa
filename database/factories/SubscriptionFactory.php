<?php

namespace Database\Factories;

use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startAt = $this->faker->dateTimeBetween('-30 days', 'now');
        $plan = Plan::factory()->create();

        return [
            'merchant_id' => Merchant::factory(),
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
            'start_at' => $startAt,
            'end_at' => (clone $startAt)->modify("+{$plan->duration_days} days"),
            'trial_started_at' => null,
            'trial_end_at' => null,
        ];
    }

    /**
     * Indicate that the subscription is a trial.
     */
    public function trial(): static
    {
        return $this->state(function (array $attributes) {
            $plan = Plan::find($attributes['plan_id']) ?? Plan::factory()->create();
            $trialStartedAt = $this->faker->dateTimeBetween('-7 days', 'now');

            return [
                'is_trial' => true,
                'trial_started_at' => $trialStartedAt,
                'trial_end_at' => (clone $trialStartedAt)->modify("+{$plan->trial_days} days"),
            ];
        });
    }

    /**
     * Indicate that the subscription is pending.
     */
    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Subscription::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the subscription is expired.
     */
    public function expired(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Subscription::STATUS_EXPIRED,
            'end_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the subscription is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    }
}
