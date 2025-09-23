<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Plan;
use Illuminate\Http\JsonResponse;

class PlanController extends BaseApiController
{
    public function index(): JsonResponse
    {
        try {
            $plans = Plan::active()
                ->orderBy('price')
                ->get()
                ->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'code' => $plan->code,
                        'name' => $plan->name,
                        'description' => $plan->description,
                        'features' => $plan->features,
                        'price' => $plan->price,
                        'currency' => $plan->currency,
                        'duration_days' => $plan->duration_days,
                        'trial_days' => $plan->trial_days,
                        'has_trial' => $plan->hasTrialPeriod(),
                        'is_active' => $plan->is_active,
                    ];
                });

            return $this->successResponse($plans, 'Plans retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve plans: ' . $e->getMessage(), 500);
        }
    }
}
