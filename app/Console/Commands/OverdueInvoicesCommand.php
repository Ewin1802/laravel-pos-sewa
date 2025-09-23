<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OverdueInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:mark-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark overdue invoices as expired and handle associated subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting overdue invoices cleanup...');

        // Find invoices that are overdue (past due_at and not paid)
        $overdueInvoices = Invoice::whereIn('status', [
            Invoice::STATUS_PENDING,
            Invoice::STATUS_AWAITING_CONFIRMATION,
        ])
            ->where('due_at', '<', now())
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');
            Log::info('Overdue invoices cleanup completed - no invoices to expire');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($overdueInvoices as $invoice) {
            // Mark invoice as expired
            $invoice->update([
                'status' => Invoice::STATUS_EXPIRED,
            ]);

            // If this was for a subscription renewal and it's still pending, mark it as expired
            if ($invoice->subscription) {
                $subscription = $invoice->subscription;
                if ($subscription->status === $subscription::STATUS_PENDING) {
                    $subscription->update([
                        'status' => $subscription::STATUS_EXPIRED,
                    ]);

                    // Revoke license tokens for expired pending subscriptions
                    $revokedTokens = $subscription->licenseTokens()
                        ->whereNull('revoked_at')
                        ->update(['revoked_at' => now()]);

                    Log::info('Pending subscription expired due to overdue invoice', [
                        'subscription_id' => $subscription->id,
                        'invoice_id' => $invoice->id,
                        'revoked_tokens' => $revokedTokens,
                    ]);
                }
            }

            $count++;

            Log::info('Invoice marked as overdue/expired', [
                'invoice_id' => $invoice->id,
                'merchant_id' => $invoice->merchant_id,
                'amount' => $invoice->amount,
                'due_at' => $invoice->due_at,
                'subscription_id' => $invoice->subscription_id,
            ]);
        }

        $this->info("Successfully marked {$count} invoices as expired.");
        Log::info("Overdue invoices cleanup completed - {$count} invoices expired");

        return self::SUCCESS;
    }
}
