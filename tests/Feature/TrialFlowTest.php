<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Merchant;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\LicenseService;
use App\Services\TrialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrialFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Merchant $merchant;
    protected Device $device;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles for testing
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);

        // Create test plan
        $this->plan = Plan::create([
            'name' => 'Test Plan',
            'code' => 'TEST',
            'description' => 'Test plan for feature testing',
            'price' => 100000, // IDR 100,000
            'currency' => 'IDR',
            'duration_days' => 30,
            'trial_days' => 7,
            'is_active' => true,
            'features' => ['test_feature'],
        ]);

        // Create test user and merchant
        $this->user = User::factory()->create();
        $this->user->assignRole('merchant');

        $this->merchant = Merchant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Business',
            'contact_name' => 'John Doe',
            'email' => 'test@business.com',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_used' => false,
        ]);

        // Create test device
        $this->device = Device::create([
            'merchant_id' => $this->merchant->id,
            'device_uid' => 'TEST_DEVICE_001',
            'label' => 'Test POS Device',
            'last_seen_at' => now(),
            'is_active' => true,
        ]);
    }

    public function test_can_complete_trial_start_flow_with_license_issuance(): void
    {
        // Arrange
        $trialService = app(TrialService::class);
        $licenseService = app(LicenseService::class);

        // Act: Start trial
        $subscription = $trialService->startTrial(
            $this->merchant,
            $this->device,
            $this->plan
        );

        // Assert: Subscription created correctly
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertEquals($this->merchant->id, $subscription->merchant_id);
        $this->assertEquals($this->plan->id, $subscription->plan_id);
        $this->assertTrue($subscription->is_trial);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->trial_started_at);
        $this->assertNotNull($subscription->trial_end_at);

        // Assert: Merchant trial flag updated
        $this->merchant->refresh();
        $this->assertTrue($this->merchant->trial_used);

        // Assert: License token issued
        $licenseToken = $this->merchant->licenseTokens()->first();
        $this->assertNotNull($licenseToken);
        $this->assertEquals($this->device->id, $licenseToken->device_id);
        $this->assertNull($licenseToken->revoked_at);

        // Assert: Trial status returns correct information
        $trialStats = $trialService->getTrialStats($this->merchant);
        $this->assertTrue($trialStats['has_trial']);
        $this->assertTrue($trialStats['trial_used']);
        $this->assertFalse($trialStats['eligible_for_trial']);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $trialStats['status']);
        $this->assertFalse($trialStats['is_expired']);
        $this->assertGreaterThan(0, $trialStats['days_remaining']);
    }

    public function test_validates_trial_eligibility_correctly(): void
    {
        // Test: Cannot start trial if already used
        $this->merchant->update(['trial_used' => true]);

        $trialService = app(TrialService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Trial has already been used');
        $trialService->startTrial($this->merchant, $this->device, $this->plan);
    }

    public function test_validates_device_ownership_for_trial(): void
    {
        // Create device for different merchant
        $otherMerchant = Merchant::factory()->create();
        $otherDevice = Device::factory()->create(['merchant_id' => $otherMerchant->id]);

        $trialService = app(TrialService::class);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Device does not belong to this merchant');
        $trialService->startTrial($this->merchant, $otherDevice, $this->plan);
    }

    public function test_handles_trial_expiration_correctly(): void
    {
        // Arrange: Start trial
        $trialService = app(TrialService::class);
        $subscription = $trialService->startTrial($this->merchant, $this->device, $this->plan);

        // Act: Simulate trial expiration
        $subscription->update([
            'trial_end_at' => now()->subDay(),
        ]);

        // Assert: Trial is expired
        $this->assertTrue($subscription->isTrialExpired());
        $this->assertEquals(0, $trialService->getTrialDaysRemaining($subscription));
        $this->assertTrue($trialService->isTrialExpiringSoon($subscription)); // Already expired

        // Act: Expire trial
        $trialService->expireTrial($subscription);

        // Assert: Subscription status updated
        $subscription->refresh();
        $this->assertEquals(Subscription::STATUS_EXPIRED, $subscription->status);

        // Assert: License tokens revoked
        $licenseToken = $this->merchant->licenseTokens()->first();
        $this->assertNotNull($licenseToken->revoked_at);
    }

    public function test_can_convert_trial_to_paid_subscription(): void
    {
        // Arrange: Start trial
        $trialService = app(TrialService::class);
        $trialSubscription = $trialService->startTrial($this->merchant, $this->device, $this->plan);

        // Act: Convert to paid
        $paidSubscription = $trialService->convertTrialToPaid($trialSubscription, $this->plan);

        // Assert: Subscription converted correctly
        $this->assertFalse($paidSubscription->is_trial);
        $this->assertEquals(Subscription::STATUS_PENDING, $paidSubscription->status);
        $this->assertEquals($this->plan->id, $paidSubscription->plan_id);
        $this->assertNull($paidSubscription->trial_end_at);
        $this->assertNotNull($paidSubscription->end_at);
    }
}
