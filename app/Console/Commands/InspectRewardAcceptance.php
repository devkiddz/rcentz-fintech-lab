<?php

namespace App\Console\Commands;

use App\Models\RewardAuditLog;
use App\Models\RewardCampaign;
use App\Models\RewardGrant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class InspectRewardAcceptance extends Command
{
    protected $signature = 'reward:inspect-ms6-acceptance';
    protected $description = 'Inspect MS6 reward, referral and giveaway authority invariants and persistent QA cases.';

    public function handle(): int
    {
        $emails = [
            'qa.reward.cash@rcentz.test',
            'qa.reward.referral@rcentz.test',
            'qa.reward.giveaway@rcentz.test',
        ];

        $users = User::query()->whereIn('email', $emails)->get()->keyBy('email');
        $campaignSlugs = [
            'qa-ms6-welcome-cash-bonus',
            'qa-ms6-referral-completion-bonus',
            'qa-ms6-research-access-giveaway',
        ];
        $campaigns = RewardCampaign::query()->whereIn('slug', $campaignSlugs)->get()->keyBy('slug');

        $rows = [];
        foreach ([
            ['WELCOME CASH', 'qa.reward.cash@rcentz.test', 'qa-ms6-welcome-cash-bonus'],
            ['REFERRAL CASH', 'qa.reward.referral@rcentz.test', 'qa-ms6-referral-completion-bonus'],
            ['GIVEAWAY', 'qa.reward.giveaway@rcentz.test', 'qa-ms6-research-access-giveaway'],
        ] as [$case, $email, $slug]) {
            $user = $users->get($email);
            $campaign = $campaigns->get($slug);
            $grant = $user && $campaign
                ? RewardGrant::query()->where('user_id', $user->id)->where('reward_campaign_id', $campaign->id)->where('status', 'fulfilled')->latest('id')->first()
                : null;

            $rows[] = [
                $case,
                $email,
                $campaign?->campaign_type ?? 'MISSING',
                $campaign?->reward_kind ?? 'MISSING',
                $grant?->status ?? 'MISSING',
                $grant?->reward_kind === 'cash' ? '$'.number_format((float) $grant->amount, 2) : ($grant?->non_cash_payload['label'] ?? '—'),
                $user?->wallet ? number_format((float) $user->wallet->balance, 2) : '—',
            ];
        }

        $this->line('Persistent MS6 Rewards acceptance cases:');
        $this->table(['Case', 'Customer', 'Type', 'Kind', 'Grant', 'Value', 'Wallet'], $rows);

        $cashMissingWallet = RewardGrant::query()
            ->where('reward_kind', 'cash')
            ->where('status', 'fulfilled')
            ->whereNull('wallet_transaction_id')
            ->count();

        $cashLedgerMismatch = RewardGrant::query()
            ->where('reward_grants.reward_kind', 'cash')
            ->where('reward_grants.status', 'fulfilled')
            ->leftJoin('wallet_transactions as wt', 'wt.id', '=', 'reward_grants.wallet_transaction_id')
            ->where(function ($q) {
                $q->whereNull('wt.id')
                    ->orWhere('wt.type', '!=', 'reward')
                    ->orWhere('wt.direction', '!=', 'credit')
                    ->orWhere('wt.status', '!=', 'completed')
                    ->orWhereRaw('ABS(wt.amount - reward_grants.amount) > 0.01')
                    ->orWhereColumn('wt.reference_id', '!=', 'reward_grants.reference');
            })
            ->count();

        $nonCashWithWallet = RewardGrant::query()
            ->where('reward_kind', 'non_cash')
            ->whereNotNull('wallet_transaction_id')
            ->count();

        $missingAudit = RewardGrant::query()
            ->where('status', 'fulfilled')
            ->whereDoesntHave('campaign')
            ->count();

        $auditMissingByReference = RewardGrant::query()
            ->where('status', 'fulfilled')
            ->get()
            ->filter(fn ($grant) => ! RewardAuditLog::query()
                ->where('reward_grant_id', $grant->id)
                ->where('action', 'grant.fulfilled')
                ->where('reference', $grant->reference)
                ->exists())
            ->count();

        $duplicateKeys = DB::table('reward_grants')
            ->select('user_id', 'idempotency_key', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('user_id', 'idempotency_key')
            ->having('aggregate', '>', 1)
            ->get()
            ->count();

        $visibleQa = RewardCampaign::query()->whereIn('slug', $campaignSlugs)->where('is_visible', true)->count();

        $caseFailures = 0;
        foreach ($rows as $row) {
            if ($row[4] !== 'fulfilled') $caseFailures++;
        }

        $checks = [
            'Cash grants missing reward wallet credit' => $cashMissingWallet,
            'Cash grant / wallet ledger mismatch' => $cashLedgerMismatch,
            'Non-cash grants mutating wallet ledger' => $nonCashWithWallet,
            'Fulfilled grants missing campaign authority' => $missingAudit,
            'Fulfilled grants missing audit receipt' => $auditMissingByReference,
            'Duplicate reward user/idempotency keys' => $duplicateKeys,
            'QA campaigns visible in public catalog' => $visibleQa,
            'Persistent QA case failures' => $caseFailures,
        ];

        $failed = 0;
        $checkRows = [];
        foreach ($checks as $label => $count) {
            $count = (int) $count;
            if ($count > 0) $failed++;
            $checkRows[] = [$label, $count];
        }

        $this->newLine();
        $this->table(['MS6 Rewards authority check', 'Count'], $checkRows);

        if ($failed > 0) {
            $this->error('MS6_REWARDS_ACCEPTANCE_FAILED');
            return self::FAILURE;
        }

        $this->info('MS6 Rewards / Bonuses / Giveaways is READY with cash wallet settlement, non-cash fulfillment, audit authority and replay-safe grants.');
        return self::SUCCESS;
    }
}
