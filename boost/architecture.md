# POS Subscription System Architecture

## Actors

### Merchant

-   Registers and manages subscription
-   Operates POS devices
-   Views invoices and payment history
-   Manages trial and paid subscriptions

### Device

-   POS hardware/software requiring license
-   Validates license tokens via API
-   Reports usage and status
-   Syncs with subscription backend

### Admin

-   Manages merchants and subscriptions
-   Configures plans and pricing
-   Monitors system usage
-   Handles payment confirmations

## Database Schema (ERD)

### users

```sql
id (PK, auto-increment)
name (varchar, not null)
email (varchar, unique, not null)
email_verified_at (timestamp, nullable)
password (varchar, not null)
role (enum: merchant, admin)
created_at, updated_at (timestamps)
```

### merchants

```sql
id (PK, auto-increment)
user_id (FK users.id, unique, not null)
business_name (varchar, not null)
business_type (varchar, nullable)
phone (varchar, nullable)
address (text, nullable)
status (enum: active, suspended, inactive)
trial_used (boolean, default false)
created_at, updated_at (timestamps)
```

### devices

```sql
id (PK, auto-increment)
merchant_id (FK merchants.id, not null)
device_name (varchar, not null)
device_identifier (varchar, unique, not null)
device_type (varchar, nullable)
status (enum: active, inactive, suspended)
last_seen_at (timestamp, nullable)
created_at, updated_at (timestamps)
```

### plans

```sql
id (PK, auto-increment)
name (varchar, not null)
description (text, nullable)
price (decimal(10,2), not null)
billing_cycle (enum: monthly, quarterly, yearly)
trial_days (integer, default 0)
max_devices (integer, default 1)
features (json, nullable)
status (enum: active, inactive)
created_at, updated_at (timestamps)
```

### subscriptions

```sql
id (PK, auto-increment)
merchant_id (FK merchants.id, not null)
plan_id (FK plans.id, not null)
status (enum: trial, active, suspended, cancelled, expired)
is_trial (boolean, default false)
trial_started_at (timestamp, nullable)
trial_ends_at (timestamp, nullable)
starts_at (timestamp, not null)
ends_at (timestamp, nullable)
auto_renew (boolean, default true)
created_at, updated_at (timestamps)
```

### invoices

```sql
id (PK, auto-increment)
subscription_id (FK subscriptions.id, not null)
invoice_number (varchar, unique, not null)
amount (decimal(10,2), not null)
tax_amount (decimal(10,2), default 0)
total_amount (decimal(10,2), not null)
due_date (date, not null)
status (enum: pending, paid, overdue, cancelled)
created_at, updated_at (timestamps)
```

### payments

```sql
id (PK, auto-increment)
invoice_id (FK invoices.id, not null)
amount (decimal(10,2), not null)
payment_method (enum: bank_transfer, credit_card, cash, other)
payment_reference (varchar, nullable)
status (enum: pending, completed, failed, cancelled)
paid_at (timestamp, nullable)
created_at, updated_at (timestamps)
```

### payment_confirmations

```sql
id (PK, auto-increment)
payment_id (FK payments.id, not null)
confirmed_by (FK users.id, nullable) -- admin user
confirmation_method (enum: manual, automatic)
confirmation_notes (text, nullable)
confirmed_at (timestamp, not null)
created_at, updated_at (timestamps)
```

### license_tokens

```sql
id (PK, auto-increment)
device_id (FK devices.id, not null)
subscription_id (FK subscriptions.id, not null)
token_hash (varchar, unique, not null)
expires_at (timestamp, not null)
last_validated_at (timestamp, nullable)
status (enum: active, revoked, expired)
created_at, updated_at (timestamps)
```

### audit_logs

```sql
id (PK, auto-increment)
user_id (FK users.id, nullable)
action (varchar, not null)
model_type (varchar, not null)
model_id (bigint, not null)
old_values (json, nullable)
new_values (json, nullable)
ip_address (varchar, nullable)
user_agent (text, nullable)
created_at (timestamp)
```

## Status Transitions

### Subscription Status Flow

-   `trial` → `active` (trial period ends + payment confirmed)
-   `trial` → `expired` (trial period ends + no payment)
-   `active` → `suspended` (payment overdue)
-   `active` → `cancelled` (manual cancellation)
-   `suspended` → `active` (payment confirmed)
-   `suspended` → `cancelled` (grace period expires)

### Payment Status Flow

-   `pending` → `completed` (payment confirmed)
-   `pending` → `failed` (payment rejected)
-   `pending` → `cancelled` (manual cancellation)

### Device Status Flow

-   `active` → `inactive` (device disconnected)
-   `active` → `suspended` (subscription issues)
-   `suspended` → `active` (subscription restored)

## Trial Rules

### One Trial Per Merchant

-   `merchants.trial_used` flag prevents multiple trials
-   Set to `true` when first trial subscription created
-   Cannot be reset (business rule)

### Plan Trial Configuration

-   `plans.trial_days` defines trial duration (0 = no trial)
-   Applied when subscription created with `is_trial = true`
-   Trial period: `trial_started_at` to `trial_ends_at`

### Subscription Trial Fields

-   `is_trial`: Boolean flag for trial subscriptions
-   `trial_started_at`: Trial start timestamp
-   `trial_ends_at`: Trial expiration timestamp
-   Trial automatically expires if no payment by `trial_ends_at`

## JWT License Token Schema

### Payload Structure

```json
{
    "iss": "pos-subscription-system",
    "sub": "device_id",
    "aud": "pos-device",
    "exp": 1234567890,
    "iat": 1234567890,
    "device_id": 123,
    "merchant_id": 456,
    "subscription_id": 789,
    "plan_features": {
        "max_transactions": 1000,
        "advanced_reports": true,
        "multi_location": false
    },
    "subscription_status": "active",
    "subscription_ends_at": "2024-12-31T23:59:59Z"
}
```

### Token Signing

-   **Algorithm**: HS256 (HMAC with SHA-256)
-   **Secret**: `JWT_SECRET_FOR_LICENSE` environment variable
-   **Expiration**: 24 hours (renewed daily)
-   **Storage**: `license_tokens.token_hash` (SHA-256 of JWT)

## API Endpoints (High-Level)

### Authentication

-   `POST /api/auth/login` - Merchant/admin login
-   `POST /api/auth/logout` - Logout
-   `POST /api/auth/refresh` - Refresh token

### Merchant Registration & Management

-   `POST /api/merchants/register` - New merchant signup
-   `GET /api/merchants/profile` - Get merchant profile
-   `PUT /api/merchants/profile` - Update merchant profile

### Subscription Management

-   `GET /api/plans` - List available plans
-   `POST /api/subscriptions` - Create subscription (trial/paid)
-   `GET /api/subscriptions/current` - Get active subscription
-   `PUT /api/subscriptions/cancel` - Cancel subscription
-   `PUT /api/subscriptions/renew` - Renew subscription

### Device Management

-   `POST /api/devices` - Register new device
-   `GET /api/devices` - List merchant devices
-   `PUT /api/devices/{id}` - Update device info
-   `DELETE /api/devices/{id}` - Remove device

### License Validation

-   `POST /api/license/validate` - Validate device license
-   `POST /api/license/refresh` - Refresh license token
-   `GET /api/license/status` - Check license status

### Billing & Payments

-   `GET /api/invoices` - List merchant invoices
-   `GET /api/invoices/{id}` - Get invoice details
-   `POST /api/payments` - Submit payment info
-   `GET /api/payments` - List payment history

### Admin Endpoints

-   `GET /api/admin/merchants` - List all merchants
-   `GET /api/admin/subscriptions` - List all subscriptions
-   `POST /api/admin/payments/confirm` - Confirm payment
-   `GET /api/admin/analytics` - System analytics

## Business Rules Checklist

### Trial Management

-   [ ] One trial per merchant (check `merchants.trial_used`)
-   [ ] Trial duration from plan configuration (`plans.trial_days`)
-   [ ] Trial auto-expires without payment
-   [ ] Trial cannot be extended or restarted

### Subscription Lifecycle

-   [ ] New subscriptions start as trial (if plan allows)
-   [ ] Active subscriptions require valid payment
-   [ ] Suspended subscriptions disable device access
-   [ ] Cancelled subscriptions cannot be reactivated

### Device Licensing

-   [ ] Devices require active subscription for license
-   [ ] License tokens expire and require renewal
-   [ ] Max devices per plan enforced
-   [ ] Suspended subscriptions revoke all device licenses

### Payment Processing

-   [ ] Invoices generated before subscription expires
-   [ ] Payment confirmation required for activation
-   [ ] Overdue payments trigger suspension
-   [ ] Grace period before cancellation

### Security & Compliance

-   [ ] JWT tokens signed with secure secret
-   [ ] License validation on every device request
-   [ ] Audit logging for all critical actions
-   [ ] User authentication required for all operations

### Data Integrity

-   [ ] Foreign key constraints enforced
-   [ ] Status transitions follow defined rules
-   [ ] Soft deletes for historical data preservation
-   [ ] Timestamps for all records
