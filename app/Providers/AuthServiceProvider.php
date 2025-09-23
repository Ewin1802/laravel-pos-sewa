<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Models\LicenseToken;
use App\Models\PaymentConfirmation;
use App\Policies\InvoicePolicy;
use App\Policies\LicenseTokenPolicy;
use App\Policies\PaymentConfirmationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        PaymentConfirmation::class => PaymentConfirmationPolicy::class,
        Invoice::class => InvoicePolicy::class,
        LicenseToken::class => LicenseTokenPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define additional gates if needed
        Gate::define('admin-only', function ($user) {
            return $user->hasRole('admin');
        });

        Gate::define('admin-or-operator', function ($user) {
            return $user->hasRole('admin') || $user->hasRole('operator');
        });
    }
}
