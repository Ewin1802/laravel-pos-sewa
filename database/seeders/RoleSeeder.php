<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Payment Confirmation permissions
            'payment_confirmation.view',
            'payment_confirmation.create',
            'payment_confirmation.update',
            'payment_confirmation.delete',
            'payment_confirmation.approve',
            'payment_confirmation.reject',

            // Invoice permissions
            'invoice.view',
            'invoice.create',
            'invoice.update',
            'invoice.delete',
            'invoice.mark_as_paid',
            'invoice.mark_as_failed',

            // License Token permissions
            'license_token.view',
            'license_token.create',
            'license_token.update',
            'license_token.delete',
            'license_token.revoke',
            'license_token.reissue',

            // General permissions
            'merchant.view',
            'merchant.create',
            'merchant.update',
            'merchant.delete',
            'device.view',
            'device.create',
            'device.update',
            'device.delete',
            'plan.view',
            'plan.create',
            'plan.update',
            'plan.delete',
            'subscription.view',
            'subscription.create',
            'subscription.update',
            'subscription.delete',
            'payment.view',
            'payment.create',
            'payment.update',
            'payment.delete',
            'audit_log.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);
        $merchantRole = Role::firstOrCreate(['name' => 'merchant']);

        // Admin gets all permissions
        $adminRole->givePermissionTo(Permission::all());

        // Operator gets read/write permissions but not critical actions
        $operatorPermissions = [
            'payment_confirmation.view',
            'payment_confirmation.create',
            'invoice.view',
            'invoice.create',
            'invoice.update',
            'license_token.view',
            'license_token.create',
            'merchant.view',
            'merchant.create',
            'merchant.update',
            'device.view',
            'device.create',
            'device.update',
            'plan.view',
            'subscription.view',
            'subscription.create',
            'subscription.update',
            'payment.view',
            'audit_log.view',
        ];
        $operatorRole->givePermissionTo($operatorPermissions);

        // Merchant gets limited permissions
        $merchantPermissions = [
            'payment_confirmation.create',
            'device.view',
            'device.create',
            'plan.view',
            'subscription.view',
            'payment.view',
        ];
        $merchantRole->givePermissionTo($merchantPermissions);
    }
}
