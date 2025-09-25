<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LicenseIssueRequest;
use App\Http\Requests\Api\V1\LicenseRefreshRequest;
use App\Models\Device;
use App\Models\Subscription;
use App\Services\LicenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LicenseController extends BaseApiController
{
    public function __construct(private LicenseService $licenseService) {}

    public function issue(LicenseIssueRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant || !$merchant->isActive()) {
                return $this->errorResponse('Merchant account is not active', 403);
            }

            $device = Device::where('id', $request->validated('device_id'))
                ->where('merchant_id', $merchant->id)
                ->where('is_active', true)
                ->firstOrFail();

            // Check for active subscription
            $subscription = $merchant->subscriptions()
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
                ->latest()
                ->first();

            if (!$subscription) {
                return $this->errorResponse('No active subscription found', 403);
            }

            // Issue license token using service
            $licenseToken = $this->licenseService->issue($merchant, $device, $subscription);

            return $this->successResponse([
                'license_token' => $licenseToken->getAttribute('plain_token'),
                'expires_at' => $licenseToken->expires_at->setTimezone('UTC'),
                'device' => $device,
                'subscription_status' => $subscription->status,
                'plan_code' => $subscription->isTrial() ? 'TRIAL' : $subscription->plan->code,
                'is_trial' => $subscription->isTrial(),
            ], 'License issued successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('License issuance failed: ' . $e->getMessage(), 422);
        }
    }

    public function refresh(LicenseRefreshRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant || !$merchant->isActive()) {
                return $this->errorResponse('Merchant account is not active', 403);
            }

            $device = Device::where('id', $request->validated('device_id'))
                ->where('merchant_id', $merchant->id)
                ->where('is_active', true)
                ->firstOrFail();

            // Refresh license token using service
            $licenseToken = $this->licenseService->refresh($merchant, $device);

            // Get current subscription for response
            $subscription = $merchant->subscriptions()
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
                ->latest()
                ->first();

            return $this->successResponse([
                'license_token' => $licenseToken->getAttribute('plain_token'),
                'expires_at' => $licenseToken->expires_at->setTimezone('UTC'),
                'device' => $device,
                'subscription_status' => $subscription?->status,
                'plan_code' => $subscription?->isTrial() ? 'TRIAL' : $subscription?->plan->code,
                'is_trial' => $subscription?->isTrial() ?? false,
            ], 'License refreshed successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('License refresh failed: ' . $e->getMessage(), 422);
        }
    }

    public function validate(Request $request): JsonResponse
    {
        try {
            $token = $request->input('token');
            $deviceId = $request->input('device_id');

            if (!$token || !$deviceId) {
                return $this->errorResponse('Token and device_id are required', 422);
            }

            $device = Device::with('merchant')->findOrFail($deviceId);

            // Validate token using service
            $payload = $this->licenseService->validateToken($token, $device);

            return $this->successResponse([
                'valid' => true,
                'payload' => $payload,
                'device' => $device,
                'merchant' => $device->merchant,
            ], 'License token is valid');
        } catch (\Exception $e) {
            return $this->errorResponse('License validation failed: ' . $e->getMessage(), 401);
        }
    }

    public function getLicense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant || !$merchant->isActive()) {
                return $this->errorResponse('Merchant account is not active', 403);
            }

            // Ambil device aktif terkait merchant (misal ambil yang terakhir dipakai)
            $device = $merchant->devices()
                ->where('is_active', true)
                ->latest()
                ->first();

            if (!$device) {
                return $this->errorResponse('No active device found', 404);
            }

            // Ambil subscription aktif
            $subscription = $merchant->subscriptions()
                ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
                ->latest()
                ->first();

            if (!$subscription) {
                return $this->errorResponse('No active subscription found', 403);
            }

            // Ambil license token terakhir dari service
            $licenseToken = $this->licenseService->getExistingLicense($merchant, $device, $subscription);

            if (!$licenseToken) {
                return $this->errorResponse('No active license found', 404);
            }

            return $this->successResponse([
                'license_token' => $licenseToken->getAttribute('plain_token'),
                'expires_at' => $licenseToken->expires_at->setTimezone('UTC'),
                'device' => $device,
                'subscription_status' => $subscription->status,
                'plan_code' => $subscription->isTrial() ? 'TRIAL' : $subscription->plan->code,
                'is_trial' => $subscription->isTrial(),
            ], 'License fetched successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Get license failed: ' . $e->getMessage(), 422);
        }
    }


}
