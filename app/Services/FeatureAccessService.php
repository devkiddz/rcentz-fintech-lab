<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class FeatureAccessService
{
    public const SIGNALS = 'signals.access';
    public const BOT_TRADER = 'bot_trader.access';
    public const COPY_TRADER = 'copy_trader.access';
    public const INVESTMENTS = 'investments.access';

    public function __construct(
        private readonly MembershipAccessService $memberships
    ) {}

    public function allows(User $user, string $key): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->memberships->hasAny($user, $key);
    }

    /**
     * @throws AuthorizationException
     */
    public function require(User $user, string $key, ?string $message = null): void
    {
        if ($this->allows($user, $key)) {
            return;
        }

        throw new AuthorizationException($message ?: $this->message($key));
    }

    private function message(string $key): string
    {
        return match ($key) {
            self::SIGNALS => 'An active membership with Signals access is required.',
            self::BOT_TRADER => 'An active membership with Bot Trader access is required.',
            self::COPY_TRADER => 'An active membership with Copy Trading access is required.',
            self::INVESTMENTS => 'An active membership with Private Investments access is required.',
            default => 'An active membership with this feature entitlement is required.',
        };
    }
}
