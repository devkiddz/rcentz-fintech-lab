<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Create wallet for new users (excluding admin)
        if (!$user->isAdmin()) {
            $user->wallet()->create([
                'balance' => 0.00,
                'currency' => 'USD',
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        // If user was changed from admin to regular user, create wallet
        if ($user->wasChanged('is_admin') && !$user->isAdmin() && !$user->wallet) {
            $user->wallet()->create([
                'balance' => 0.00,
                'currency' => 'USD',
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        // Wallet will be automatically deleted due to cascade
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}
