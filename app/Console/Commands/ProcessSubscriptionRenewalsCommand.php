<?php

namespace App\Console\Commands;

use App\Services\SubscriptionRenewalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewalsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-renewals
                            {--days-before=7 : Days before expiry to generate renewal invoices}
                            {--auto-expire : Automatically expire overdue subscriptions}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription renewals and generate renewal invoices';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionRenewalService $renewalService): int
    {
        $daysBeforeExpiry = (int) $this->option('days-before');
        $autoExpire = $this->option('auto-expire');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info("🔄 Starting subscription renewal process...");
        $this->info("📅 Generating renewal invoices for subscriptions expiring within {$daysBeforeExpiry} days");

        try {
            // Generate renewal invoices for expiring subscriptions
            if (!$dryRun) {
                $generated = $renewalService->generateRenewalInvoices($daysBeforeExpiry);
            } else {
                $expiringSubscriptions = $renewalService->getSubscriptionsExpiringWithin($daysBeforeExpiry);
                $generated = $expiringSubscriptions->map(function ($subscription) {
                    return [
                        'merchant_id' => $subscription->merchant_id,
                        'subscription_id' => $subscription->id,
                        'amount' => $subscription->plan->price,
                        'expires_at' => $subscription->end_at,
                    ];
                })->toArray();
            }

            if (empty($generated)) {
                $this->info('✅ No renewal invoices needed to be generated');
            } else {
                $this->info("📄 Generated " . count($generated) . " renewal invoices:");
                $this->table(
                    ['Merchant ID', 'Subscription ID', 'Amount', 'Status'],
                    collect($generated)->map(function ($item) use ($dryRun) {
                        return [
                            $item['merchant_id'],
                            $item['subscription_id'],
                            number_format($item['amount']),
                            $dryRun ? 'DRY RUN' : 'CREATED'
                        ];
                    })
                );
            }

            // Auto-expire overdue subscriptions if requested
            if ($autoExpire) {
                $this->info("⏰ Processing subscription expirations...");

                if (!$dryRun) {
                    $expiredCount = $renewalService->expireOverdueSubscriptions();
                } else {
                    $expiredSubscriptions = $renewalService->getExpiredSubscriptions();
                    $expiredCount = $expiredSubscriptions->count();
                }

                if ($expiredCount > 0) {
                    $status = $dryRun ? 'WOULD EXPIRE' : 'EXPIRED';
                    $this->warn("⚠️  {$status} {$expiredCount} overdue subscriptions");
                } else {
                    $this->info("✅ No subscriptions needed to be expired");
                }
            }

            // Show summary statistics
            $this->showRenewalSummary($renewalService, $dryRun);

            if (!$dryRun) {
                Log::info('Subscription renewal process completed', [
                    'generated_invoices' => count($generated),
                    'expired_subscriptions' => $autoExpire ? ($expiredCount ?? 0) : 0,
                    'days_before_expiry' => $daysBeforeExpiry,
                ]);
            }

            $this->info("✅ Subscription renewal process completed successfully!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error processing subscription renewals: " . $e->getMessage());
            Log::error('Subscription renewal process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    private function showRenewalSummary(SubscriptionRenewalService $renewalService, bool $dryRun): void
    {
        $this->info("\n📊 Renewal Summary:");

        // Subscriptions expiring within different timeframes
        $expiring1Day = $renewalService->getSubscriptionsExpiringWithin(1);
        $expiring3Days = $renewalService->getSubscriptionsExpiringWithin(3);
        $expiring7Days = $renewalService->getSubscriptionsExpiringWithin(7);
        $expiring30Days = $renewalService->getSubscriptionsExpiringWithin(30);
        $expired = $renewalService->getExpiredSubscriptions();

        $summaryData = [
            ['Timeframe', 'Count', 'Status'],
            ['Expiring within 1 day', $expiring1Day->count(), '🔴 Critical'],
            ['Expiring within 3 days', $expiring3Days->count(), '🟡 Warning'],
            ['Expiring within 7 days', $expiring7Days->count(), '🟠 Attention'],
            ['Expiring within 30 days', $expiring30Days->count(), '🔵 Info'],
            ['Already expired', $expired->count(), '⚫ Expired'],
        ];

        $this->table(
            array_shift($summaryData),
            $summaryData
        );

        if ($dryRun) {
            $this->info("\n💡 Run without --dry-run to actually process renewals");
        }
    }
}