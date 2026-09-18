<?php

namespace App\Services;

use App\Models\Signal;
use App\Models\SignalDelivery;
use App\Models\SignalDistribution;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SignalDistributionService
{
    public function __construct(
        private readonly MembershipAccessService $membershipAccess
    ) {}

    public function distributeMembership(Signal $signal, User $actor): SignalDistribution
    {
        $recipients = User::query()
            ->where('is_admin', false)
            ->with(['memberships.plan.type', 'memberships.plan.entitlements'])
            ->orderBy('id')
            ->get()
            ->filter(fn (User $user) => $user->isAccountActive())
            ->values();

        return $this->distribute(
            $signal,
            $actor,
            $recipients,
            'membership',
            true,
            'general',
            'Membership distribution using signals.access eligibility.'
        );
    }

    public function distributeComplimentary(
        Signal $signal,
        User $actor,
        ?array $userIds = null,
        ?string $reason = null
    ): SignalDistribution {
        $query = User::query()->where('is_admin', false)->orderBy('id');

        if ($userIds !== null) {
            $ids = collect($userIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
            $query->whereIn('id', $ids);
        }

        $recipients = $query->get()
            ->filter(fn (User $user) => $user->isAccountActive())
            ->values();

        return $this->distribute(
            $signal,
            $actor,
            $recipients,
            'complimentary',
            false,
            $userIds !== null ? 'individual' : 'general',
            filled($reason) ? trim((string) $reason) : 'Complimentary Signal distribution by administrator.'
        );
    }

    private function distribute(
        Signal $signal,
        User $actor,
        Collection $recipients,
        string $mode,
        bool $membershipRulesApplied,
        string $audienceScope,
        string $reason
    ): SignalDistribution {
        $signal->loadMissing(['stock', 'marketInstrument.stock', 'marketInstrument.forexPair', 'targets']);

        if (! in_array($signal->status, ['published', 'active'], true)) {
            throw new RuntimeException('Only published or active Signals may be distributed.');
        }

        return DB::transaction(function () use (
            $signal,
            $actor,
            $recipients,
            $mode,
            $membershipRulesApplied,
            $audienceScope,
            $reason
        ) {
            $distribution = SignalDistribution::create([
                'signal_id' => $signal->id,
                'initiated_by_user_id' => $actor->id,
                'mode' => $mode,
                'membership_rules_applied' => $membershipRulesApplied,
                'reason' => $reason,
                'audience_snapshot' => [
                    'requested_recipients' => $recipients->count(),
                    'audience_scope' => $audienceScope,
                    'access_key' => $membershipRulesApplied ? 'signals.access' : null,
                    'policy_scope' => $membershipRulesApplied
                        ? 'S4 access gate only; quotas and allowed instruments are enforced in S6.'
                        : 'Administrator complimentary override; normal allowance is not consumed.',
                ],
                'started_at' => now(),
            ]);

            $delivered = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($recipients as $user) {
                if ($membershipRulesApplied && ! $this->hasSignalAccess($user)) {
                    $skipped++;
                    continue;
                }

                if (SignalDelivery::query()
                    ->where('signal_id', $signal->id)
                    ->where('user_id', $user->id)
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                try {
                    DB::transaction(function () use ($distribution, $signal, $user, $mode, $audienceScope, $reason) {
                        SignalDelivery::create([
                            'distribution_id' => $distribution->id,
                            'signal_id' => $signal->id,
                            'user_id' => $user->id,
                            'reason' => $mode,
                            'delivered_at' => now(),
                            'metadata' => [
                                'symbol' => $signal->instrument_symbol,
                                'asset_class' => $signal->asset_class,
                                'direction' => $signal->direction,
                                'timeframe' => $signal->timeframe,
                                'signal_status' => $signal->status,
                                'distribution_reason' => $reason,
                                'audience_scope' => $audienceScope,
                                'normal_allowance_consumed' => $mode === 'membership',
                            ],
                        ]);

                        NotificationService::createSignalNotification($user, $signal, $mode, [
                            'distribution_id' => $distribution->id,
                            'distribution_reason' => $reason,
                        ]);
                    }, 3);

                    $delivered++;
                } catch (\Throwable) {
                    $failed++;
                }
            }

            $distribution->update([
                'completed_at' => now(),
                'delivered_count' => $delivered,
                'skipped_count' => $skipped,
                'failed_count' => $failed,
                'audience_snapshot' => array_merge((array) $distribution->audience_snapshot, [
                    'delivered' => $delivered,
                    'skipped' => $skipped,
                    'failed' => $failed,
                ]),
            ]);

            $distribution = $distribution->fresh(['deliveries']);

            NotificationService::createSignalAdminDistributionReceipt(
                $actor,
                $signal,
                $distribution,
                $audienceScope
            );

            return $distribution;
        }, 3);
    }

    private function hasSignalAccess(User $user): bool
    {
        return $this->membershipAccess
            ->activeMemberships($user)
            ->contains(function ($membership) {
                return $membership->plan?->entitlements
                    ?->contains(fn ($entitlement) => $entitlement->enabled && $entitlement->key === 'signals.access')
                    ?? false;
            });
    }
}
