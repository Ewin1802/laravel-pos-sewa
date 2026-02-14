<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Exception;

class SubscriptionRenewalService
{
    /**
     * Renew an expired or expiring subscription
     */
    public function renewSubscription(Merchant $merchant, ?Plan $newPlan = null): array
    {
        return DB::transaction(function () use ($merchant, $newPlan) {
            // Get the current or most recent subscription
            $subscription = $this->getCurrentOrLatestSubscription($merchant);

            if (!$subscription) {
                throw ValidationException::withMessages([
                    'subscription' => ['No subscription found to renew'],
                ]);
            }

            // Determine the plan for renewal
            $planToUse = $newPlan ?: $subscription->plan;

            // Validate renewal eligibility
            $this->validateRenewalEligibility($merchant, $subscription, $planToUse);

            // Check if already has active pending renewal invoice
            $existingInvoice = $merchant->invoices()
                ->whereIn('status', [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_AWAITING_CONFIRMATION
                ])
                ->where('subscription_id', $subscription->id)
                ->where('due_at', '>', now())
                ->latest()
                ->first();

            if ($existingInvoice) {
                return [
                    'subscription' => $subscription->load('plan'),
                    'invoice' => $existingInvoice,
                    'payment_instructions' => app(CheckoutService::class)
                        ->getPaymentInstructions($existingInvoice),
                    'renewal_type' => $newPlan ? 'upgrade' : 'renewal',
                ];
            }


            // Create renewal invoice
            $invoice = $this->createRenewalInvoice($merchant, $planToUse, $subscription);

            // Create or update subscription for renewal
            $renewedSubscription = $this->createRenewalSubscription($merchant, $planToUse, $invoice, $subscription);

            return [
                'subscription' => $renewedSubscription->load('plan'),
                'invoice' => $invoice,
                'payment_instructions' => app(CheckoutService::class)->getPaymentInstructions($invoice),
                'renewal_type' => $newPlan ? 'upgrade' : 'renewal',
            ];
        });
    }

    /**
     * Get subscriptions that are about to expire (within specified days)
     */
    public function getSubscriptionsExpiringWithin(int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        $expirationDate = now()->addDays($days);

        return Subscription::with(['merchant', 'plan'])
            ->where('status', Subscription::STATUS_ACTIVE)
            ->where('is_trial', false)
            ->where('end_at', '<=', $expirationDate)
            ->where('end_at', '>', now())
            ->get();
    }

    /**
     * Get expired subscriptions
     */
    public function getExpiredSubscriptions(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::with(['merchant', 'plan'])
            ->where('status', Subscription::STATUS_EXPIRED)
            ->orWhere(function ($query) {
                $query->where('status', Subscription::STATUS_ACTIVE)
                    ->where('end_at', '<', now());
            })
            ->get();
    }

    /**
     * Auto-expire subscriptions that have passed their end date
     */
    public function expireOverdueSubscriptions(): int
    {
        $expiredCount = 0;

        $overdueSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('end_at', '<', now())
            ->get();

        foreach ($overdueSubscriptions as $subscription) {
            $subscription->update(['status' => Subscription::STATUS_EXPIRED]);

            // Revoke license tokens for expired subscriptions
            $subscription->licenseTokens()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $expiredCount++;
        }

        return $expiredCount;
    }

    /**
     * Generate renewal invoices for subscriptions expiring soon
     */
    public function generateRenewalInvoices(int $daysBeforeExpiry = 7): array
    {
        $generated = [];
        $expiringSubscriptions = $this->getSubscriptionsExpiringWithin($daysBeforeExpiry);

        foreach ($expiringSubscriptions as $subscription) {
            // Check if there's already a pending invoice for this subscription
            $existingInvoice = $subscription->merchant->invoices()
                ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])
                ->where('subscription_id', $subscription->id)
                ->first();

            if ($existingInvoice) {
                continue; // Skip if already has pending invoice
            }

            try {
                $invoice = $this->createRenewalInvoice($subscription->merchant, $subscription->plan, $subscription);
                $generated[] = [
                    'merchant_id' => $subscription->merchant_id,
                    'subscription_id' => $subscription->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->amount,
                ];
            } catch (Exception $e) {
                // Log error but continue with other subscriptions
                logger()->error('Failed to generate renewal invoice', [
                    'subscription_id' => $subscription->id,
                    'merchant_id' => $subscription->merchant_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $generated;
    }

    /**
     * Get renewal statistics for a merchant
     */
    public function getRenewalStats(Merchant $merchant): array
    {
        $currentSubscription = $this->getCurrentOrLatestSubscription($merchant);

        if (!$currentSubscription) {
            return [
                'has_subscription' => false,
                'can_renew' => false,
                'renewal_available' => false,
            ];
        }

        $canRenew = $this->canRenewSubscription($merchant, $currentSubscription);
        $daysUntilExpiry = $currentSubscription->end_at ? $currentSubscription->end_at->diffInDays(now(), false) : null;
        $isExpired = $currentSubscription->isExpired() || ($currentSubscription->end_at && $currentSubscription->end_at->isPast());

        // Check for pending renewal invoice
        $pendingRenewalInvoice = $merchant->invoices()
            ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])
            ->where('subscription_id', $currentSubscription->id)
            ->first();

        return [
            'has_subscription' => true,
            'subscription' => [
                'id' => $currentSubscription->id,
                'status' => $currentSubscription->status,
                'is_trial' => $currentSubscription->is_trial,
                'end_at' => $currentSubscription->end_at,
                'is_expired' => $isExpired,
                'days_until_expiry' => $daysUntilExpiry,
                'plan' => [
                    'id' => $currentSubscription->plan->id,
                    'name' => $currentSubscription->plan->name,
                    'code' => $currentSubscription->plan->code,
                    'price' => $currentSubscription->plan->price,
                    'currency' => $currentSubscription->plan->currency,
                ],
            ],
            'can_renew' => $canRenew,
            'renewal_available' => $canRenew && !$pendingRenewalInvoice,
            'pending_renewal_invoice' => $pendingRenewalInvoice ? [
                'id' => $pendingRenewalInvoice->id,
                'amount' => $pendingRenewalInvoice->amount,
                'currency' => $pendingRenewalInvoice->currency,
                'status' => $pendingRenewalInvoice->status,
                'due_at' => $pendingRenewalInvoice->due_at,
            ] : null,
        ];
    }

    /**
     * Get current or latest subscription for a merchant
     */
    private function getCurrentOrLatestSubscription(Merchant $merchant): ?Subscription
    {
        // First try to get active or pending subscription
        $subscription = $merchant->subscriptions()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
            ->latest()
            ->first();

        // If no active/pending, get the most recent expired one
        if (!$subscription) {
            $subscription = $merchant->subscriptions()
                ->where('status', Subscription::STATUS_EXPIRED)
                ->latest()
                ->first();
        }

        return $subscription;
    }

    /**
     * Validate if subscription can be renewed
     */
   private function validateRenewalEligibility(
        Merchant $merchant,
        Subscription $subscription,
        Plan $plan
    ): void {

        // ==============================
        // 1. Check Merchant Active
        // ==============================
        if (!$merchant->isActive()) {
            throw ValidationException::withMessages([
                'merchant' => ['Merchant account is not active'],
            ]);
        }

        // ==============================
        // 2. Check Plan Active
        // ==============================
        if (!$plan->isActive()) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan is not active'],
            ]);
        }

        // ==============================
        // 3. Auto Cancel Expired Invoices
        // ==============================
        $merchant->invoices()
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION,
            ])
            ->where('due_at', '<=', now())
            ->update([
                'status' => Invoice::STATUS_CANCELLED,
                'updated_at' => now(),
            ]);

        // ==============================
        // 4. Check Active Unpaid Invoices
        //    (Exclude current invoice if exists)
        // ==============================
        $hasActiveUnpaidInvoice = $merchant->invoices()
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION,
            ])
            ->where('id', '!=', $subscription->current_invoice_id ?? 0)
            ->where('due_at', '>', now())
            ->exists();

        if ($hasActiveUnpaidInvoice) {
            throw ValidationException::withMessages([
                'payment' => [
                    'Please complete existing pending payments before renewing'
                ],
            ]);
        }

        // ==============================
        // 5. Check If Subscription Can Be Renewed
        // ==============================
        if (!$this->canRenewSubscription($merchant, $subscription)) {
            throw ValidationException::withMessages([
                'subscription' => [
                    'This subscription cannot be renewed at this time'
                ],
            ]);
        }
    }


    /**
     * Check if a subscription can be renewed
     */
    private function canRenewSubscription(Merchant $merchant, Subscription $subscription): bool
    {
        // Cannot renew trial subscriptions
        if ($subscription->is_trial) {
            return false;
        }

        // Can renew if subscription is active and expiring soon (within 30 days)
        if ($subscription->status === Subscription::STATUS_ACTIVE) {
            return $subscription->end_at
                && $subscription->end_at->isFuture()
                && $subscription->end_at->diffInDays(now()) <= 30;
        }

        if ($subscription->status === Subscription::STATUS_EXPIRED) {
            return $subscription->end_at
                && $subscription->end_at->diffInDays(now()) <= 90;
        }


        // Cannot renew pending or cancelled subscriptions
        return false;
    }

    /**
     * Create renewal invoice
     */
    private function createRenewalInvoice(Merchant $merchant, Plan $plan, Subscription $subscription): Invoice
    {
        $dueAt = now()->addDays(3); // 3 days to pay for renewal

        return Invoice::create([
            'merchant_id' => $merchant->id,
            'subscription_id' => $subscription->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'due_at' => $dueAt,
            'status' => Invoice::STATUS_PENDING,
            'note' => "Renewal: {$plan->name} plan",
        ]);
    }

    /**
     * Create renewal subscription
     */
    private function createRenewalSubscription(Merchant $merchant, Plan $plan, Invoice $invoice, Subscription $oldSubscription): Subscription
    {
        // If renewing with the same plan, extend the existing subscription
        if ($oldSubscription->plan_id === $plan->id && $oldSubscription->status !== Subscription::STATUS_EXPIRED) {
            $oldSubscription->update([
                'status' => Subscription::STATUS_PENDING,
                'current_invoice_id' => $invoice->id,
            ]);

            return $oldSubscription;
        }

        // Create new subscription for plan changes or expired subscriptions
        $newSubscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $plan->id,
            'start_at' => null, // Will be set when payment is confirmed
            'end_at' => null, // Will be calculated when payment is confirmed
            'status' => Subscription::STATUS_PENDING,
            'current_invoice_id' => $invoice->id,
            'is_trial' => false,
        ]);

        // If old subscription is still active, mark it as cancelled
        if ($oldSubscription->status === Subscription::STATUS_ACTIVE) {
            $oldSubscription->update(['status' => Subscription::STATUS_CANCELLED]);
        }

        return $newSubscription;
    }
}
