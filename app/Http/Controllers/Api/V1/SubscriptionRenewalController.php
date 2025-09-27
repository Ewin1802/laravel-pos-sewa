<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use App\Services\SubscriptionRenewalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SubscriptionRenewalController extends BaseApiController
{
    public function __construct(private SubscriptionRenewalService $renewalService) {}

    /**
     * Get renewal status and options for the authenticated merchant
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $renewalStats = $this->renewalService->getRenewalStats($merchant);

            return $this->successResponse($renewalStats, 'Renewal status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve renewal status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Renew subscription with the same plan
     */
    public function renew(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $result = $this->renewalService->renewSubscription($merchant);

            return $this->successResponse($result, 'Subscription renewal initiated successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Renewal validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Renewal failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Renew subscription with a different plan (upgrade/downgrade)
     */
    public function renewWithPlan(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $validated = $request->validate([
                'plan_id' => 'required|exists:plans,id',
            ]);

            $plan = Plan::findOrFail($validated['plan_id']);

            $result = $this->renewalService->renewSubscription($merchant, $plan);

            return $this->successResponse($result, 'Subscription renewal with plan change initiated successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Renewal validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Renewal failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Get available plans for renewal
     */
    public function availablePlans(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $plans = Plan::where('is_active', true)
                ->select(['id', 'name', 'code', 'description', 'features', 'price', 'currency', 'duration_days', 'trial_days'])
                ->get();

            // Get current subscription to highlight current plan
            $renewalStats = $this->renewalService->getRenewalStats($merchant);
            $currentPlanId = $renewalStats['has_subscription'] ? $renewalStats['subscription']['plan']['id'] : null;

            $plansData = $plans->map(function ($plan) use ($currentPlanId) {
                return [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'code' => $plan->code,
                    'description' => $plan->description,
                    'features' => $plan->features,
                    'price' => $plan->price,
                    'currency' => $plan->currency,
                    'duration_days' => $plan->duration_days,
                    'trial_days' => $plan->trial_days,
                    'is_current_plan' => $plan->id === $currentPlanId,
                ];
            });

            return $this->successResponse([
                'plans' => $plansData,
                'current_subscription' => $renewalStats['has_subscription'] ? $renewalStats['subscription'] : null,
            ], 'Available plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve available plans: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get renewal history for the merchant
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $subscriptions = $merchant->subscriptions()
                ->with(['plan', 'invoices'])
                ->where('is_trial', false)
                ->orderBy('created_at', 'desc')
                ->get();

            $history = $subscriptions->map(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'start_at' => $subscription->start_at,
                    'end_at' => $subscription->end_at,
                    'plan' => [
                        'id' => $subscription->plan->id,
                        'name' => $subscription->plan->name,
                        'code' => $subscription->plan->code,
                        'price' => $subscription->plan->price,
                        'currency' => $subscription->plan->currency,
                        'duration_days' => $subscription->plan->duration_days,
                    ],
                    'invoices_count' => $subscription->invoices->count(),
                    'total_paid' => $subscription->invoices->where('status', 'paid')->sum('amount'),
                    'created_at' => $subscription->created_at,
                    'updated_at' => $subscription->updated_at,
                ];
            });

            return $this->successResponse([
                'subscriptions' => $history,
                'total_subscriptions' => $subscriptions->count(),
                'active_subscriptions' => $subscriptions->where('status', 'active')->count(),
                'expired_subscriptions' => $subscriptions->where('status', 'expired')->count(),
            ], 'Subscription history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve subscription history: ' . $e->getMessage(), 500);
        }
    }
}