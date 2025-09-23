<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;


class TrialService
{
    public function __construct(private LicenseService $licenseService) {}

    /**
     * Start a trial subscription for a merchant
     */
    public function startTrial(Merchant $merchant, Device $device, ?Plan $plan = null, int $fallbackDays = 7): Subscription
    {
        // Validate trial eligibility
        $this->validateTrialEligibility($merchant, $device, $plan, $fallbackDays);

        // Calculate trial duration
        $trialDays = $this->calculateTrialDays($plan, $fallbackDays);

        return DB::transaction(function () use ($merchant, $device, $plan, $trialDays) {
            // Mark merchant as having used trial
            $merchant->update(['trial_used' => true]);

            // Create trial subscription
            $subscription = $this->createTrialSubscription($merchant, $plan, $trialDays);

            // Issue license token with trial claims
            $this->licenseService->issue($merchant, $device, $subscription, [
                'trial' => true,
                'trial_days' => $trialDays,
                'trial_expires_at' => $subscription->trial_end_at->toISOString(),
            ]);

            return $subscription->load('plan');
        });
    }

    /**
     * Validate if merchant is eligible for trial
     */
    public function validateTrialEligibility(Merchant $merchant, Device $device, ?Plan $plan = null, int $fallbackDays = 7): void
    {
        // Check if merchant account is active
        if (!$merchant->isActive()) {
            throw ValidationException::withMessages([
                'merchant' => ['Merchant account is not active'],
            ]);
        }

        // Check if merchant has already used trial
        if ($merchant->hasUsedTrial()) {
            throw ValidationException::withMessages([
                'trial' => ['Trial has already been used for this merchant account'],
            ]);
        }

        // Check if device belongs to merchant and is active
        if ($device->merchant_id !== $merchant->id) {
            throw ValidationException::withMessages([
                'device' => ['Device does not belong to this merchant'],
            ]);
        }

        if (!$device->isActive()) {
            throw ValidationException::withMessages([
                'device' => ['Device is not active'],
            ]);
        }

        // Check if merchant already has active subscription
        if ($this->hasActiveSubscription($merchant)) {
            throw ValidationException::withMessages([
                'subscription' => ['Merchant already has an active subscription'],
            ]);
        }

        // Validate plan if provided
        if ($plan !== null) {
            if (!$plan->isActive()) {
                throw ValidationException::withMessages([
                    'plan' => ['Selected plan is not active'],
                ]);
            }

            if (!$plan->hasTrialPeriod()) {
                throw ValidationException::withMessages([
                    'plan' => ['Selected plan does not support trial period'],
                ]);
            }
        }

        // Validate trial days
        $trialDays = $this->calculateTrialDays($plan, $fallbackDays);
        if ($trialDays <= 0) {
            throw ValidationException::withMessages([
                'trial_days' => ['Trial period must be greater than 0 days'],
            ]);
        }
    }

    /**
     * Calculate trial duration in days
     */
    public function calculateTrialDays(?Plan $plan = null, int $fallbackDays = 7): int
    {
        if ($plan && $plan->trial_days > 0) {
            return $plan->trial_days;
        }

        return $fallbackDays;
    }

    /**
     * Check if merchant has active subscription
     */
    public function hasActiveSubscription(Merchant $merchant): bool
    {
        return $merchant->subscriptions()
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_PENDING,
            ])
            ->exists();
    }

    /**
     * Create trial subscription record
     */
    private function createTrialSubscription(Merchant $merchant, ?Plan $plan, int $trialDays): Subscription
    {
        $now = now();
        $trialEndAt = $now->copy()->addDays($trialDays)->endOfDay();

        return Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $plan?->id,
            'start_at' => $now,
            'end_at' => null, // Trial doesn't have a regular end date initially
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => true,
            'trial_started_at' => $now,
            'trial_end_at' => $trialEndAt,
        ]);
    }

    /**
     * Check if trial is about to expire (useful for notifications)
     */
    public function isTrialExpiringSoon(Subscription $subscription, int $daysBeforeExpiry = 3): bool
    {
        if (!$subscription->isTrial() || !$subscription->trial_end_at) {
            return false;
        }

        $expiryThreshold = now()->addDays($daysBeforeExpiry);
        return $subscription->trial_end_at->lte($expiryThreshold);
    }

    /**
     * Get trial days remaining
     */
    public function getTrialDaysRemaining(Subscription $subscription): int
    {
        if (!$subscription->isTrial() || !$subscription->trial_end_at) {
            return 0;
        }

        $daysRemaining = now()->diffInDays($subscription->trial_end_at, false);
        return max(0, (int) $daysRemaining);
    }

    /**
     * Convert trial to paid subscription
     */
    public function convertTrialToPaid(Subscription $trialSubscription, Plan $plan): Subscription
    {
        if (!$trialSubscription->isTrial()) {
            throw ValidationException::withMessages([
                'subscription' => ['Subscription is not a trial subscription'],
            ]);
        }

        if ($trialSubscription->isTrialExpired()) {
            throw ValidationException::withMessages([
                'trial' => ['Trial period has already expired'],
            ]);
        }

        if (!$plan->isActive()) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan is not active'],
            ]);
        }

        return DB::transaction(function () use ($trialSubscription, $plan) {
            $now = now();
            $endAt = $now->copy()->addDays($plan->duration_days);

            // Update trial subscription to paid
            $trialSubscription->update([
                'plan_id' => $plan->id,
                'is_trial' => false,
                'start_at' => $now, // Reset start time for paid period
                'end_at' => $endAt,
                'status' => Subscription::STATUS_PENDING, // Pending payment
                'trial_end_at' => null, // Clear trial end date
            ]);

            return $trialSubscription->fresh('plan');
        });
    }

    /**
     * Expire trial subscription
     */
    public function expireTrial(Subscription $trialSubscription): void
    {
        if (!$trialSubscription->isTrial()) {
            throw ValidationException::withMessages([
                'subscription' => ['Subscription is not a trial subscription'],
            ]);
        }

        DB::transaction(function () use ($trialSubscription) {
            // Update subscription status to expired
            $trialSubscription->update([
                'status' => Subscription::STATUS_EXPIRED,
            ]);

            // Revoke all license tokens for this merchant
            $this->licenseService->revokeAllMerchantTokens($trialSubscription->merchant);
        });
    }

    /**
     * Get trial statistics for a merchant
     */
    public function getTrialStats(Merchant $merchant): array
    {
        $trialSubscription = $merchant->subscriptions()
            ->where('is_trial', true)
            ->with('plan')
            ->first();

        if (!$trialSubscription) {
            return [
                'has_trial' => false,
                'trial_used' => $merchant->hasUsedTrial(),
                'eligible_for_trial' => !$merchant->hasUsedTrial() && $merchant->isActive(),
            ];
        }

        return [
            'has_trial' => true,
            'trial_used' => true,
            'eligible_for_trial' => false,
            'subscription_id' => $trialSubscription->id,
            'status' => $trialSubscription->status,
            'trial_started_at' => $trialSubscription->trial_started_at,
            'trial_end_at' => $trialSubscription->trial_end_at,
            'days_remaining' => $this->getTrialDaysRemaining($trialSubscription),
            'is_expired' => $trialSubscription->isTrialExpired(),
            'is_expiring_soon' => $this->isTrialExpiringSoon($trialSubscription),
            'plan' => $trialSubscription->plan ? [
                'id' => $trialSubscription->plan->id,
                'name' => $trialSubscription->plan->name,
                'code' => $trialSubscription->plan->code,
            ] : null,
        ];
    }

    /**
     * Find expired trials that need cleanup
     */
    public function findExpiredTrials(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::where('is_trial', true)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('trial_end_at', '<', now())
            ->with(['merchant', 'plan'])
            ->get();
    }

    /**
     * Bulk expire trials (useful for scheduled commands)
     */
    public function bulkExpireTrials(): int
    {
        $expiredTrials = $this->findExpiredTrials();
        $count = 0;

        foreach ($expiredTrials as $trial) {
            try {
                $this->expireTrial($trial);
                $count++;
            } catch (\Exception $e) {
                // Log error but continue with other trials
                Log::error('Failed to expire trial subscription', [
                    'subscription_id' => $trial->id,
                    'merchant_id' => $trial->merchant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }
}
