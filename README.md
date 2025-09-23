# POS Subscription Backend

A comprehensive Laravel 12-based backend system for managing Point of Sale (POS) subscription services with trial management, payment processing, and license distribution.

## 🚀 Features

### Core Functionality

-   **Merchant Management**: Complete merchant registration and management system
-   **Device Registration**: Secure device registration and management for POS systems
-   **Subscription Plans**: Flexible subscription plans with trial periods
-   **Trial Management**: Automated trial period handling with expiration tracking
-   **Payment Processing**: Invoice generation and payment confirmation handling
-   **License Management**: JWT-based license token generation and validation
-   **Role-Based Authorization**: Admin, operator, and merchant role management

### Advanced Features

-   **Automated Workflows**: Trial expiration, license renewal, and payment processing
-   **Scheduled Maintenance**: Daily automated tasks for system cleanup and notifications
-   **Audit Logging**: Complete audit trail for all system activities
-   **RESTful API**: Comprehensive API for frontend integration
-   **Admin Panel**: Filament v4-based admin interface with role-based permissions
-   **Smart Payment Processing**: One-click invoice approval with automatic subscription activation
-   **Visual Evidence Review**: Integrated image/document viewer for payment confirmations
-   **Real-time Validation**: License verification and device authentication
-   **Comprehensive Testing**: 100% test coverage with PHPUnit feature and unit tests

## 🛠 Tech Stack

-   **Framework**: Laravel 12.25.0
-   **PHP**: 8.3.22
-   **Database**: MySQL/SQLite
-   **Admin Panel**: Filament v4.0.3
-   **Authentication**: Laravel Sanctum + JWT
-   **Authorization**: Spatie Laravel Permission
-   **Code Quality**: Laravel Pint v1
-   **Testing**: PHPUnit with Feature & Unit tests (29 passing tests)

## 📋 System Requirements

-   PHP 8.3.22+
-   Laravel 12+
-   MySQL 8.0+ or SQLite
-   Composer 2.0+
-   Node.js 18+ (for asset compilation)

## 🚀 Quick Start

### 1. Installation

```bash
# Clone the repository
git clone <repository-url>
cd pos-subscription-be

# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 2. Database Setup

```bash
# Configure your database in .env file
# For SQLite (recommended for development):
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# For MySQL:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=pos_subscription
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Create SQLite database file (if using SQLite)
touch database/database.sqlite

# Run migrations
php artisan migrate

# Seed the database with roles, plans, and admin user
php artisan db:seed
```

### 3. Admin Panel Access

```bash
# Start the development server
php artisan serve

# Access admin panel at: http://localhost:8000/admin
# Login credentials:
# Email: admin@pos.test
# Password: password
```

### 4. Development Tools

```bash
# Run tests
php artisan test

# Code formatting
vendor/bin/pint

# Build assets
npm run build

# Development with hot reload
npm run dev
```

## 📚 API Documentation

### Authentication Endpoints

-   `POST /api/v1/auth/register` - Merchant registration
-   `POST /api/v1/auth/login` - User authentication
-   `POST /api/v1/auth/logout` - User logout

### Device Management

-   `POST /api/v1/devices/register` - Register new device
-   `GET /api/v1/devices` - List merchant devices
-   `PUT /api/v1/devices/{id}` - Update device information

### Subscription & Trial

-   `GET /api/v1/plans` - List available subscription plans
-   `POST /api/v1/trials/start` - Start trial subscription with license
-   `GET /api/v1/trials/status` - Check trial status and remaining days
-   `POST /api/v1/checkouts` - Create subscription checkout process

### Payment Processing

-   `POST /api/v1/payment-confirmations` - Submit payment proof with evidence
-   `GET /api/v1/invoices` - List merchant invoices
-   `GET /api/v1/invoices/{id}` - Get invoice details

### License Management

-   `POST /api/v1/licenses/issue` - Issue new license token
-   `POST /api/v1/licenses/refresh` - Refresh existing license token
-   `POST /api/v1/licenses/validate` - Validate license token authenticity

## 🏗 System Architecture

### Database Schema

-   **merchants**: Business information and trial usage tracking
-   **devices**: POS device registration and management
-   **plans**: Subscription plan definitions with trial periods
-   **subscriptions**: Active subscriptions with trial tracking
-   **invoices**: Payment invoices and billing
-   **payment_confirmations**: Payment proof submissions with admin approval
-   **payments**: Confirmed payment records
-   **license_tokens**: JWT license token management with expiration
-   **audit_logs**: Complete system activity tracking

### Business Logic Flows

1. **Trial Flow**:
    - Merchant Registration → Device Registration → Plan Selection → Trial Start → License Issue
2. **Checkout to Payment Flow**:
    - Trial End → Checkout Creation → Invoice Generation → Payment Confirmation → Admin Approval → License Renewal
3. **License Refresh Flow**:
    - License Expiry Check → Subscription Validation → New Token Issue → Old Token Revocation

### Service Layer Architecture

-   **TrialService**: Trial eligibility, start/end management, conversion to paid
-   **CheckoutService**: Invoice creation, payment processing orchestration
-   **LicenseService**: Token issuance, refresh, validation, and device binding
-   **PaymentService**: Payment confirmation processing and approval workflows

## 🔐 Security Features

### Role-Based Authorization

-   **Admin**: Full system access, approve payments, manage licenses
-   **Operator**: Read/write access, no critical operations
-   **Merchant**: Limited access to own data and devices only

### Protected Operations

-   Payment confirmation approval (Admin only)
-   Invoice payment marking (Admin only)
-   License revocation/reissue (Admin only)
-   System configuration access (Admin only)

### Data Protection

-   JWT token-based authentication with device binding
-   Device-specific license validation and expiration
-   Encrypted sensitive data storage
-   Complete audit trail for all critical operations
-   Foreign key constraints and data integrity checks

## 🎯 Admin Panel Features

### Navigation Groups

-   **Merchants**: Merchant profiles and device management
-   **Subscriptions**: Plans and active subscription monitoring
-   **Payments**: Invoices, confirmations, and payment approval workflow
-   **Licenses**: License token management and expiration monitoring
-   **System**: Audit logs and comprehensive system monitoring

### Smart Payment Processing

-   **One-Click Invoice Approval**: Mark invoices as paid with automatic subscription activation
-   **Automated Workflows**: Payment approval automatically triggers subscription activation and payment confirmation approval
-   **Visual Evidence Review**: Integrated image/PDF viewer for payment evidence files
-   **Comprehensive Status Management**: Easy status changes with detailed admin notes and audit trails

### Key Administrative Actions

-   **Payment Confirmations**:
-   View payment evidence (images/PDFs) directly in admin panel
-   Approve confirmations with automatic invoice payment and subscription activation
-   Reject with detailed admin notes and reason tracking
-   Filter and search by status, invoice ID, or merchant

-   **Invoice Management**:
-   Mark as paid with automatic subscription activation
-   Cancel invoices with reason tracking
-   View related payment confirmations and evidence
-   Complete payment history and audit trails

-   **Advanced Features**:
-   Approve/reject payment confirmations with admin notes
-   Mark invoices as paid/failed with audit trails
-   Revoke/reissue license tokens for security or troubleshooting
-   Monitor trial expirations and conversion rates
-   View comprehensive audit trails with user attribution
-   Manage merchant account status (active/suspended)

## 🧪 Comprehensive Testing

### Test Coverage: 29 Tests Passing ✅

```bash
# Run all tests (29 passing)
php artisan test

# Unit Tests (14 tests)
php artisan test --testsuite=Unit
# - TrialService: Trial eligibility, days calculation, expiration logic
# - Business rule validation and edge cases

# Feature Tests (15 tests)
php artisan test --testsuite=Feature
# - Complete trial start flow with license issuance
# - Checkout to payment approval end-to-end workflow
# - License refresh and expiration scenarios
# - Device ownership and merchant status validation

# Code formatting check
vendor/bin/pint --test
```

### Test Categories

1. **Unit Tests**: Core business logic validation

    - Trial eligibility and validation rules
    - License service functionality
    - Service layer business rules

2. **Feature Tests**: End-to-end workflow testing

    - Trial start to license issuance flow
    - Payment confirmation and approval process
    - License refresh and device validation
    - Error handling and edge cases

3. **Database Tests**: Schema and relationship integrity
    - Factory-generated test data
    - Foreign key constraint validation
    - Migration compatibility testing

## 📦 Commands

### Custom Artisan Commands

````bash
# Clean up expired trials
php artisan trials:cleanup

# Clean up expired license tokens
php artisan licenses:cleanup

## 📦 Available Artisan Commands

### System Management

```bash
# Run database migrations
php artisan migrate

# Seed database with initial data
php artisan db:seed

# Clear application caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run queue workers (for background jobs)
php artisan queue:work
````

### Custom Commands (Implemented)

```bash
# Daily maintenance commands (automated via scheduler)
php artisan subscriptions:mark-expired       # Mark expired subscriptions
php artisan invoices:mark-overdue           # Mark overdue invoices as expired
php artisan notifications:expiration-alerts # Send expiration notifications

# Manual execution
php artisan schedule:list                   # View scheduled tasks
php artisan schedule:run                    # Run scheduler manually
```

### Custom Commands (Future Implementation)

```bash
# Clean up expired trials
php artisan trials:cleanup

# Clean up expired license tokens
php artisan licenses:cleanup

# Generate system health report
php artisan system:status

# Process pending payment confirmations
php artisan payments:process
```

## ⏰ Automated Scheduling

The system includes automated daily maintenance tasks configured in `bootstrap/app.php`:

### Daily Schedule

```php
// Daily at 2:00 AM - Mark expired subscriptions
$schedule->command('subscriptions:mark-expired')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground();

// Daily at 2:30 AM - Mark overdue invoices as expired
$schedule->command('invoices:mark-overdue')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->runInBackground();

// Daily at 9:00 AM - Send expiration notifications
$schedule->command('notifications:expiration-alerts')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground();
```

### Production Setup

Add to your server's crontab for automated execution:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Scheduled Tasks Features

-   **Subscription Expiry**: Automatically marks expired subscriptions and revokes license tokens
-   **Invoice Management**: Marks overdue invoices as expired and handles related subscriptions
-   **Proactive Notifications**: Sends alerts for subscriptions/invoices expiring in 3 days and 1 day
-   **Comprehensive Logging**: All automated tasks log detailed information for audit trails
-   **Safe Execution**: Commands use `withoutOverlapping()` to prevent conflicts

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_NAME="POS Subscription Backend"
APP_ENV=local
APP_KEY=base64:generated-key
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Database (SQLite recommended for development)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# JWT Configuration (if using JWT tokens)
JWT_SECRET=your-jwt-secret-key
JWT_TTL=1440

# Trial & License Configuration
TRIAL_DEFAULT_DURATION_DAYS=7
LICENSE_DEFAULT_DURATION_DAYS=30
LICENSE_REFRESH_THRESHOLD_DAYS=7

# Payment Configuration
PAYMENT_CURRENCY=IDR
PAYMENT_CONFIRMATION_TIMEOUT_HOURS=24
```

### Subscription Plans Configuration

The system comes with pre-configured subscription plans:

-   **Monthly Plan** (MONTHLY): IDR 99,000/month with 7-day trial
-   **Quarterly Plan** (QUARTERLY): IDR 249,000/3 months with 14-day trial
-   **Annual Plan** (ANNUAL): IDR 899,000/year with 30-day trial

## 📈 Monitoring & Maintenance

### Automated Daily Tasks

The system includes automated maintenance through Laravel's scheduler:

-   **2:00 AM**: Mark expired subscriptions and revoke associated license tokens
-   **2:30 AM**: Mark overdue invoices as expired and handle pending subscriptions
-   **9:00 AM**: Send expiration notifications for subscriptions/invoices due in 3/1 days

### System Health Monitoring

-   Database connectivity and migration status
-   License token validation and expiration tracking
-   Trial period monitoring and conversion rates
-   Payment processing status and approval workflows
-   Admin panel access and navigation functionality
-   Automated task execution and logging

### Performance Considerations

-   Optimized database queries with eager loading
-   Efficient license validation with caching
-   Background job processing for heavy operations
-   Audit log archiving and cleanup strategies
-   Scheduled task overlap prevention and monitoring

## 🚨 Troubleshooting

### Common Issues

1. **Filament Navigation Error**: Ensure NavigationGroup enum is properly configured
2. **Database Migration Issues**: Check foreign key constraints and data integrity
3. **Test Failures**: Verify factory data matches current database schema
4. **License Validation**: Confirm JWT secret configuration and token expiration

### Debug Commands

```bash
# Check application status
php artisan about

# View failed jobs
php artisan queue:failed

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()

# Validate configuration
php artisan config:show
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Run tests (`php artisan test`)
4. Commit changes (`git commit -m 'Add amazing feature'`)
5. Push to branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

### Development Standards

-   Follow Laravel coding standards
-   Write comprehensive tests for new features
-   Use Laravel Pint for code formatting (`vendor/bin/pint`)
-   Document API endpoints and business logic changes
-   Maintain backward compatibility

## 📄 License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## 🙏 Acknowledgments

-   Built with Laravel 12 and Filament v4
-   Inspired by modern SaaS subscription management systems
-   Community contributions and feedback

---

**Ready to manage your POS subscriptions efficiently!** 🎉

For support or questions, please check the documentation or create an issue in the repository.

### Scheduled Tasks

-   **Daily 2:00 AM**: Expired subscription cleanup and license revocation
-   **Daily 2:30 AM**: Overdue invoice processing and status updates
-   **Daily 9:00 AM**: Expiration notification alerts (3-day and 1-day warnings)
-   **Continuous**: Audit log generation and system monitoring

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:

-   Create an issue in this repository
-   Contact the development team
-   Check the documentation in the `/docs` folder

## 🔄 Changelog

### v1.0.0 (2025-08-25)

-   Initial release
-   Complete subscription management system
-   Role-based authorization implementation
-   Filament admin panel integration
-   JWT license management
-   Payment processing workflow
-   Trial management system
-   Automated daily maintenance tasks
-   Comprehensive test suite (29 passing tests)
-   Flutter integration documentation
-   Postman API collection

---

**Built with ❤️ using Laravel 12 and Filament v4**
