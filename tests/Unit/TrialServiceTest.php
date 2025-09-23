<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\LicenseService;
use App\Services\TrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TrialServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrialService $trialService;
    private LicenseService $mockLicenseService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockLicenseService = Mockery::mock(LicenseService::class);
        $this->trialService = new TrialService($this->mockLicenseService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_calculate_trial_days_returns_plan_trial_days(): void
    {
        $plan = Plan::factory()->make(['trial_days' => 14]);

        $result = $this->trialService->calculateTrialDays($plan, 7);

        $this->assertEquals(14, $result);
    }

    public function test_calculate_trial_days_returns_fallback_when_plan_has_no_trial(): void
    {
        $plan = Plan::factory()->make(['trial_days' => 0]);

        $result = $this->trialService->calculateTrialDays($plan, 7);

        $this->assertEquals(7, $result);
    }

    public function test_calculate_trial_days_returns_fallback_when_no_plan(): void
    {
        $result = $this->trialService->calculateTrialDays(null, 10);

        $this->assertEquals(10, $result);
    }

    public function test_has_active_subscription_returns_true_when_active_subscription_exists(): void
    {
        $merchant = Merchant::factory()->create();
        Subscription::factory()->create([
            'merchant_id' => $merchant->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $result = $this->trialService->hasActiveSubscription($merchant);

        $this->assertTrue($result);
    }

    public function test_has_active_subscription_returns_true_when_pending_subscription_exists(): void
    {
        $merchant = Merchant::factory()->create();
        Subscription::factory()->create([
            'merchant_id' => $merchant->id,
            'status' => Subscription::STATUS_PENDING,
        ]);

        $result = $this->trialService->hasActiveSubscription($merchant);

        $this->assertTrue($result);
    }

    public function test_has_active_subscription_returns_false_when_no_active_subscription(): void
    {
        $merchant = Merchant::factory()->create();
        Subscription::factory()->create([
            'merchant_id' => $merchant->id,
            'status' => Subscription::STATUS_EXPIRED,
        ]);

        $result = $this->trialService->hasActiveSubscription($merchant);

        $this->assertFalse($result);
    }

    public function test_validate_trial_eligibility_throws_exception_when_merchant_not_active(): void
    {
        $merchant = Merchant::factory()->create(['status' => Merchant::STATUS_SUSPENDED]);
        $device = Device::factory()->create(['merchant_id' => $merchant->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Merchant account is not active');

        $this->trialService->validateTrialEligibility($merchant, $device);
    }

    public function test_validate_trial_eligibility_throws_exception_when_trial_already_used(): void
    {
        $merchant = Merchant::factory()->create(['trial_used' => true]);
        $device = Device::factory()->create(['merchant_id' => $merchant->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Trial has already been used');

        $this->trialService->validateTrialEligibility($merchant, $device);
    }

    public function test_validate_trial_eligibility_throws_exception_when_device_belongs_to_different_merchant(): void
    {
        $merchant = Merchant::factory()->create();
        $otherMerchant = Merchant::factory()->create();
        $device = Device::factory()->create(['merchant_id' => $otherMerchant->id]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Device does not belong to this merchant');

        $this->trialService->validateTrialEligibility($merchant, $device);
    }

    public function test_validate_trial_eligibility_throws_exception_when_device_not_active(): void
    {
        $merchant = Merchant::factory()->create();
        $device = Device::factory()->create([
            'merchant_id' => $merchant->id,
            'is_active' => false,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Device is not active');

        $this->trialService->validateTrialEligibility($merchant, $device);
    }

    public function test_validate_trial_eligibility_throws_exception_when_merchant_has_active_subscription(): void
    {
        $merchant = Merchant::factory()->create();
        $device = Device::factory()->create(['merchant_id' => $merchant->id]);
        Subscription::factory()->create([
            'merchant_id' => $merchant->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Merchant already has an active subscription');

        $this->trialService->validateTrialEligibility($merchant, $device);
    }

    public function test_get_trial_days_remaining_returns_zero_for_non_trial_subscription(): void
    {
        $subscription = Subscription::factory()->make(['is_trial' => false]);

        $result = $this->trialService->getTrialDaysRemaining($subscription);

        $this->assertEquals(0, $result);
    }

    public function test_is_trial_expiring_soon_returns_false_for_non_trial_subscription(): void
    {
        $subscription = Subscription::factory()->make(['is_trial' => false]);

        $result = $this->trialService->isTrialExpiringSoon($subscription);

        $this->assertFalse($result);
    }

    public function test_find_expired_trials_returns_only_expired_active_trials(): void
    {
        // Create expired trial subscription
        $expiredTrial = Subscription::factory()->create([
            'is_trial' => true,
            'status' => Subscription::STATUS_ACTIVE,
            'trial_end_at' => now()->subDay(),
        ]);

        // Create non-expired trial
        Subscription::factory()->create([
            'is_trial' => true,
            'status' => Subscription::STATUS_ACTIVE,
            'trial_end_at' => now()->addDay(),
        ]);

        // Create expired but already marked as expired
        Subscription::factory()->create([
            'is_trial' => true,
            'status' => Subscription::STATUS_EXPIRED,
            'trial_end_at' => now()->subDay(),
        ]);

        $result = $this->trialService->findExpiredTrials();

        $this->assertCount(1, $result);
        $this->assertEquals($expiredTrial->id, $result->first()->id);
    }
}
