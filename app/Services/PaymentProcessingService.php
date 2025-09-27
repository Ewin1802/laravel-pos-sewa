<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PaymentConfirmation;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Exception;

class PaymentProcessingService
{
    /**
     * Mark invoice as paid and activate related subscription
     */
    public function markInvoiceAsPaid(Invoice $invoice, string $method = 'manual', ?string $reference = null): bool
    {
        try {
            DB::beginTransaction();

            // Update invoice status
            $invoice->update([
                'status' => Invoice::STATUS_PAID,
                'paid_at' => now(),
            ]);

            // Create payment record
            Payment::create([
                'invoice_id' => $invoice->id,
                'amount' => $invoice->amount,
                'paid_at' => now(),
                'method' => $method,
                'reference_no' => $reference,
            ]);

            // Activate subscription if exists
            if ($invoice->subscription) {
                $this->activateSubscription($invoice->subscription);
            }

            // Approve pending payment confirmations for this invoice
            PaymentConfirmation::where('invoice_id', $invoice->id)
                ->where('status', PaymentConfirmation::STATUS_SUBMITTED)
                ->update([
                    'status' => PaymentConfirmation::STATUS_APPROVED,
                    'reviewed_at' => now(),
                    'admin_note' => 'Auto-approved when invoice marked as paid',
                ]);

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approve payment confirmation and mark invoice as paid
     */
    public function approvePaymentConfirmation(PaymentConfirmation $confirmation, ?int $reviewedBy = null, ?string $adminNote = null): bool
    {
        try {
            DB::beginTransaction();

            // Update payment confirmation
            $confirmation->update([
                'status' => PaymentConfirmation::STATUS_APPROVED,
                'reviewed_by' => $reviewedBy,
                'reviewed_at' => now(),
                'admin_note' => $adminNote ?? 'Payment confirmed and approved',
            ]);

            // Mark invoice as paid
            $invoice = $confirmation->invoice;
            $this->markInvoiceAsPaid(
                $invoice,
                'payment_confirmation',
                $confirmation->reference_no
            );

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reject payment confirmation
     */
    public function rejectPaymentConfirmation(PaymentConfirmation $confirmation, ?int $reviewedBy = null, ?string $adminNote = null): bool
    {
        $confirmation->update([
            'status' => PaymentConfirmation::STATUS_REJECTED,
            'reviewed_by' => $reviewedBy,
            'reviewed_at' => now(),
            'admin_note' => $adminNote ?? 'Payment confirmation rejected',
        ]);

        return true;
    }

    /**
     * Activate subscription (handles both new subscriptions and renewals)
     */
    private function activateSubscription(Subscription $subscription): void
    {
        $plan = $subscription->plan;
        $now = now();

        // Check if this is a renewal (subscription already has start_at and end_at)
        $isRenewal = $subscription->start_at && $subscription->end_at;

        if ($isRenewal) {
            // For renewals, extend from the current end date or now (whichever is later)
            $startDate = $subscription->end_at->isFuture() ? $subscription->end_at : $now;
            $endDate = $startDate->copy()->addDays($plan->duration_days);
        } else {
            // For new subscriptions, start from now
            $startDate = $now;
            $endDate = $now->copy()->addDays($plan->duration_days);
        }

        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'start_at' => $startDate,
            'end_at' => $endDate,
        ]);

        // If this is a renewal, log it for audit purposes
        if ($isRenewal) {
            logger()->info('Subscription renewed', [
                'subscription_id' => $subscription->id,
                'merchant_id' => $subscription->merchant_id,
                'plan_id' => $subscription->plan_id,
                'new_start_at' => $startDate,
                'new_end_at' => $endDate,
            ]);
        }
    }
}
