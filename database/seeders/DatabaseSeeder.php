<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
            'description' => 'Pilihan Sempurna untuk Usaha yang baru memulai Sistem Kasir Digital.',
            'features' => [
                'Transaksi Tanpa Batas',
                'Laporan Penjualan',
                'Terhubung dengan Printer Struk',
                '1 Akun 1 Device',
                'Backup Database',
                'Restore Database',
            ],
            'price' => 135000, // IDR 199,000
            'currency' => 'IDR',
            'duration_days' => 30,
            'trial_days' => 7,
            'is_active' => true,
        ]);

        Plan::create([
            'name' => 'Yearly Plan',
            'code' => 'YEARLY',
            'description' => 'Pilihan terbaik untuk bisnis yang sedang berkembang. Hemat 15%.',
            'features' => [
                'Transaksi Tanpa Batas',
                'Laporan Penjualan',
                'Terhubung dengan Printer Struk',
                '1 Akun 1 Device',
                'Backup Database',
                'Restore Database',
                'Hemat 15%',
            ],
            'price' => 1375000, // IDR 1,990,000
            'currency' => 'IDR',
            'duration_days' => 365,
            'trial_days' => 14,
            'is_active' => true,
        ]);

        // Create admin user
        $adminUser = User::create([
            'name' => 'Ewin Lantapa',
            'email' => 'admin@pos.test',
            'password' => Hash::make('79797979'),
            'email_verified_at' => now(),
            'role' => UserRole::ADMIN,
        ]);
        $adminUser->assignRole('admin');

        // Create operator user
        $operatorUser = User::factory()->create([
            'name' => 'Operator User',
            'email' => 'operator@example.com',
            'role' => UserRole::OPERATOR,
        ]);
        $operatorUser->assignRole('operator');

        // Create test merchant user
        $merchantUser = User::factory()->create([
            'name' => 'Test Merchant',
            'email' => 'merchant@example.com',
            'role' => UserRole::MERCHANT,
        ]);
        $merchantUser->assignRole('merchant');
    }
}
