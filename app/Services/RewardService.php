<?php

namespace App\Services;

use App\Models\RewardAuditLog;
use App\Models\RewardCampaign;
use App\Models\RewardGrant;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Support\ProductionDemoGuard;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class RewardService
{
    public function __construct(
        private FinancialActivityService $activity,
        private ProductionDemoGuard $productionDemo
    ) {}

    public function grant(
        User $user,
        RewardCampaign $campaign,
        string $sourceType,
        string $sourceReference,
        ?User $actor = null,
        array $metadata = []
    ): RewardGrant {
        $this->productionDemo->assertMutationAllowed(
            $user,
            'reward fulfillment'
        );

        $sourceType = trim($sourceType);
        $sourceReference = trim($sourceReference);

        if ($sourceType === '' || strlen($sourceType) > 60) {
            throw new RuntimeException('A valid reward source type is required.');
        }
        if ($sourceReference === '' || strlen($sourceReference) > 120) {
            throw new RuntimeException('A valid reward source reference is required.');
        }

        return DB::transaction(function () use ($user, $campaign, $sourceType, $sourceReference, $actor, $metadata) {
            $campaign = RewardCampaign::query()->lockForUpdate()->findOrFail($campaign->id);
            $idempotencyKey = $this->idempotencyKey($campaign, $sourceType, $sourceReference);

            $existing = RewardGrant::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if ((int) $existing->reward_campaign_id !== (int) $campaign->id
                    || $existing->source_type !== $sourceType
                    || $existing->source_reference !== $sourceReference) {
                    throw new RuntimeException('Reward idempotency key conflict detected.');
                }

                return $existing->fresh(['campaign', 'walletTransaction', 'financialActivity']);
            }

            if (! $campaign->is_open) {
                throw new RuntimeException('This reward campaign is not currently open.');
            }

            if ($campaign->eligibility_key) {
                $eligibility = (array) ($metadata['eligibility'] ?? []);
                $eligible = (bool) ($eligibility[$campaign->eligibility_key] ?? false);
                if (! $eligible) {
                    throw new RuntimeException('Reward eligibility requirement was not satisfied.');
                }
            }

            if ($campaign->max_grants !== null) {
                $granted = RewardGrant::query()
                    ->where('reward_campaign_id', $campaign->id)
                    ->where('status', 'fulfilled')
                    ->count();

                if ($granted >= (int) $campaign->max_grants) {
                    throw new RuntimeException('This reward campaign has reached its grant limit.');
                }
            }

            $perUserLimit = max(1, (int) $campaign->per_user_limit);
            $userGrants = RewardGrant::query()
                ->where('reward_campaign_id', $campaign->id)
                ->where('user_id', $user->id)
                ->where('status', 'fulfilled')
                ->count();

            if ($userGrants >= $perUserLimit) {
                throw new RuntimeException('This customer has already reached the reward limit for this campaign.');
            }

            $kind = $campaign->reward_kind;
            if (! in_array($kind, ['cash', 'non_cash'], true)) {
                throw new RuntimeException('Unsupported reward fulfillment kind.');
            }

            $reference = $this->reference($campaign);
            $walletTransaction = null;
            $financialActivity = null;
            $amount = 0.0;
            $nonCashPayload = null;

            if ($kind === 'cash') {
                $amount = round((float) $campaign->cash_amount, 2);
                if ($amount <= 0) {
                    throw new RuntimeException('Cash reward amount must be greater than zero.');
                }

                $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first();
                if (! $wallet) {
                    throw new RuntimeException('This customer does not have a wallet for reward settlement.');
                }

                $before = $this->activity->snapshot($wallet);

                $walletTransaction = WalletTransaction::query()->create([
                    'wallet_id' => $wallet->id,
                    'payment_method_id' => null,
                    'type' => 'reward',
                    'direction' => 'credit',
                    'amount' => $amount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => $reference,
                    'description' => 'Reward credit: '.$campaign->name,
                ]);

                $wallet->update(['balance' => round((float) $wallet->balance + $amount, 2)]);

                $financialActivity = $this->activity->record(
                    $user,
                    'reward.grant',
                    'Reward credited',
                    $campaign->name,
                    $reference,
                    'completed',
                    'credit',
                    $amount,
                    $wallet,
                    $walletTransaction,
                    null,
                    $before,
                    [
                        'reward_campaign_id' => $campaign->id,
                        'source_type' => $sourceType,
                        'source_reference' => $sourceReference,
                    ],
                    $actor ? 'admin' : 'system',
                    $actor?->id
                );
            } else {
                $label = trim((string) $campaign->non_cash_label);
                if ($label === '') {
                    throw new RuntimeException('Non-cash reward label is required.');
                }

                $nonCashPayload = [
                    'label' => $label,
                    'campaign_type' => $campaign->campaign_type,
                    'details' => $campaign->metadata['non_cash_details'] ?? null,
                ];
            }

            $grant = RewardGrant::query()->create([
                'reward_campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'wallet_transaction_id' => $walletTransaction?->id,
                'financial_activity_id' => $financialActivity?->id,
                'granted_by_user_id' => $actor?->id,
                'source_type' => $sourceType,
                'source_reference' => $sourceReference,
                'reward_kind' => $kind,
                'amount' => $amount,
                'currency' => strtoupper((string) ($campaign->currency ?: 'USD')),
                'non_cash_payload' => $nonCashPayload,
                'status' => 'fulfilled',
                'reference' => $reference,
                'idempotency_key' => $idempotencyKey,
                'metadata' => array_merge($metadata, ['campaign_type' => $campaign->campaign_type]),
                'granted_at' => now(),
            ]);

            RewardAuditLog::query()->create([
                'reward_campaign_id' => $campaign->id,
                'reward_grant_id' => $grant->id,
                'actor_user_id' => $actor?->id,
                'target_user_id' => $user->id,
                'action' => 'grant.fulfilled',
                'reference' => $reference,
                'reason' => 'Reward campaign grant fulfilled.',
                'metadata' => [
                    'source_type' => $sourceType,
                    'source_reference' => $sourceReference,
                    'reward_kind' => $kind,
                    'amount' => $amount,
                ],
                'occurred_at' => now(),
            ]);

            NotificationService::createSystemNotification(
                $user,
                'Reward received',
                $kind === 'cash'
                    ? 'You received a '.strtoupper((string) $campaign->currency).' '.number_format($amount, 2).' reward from '.$campaign->name.'.'
                    : 'You received '.$campaign->non_cash_label.' from '.$campaign->name.'.',
                ['reward_grant_id' => $grant->id, 'reward_campaign_id' => $campaign->id]
            );

            return $grant->fresh(['campaign', 'walletTransaction', 'financialActivity']);
        });
    }

    private function idempotencyKey(RewardCampaign $campaign, string $sourceType, string $sourceReference): string
    {
        return 'reward:'.$campaign->id.':'.substr(hash('sha256', strtolower($sourceType).'|'.$sourceReference), 0, 48);
    }

    private function reference(RewardCampaign $campaign): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/', '', $campaign->campaign_type ?: 'RWD')) ?: 'RWD';
        $prefix = substr($prefix, 0, 10);

        do {
            $reference = 'RWD-'.$prefix.'-'.strtoupper(Str::random(12));
        } while (RewardGrant::query()->where('reference', $reference)->exists());

        return $reference;
    }
}
