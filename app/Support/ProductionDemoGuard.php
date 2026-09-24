<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Validation\ValidationException;

final class ProductionDemoGuard
{
    public function isReadOnly(User $user): bool
    {
        return $user->isProductionDemo()
            && (bool) config('release.production_demo.read_only', true);
    }

    public function assertMutationAllowed(
        User $user,
        string $operation = 'financial mutation'
    ): void {
        if (! $this->isReadOnly($user)) {
            return;
        }

        throw ValidationException::withMessages([
            'account' => 'This protected production demo account is read-only. '
                .'The requested '.$operation.' was not performed.',
        ]);
    }
}
