<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'device_uid',
        'label',
        'last_seen_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function licenseTokens(): HasMany
    {
        return $this->hasMany(LicenseToken::class);
    }

    public function currentLicenseToken(): HasMany
    {
        return $this->licenseTokens()
            ->whereNull('revoked_at')
            ->where('expires_at', '>', now());
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
