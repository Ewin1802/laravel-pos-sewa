<?php

namespace App\Policies;

use App\Models\LicenseToken;
use App\Models\User;

class LicenseTokenPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LicenseToken $licenseToken): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LicenseToken $licenseToken): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LicenseToken $licenseToken): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can revoke license tokens.
     */
    public function revoke(User $user, LicenseToken $licenseToken): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can reissue license tokens.
     */
    public function reissue(User $user, LicenseToken $licenseToken): bool
    {
        return $user->hasRole('admin');
    }
}
