<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\PaymentConfirmation;
use App\Models\LicenseToken;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ComprehensiveTestSeeder extends Seeder
{
    /**
     * Seed comprehensive test data for all scenarios.
     */
    public function run(): void
    {
        // Create additional plans for testing
        $this->createTestPlans();

        // Create merchants with different scenarios
        $this->createTrialMerchants();
        $this->createActiveMerchants();
        $this->createPendingMerchants();
        $this->createCancelledMerchants();

        $this->command->info('Comprehensive test data created successfully!');
    }

    private function createTestPlans(): void
    {
        // Create a free trial plan
        Plan::firstOrCreate([
            'code' => 'FREE_TRIAL',
        ], [
            'name' => 'Free Trial',
            'description' => 'Free trial plan for new users.',
            'features' => [
                'Limited Transactions (100/month)',
                'Basic Reports',
                '1 Device Only',
                '7 Days Trial',
            ],
            'price' => 0,
            'currency' => 'IDR',
            'duration_days' => 7,
            'trial_days' => 7,
            'is_active' => true,
        ]);

        // Create a premium plan
        Plan::firstOrCreate([
            'code' => 'PREMIUM',
        ], [
            'name' => 'Premium Plan',
            'description' => 'Premium plan with advanced features.',
            'features' => [
                'Unlimited Transactions',
                'Advanced Reports',
                'Multiple Devices (up to 5)',
                'Priority Support',
                'Cloud Backup',
                'Advanced Analytics',
            ],
            'price' => 299000, // IDR 299,000
            'currency' => 'IDR',
            'duration_days' => 30,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        $this->command->info('Test plans created');
    }

    private function createTrialMerchants(): void
    {
        $freePlan = Plan::where('code', 'FREE_TRIAL')->first();
        $monthlyPlan = Plan::where('code', 'MONTHLY')->first();

        $trialMerchants = [
            [
                'name' => 'Active Trial Merchant',
                'email' => 'active-trial@example.com',
                'business_name' => 'Active Trial Business',
                'plan' => $freePlan,
                'trial_days_left' => 5,
            ],
            [
                'name' => 'Expiring Trial Merchant',
                'email' => 'expiring-trial@example.com',
                'business_name' => 'Expiring Trial Business',
                'plan' => $monthlyPlan,
                'trial_days_left' => 1,
            ],
            [
                'name' => 'Expired Trial Merchant',
                'email' => 'expired-trial@example.com',
                'business_name' => 'Expired Trial Business',
                'plan' => $monthlyPlan,
                'trial_days_left' => -2,
            ],
        ];

        foreach ($trialMerchants as $merchantData) {
            $this->createTrialMerchant($merchantData);
        }

        $this->command->info('Trial merchants created');
    }

    private function createActiveMerchants(): void
    {
        $monthlyPlan = Plan::where('code', 'MONTHLY')->first();
        $yearlyPlan = Plan::where('code', 'YEARLY')->first();
        $premiumPlan = Plan::where('code', 'PREMIUM')->first();

        $activeMerchants = [
            [
                'name' => 'Happy Monthly Customer',
                'email' => 'happy-monthly@example.com',
                'business_name' => 'Happy Monthly Business',
                'plan' => $monthlyPlan,
                'days_remaining' => 20,
            ],
            [
                'name' => 'Happy Yearly Customer',
                'email' => 'happy-yearly@example.com',
                'business_name' => 'Happy Yearly Business',
                'plan' => $yearlyPlan,
                'days_remaining' => 300,
            ],
            [
                'name' => 'Premium Customer',
                'email' => 'premium@example.com',
                'business_name' => 'Premium Business',
                'plan' => $premiumPlan,
                'days_remaining' => 15,
            ],
        ];

        foreach ($activeMerchants as $merchantData) {
            $this->createActiveMerchant($merchantData);
        }

        $this->command->info('Active merchants created');
    }

    private function createPendingMerchants(): void
    {
        $monthlyPlan = Plan::where('code', 'MONTHLY')->first();
        $premiumPlan = Plan::where('code', 'PREMIUM')->first();

        $pendingMerchants = [
            [
                'name' => 'Pending Payment Merchant',
                'email' => 'pending-payment@example.com',
                'business_name' => 'Pending Payment Business',
                'plan' => $monthlyPlan,
                'has_payment_confirmation' => true,
            ],
            [
                'name' => 'Overdue Payment Merchant',
                'email' => 'overdue-payment@example.com',
                'business_name' => 'Overdue Payment Business',
                'plan' => $premiumPlan,
                'has_payment_confirmation' => false,
                'overdue_days' => 3,
            ],
        ];

        foreach ($pendingMerchants as $merchantData) {
            $this->createPendingMerchant($merchantData);
        }

        $this->command->info('Pending merchants created');
    }

    private function createCancelledMerchants(): void
    {
        $monthlyPlan = Plan::where('code', 'MONTHLY')->first();

        // Create user
        $user = User::create([
            'name' => 'Cancelled Merchant',
            'email' => 'cancelled@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create merchant
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => 'Cancelled Business',
            'contact_name' => 'Cancelled Merchant',
            'email' => 'cancelled@example.com',
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'address' => 'Test Address',
            'status' => Merchant::STATUS_ACTIVE,
            'trial_used' => true,
        ]);

        // Create cancelled subscription
        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $monthlyPlan->id,
            'start_at' => now()->subDays(15),
            'end_at' => now()->subDays(5),
            'status' => Subscription::STATUS_CANCELLED,
            'is_trial' => false,
        ]);

        $this->command->info('Cancelled merchants created');
    }

    private function createTrialMerchant(array $data): void
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create merchant
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => $data['business_name'],
            'contact_name' => $data['name'],
            'email' => $data['email'],
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'address' => 'Test Address',
            'status' => Merchant::STATUS_ACTIVE,
            'trial_used' => false,
        ]);

        // Create device
        $device = Device::create([
            'merchant_id' => $merchant->id,
            'device_uid' => 'DEVICE_' . strtoupper(str_replace('-', '_', $merchant->id)),
            'label' => 'Test Device for ' . $data['business_name'],
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        // Create trial subscription
        $trialStarted = now()->subDays($data['plan']->trial_days - $data['trial_days_left']);
        $trialEnds = now()->addDays($data['trial_days_left']);
        $status = $data['trial_days_left'] > 0 ? Subscription::STATUS_ACTIVE : Subscription::STATUS_EXPIRED;

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $data['plan']->id,
            'start_at' => null,
            'end_at' => null,
            'status' => $status,
            'is_trial' => true,
            'trial_started_at' => $trialStarted,
            'trial_end_at' => $trialEnds,
        ]);

        // Mark trial as used
        $merchant->update(['trial_used' => true]);
    }

    private function createActiveMerchant(array $data): void
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create merchant
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => $data['business_name'],
            'contact_name' => $data['name'],
            'email' => $data['email'],
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'address' => 'Test Address',
            'status' => Merchant::STATUS_ACTIVE,
            'trial_used' => true,
        ]);

        // Create device
        $device = Device::create([
            'merchant_id' => $merchant->id,
            'device_uid' => 'DEVICE_' . strtoupper(str_replace('-', '_', $merchant->id)),
            'label' => 'Test Device for ' . $data['business_name'],
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        // Create active subscription
        $startDate = now()->subDays($data['plan']->duration_days - $data['days_remaining']);
        $endDate = now()->addDays($data['days_remaining']);

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $data['plan']->id,
            'start_at' => $startDate,
            'end_at' => $endDate,
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
        ]);

        // Create paid invoice
        $invoice = Invoice::create([
            'merchant_id' => $merchant->id,
            'subscription_id' => $subscription->id,
            'amount' => $data['plan']->price,
            'currency' => $data['plan']->currency,
            'due_at' => $startDate->copy()->addDays(2),
            'paid_at' => $startDate->copy()->addDays(1),
            'status' => Invoice::STATUS_PAID,
            'note' => "Subscription to {$data['plan']->name} plan",
        ]);

        $subscription->update(['current_invoice_id' => $invoice->id]);
    }

    private function createPendingMerchant(array $data): void
    {
        // Create user
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Create merchant
        $merchant = Merchant::create([
            'user_id' => $user->id,
            'name' => $data['business_name'],
            'contact_name' => $data['name'],
            'email' => $data['email'],
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'address' => 'Test Address',
            'status' => Merchant::STATUS_ACTIVE,
            'trial_used' => true,
        ]);

        // Create device
        $device = Device::create([
            'merchant_id' => $merchant->id,
            'device_uid' => 'DEVICE_' . strtoupper(str_replace('-', '_', $merchant->id)),
            'label' => 'Test Device for ' . $data['business_name'],
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        // Create pending subscription
        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $data['plan']->id,
            'start_at' => null,
            'end_at' => null,
            'status' => Subscription::STATUS_PENDING,
            'is_trial' => false,
        ]);

        // Create pending/overdue invoice
        $dueDate = isset($data['overdue_days'])
            ? now()->subDays($data['overdue_days'])
            : now()->addDays(1);

        $invoice = Invoice::create([
            'merchant_id' => $merchant->id,
            'subscription_id' => $subscription->id,
            'amount' => $data['plan']->price,
            'currency' => $data['plan']->currency,
            'due_at' => $dueDate,
            'status' => isset($data['overdue_days']) ? Invoice::STATUS_PENDING : Invoice::STATUS_AWAITING_CONFIRMATION,
            'note' => "Subscription to {$data['plan']->name} plan",
        ]);

        $subscription->update(['current_invoice_id' => $invoice->id]);

        // Create payment confirmation if specified
        if ($data['has_payment_confirmation'] ?? false) {
            PaymentConfirmation::create([
                'invoice_id' => $invoice->id,
                'merchant_id' => $merchant->id,
                'amount' => $invoice->amount,
                'payment_method' => 'bank_transfer',
                'reference_no' => 'REF' . time(),
                'payment_date' => now()->subDays(1),
                'status' => PaymentConfirmation::STATUS_SUBMITTED,
                'notes' => 'Test payment confirmation',
            ]);

            $invoice->update(['status' => Invoice::STATUS_AWAITING_CONFIRMATION]);
        }
    }
}