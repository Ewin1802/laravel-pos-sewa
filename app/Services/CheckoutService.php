<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    /**
     * Start checkout process for a merchant and plan
     */
    public function start(Merchant $merchant, Plan $plan, string $deviceUid): array
    {
        // Validate checkout eligibility
        $this->validateCheckoutEligibility($merchant, $plan, $deviceUid);

        return DB::transaction(function () use ($merchant, $plan, $deviceUid) {
            // Get or create device
            $device = $this->getOrCreateDevice($merchant, $deviceUid);

            // Create pending invoice
            $invoice = $this->createPendingInvoice($merchant, $plan);

            // Create or update subscription
            $subscription = $this->createOrUpdateSubscription($merchant, $plan, $invoice);

            return [
                'invoice' => $invoice,
                'subscription' => $subscription->load('plan'),
                'device' => $device,
                'payment_instructions' => $this->getPaymentInstructions($invoice),
            ];
        });
    }

    /**
     * Validate if merchant is eligible for checkout
     */
    // public function validateCheckoutEligibility(Merchant $merchant, Plan $plan, string $deviceUid): void
    // {
    //     // Check if merchant account is active
    //     if (!$merchant->isActive()) {
    //         throw ValidationException::withMessages([
    //             'merchant' => ['Merchant account is not active'],
    //         ]);
    //     }

    //     // Check if plan is active
    //     if (!$plan->isActive()) {
    //         throw ValidationException::withMessages([
    //             'plan' => ['Selected plan is not active'],
    //         ]);
    //     }

    //     // Validate device UID format
    //     if (empty($deviceUid) || strlen($deviceUid) > 255) {
    //         throw ValidationException::withMessages([
    //             'device_uid' => ['Device UID is required and must not exceed 255 characters'],
    //         ]);
    //     }

    //     // Check if merchant has unpaid invoices

        // $unpaidInvoices = $merchant->invoices()
        //     ->whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])
        //     ->count();


    //     if ($unpaidInvoices > 0) {
    //         throw ValidationException::withMessages([
    //             'payment' => ['You have unpaid invoices. Please complete existing payments before creating new ones.'],
    //         ]);
    //     }
    // }

    public function validateCheckoutEligibility(Merchant $merchant, Plan $plan, string $deviceUid): void
    {
        // ==============================
        // 1. Validate Merchant
        // ==============================
        if (!$merchant->isActive()) {
            throw ValidationException::withMessages([
                'merchant' => ['Merchant account is not active'],
            ]);
        }

        // ==============================
        // 2. Validate Plan
        // ==============================
        if (!$plan->isActive()) {
            throw ValidationException::withMessages([
                'plan' => ['Selected plan is not active'],
            ]);
        }

        // ==============================
        // 3. Validate Device UID
        // ==============================
        if (empty($deviceUid) || strlen($deviceUid) > 255) {
            throw ValidationException::withMessages([
                'device_uid' => ['Device UID is required and must not exceed 255 characters'],
            ]);
        }

        // ==============================
        // 4. Auto Cancel Expired Invoices
        // ==============================
        $merchant->invoices()
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION
            ])
            ->where('due_at', '<=', now())
            ->update([
                'status' => Invoice::STATUS_CANCELLED,
                'updated_at' => now(),
            ]);

        // ==============================
        // 5. Check Active Unpaid Invoice
        // ==============================
        $hasActiveUnpaidInvoice = $merchant->invoices()
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION
            ])
            ->where('due_at', '>', now())
            ->exists();

        if ($hasActiveUnpaidInvoice) {
            throw ValidationException::withMessages([
                'payment' => [
                    'You have unpaid invoices. Please complete existing payments before creating new ones.'
                ],
            ]);
        }
    }


    /**
     * Get existing device or create new one
     */
    private function getOrCreateDevice(Merchant $merchant, string $deviceUid): Device
    {
        return Device::firstOrCreate(
            [
                'merchant_id' => $merchant->id,
                'device_uid' => $deviceUid,
            ],
            [
                'label' => 'Device ' . substr($deviceUid, -8), // Use last 8 chars as default label
                'is_active' => true,
                'last_seen_at' => now(),
            ]
        );
    }

    /**
     * Create pending invoice for the subscription
     */
    private function createPendingInvoice(Merchant $merchant, Plan $plan): Invoice
    {
        $dueAt = now()->addDays(2);

        return Invoice::create([
            'merchant_id' => $merchant->id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'due_at' => $dueAt,
            'status' => Invoice::STATUS_PENDING,
            'note' => "Subscription to {$plan->name} plan",
        ]);
    }

    /**
     * Create or update subscription to pending status
     */
    private function createOrUpdateSubscription(Merchant $merchant, Plan $plan, Invoice $invoice): Subscription
    {
        // Check if there's an existing active/pending subscription
        $existingSubscription = $merchant->subscriptions()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
            ->first();

        if ($existingSubscription) {
            // Update existing subscription
            $existingSubscription->update([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'current_invoice_id' => $invoice->id,
            ]);

            // Link invoice to subscription
            $invoice->update(['subscription_id' => $existingSubscription->id]);

            return $existingSubscription;
        }

        // Create new subscription
        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $plan->id,
            'start_at' => null, // Will be set when payment is confirmed
            'end_at' => null, // Will be calculated when payment is confirmed
            'status' => Subscription::STATUS_PENDING,
            'current_invoice_id' => $invoice->id,
            'is_trial' => false,
        ]);

        // Link invoice to subscription
        $invoice->update(['subscription_id' => $subscription->id]);

        return $subscription;
    }

    /**
     * Get payment instructions for the invoice
     */
    public function getPaymentInstructions(Invoice $invoice): array
    {
        return [
            'amount' => $invoice->amount,
            'currency' => $invoice->currency,
            'due_date' => $invoice->due_at->format('Y-m-d H:i:s'),
            'invoice_id' => $invoice->id,
            'payment_methods' => [
                'bank_transfer' => [
                    'account_name' => config('app.payment.bank_account_name', 'POS System'),
                    'account_number' => config('app.payment.bank_account_number', '1234567890'),
                    'bank_name' => config('app.payment.bank_name', 'Sample Bank'),
                    'reference' => "INV-{$invoice->id}",
                ],
                'digital_wallet' => [
                    'wallet_number' => config('app.payment.wallet_number', '081234567890'),
                    'wallet_name' => config('app.payment.wallet_name', 'POS System Wallet'),
                    'reference' => "INV-{$invoice->id}",
                ],
            ],
        ];
    }

    /**
     * Get checkout statistics for a merchant - only returns the latest invoice
     */
    // public function getCheckoutStats(Merchant $merchant): array
    // {
    //     // Get the latest invoice regardless of status
    //     $latestInvoice = $merchant->invoices()
    //         ->with('subscription.plan')
    //         ->latest('created_at')
    //         ->first();

    //     $activeDevices = $merchant->devices()->where('is_active', true)->get();

    //     return [
    //         'latest_invoice' => $latestInvoice ? [
    //             'id' => $latestInvoice->id,
    //             'amount' => $latestInvoice->amount,
    //             'currency' => $latestInvoice->currency,
    //             'due_at' => $latestInvoice->due_at,
    //             'description' => $latestInvoice->note,
    //             'status' => $latestInvoice->status,
    //             'created_at' => $latestInvoice->created_at,
    //             'updated_at' => $latestInvoice->updated_at,
    //             'plan' => $latestInvoice->subscription?->plan ? [
    //                 'id' => $latestInvoice->subscription->plan->id,
    //                 'name' => $latestInvoice->subscription->plan->name,
    //                 'code' => $latestInvoice->subscription->plan->code,
    //             ] : null,
    //         ] : null,
    //         'devices' => $activeDevices,
    //         'summary' => [
    //             'has_active_invoice' => $latestInvoice !== null,
    //             'invoice_status' => $latestInvoice?->status,
    //             'device_count' => $activeDevices->count(),
    //         ],
    //     ];
    // }

    public function getCheckoutStats(Merchant $merchant): array
    {
        // Auto cancel expired invoices before fetching stats
        $merchant->invoices()
            ->whereIn('status', [
                Invoice::STATUS_PENDING,
                Invoice::STATUS_AWAITING_CONFIRMATION
            ])
            ->where('due_at', '<=', now())
            ->update([
                'status' => Invoice::STATUS_CANCELLED,
                'updated_at' => now(),
            ]);

        // Get latest invoice
        $latestInvoice = $merchant->invoices()
            ->with('subscription.plan')
            ->latest('created_at')
            ->first();

        $activeDevices = $merchant->devices()
            ->where('is_active', true)
            ->get();

        $hasActiveInvoice = false;

        if ($latestInvoice) {
            $hasActiveInvoice = in_array(
                $latestInvoice->status,
                [
                    Invoice::STATUS_PENDING,
                    Invoice::STATUS_AWAITING_CONFIRMATION
                ]
            ) && $latestInvoice->due_at > now();
        }

        return [
            'latest_invoice' => $latestInvoice ? [
                'id' => $latestInvoice->id,
                'amount' => $latestInvoice->amount,
                'currency' => $latestInvoice->currency,
                'due_at' => $latestInvoice->due_at,
                'description' => $latestInvoice->note,
                'status' => $latestInvoice->status,
                'created_at' => $latestInvoice->created_at,
                'updated_at' => $latestInvoice->updated_at,
                'plan' => $latestInvoice->subscription?->plan ? [
                    'id' => $latestInvoice->subscription->plan->id,
                    'name' => $latestInvoice->subscription->plan->name,
                    'code' => $latestInvoice->subscription->plan->code,
                ] : null,
            ] : null,

            'devices' => $activeDevices,

            'summary' => [
                'has_active_invoice' => $hasActiveInvoice,
                'invoice_status' => $latestInvoice?->status,
                'invoice_is_expired' => $latestInvoice
                    ? $latestInvoice->due_at <= now()
                    : null,
                'device_count' => $activeDevices->count(),
            ],
        ];
    }


    /**
     * Cancel pending checkout (mark invoice as cancelled)
     */
    public function cancelCheckout(Merchant $merchant, Invoice $invoice): void
    {
        if ($invoice->merchant_id !== $merchant->id) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice does not belong to this merchant'],
            ]);
        }

        if (!in_array($invoice->status, [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_CONFIRMATION])) {
            throw ValidationException::withMessages([
                'invoice' => ['Invoice cannot be cancelled in its current status'],
            ]);
        }

        DB::transaction(function () use ($invoice) {
            // Update invoice status
            $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

            // Cancel associated subscription if it's still pending
            if ($invoice->subscription && $invoice->subscription->status === Subscription::STATUS_PENDING) {
                $invoice->subscription->update([
                    'status' => Subscription::STATUS_CANCELLED,
                    'current_invoice_id' => null,
                ]);
            }
        });
    }
}
