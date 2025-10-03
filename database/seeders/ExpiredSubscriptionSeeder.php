<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ExpiredSubscriptionSeeder extends Seeder
{
    /**
     * Seed expired subscriptions for testing renewal functionality.
     */
    public function run(): void
    {
        // Get existing plans
        $monthlyPlan = Plan::where('code', 'MONTHLY')->first();
        $yearlyPlan = Plan::where('code', 'YEARLY')->first();

        if (!$monthlyPlan || !$yearlyPlan) {
            $this->command->error('Plans not found. Please run DatabaseSeeder first.');
            return;
        }

        // Create merchants with expired subscriptions
        $expiredMerchants = [
            [
                'name' => 'Expired Monthly Merchant',
                'email' => 'expired-monthly@example.com',
                'business_name' => 'Monthly Business',
                'plan' => $monthlyPlan,
                'expired_days_ago' => 5,
            ],
            [
                'name' => 'Expired Yearly Merchant',
                'email' => 'expired-yearly@example.com',
                'business_name' => 'Yearly Business',
                'plan' => $yearlyPlan,
                'expired_days_ago' => 10,
            ],
            [
                'name' => 'Recently Expired Merchant',
                'email' => 'recent-expired@example.com',
                'business_name' => 'Recent Business',
                'plan' => $monthlyPlan,
                'expired_days_ago' => 1,
            ],
            [
                'name' => 'Long Expired Merchant',
                'email' => 'long-expired@example.com',
                'business_name' => 'Long Expired Business',
                'plan' => $yearlyPlan,
                'expired_days_ago' => 30,
            ],
        ];

        foreach ($expiredMerchants as $merchantData) {
            $this->createExpiredSubscription($merchantData);
        }

        // Create merchants with subscriptions expiring soon
        $soonToExpireMerchants = [
            [
                'name' => 'Expiring Soon Merchant',
                'email' => 'expiring-soon@example.com',
                'business_name' => 'Soon to Expire Business',
                'plan' => $monthlyPlan,
                'expires_in_days' => 3,
            ],
            [
                'name' => 'Expiring Tomorrow Merchant',
                'email' => 'expiring-tomorrow@example.com',
                'business_name' => 'Tomorrow Business',
                'plan' => $monthlyPlan,
                'expires_in_days' => 1,
            ],
        ];

        foreach ($soonToExpireMerchants as $merchantData) {
            $this->createSoonToExpireSubscription($merchantData);
        }

        $this->command->info('Expired subscription test data created successfully!');
    }

    private function createExpiredSubscription(array $data): void
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
            'last_seen_at' => now()->subDays($data['expired_days_ago']),
        ]);

        // Create expired subscription
        $startDate = now()->subDays($data['plan']->duration_days + $data['expired_days_ago']);
        $endDate = now()->subDays($data['expired_days_ago']);

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $data['plan']->id,
            'start_at' => $startDate,
            'end_at' => $endDate,
            'status' => Subscription::STATUS_EXPIRED,
            'is_trial' => false,
        ]);

        // Create paid invoice for the expired subscription
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

        $this->command->info("Created expired subscription for {$data['business_name']} (expired {$data['expired_days_ago']} days ago)");
    }

    private function createSoonToExpireSubscription(array $data): void
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

        // Create active subscription that expires soon
        $startDate = now()->subDays($data['plan']->duration_days - $data['expires_in_days']);
        $endDate = now()->addDays($data['expires_in_days']);

        $subscription = Subscription::create([
            'merchant_id' => $merchant->id,
            'plan_id' => $data['plan']->id,
            'start_at' => $startDate,
            'end_at' => $endDate,
            'status' => Subscription::STATUS_ACTIVE,
            'is_trial' => false,
        ]);

        // Create paid invoice for the active subscription
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

        $this->command->info("Created soon-to-expire subscription for {$data['business_name']} (expires in {$data['expires_in_days']} days)");
    }
}