<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Models\Plan;
use App\Services\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends BaseApiController
{
    public function __construct(private CheckoutService $checkoutService) {}

    /**
     * Start checkout process for a plan subscription
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $validated = $request->validated();
            $plan = Plan::findOrFail($validated['plan_id']);

            // Start checkout process using service
            $result = $this->checkoutService->start(
                merchant: $merchant,
                plan: $plan,
                deviceUid: $validated['device_uid']
            );

            return $this->successResponse($result, 'Checkout completed successfully', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Checkout failed: ' . $e->getMessage(), 422);
        }
    }

    /**
     * Get checkout status and pending payments for the merchant
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $checkoutStats = $this->checkoutService->getCheckoutStats($merchant);

            return $this->successResponse($checkoutStats, 'Checkout status retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve checkout status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a pending checkout/invoice
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant) {
                return $this->errorResponse('Merchant account not found', 404);
            }

            $validated = $request->validate([
                'invoice_id' => 'required|integer|exists:invoices,id',
            ]);

            $invoice = $merchant->invoices()->findOrFail($validated['invoice_id']);

            $this->checkoutService->cancelCheckout($merchant, $invoice);

            return $this->successResponse([], 'Checkout cancelled successfully');
        } catch (ValidationException $e) {
            return $this->errorResponse('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to cancel checkout: ' . $e->getMessage(), 422);
        }
    }
}
