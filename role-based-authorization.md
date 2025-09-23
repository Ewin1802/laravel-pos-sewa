# Role-Based Authorization Implementation

## Overview

This document outlines the implementation of role-based authorization for the POS Subscription Backend using Spatie Laravel Permission package integrated with Filament admin panel.

## Roles and Permissions

### Roles

-   **admin**: Full system access with all permissions
-   **operator**: Read/write access but no critical actions
-   **merchant**: Limited access for merchants using the system

### Key Permissions

-   `payment_confirmation.approve/reject`: Only admins can approve/reject payment confirmations
-   `invoice.mark_as_paid/failed`: Only admins can mark invoices as paid or failed
-   `license_token.revoke/reissue`: Only admins can revoke or reissue license tokens

## Implementation Files

### 1. Policies

-   `app/Policies/PaymentConfirmationPolicy.php`: Controls access to payment confirmation operations
-   `app/Policies/InvoicePolicy.php`: Controls access to invoice operations
-   `app/Policies/LicenseTokenPolicy.php`: Controls access to license token operations

### 2. Seeders

-   `database/seeders/RoleSeeder.php`: Creates roles and assigns permissions
-   `database/seeders/DatabaseSeeder.php`: Updated to seed roles and create users with roles

### 3. User Model

-   `app/Models/User.php`: Added `HasRoles` trait from Spatie Permission

### 4. Service Provider

-   `app/Providers/AuthServiceProvider.php`: Registers policies and defines gates

### 5. Filament Resources

Updated all Filament resources with:

-   Policy registration
-   Authorization checks for view/create/edit/delete operations
-   Navigation groups for better organization
-   Role-based actions (approve/reject, mark as paid, revoke/reissue)

### 6. Filament Admin Panel

-   `app/Providers/Filament/AdminPanelProvider.php`: Configured navigation groups

## Navigation Groups

-   **Merchants**: Merchants, Devices
-   **Subscriptions**: Plans, Subscriptions
-   **Payments**: Invoices, Payment Confirmations, Payments
-   **Licenses**: License Tokens
-   **System**: Audit Logs

## Key Features

### PaymentConfirmation Actions

-   **Approve**: Only admins can approve payment confirmations
-   **Reject**: Only admins can reject payment confirmations
-   Visible only for pending confirmations
-   Updates reviewed_by and reviewed_at fields

### Invoice Actions

-   **Mark as Paid**: Only admins can mark invoices as paid
-   **Mark as Failed**: Only admins can mark invoices as failed
-   Visible only for pending/processing invoices

### LicenseToken Actions

-   **Revoke**: Only admins can revoke license tokens
-   **Reissue**: Only admins can reissue new tokens (creates new token, revokes old)
-   Smart status badges (active/expired/revoked)

## Database

-   Spatie Permission tables: `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
-   Migration: `2025_08_24_232112_create_permission_tables.php`

## Sample Users

The seeder creates:

-   `admin@example.com` with admin role
-   `operator@example.com` with operator role
-   `merchant@example.com` with merchant role

## Security Features

-   Policy-based authorization at resource level
-   Gate-based authorization for specific actions
-   Role-based navigation and action visibility
-   Proper audit trail with user tracking for critical actions

## Testing Authorization

1. Login with different user roles
2. Test visibility of navigation items
3. Test action buttons based on user permissions
4. Verify critical actions are restricted to admins only

## Next Steps

-   Add middleware protection for API routes
-   Implement permission-based API access
-   Add role management interface in Filament
-   Consider adding department-level permissions if needed
