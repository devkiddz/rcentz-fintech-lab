<?php

namespace App\Console\Commands;

use App\Models\RewardCampaign;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\FinancialActivityService;
use App\Services\RewardService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SeedRealRewardCases extends Command
{
    protected $signature = 'reward:seed-ms6-real-cases';
    protected $description = 'Create persistent synthetic MS6 reward, referral and giveaway acceptance cases.';

    public function handle(RewardService $rewards, FinancialActivityService $activity): int
    {
        $password = (string) config('bootstrap.live_test.user_password');
        if (strlen($password) < 10) {
            $this->error('LIVE_TEST_USER_PASSWORD must be configured with at least 10 characters.');
            return self::FAILURE;
        }

        $cashUser = $this->user('qa.reward.cash@rcentz.test', 'Chidi Reward QA', $password);
        $referralUser = $this->user('qa.reward.referral@rcentz.test', 'Mariam Referral QA', $password);
        $giveawayUser = $this->user('qa.reward.giveaway@rcentz.test', 'Tobi Giveaway QA', $password);

        $this->fund($cashUser, 100.00, 'QA-MS6-FUND-CASH', $activity);
        $this->fund($referralUser, 250.00, 'QA-MS6-FUND-REFERRAL', $activity);
        $this->fund($giveawayUser, 500.00, 'QA-MS6-FUND-GIVEAWAY', $activity);

        $welcome = $this->campaign([
            'name' => 'QA Welcome Cash Bonus',
            'slug' => 'qa-ms6-welcome-cash-bonus',
            'campaign_type' => 'bonus',
            'description' => 'Synthetic MS6 cash reward acceptance campaign.',
            'reward_kind' => 'cash',
            'cash_amount' => 25.00,
            'currency' => 'USD',
            'non_cash_label' => null,
            'eligibility_key' => 'account.verified',
            'status' => 'active',
            'max_grants' => 100,
            'per_user_limit' => 1,
            'is_visible' => false,
            'metadata' => ['qa_fixture' => true],
        ]);

        $referral = $this->campaign([
            'name' => 'QA Referral Completion Bonus',
            'slug' => 'qa-ms6-referral-completion-bonus',
            'campaign_type' => 'referral',
            'description' => 'Synthetic MS6 referral reward acceptance campaign.',
            'reward_kind' => 'cash',
            'cash_amount' => 40.00,
            'currency' => 'USD',
            'non_cash_label' => null,
            'eligibility_key' => 'referral.completed',
            'status' => 'active',
            'max_grants' => 100,
            'per_user_limit' => 1,
            'is_visible' => false,
            'metadata' => ['qa_fixture' => true],
        ]);

        $giveaway = $this->campaign([
            'name' => 'QA Research Access Giveaway',
            'slug' => 'qa-ms6-research-access-giveaway',
            'campaign_type' => 'giveaway',
            'description' => 'Synthetic MS6 non-cash giveaway acceptance campaign.',
            'reward_kind' => 'non_cash',
            'cash_amount' => null,
            'currency' => 'USD',
            'non_cash_label' => 'Research Access Pass',
            'eligibility_key' => 'giveaway.winner',
            'status' => 'active',
            'max_grants' => 10,
            'per_user_limit' => 1,
            'is_visible' => false,
            'metadata' => ['qa_fixture' => true, 'non_cash_details' => 'Synthetic one-time research access award.'],
        ]);

        $cashGrant = $rewards->grant(
            $cashUser,
            $welcome,
            'account_verified',
            'QA-MS6-WELCOME-001',
            null,
            ['eligibility' => ['account.verified' => true], 'qa_case' => 'WELCOME CASH']
        );
        $cashReplay = $rewards->grant(
            $cashUser,
            $welcome,
            'account_verified',
            'QA-MS6-WELCOME-001',
            null,
            ['eligibility' => ['account.verified' => true], 'qa_case' => 'WELCOME CASH']
        );

        $limitRejected = false;
        try {
            $rewards->grant(
                $cashUser,
                $welcome,
                'account_verified',
                'QA-MS6-WELCOME-SECOND',
                null,
                ['eligibility' => ['account.verified' => true], 'qa_case' => 'WELCOME CASH LIMIT']
            );
        } catch (\Throwable) {
            $limitRejected = true;
        }

        $referralGrant = $rewards->grant(
            $referralUser,
            $referral,
            'referral',
            'QA-REFERRAL-001',
            null,
            ['eligibility' => ['referral.completed' => true], 'qa_case' => 'REFERRAL CASH']
        );
        $referralReplay = $rewards->grant(
            $referralUser,
            $referral,
            'referral',
            'QA-REFERRAL-001',
            null,
            ['eligibility' => ['referral.completed' => true], 'qa_case' => 'REFERRAL CASH']
        );

        $giveawayWalletBefore = (float) $giveawayUser->wallet()->firstOrFail()->balance;
        $giveawayGrant = $rewards->grant(
            $giveawayUser,
            $giveaway,
            'giveaway_draw',
            'QA-GIVEAWAY-DRAW-001',
            null,
            ['eligibility' => ['giveaway.winner' => true], 'qa_case' => 'NON CASH GIVEAWAY']
        );
        $giveawayReplay = $rewards->grant(
            $giveawayUser,
            $giveaway,
            'giveaway_draw',
            'QA-GIVEAWAY-DRAW-001',
            null,
            ['eligibility' => ['giveaway.winner' => true], 'qa_case' => 'NON CASH GIVEAWAY']
        );
        $giveawayWalletAfter = (float) $giveawayUser->wallet()->firstOrFail()->balance;

        $cashWallet = (float) $cashUser->wallet()->firstOrFail()->balance;
        $referralWallet = (float) $referralUser->wallet()->firstOrFail()->balance;

        if ((int) $cashGrant->id !== (int) $cashReplay->id || ! $limitRejected) {
            throw new RuntimeException('Welcome bonus replay/per-user-limit acceptance failed.');
        }
        if ((int) $referralGrant->id !== (int) $referralReplay->id) {
            throw new RuntimeException('Referral reward replay acceptance failed.');
        }
        if ((int) $giveawayGrant->id !== (int) $giveawayReplay->id || abs($giveawayWalletAfter - $giveawayWalletBefore) > 0.001) {
            throw new RuntimeException('Non-cash giveaway acceptance failed.');
        }

        $this->newLine();
        $this->info('Persistent real Rewards / Bonuses / Giveaways QA cases');
        $this->line('All @rcentz.test identities and QA reward campaigns are synthetic acceptance fixtures.');
        $this->table(
            ['Case', 'Customer', 'Campaign', 'Kind', 'Grant', 'Wallet', 'Replay', 'Limit/Wallet'],
            [
                ['WELCOME CASH', $cashUser->email, $welcome->name, 'cash', '$25.00', number_format($cashWallet, 2), 'PASS', $limitRejected ? 'PASS' : 'FAIL'],
                ['REFERRAL CASH', $referralUser->email, $referral->name, 'cash', '$40.00', number_format($referralWallet, 2), 'PASS', 'PASS'],
                ['GIVEAWAY', $giveawayUser->email, $giveaway->name, 'non_cash', 'Research Access Pass', number_format($giveawayWalletAfter, 2), 'PASS', abs($giveawayWalletAfter - $giveawayWalletBefore) < 0.001 ? 'PASS' : 'FAIL'],
            ]
        );

        $this->line('Shared QA password: '.$password);
        $this->line('  '.$cashUser->email.' → welcome cash bonus');
        $this->line('  '.$referralUser->email.' → referral cash bonus');
        $this->line('  '.$giveawayUser->email.' → non-cash giveaway prize');

        return self::SUCCESS;
    }

    private function user(string $email, string $name, string $password): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'is_admin' => false,
                'country' => 'Nigeria',
                'currency' => 'USD',
                'date_of_birth' => '1990-01-01',
                'account_status' => 'active',
            ]
        );
    }

    private function fund(User $user, float $amount, string $reference, FinancialActivityService $activity): void
    {
        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'reserved_balance' => 0, 'currency' => 'USD']
        );

        if (WalletTransaction::query()->where('wallet_id', $wallet->id)->where('reference_id', $reference)->exists()) {
            return;
        }

        $before = $activity->snapshot($wallet);
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'payment_method_id' => null,
            'type' => 'deposit',
            'direction' => 'credit',
            'amount' => $amount,
            'fee' => 0,
            'status' => 'completed',
            'reference_id' => $reference,
            'description' => 'Synthetic QA funding for MS6 reward acceptance.',
        ]);
        $wallet->update(['balance' => round((float) $wallet->balance + $amount, 2)]);
        $activity->record(
            $user,
            'qa.seed',
            'MS6 QA funding',
            'Synthetic acceptance fixture funding.',
            $reference,
            'completed',
            'credit',
            $amount,
            $wallet,
            $transaction,
            null,
            $before,
            ['qa_fixture' => true, 'milestone' => 'MS6'],
            'system',
            null
        );
    }

    private function campaign(array $data): RewardCampaign
    {
        return RewardCampaign::query()->updateOrCreate(['slug' => $data['slug']], $data);
    }
}
