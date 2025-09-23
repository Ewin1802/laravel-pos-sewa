<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends BaseApiController
{
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant not found', 404);
            }

            $subscription = $merchant->subscriptions()
                ->with(['plan', 'currentInvoice'])
                ->whereIn('status', [
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_PENDING,
                ])
                ->latest()
                ->first();

            if (!$subscription) {
                return $this->successResponse([
                    'has_subscription' => false,
                    'subscription' => null,
                ], 'No active subscription found');
            }

            $data = [
                'has_subscription' => true,
                'subscription' => [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'is_trial' => $subscription->is_trial,
                    'start_at' => $subscription->start_at,
                    'end_at' => $subscription->end_at,
                    'trial_started_at' => $subscription->trial_started_at,
                    'trial_end_at' => $subscription->trial_end_at,
                    'is_trial_expired' => $subscription->isTrialExpired(),
                    'plan' => [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'code' => $subscription->plan->code,
                        'price' => $subscription->plan->price,
                        'currency' => $subscription->plan->currency,
                        'duration_days' => $subscription->plan->duration_days,
                        'trial_days' => $subscription->plan->trial_days,
                    ],
                    'current_invoice' => $subscription->currentInvoice ? [
                        'id' => $subscription->currentInvoice->id,
                        'amount' => $subscription->currentInvoice->amount,
                        'currency' => $subscription->currentInvoice->currency,
                        'status' => $subscription->currentInvoice->status,
                        'due_at' => $subscription->currentInvoice->due_at,
                        'is_overdue' => $subscription->currentInvoice->isOverdue(),
                    ] : null,
                ],
            ];

            return $this->successResponse($data, 'Subscription status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve subscription status: ' . $e->getMessage(), 500);
        }
    }
}
