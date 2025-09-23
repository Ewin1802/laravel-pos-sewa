<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\RegisterMerchantRequest;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function registerMerchant(RegisterMerchantRequest $request): JsonResponse
    {
        try {
            $data = DB::transaction(function () use ($request) {
                // Create user
                $user = User::create([
                    'name' => $request->validated('name'),
                    'email' => $request->validated('email'),
                    'password' => Hash::make($request->validated('password')),
                ]);

                // Create merchant
                $merchant = Merchant::create([
                    'user_id' => $user->id,
                    'name' => $request->validated('business_name'),
                    'contact_name' => $request->validated('contact_name'),
                    'email' => $request->validated('email'),
                    'phone' => $request->validated('phone'),
                    'whatsapp' => $request->validated('whatsapp'),
                    'address' => $request->validated('address'),
                    'status' => Merchant::STATUS_ACTIVE,
                    'trial_used' => false,
                ]);

                // Create access token
                $token = $user->createToken('merchant-token')->plainTextToken;

                return [
                    'user' => $user,
                    'merchant' => $merchant,
                    'token' => $token,
                ];
            });

            return $this->successResponse($data, 'Merchant registered successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 422);
        }
    }

    // public function login(Request $request): JsonResponse
    // {
    //     $credentials = $request->only('email', 'password');

    //     if (!Auth::attempt($credentials)) {
    //         return $this->errorResponse('Invalid email or password', 401);
    //     }

    //     $user = Auth::user();
    //     $token = $user->createToken('merchant-token')->plainTextToken;
    //     $merchant = $user->merchant;

    //     if (!$merchant || !$merchant->isActive()) {
    //         return $this->errorResponse('Merchant account is not active', 403);
    //     }

    //     // Cari device aktif (misalnya device yang login sekarang)
    //     $device = $merchant->devices()
    //         ->where('is_active', true)
    //         ->latest()
    //         ->first();

    //     // Ambil subscription aktif
    //     $subscription = $merchant->subscriptions()
    //         ->whereIn('status', [\App\Models\Subscription::STATUS_ACTIVE, \App\Models\Subscription::STATUS_PENDING])
    //         ->latest()
    //         ->first();

    //     $licenseData = null;
    //     $subscriptionMessage = null;

    //     if ($subscription && $device) {
    //         // Coba ambil license aktif
    //         $licenseToken = app(\App\Services\LicenseService::class)
    //             ->getExistingLicense($merchant, $device, $subscription);

    //         if ($licenseToken) {
    //             $licenseData = [
    //                 'license_token' => $licenseToken->getAttribute('plain_token'),
    //                 'expires_at' => $licenseToken->expires_at,
    //                 'device' => $device,
    //                 'subscription_status' => $subscription->status,
    //                 'plan_code' => $subscription->isTrial() ? 'TRIAL' : $subscription->plan->code,
    //                 'is_trial' => $subscription->isTrial(),
    //             ];
    //         } else {
    //             $subscriptionMessage = 'No active license found. Please renew or subscribe to continue.';
    //         }
    //     } else {
    //         $subscriptionMessage = 'No active subscription found. Please subscribe to continue.';
    //     }

    //     return $this->successResponse([
    //         'user' => $user,
    //         'merchant' => $merchant,
    //         'device' => $device,
    //         'token' => $token,
    //         'license' => $licenseData,
    //         'subscription_message' => $subscriptionMessage,
    //     ], 'Login successful');
    // }


    public function login(Request $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return $this->errorResponse('Invalid email or password', 401);
        }

        $user = Auth::user();
        $token = $user->createToken('merchant-token')->plainTextToken;

        $merchant = $user->merchant;

        return $this->successResponse([
            'user' => $user,
            'merchant' => $merchant,
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully');
    }
}
