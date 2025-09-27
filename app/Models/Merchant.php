<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Merchant extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'user_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'whatsapp',
        'address',
        'status',
        'trial_used',
    ];

    protected function casts(): array
    {
        return [
            'trial_used' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function licenseTokens(): HasMany
    {
        return $this->hasMany(LicenseToken::class);
    }

    public function currentSubscription(): HasMany
    {
        return $this->subscriptions()->whereIn('status', [
            'active',
            'pending',
        ]);
    }

    public function activeDevices(): HasMany
    {
        return $this->devices()->where('is_active', true);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function hasUsedTrial(): bool
    {
        return $this->trial_used;
    }
}
