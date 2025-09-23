<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\DeviceRegisterRequest;
use App\Models\Device;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends BaseApiController
{
    public function register(DeviceRegisterRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $merchant = $user->merchant;

            if (!$merchant || !$merchant->isActive()) {
                return $this->errorResponse('Merchant account is not active', 403);
            }

            // Check if device already exists for this merchant
            $existingDevice = Device::where('merchant_id', $merchant->id)
                ->where('device_uid', $request->validated('device_uid'))
                ->first();

            if ($existingDevice) {
                return $this->errorResponse('Device already registered', 409);
            }

            $device = Device::create([
                'merchant_id' => $merchant->id,
                'device_uid' => $request->validated('device_uid'),
                'label' => $request->validated('label'),
                'is_active' => true,
            ]);

            return $this->successResponse($device, 'Device registered successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Device registration failed: ' . $e->getMessage(), 422);
        }
    }
}
