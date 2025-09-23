<?php
/**
 * AI Guidelines for POS Subscription System
 *
 * These guidelines help AI assistants understand the specific business logic,
 * architecture patterns, and implementation requirements for this Laravel-based
 * POS subscription management system.
 */
?>

<guidelines>
    <purpose>
        This is a Laravel-based POS (Point of Sale) subscription management system that handles:
        - Merchant registration and subscription management
        - Device licensing and validation via JWT tokens
        - Trial subscriptions with one-trial-per-merchant rule
        - Payment processing and confirmation workflows
        - Admin oversight and system monitoring
    </purpose>

    <architecture>
        <actors>
            <merchant>
                - Registers for subscriptions (trial or paid)
                - Manages POS devices requiring licenses
                - Views billing history and payment status
                - Limited to one trial period per merchant account
            </merchant>

            <device>
                - POS hardware/software requiring valid license tokens
                - Validates JWT tokens via API endpoints
                - Reports usage statistics and connectivity status
                - Constrained by subscription plan limits (max_devices)
            </device>

            <admin>
                - Manages merchant accounts and subscriptions
                - Confirms payments manually when required
                - Monitors system health and usage analytics
                - Configures subscription plans and pricing
            </admin>
        </actors>

        <core_entities>
            <merchants>Represent business entities with subscription relationships</merchants>
            <subscriptions>Core business logic with trial/active/suspended states</subscriptions>
            <devices>POS endpoints requiring license validation</devices>
            <plans>Subscription templates with pricing and feature definitions</plans>
            <payments>Financial transactions requiring confirmation workflows</payments>
            <license_tokens>JWT-based device authorization mechanism</license_tokens>
        </core_entities>
    </architecture>

    <business_rules>
        <trial_management>
            - ONE trial per merchant (enforced via merchants.trial_used flag)
            - Trial duration defined by plans.trial_days
            - Trial automatically expires without payment confirmation
            - No trial extensions or resets allowed (strict business rule)
            - Trial subscriptions must transition to paid or expire
        </trial_management>

        <subscription_lifecycle>
            - New subscriptions start as trial if plan allows (trial_days > 0)
            - Active subscriptions require confirmed payment before expiration
            - Suspended subscriptions immediately revoke all device licenses
            - Cancelled subscriptions cannot be reactivated (create new instead)
            - Auto-renewal handled via subscription.auto_renew flag
        </subscription_lifecycle>

        <device_licensing>
            - Devices require active subscription for license generation
            - JWT tokens expire daily and require renewal
            - Max devices per subscription enforced by plan.max_devices
            - Suspended/expired subscriptions revoke ALL device access
            - License validation required on every device API request
        </device_licensing>

        <payment_processing>
            - Invoices auto-generated before subscription expiration
            - Manual payment confirmation required by admin users
            - Grace period before suspension (configurable)
            - Payment confirmations trigger subscription activation
            - Failed payments trigger suspension workflow
        </payment_processing>
    </business_rules>

    <technical_patterns>
        <jwt_licensing>
            - Algorithm: HS256 (HMAC with SHA-256)
            - Secret: JWT_SECRET_FOR_LICENSE environment variable
            - Payload includes device_id, merchant_id, subscription_id, plan_features
            - 24-hour expiration with daily renewal requirement
            - Token hash stored in license_tokens table for revocation
        </jwt_licensing>

        <status_management>
            - Use enum fields for all status columns (subscription, payment, device)
            - Implement status transition validation in model mutators
            - Audit all status changes via audit_logs table
            - Use database constraints to prevent invalid state transitions
        </status_management>

        <security_requirements>
            - JWT tokens for device authentication (separate from user auth)
            - API rate limiting for license validation endpoints
            - Audit logging for all critical business actions
            - IP and user agent tracking for security analysis
            - Role-based access control (merchant vs admin)
        </security_requirements>
    </technical_patterns>

    <implementation_guidelines>
        <model_relationships>
            - User hasOne Merchant (for merchant users)
            - Merchant hasMany Devices, hasMany Subscriptions
            - Subscription belongsTo Plan, hasMany Invoices
            - Invoice hasMany Payments
            - Payment hasOne PaymentConfirmation
            - Device hasMany LicenseTokens
            - Use eager loading to prevent N+1 queries
        </model_relationships>

        <validation_rules>
            - Trial eligibility check before subscription creation
            - Device limit enforcement before device registration
            - Payment amount validation against invoice total
            - License token expiration validation on API requests
            - Status transition validation in model observers
        </validation_rules>

        <api_design>
            - RESTful endpoints with resource controllers
            - JWT authentication for device license validation
            - Laravel Sanctum for merchant/admin web authentication
            - API versioning for device compatibility
            - Consistent error response formats
            - Rate limiting on license validation endpoints
        </api_design>

        <testing_strategy>
            - Feature tests for complete subscription workflows
            - Unit tests for business rule validation logic
            - API tests for device license validation
            - Database tests for constraint enforcement
            - Mock external payment gateway integrations
        </testing_strategy>
    </implementation_guidelines>

    <common_workflows>
        <merchant_trial_signup>
            1. Validate merchant hasn't used trial (merchants.trial_used = false)
            2. Create merchant record with trial_used = true
            3. Create trial subscription with is_trial = true
            4. Set trial_started_at and trial_ends_at from plan.trial_days
            5. Allow device registration and license generation
        </merchant_trial_signup>

        <trial_to_paid_conversion>
            1. Generate invoice before trial_ends_at
            2. Merchant submits payment information
            3. Admin confirms payment via payment_confirmations
            4. Update subscription status from 'trial' to 'active'
            5. Update subscription.starts_at and ends_at for billing cycle
        </trial_to_paid_conversion>

        <device_license_validation>
            1. Device sends license token to /api/license/validate
            2. Verify JWT signature using JWT_SECRET_FOR_LICENSE
            3. Check subscription status (must be 'trial' or 'active')
            4. Validate device_id exists and is active
            5. Update license_tokens.last_validated_at
            6. Return license status and subscription info
        </device_license_validation>

        <subscription_suspension>
            1. Payment overdue triggers suspension workflow
            2. Update subscription status to 'suspended'
            3. Revoke all license_tokens for merchant devices
            4. Send suspension notification to merchant
            5. Start grace period timer for cancellation
        </subscription_suspension>
    </common_workflows>

    <database_conventions>
        <naming>
            - Use snake_case for table and column names
            - Foreign keys: {table}_id (e.g., merchant_id, subscription_id)
            - Boolean fields: is_{property} or has_{property}
            - Status enums: active, inactive, suspended, cancelled, expired
            - Timestamp fields: {action}_at (e.g., trial_started_at, confirmed_at)
        </naming>

        <constraints>
            - Foreign key constraints for data integrity
            - Unique constraints on business identifiers
            - Check constraints for enum validation
            - Not null constraints for required fields
            - Default values for boolean and status fields
        </constraints>

        <indexing>
            - Index foreign key columns for join performance
            - Composite index on (merchant_id, status) for subscriptions
            - Index on license_tokens.expires_at for cleanup jobs
            - Index on audit_logs.created_at for log queries
        </indexing>
    </database_conventions>

    <error_handling>
        <business_rule_violations>
            - TrialAlreadyUsedException when merchant attempts second trial
            - DeviceLimitExceededException when plan max_devices reached
            - SubscriptionExpiredException for license validation on expired subscription
            - InvalidStatusTransitionException for illegal status changes
        </business_rule_violations>

        <api_responses>
            - 401 Unauthorized for invalid license tokens
            - 403 Forbidden for suspended subscription device access
            - 422 Unprocessable Entity for business rule violations
            - 429 Too Many Requests for rate limit exceeded
            - Include error codes for client-side handling
        </api_responses>
    </error_handling>

    <performance_considerations>
        <caching>
            - Cache active license tokens for fast validation
            - Cache subscription status for device authorization
            - Cache plan features for license payload generation
            - Use Redis for high-frequency license validation
        </caching>

        <optimization>
            - Batch license token renewals for efficiency
            - Queue subscription expiration notifications
            - Background jobs for invoice generation
            - Database indexing for fast license lookups
        </optimization>
    </performance_considerations>

    <monitoring_requirements>
        <metrics>
            - License validation request rate and response time
            - Subscription conversion rate (trial to paid)
            - Payment confirmation latency
            - Device connectivity and usage patterns
            - System error rates and types
        </metrics>

        <alerts>
            - Failed license validations exceeding threshold
            - Subscription expiration approaching without payment
            - Payment confirmation delays
            - Unusual device registration patterns
            - JWT token generation failures
        </alerts>
    </monitoring_requirements>
</guidelines>
