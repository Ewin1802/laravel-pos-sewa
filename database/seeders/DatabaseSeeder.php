<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RoleSeeder::class,
            // AdminUserSeeder::class,
        ]);

        // Create subscription plans
        Plan::create([
            'name' => 'Monthly Plan',
            'code' => 'MONTHLY',
            'description' => 'Perfect for small businesses getting started with POS system. Includes essential features with 7-day free trial.',
            'features' => [
                'Unlimited Transactions',
                'Basic Sales Reports',
                'Inventory Management',
                'Customer Database',
                'Email Support',
                '1 Device License',
                'Cloud Backup'
            ],
            'price' => 199000, // IDR 199,000
            'currency' => 'IDR',
            'duration_days' => 30,
            'trial_days' => 7,
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Yearly Plan',
            'code' => 'YEARLY',
            'description' => 'Best value for growing businesses. Save 17% with annual billing and get extended trial period with premium features.',
            'features' => [
                'Unlimited Transactions',
                'Advanced Analytics & Reports',
                'Multi-location Support',
                'Advanced Inventory Management',
                'Customer Loyalty Program',
                'API Access',
                'Priority Support',
                'Up to 5 Device Licenses',
                'Advanced Cloud Backup',
                'Custom Reports',
                'Sales Forecasting',
                'Staff Management'
            ],
            'price' => 1990000, // IDR 1,990,000
            'currency' => 'IDR',
            'duration_days' => 365,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@pos.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $adminUser->assignRole('admin');

        // Create operator user
        $operatorUser = User::factory()->create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
        ]);
        $operatorUser->assignRole('operator');

        // Create test merchant user
        $merchantUser = User::factory()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant@example.com',
        ]);
        $merchantUser->assignRole('merchant');
    }
}
