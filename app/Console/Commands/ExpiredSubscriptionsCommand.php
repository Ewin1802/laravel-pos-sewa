<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpiredSubscriptionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:mark-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark expired subscriptions as expired and revoke associated license tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting expired subscriptions cleanup...');

        // Find active subscriptions that have passed their end date
        $expiredSubscriptions = Subscription::where('status', Subscription::STATUS_ACTIVE)
            ->where('end_at', '<', now())
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No expired subscriptions found.');
            Log::info('Expired subscriptions cleanup completed - no subscriptions to expire');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expiredSubscriptions as $subscription) {
            // Mark subscription as expired
            $subscription->update([
                'status' => Subscription::STATUS_EXPIRED,
            ]);

            // Revoke all active license tokens for this subscription
            $revokedTokens = $subscription->licenseTokens()
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $count++;

            Log::info('Subscription expired', [
                'subscription_id' => $subscription->id,
                'merchant_id' => $subscription->merchant_id,
                'plan_code' => $subscription->plan->code,
                'expired_at' => $subscription->end_at,
                'revoked_tokens' => $revokedTokens,
            ]);
        }

        $this->info("Successfully marked {$count} subscriptions as expired.");
        Log::info("Expired subscriptions cleanup completed - {$count} subscriptions expired");

        return self::SUCCESS;
    }
}
