<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirationNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:expiration-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for subscriptions and invoices expiring in 3 or 1 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for expiring subscriptions and invoices...');

        // Check for subscriptions expiring in 3 days
        $this->checkExpiringSubscriptions(3);

        // Check for subscriptions expiring in 1 day
        $this->checkExpiringSubscriptions(1);

        // Check for invoices due in 3 days
        $this->checkDueInvoices(3);

        // Check for invoices due in 1 day
        $this->checkDueInvoices(1);

        $this->info('Expiration notifications check completed.');

        return self::SUCCESS;
    }

    /**
     * Check for subscriptions expiring in specified days
     */
    private function checkExpiringSubscriptions(int $days): void
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        $expiringSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->whereBetween('end_at', [$targetDate, $endDate])
            ->with(['merchant', 'plan'])
            ->get();

        foreach ($expiringSubscriptions as $subscription) {
            $this->info("Subscription expiring in {$days} days: {$subscription->merchant->name} - {$subscription->plan->name}");

            Log::warning("Subscription expiring in {$days} days", [
                'days_until_expiry' => $days,
                'subscription_id' => $subscription->id,
                'merchant_id' => $subscription->merchant_id,
                'merchant_name' => $subscription->merchant->name,
                'merchant_email' => $subscription->merchant->email,
                'plan_name' => $subscription->plan->name,
                'plan_code' => $subscription->plan->code,
                'end_at' => $subscription->end_at,
                'is_trial' => $subscription->is_trial,
                'notification_type' => 'subscription_expiring',
            ]);
        }

        if ($expiringSubscriptions->isNotEmpty()) {
            Log::info("Found {$expiringSubscriptions->count()} subscriptions expiring in {$days} days");
        }
    }

    /**
     * Check for invoices due in specified days
     */
    private function checkDueInvoices(int $days): void
    {
        $targetDate = now()->addDays($days)->startOfDay();
        $endDate = now()->addDays($days)->endOfDay();

        $dueInvoices = Invoice::whereIn('status', [
            Invoice::STATUS_PENDING,
            Invoice::STATUS_AWAITING_CONFIRMATION,
        ])
            ->whereBetween('due_at', [$targetDate, $endDate])
            ->with(['merchant', 'subscription.plan'])
            ->get();

        foreach ($dueInvoices as $invoice) {
            $planInfo = $invoice->subscription ? $invoice->subscription->plan->name : 'N/A';
            $this->info("Invoice due in {$days} days: {$invoice->merchant->name} - {$planInfo} - " . number_format($invoice->amount / 100, 0) . " IDR");

            Log::warning("Invoice due in {$days} days", [
                'days_until_due' => $days,
                'invoice_id' => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
                'merchant_name' => $invoice->merchant->name,
                'merchant_email' => $invoice->merchant->email,
                'amount' => $invoice->amount,
                'amount_formatted' => number_format($invoice->amount / 100, 0) . ' IDR',
                'currency' => $invoice->currency,
                'due_at' => $invoice->due_at,
                'status' => $invoice->status,
                'subscription_id' => $invoice->subscription_id,
                'plan_name' => $planInfo,
                'notification_type' => 'invoice_due',
            ]);
        }

        if ($dueInvoices->isNotEmpty()) {
            Log::info("Found {$dueInvoices->count()} invoices due in {$days} days");
        }
    }
}
