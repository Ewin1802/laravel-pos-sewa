<?php

namespace App\Policies;

use App\Models\PaymentConfirmation;
use App\Models\User;

class PaymentConfirmationPolicy
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
    public function view(User $user, PaymentConfirmation $paymentConfirmation): bool
    {
        return $user->hasRole('admin') || $user->hasRole('operator');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Anyone can create payment confirmations (merchants upload proof)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PaymentConfirmation $paymentConfirmation): bool
    {
        // Only admins can update payment confirmations
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PaymentConfirmation $paymentConfirmation): bool
    {
        // Only admins can delete payment confirmations
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can approve payment confirmations.
     */
    public function approve(User $user, PaymentConfirmation $paymentConfirmation): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can reject payment confirmations.
     */
    public function reject(User $user, PaymentConfirmation $paymentConfirmation): bool
    {
        return $user->hasRole('admin');
    }
}
