<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CleanupV1Database extends Command
{
    protected $signature = 'v1:database-cleanup
        {--apply : Apply safe V1 cleanup and quarantine operations}';

    protected $description =
        'Audit or apply V1 database hygiene without deleting financial ledger history.';

    public function handle(): int
    {
        $qaUsers = collect();
        $qaUserIds = collect();

        if (Schema::hasTable('users')) {
            $qaUsers = DB::table('users')
                ->when(
                    Schema::hasColumn('users', 'is_production_demo'),
                    fn ($query) => $query->where('is_production_demo', false)
                )
                ->where('email', 'like', 'qa.%@rcentz.test')
                ->get(['id', 'email']);

            $qaUserIds = $qaUsers->pluck('id');
        }

        $staleSessionCutoff = now()->subDays(14)->timestamp;
        $staleSessions = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('last_activity', '<', $staleSessionCutoff)->count()
            : 0;

        $oldResetTokens = 0;
        if (
            Schema::hasTable('password_reset_tokens')
            && Schema::hasColumn('password_reset_tokens', 'created_at')
        ) {
            $oldResetTokens = DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subDay())
                ->count();
        }

        $acceptance = [
            'market_instruments' => $this->countLike('market_instruments', 'name', '%ACCEPTANCE%'),
            'stocks' => $this->countLike('stocks', 'company_name', '%ACCEPTANCE%'),
            'forex_pairs' => $this->countLike('forex_pairs', 'name', '%ACCEPTANCE%'),
            'crypto_pairs' => $this->countLike('crypto_pairs', 'name', '%ACCEPTANCE%'),
            'bot_products' => $this->countLike('bot_products', 'name', '%ACCEPTANCE%'),
            'private_investment_instruments' => $this->privateInvestmentQaCount(),
            'private_market_references' => $this->privateReferenceQaCount(),
        ];

        $this->table(
            ['V1 database hygiene candidate', 'Rows'],
            [
                ['QA users (qa.*@rcentz.test)', $qaUsers->count()],
                ['Sessions older than 14 days', $staleSessions],
                ['Password reset tokens older than 24h', $oldResetTokens],
                ...collect($acceptance)
                    ->map(fn ($count, $table) => ['Acceptance/QA '.$table, $count])
                    ->values()
                    ->all(),
            ]
        );

        if (! $this->option('apply')) {
            $this->warn(
                'Dry run only. Re-run with --apply to quarantine QA fixtures '
                .'and prune stale authentication state. Financial ledger rows are never deleted.'
            );
            return self::SUCCESS;
        }

        DB::transaction(function () use (
            $qaUsers,
            $qaUserIds,
            $staleSessionCutoff
        ): void {
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')
                    ->where('last_activity', '<', $staleSessionCutoff)
                    ->delete();

                if ($qaUserIds->isNotEmpty()) {
                    DB::table('sessions')
                        ->whereIn('user_id', $qaUserIds)
                        ->delete();
                }
            }

            if (
                Schema::hasTable('password_reset_tokens')
                && Schema::hasColumn('password_reset_tokens', 'created_at')
            ) {
                DB::table('password_reset_tokens')
                    ->where('created_at', '<', now()->subDay())
                    ->delete();

                if ($qaUsers->isNotEmpty()) {
                    DB::table('password_reset_tokens')
                        ->whereIn('email', $qaUsers->pluck('email'))
                        ->delete();
                }
            }

            // Preserve QA financial history for auditability; block the identities
            // instead of deleting users or ledger rows out from underneath FKs.
            if (
                $qaUserIds->isNotEmpty()
                && Schema::hasColumn('users', 'account_status')
            ) {
                DB::table('users')
                    ->whereIn('id', $qaUserIds)
                    ->update([
                        'account_status' => 'blocked',
                        'status_reason' =>
                            'V1 release cleanup: QA fixture quarantined from customer access.',
                        'status_until' => null,
                        'remember_token' => null,
                        'updated_at' => now(),
                    ]);
            }

            $this->disableAcceptanceRows();
        });

        $this->info(
            'V1 database cleanup applied. QA/acceptance fixtures were quarantined, '
            .'stale auth state was pruned, and financial audit history was preserved.'
        );

        return self::SUCCESS;
    }

    private function countLike(
        string $table,
        string $column,
        string $pattern
    ): int {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return (int) DB::table($table)
            ->where($column, 'like', $pattern)
            ->count();
    }

    private function privateInvestmentQaCount(): int
    {
        if (! Schema::hasTable('private_investment_instruments')) {
            return 0;
        }

        return (int) DB::table('private_investment_instruments')
            ->where(function ($query) {
                $query->where('symbol', 'like', 'QA%')
                    ->orWhere('name', 'like', '%ACCEPTANCE%');
            })
            ->count();
    }

    private function privateReferenceQaCount(): int
    {
        if (! Schema::hasTable('private_market_references')) {
            return 0;
        }

        return (int) DB::table('private_market_references')
            ->where(function ($query) {
                $query->where('symbol', 'like', 'QA%')
                    ->orWhere('name', 'like', '%ACCEPTANCE%');
            })
            ->count();
    }

    private function disableAcceptanceRows(): void
    {
        $this->setInactive(
            'market_instruments',
            'name',
            '%ACCEPTANCE%',
            ['is_active' => false]
        );

        $this->setInactive(
            'stocks',
            'company_name',
            '%ACCEPTANCE%',
            ['is_active' => false]
        );

        $this->setInactive(
            'forex_pairs',
            'name',
            '%ACCEPTANCE%',
            ['is_active' => false]
        );

        $this->setInactive(
            'crypto_pairs',
            'name',
            '%ACCEPTANCE%',
            ['is_active' => false]
        );

        $this->setInactive(
            'bot_products',
            'name',
            '%ACCEPTANCE%',
            ['is_active' => false]
        );

        if (Schema::hasTable('private_investment_instruments')) {
            DB::table('private_investment_instruments')
                ->where(function ($query) {
                    $query->where('symbol', 'like', 'QA%')
                        ->orWhere('name', 'like', '%ACCEPTANCE%');
                })
                ->update([
                    'status' => 'paused',
                    'is_visible' => false,
                    'available_units' => 0,
                    'updated_at' => now(),
                ]);
        }

        if (Schema::hasTable('private_market_references')) {
            DB::table('private_market_references')
                ->where(function ($query) {
                    $query->where('symbol', 'like', 'QA%')
                        ->orWhere('name', 'like', '%ACCEPTANCE%');
                })
                ->update([
                    'status' => 'paused',
                    'updated_at' => now(),
                ]);
        }

        if (
            Schema::hasTable('public_investment_base_assets')
            && Schema::hasTable('market_instruments')
        ) {
            $acceptanceMarketIds = DB::table('market_instruments')
                ->where('name', 'like', '%ACCEPTANCE%')
                ->pluck('id');

            if ($acceptanceMarketIds->isNotEmpty()) {
                DB::table('public_investment_base_assets')
                    ->whereIn('market_instrument_id', $acceptanceMarketIds)
                    ->update([
                        'status' => 'paused',
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    private function setInactive(
        string $table,
        string $column,
        string $pattern,
        array $values
    ): void {
        if (
            ! Schema::hasTable($table)
            || ! Schema::hasColumn($table, $column)
        ) {
            return;
        }

        foreach (array_keys($values) as $key) {
            if (! Schema::hasColumn($table, $key)) {
                return;
            }
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $values['updated_at'] = now();
        }

        DB::table($table)
            ->where($column, 'like', $pattern)
            ->update($values);
    }
}
