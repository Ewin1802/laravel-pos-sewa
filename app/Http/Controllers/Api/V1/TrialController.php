<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\TrialStartRequest;
use App\Models\Device;
use App\Models\Plan;
use App\Services\TrialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TrialController extends BaseApiController
{
    public function __construct(private TrialService $trialService) {}

    /**
     * Start a trial subscription
     */
    public function start(TrialStartRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $validated = $request->validated();

            // Get device
            $device = Device::where('merchant_id', $merchant->id)
                ->where('device_uid', $validated['device_uid'])
                ->firstOrFail();

            // Get plan if specified
            $plan = null;
            if (isset($validated['plan_id'])) {
                $plan = Plan::findOrFail($validated['plan_id']);
            }

            // Start trial using service
            $subscription = $this->trialService->startTrial(
                merchant: $merchant,
                device: $device,
                plan: $plan,
                fallbackDays: $validated['trial_days'] ?? 7
            );

            $trialStats = $this->trialService->getTrialStats($merchant);

            return $this->successResponse([
                'subscription' => $subscription,
                'trial_stats' => $trialStats,
            ], 'Trial started successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Trial start failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Get trial status for the authenticated merchant
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $trialStats = $this->trialService->getTrialStats($merchant);

            return $this->successResponse($trialStats, 'Trial status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve trial status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Convert trial to paid subscription
     */
    public function convert(Request $request): JsonResponse
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

            // Get current trial subscription
            $trialSubscription = $merchant->subscriptions()
                ->where('is_trial', true)
                ->where('status', 'active')
                ->first();

            if (!$trialSubscription) {
                return $this->errorResponse('No active trial subscription found', 404);
            }

            $plan = Plan::findOrFail($validated['plan_id']);

            $subscription = $this->trialService->convertTrialToPaid($trialSubscription, $plan);

            return $this->successResponse([
                'subscription' => $subscription->load('plan'),
                'message' => 'Trial converted to paid subscription. Please complete payment to activate.',
            ], 'Trial converted successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Trial conversion failed: ' . $e->getMessage(), 422);
        }
    }
}
