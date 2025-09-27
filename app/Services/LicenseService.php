<?php

namespace App\Services;

use App\Models\Device;
use App\Models\LicenseToken;
use App\Models\Merchant;
use App\Models\Subscription;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class LicenseService
{
    /**
     * Issue a new license token for a device
     */
    public function issue(Merchant $merchant, Device $device, Subscription $subscription, array $claims = []): LicenseToken
    {
        // Validate inputs
        $this->validateIssueRequest($merchant, $device, $subscription);

        // Revoke any existing active tokens for this device
        $this->revokeActiveTokensForDevice($device);

        // Generate JWT payload
        $payload = $this->buildJwtPayload($merchant, $device, $subscription, $claims);

        // Sign the JWT
        $jwt = $this->signJwt($payload);

        // Store license token in database
        $licenseToken = LicenseToken::create([
            'merchant_id' => $merchant->id,
            'device_id' => $device->id,
            'subscription_id' => $subscription->id,
            'token' => hash('sha256', $jwt),
            'plain_token' => $jwt,
            'expires_at' => now()->createFromTimestamp($payload['exp']),
            'last_refreshed_at' => now(),
        ]);

        // Set the plain JWT on the model for immediate use
        // $licenseToken->setAttribute('plain_token', $jwt); // Already set during creation

        // Update device last seen
        $device->updateLastSeen();

        return $licenseToken;
    }

    /**
     * Refresh an existing license token
     */
    public function refresh(Merchant $merchant, Device $device): LicenseToken
    {
        // Validate inputs
        $this->validateRefreshRequest($merchant, $device);

        // Get subscription for refresh (includes expired to check expiry)
        $subscription = $this->getSubscriptionForRefresh($merchant);

        if (!$subscription) {
            throw new \Exception('No active subscription found for merchant');
        }

        // Check if subscription is expired
        if ($subscription->isExpired() || ($subscription->isTrial() && $subscription->isTrialExpired())) {
            throw new \Exception('Subscription has expired');
        }

        // Get existing token
        $existingToken = $this->getActiveTokenForDevice($device);

        if (!$existingToken) {
            throw new \Exception('No active license token found for device');
        }

        // Revoke the existing token
        $existingToken->revoke();

        // Generate new JWT payload
        $payload = $this->buildJwtPayload($merchant, $device, $subscription);

        // Sign the new JWT
        $jwt = $this->signJwt($payload);

        // Create new token
        $newToken = LicenseToken::create([
            'merchant_id' => $merchant->id,
            'device_id' => $device->id,
            'subscription_id' => $subscription->id,
            'token' => hash('sha256', $jwt),
            'plain_token' => $jwt,
            'expires_at' => now()->createFromTimestamp($payload['exp']),
            'last_refreshed_at' => now(),
        ]);

        // Update device last seen
        $device->updateLastSeen();

        return $newToken;
    }

    /**
     * Revoke a license token
     */
    public function revoke(LicenseToken $token): void
    {
        $token->revoke();
    }

    /**
     * Validate issue request parameters
     */
    private function validateIssueRequest(Merchant $merchant, Device $device, Subscription $subscription): void
    {
        if (!$merchant->isActive()) {
            throw new \Exception('Merchant account is not active');
        }

        if (!$device->isActive()) {
            throw new \Exception('Device is not active');
        }

        if ($device->merchant_id !== $merchant->id) {
            throw new \Exception('Device does not belong to this merchant');
        }

        if ($subscription->merchant_id !== $merchant->id) {
            throw new \Exception('Subscription does not belong to this merchant');
        }

        if (!in_array($subscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])) {
            throw new \Exception('Subscription is not active or pending');
        }
    }

    /**
     * Validate refresh request parameters
     */
    private function validateRefreshRequest(Merchant $merchant, Device $device): void
    {
        if (!$merchant->isActive()) {
            throw new \Exception('Merchant account is not active');
        }

        if (!$device->isActive()) {
            throw new \Exception('Device is not active');
        }

        if ($device->merchant_id !== $merchant->id) {
            throw new \Exception('Device does not belong to this merchant');
        }
    }

    /**
     * Get active subscription for merchant
     */
    private function getActiveSubscription(Merchant $merchant): ?Subscription
    {
        return $merchant->subscriptions()
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_PENDING])
            ->latest()
            ->first();
    }

    /**
     * Get subscription for refresh (includes expired subscriptions to check expiry)
     */
    private function getSubscriptionForRefresh(Merchant $merchant): ?Subscription
    {
        return $merchant->subscriptions()
            ->latest()
            ->first();
    }

    /**
     * Get active token for device
     */
    private function getActiveTokenForDevice(Device $device): ?LicenseToken
    {
        return LicenseToken::where('device_id', $device->id)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    /**
     * Revoke all active tokens for a device
     */
    private function revokeActiveTokensForDevice(Device $device): void
    {
        LicenseToken::where('device_id', $device->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Build JWT payload with all required claims
     */
    private function buildJwtPayload(Merchant $merchant, Device $device, Subscription $subscription, array $customClaims = []): array
    {
        $now = now();
        $expiresAt = $subscription->isTrial() && $subscription->trial_end_at
            ? $subscription->trial_end_at
            : ($subscription->end_at ?? $now->addDay());

        // Ensure expiration is not more than 30 days from now (security measure)
        $maxExpiration = $now->copy()->addDays(30);
        if ($expiresAt->gt($maxExpiration)) {
            $expiresAt = $maxExpiration;
        }

        $payload = [
            // Standard JWT claims
            'iss' => 'jagoflutter-pos',
            'sub' => 'license',
            'exp' => $expiresAt->timestamp,
            'nbf' => ($subscription->start_at ?? $now)->timestamp,
            'iat' => $now->timestamp,
            'jti' => (string) Str::uuid(),

            // Custom claims
            'merchant_id' => $merchant->id,
            'device_id' => $device->id,
            'plan' => $subscription->isTrial() ? 'TRIAL' : $subscription->plan->code,
            'trial' => $subscription->isTrial(),

            // Additional subscription info
            'subscription_id' => $subscription->id,
            'subscription_status' => $subscription->status,
            'merchant_status' => $merchant->status,
            'device_uid' => $device->device_uid,
        ];

        // Merge custom claims
        return array_merge($payload, $customClaims);
    }

    /**
     * Sign JWT with configured secret
     */
    private function signJwt(array $payload): string
    {
        $secret = config('app.license_jwt_secret', config('app.jwt_secret_for_license'));

        if (empty($secret)) {
            throw new \Exception('JWT secret for license tokens is not configured');
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Verify and decode JWT token
     */
    public function verifyJwt(string $jwt): array
    {
        $secret = config('app.license_jwt_secret', config('app.jwt_secret_for_license'));

        if (empty($secret)) {
            throw new \Exception('JWT secret for license tokens is not configured');
        }

        try {
            $decoded = JWT::decode($jwt, new Key($secret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            throw new \Exception('Invalid JWT token: ' . $e->getMessage());
        }
    }

    /**
     * Validate a license token for a device
     */
    public function validateToken(string $jwt, Device $device): array
    {
        // Decode JWT
        $payload = $this->verifyJwt($jwt);

        // Check if token is in database and not revoked
        $tokenHash = hash('sha256', $jwt);
        $dbToken = LicenseToken::where('device_id', $device->id)
            ->where('token', $tokenHash)
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$dbToken) {
            throw new \Exception('Token not found in database or has been revoked');
        }

        // Validate device match
        if ($payload['device_id'] !== $device->id) {
            throw new \Exception('Token device mismatch');
        }

        // Validate merchant match
        if ($payload['merchant_id'] !== $device->merchant_id) {
            throw new \Exception('Token merchant mismatch');
        }

        // Check if merchant is still active
        if (!$device->merchant->isActive()) {
            throw new \Exception('Merchant account is no longer active');
        }

        // Check if device is still active
        if (!$device->isActive()) {
            throw new \Exception('Device is no longer active');
        }

        return $payload;
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(Subscription $subscription): \Carbon\Carbon
    {
        $now = now();
        $expiresAt = $subscription->isTrial() && $subscription->trial_end_at
            ? $subscription->trial_end_at
            : ($subscription->end_at ?? $now->addDay());

        // Ensure expiration is not more than 30 days from now
        $maxExpiration = $now->copy()->addDays(30);
        if ($expiresAt->gt($maxExpiration)) {
            $expiresAt = $maxExpiration;
        }

        return $expiresAt;
    }

    /**
     * Revoke all tokens for a merchant (useful for subscription suspension)
     */
    public function revokeAllMerchantTokens(Merchant $merchant): int
    {
        return LicenseToken::where('merchant_id', $merchant->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Cleanup expired tokens
     */
    public function cleanupExpiredTokens(): int
    {
        return LicenseToken::where('expires_at', '<', now())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function getExistingLicense($merchant, $device, $subscription)
    {
        return $subscription->licenseTokens()
            ->where('device_id', $device->id)
            ->whereNull('revoked_at')
            // ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
}
