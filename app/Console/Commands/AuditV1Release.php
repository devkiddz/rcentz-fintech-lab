<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentInstrument;
use App\Services\PrivateInvestmentReserveMarketService;
use App\Services\PrivateInvestmentReserveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class AuditV1Release extends Command
{
    protected $signature =
        'v1:release-audit {--production : Enforce production deployment requirements}';

    protected $description =
        'Audit V1 release security, protected demos, database hygiene and investment Base Asset authority.';

    private int $failures = 0;
    private int $warnings = 0;
    private array $rows = [];

    public function handle(
        PrivateInvestmentReserveService $reserves,
        PrivateInvestmentReserveMarketService $markets
    ): int {
        $strict = (bool) $this->option('production');

        $this->auditSchema();
        $this->auditProtectedDemos($strict);
        $this->auditQaContamination($strict);
        $this->auditBaseAssets($reserves, $markets);
        $this->auditWalletIntegrity();
        $this->auditRouteProtection();
        $this->auditSecurityConfiguration($strict);

        $this->table(
            ['Area', 'Status', 'Detail'],
            $this->rows
        );

        $this->newLine();
        $this->line(
            'Release audit summary: '
            .$this->failures.' failure(s), '
            .$this->warnings.' warning(s).'
        );

        if ($this->failures > 0) {
            $this->error('V1_RELEASE_AUDIT_FAILED');
            return self::FAILURE;
        }

        $this->info('V1_RELEASE_AUDIT_PASS');
        return self::SUCCESS;
    }

    private function auditSchema(): void
    {
        foreach ([
            'users',
            'wallets',
            'wallet_transactions',
            'market_instruments',
            'broker_orders',
            'market_execution_transactions',
            'trade_positions',
            'private_investment_instruments',
            'private_investment_assets',
            'private_investment_holdings',
            'private_investment_transactions',
            'private_investment_reserve_events',
            'private_market_references',
            'private_market_reference_prices',
            'public_investment_base_assets',
        ] as $table) {
            $this->check(
                'Schema',
                Schema::hasTable($table),
                $table.' table',
                true
            );
        }

        $this->check(
            'Schema',
            Schema::hasColumn('users', 'is_production_demo'),
            'users.is_production_demo protection flag',
            true
        );
    }

    private function auditProtectedDemos(bool $strict): void
    {
        if (! Schema::hasColumn('users', 'is_production_demo')) {
            return;
        }

        $demos = DB::table('users')
            ->where('is_production_demo', true)
            ->get(['id', 'email', 'is_admin', 'account_status']);

        $this->check(
            'Protected demos',
            $demos->count() === 3,
            'Expected exactly 3 protected demo users; found '.$demos->count().'.',
            $strict
        );

        $invalid = $demos->filter(
            fn ($user) =>
                (bool) $user->is_admin
                || ! str_ends_with(strtolower((string) $user->email), '.test')
        )->count();

        $this->check(
            'Protected demos',
            $invalid === 0,
            'Demo identities are non-admin and use non-deliverable .test email addresses.',
            true
        );

        $demoIds = $demos->pluck('id');

        if ($demoIds->isNotEmpty()) {
            $activeAutomation = 0;

            if (Schema::hasTable('automatic_investment_plans')) {
                $activeAutomation += DB::table('automatic_investment_plans')
                    ->whereIn('user_id', $demoIds)
                    ->where('is_active', true)
                    ->count();
            }

            if (Schema::hasTable('stock_trade_plans')) {
                $activeAutomation += DB::table('stock_trade_plans')
                    ->whereIn('user_id', $demoIds)
                    ->whereIn('status', ['active', 'due'])
                    ->count();
            }

            if (Schema::hasTable('trading_bots')) {
                $activeAutomation += DB::table('trading_bots')
                    ->whereIn('user_id', $demoIds)
                    ->where('status', 'active')
                    ->count();
            }

            if (Schema::hasTable('bot_subscriptions')) {
                $activeAutomation += DB::table('bot_subscriptions')
                    ->whereIn('user_id', $demoIds)
                    ->where('status', 'active')
                    ->count();
            }

            if (Schema::hasTable('copy_relationships')) {
                $activeAutomation += DB::table('copy_relationships')
                    ->where(function ($query) use ($demoIds) {
                        $query->whereIn('follower_id', $demoIds)
                            ->orWhereIn('provider_id', $demoIds);
                    })
                    ->whereNotIn('status', ['paused', 'stopped', 'completed'])
                    ->count();
            }

            $this->check(
                'Protected demos',
                $activeAutomation === 0,
                'Autonomous financial paths attached to protected demos: '.$activeAutomation.'.',
                true
            );
        }
    }

    private function auditQaContamination(bool $strict): void
    {
        $qaUsers = Schema::hasTable('users')
            ? DB::table('users')
                ->when(
                    Schema::hasColumn('users', 'is_production_demo'),
                    fn ($query) => $query->where('is_production_demo', false)
                )
                ->where('email', 'like', 'qa.%@rcentz.test')
                ->where(function ($query) {
                    $query->whereNull('account_status')
                        ->orWhere('account_status', '!=', 'blocked');
                })
                ->count()
            : 0;

        $visibleQaInvestments = Schema::hasTable('private_investment_instruments')
            ? DB::table('private_investment_instruments')
                ->where(function ($query) {
                    $query->where('symbol', 'like', 'QA%')
                        ->orWhere('name', 'like', '%ACCEPTANCE%');
                })
                ->where(function ($query) {
                    $query->where('is_visible', true)
                        ->orWhere('status', 'active');
                })
                ->count()
            : 0;

        $this->check(
            'Database hygiene',
            $qaUsers === 0,
            'Unquarantined QA users: '.$qaUsers.'.',
            $strict
        );

        $this->check(
            'Database hygiene',
            $visibleQaInvestments === 0,
            'Visible/active QA private investments: '.$visibleQaInvestments.'.',
            $strict
        );
    }

    private function auditBaseAssets(
        PrivateInvestmentReserveService $reserves,
        PrivateInvestmentReserveMarketService $markets
    ): void {
        if (
            ! Schema::hasTable('private_investment_assets')
            || ! Schema::hasTable('public_investment_base_assets')
            || ! Schema::hasTable('private_market_references')
        ) {
            return;
        }

        $marketLinkedMismatch = DB::table('private_investment_assets as a')
            ->leftJoin(
                'public_investment_base_assets as b',
                'b.id',
                '=',
                'a.public_investment_base_asset_id'
            )
            ->where('a.status', 'active')
            ->where('a.is_reserve_backing', true)
            ->where('a.valuation_mode', 'market_linked')
            ->where(function ($query) {
                $query->whereNull('a.market_instrument_id')
                    ->orWhereNull('a.public_investment_base_asset_id')
                    ->orWhereNotNull('a.private_market_reference_id')
                    ->orWhereNull('b.id')
                    ->orWhereColumn(
                        'b.market_instrument_id',
                        '!=',
                        'a.market_instrument_id'
                    );
            })
            ->count();

        $privateLinkedMismatch = DB::table('private_investment_assets')
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->where('valuation_mode', 'private')
            ->where(function ($query) {
                $query->whereNull('private_market_reference_id')
                    ->orWhereNotNull('market_instrument_id')
                    ->orWhereNotNull('public_investment_base_asset_id');
            })
            ->count();

        $invalidReserveValues = DB::table('private_investment_assets')
            ->where('status', 'active')
            ->where('is_reserve_backing', true)
            ->where(function ($query) {
                $query->where('reserve_quantity', '<=', 0)
                    ->orWhere('current_unit_price', '<=', 0)
                    ->orWhere('current_valuation', '<=', 0);
            })
            ->count();

        $invalidReferenceAsset = DB::table('private_investment_instruments as i')
            ->leftJoin(
                'private_investment_assets as a',
                'a.id',
                '=',
                'i.reference_asset_id'
            )
            ->whereNotNull('i.reference_asset_id')
            ->where(function ($query) {
                $query->whereNull('a.id')
                    ->orWhereColumn('a.instrument_id', '!=', 'i.id')
                    ->orWhere('a.status', '!=', 'active')
                    ->orWhere('a.is_reserve_backing', false);
            })
            ->count();

        $invalidPrivatePrices = DB::table('private_market_references')
            ->where('status', 'active')
            ->where('current_price', '<=', 0)
            ->count();

        $this->check(
            'Investment Base Assets',
            $marketLinkedMismatch === 0,
            'Public-market reserve authority mismatches: '.$marketLinkedMismatch.'.',
            true
        );

        $this->check(
            'Investment Base Assets',
            $privateLinkedMismatch === 0,
            'Private-reference reserve authority mismatches: '.$privateLinkedMismatch.'.',
            true
        );

        $this->check(
            'Investment Base Assets',
            $invalidReserveValues === 0,
            'Active reserve assets with non-positive quantity/price/value: '.$invalidReserveValues.'.',
            true
        );

        $this->check(
            'Investment Base Assets',
            $invalidReferenceAsset === 0,
            'Invalid instrument reference_asset_id relationships: '.$invalidReferenceAsset.'.',
            true
        );

        $this->check(
            'Investment Base Assets',
            $invalidPrivatePrices === 0,
            'Active private Base Assets with non-positive valuation: '.$invalidPrivatePrices.'.',
            true
        );

        $publicPriceFailures = 0;

        $publicAssets = DB::table('public_investment_base_assets')
            ->where('status', 'active')
            ->pluck('market_instrument_id');

        foreach ($publicAssets as $marketId) {
            $market = \App\Models\MarketInstrument::query()->find($marketId);

            if (! $market || ! $market->is_active) {
                $publicPriceFailures++;
                continue;
            }

            try {
                if ($markets->currentPrice($market) <= 0) {
                    $publicPriceFailures++;
                }
            } catch (\Throwable) {
                $publicPriceFailures++;
            }
        }

        $this->check(
            'Investment Base Assets',
            $publicPriceFailures === 0,
            'Active Public Base Assets without active positive-price market authority: '.$publicPriceFailures.'.',
            true
        );

        $coverageFailures = 0;

        foreach (PrivateInvestmentInstrument::query()->get() as $instrument) {
            $summary = $reserves->summary($instrument);

            if (
                $summary['customer_units'] > 0
                && ! $summary['customer_fully_backed']
            ) {
                $coverageFailures++;
                continue;
            }

            if (
                $instrument->status === 'active'
                && (
                    $summary['reserve_assets'] <= 0
                    || ! $summary['listed_fully_backed']
                    || $summary['sellable_units'] + 0.000001
                        < (float) $instrument->available_units
                )
            ) {
                $coverageFailures++;
            }
        }

        $this->check(
            'Investment Base Assets',
            $coverageFailures === 0,
            'Investment instruments failing reserve/customer coverage: '.$coverageFailures.'.',
            true
        );
    }

    private function auditWalletIntegrity(): void
    {
        if (! Schema::hasTable('wallets')) {
            return;
        }

        $invalidBalances = DB::table('wallets')
            ->where(function ($query) {
                $query->where('balance', '<', 0)
                    ->orWhere('reserved_balance', '<', 0)
                    ->orWhereColumn('reserved_balance', '>', 'balance');
            })
            ->count();

        $missingWallets = DB::table('users as u')
            ->leftJoin('wallets as w', 'w.user_id', '=', 'u.id')
            ->where('u.is_admin', false)
            ->whereNull('w.id')
            ->count();

        $duplicateWallets = DB::table('wallets')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->having('total', '>', 1)
            ->count();

        $this->check(
            'Wallet integrity',
            $invalidBalances === 0,
            'Wallets with negative or over-reserved balances: '.$invalidBalances.'.',
            true
        );

        $this->check(
            'Wallet integrity',
            $missingWallets === 0,
            'Non-admin users without a wallet: '.$missingWallets.'.',
            true
        );

        $this->check(
            'Wallet integrity',
            $duplicateWallets === 0,
            'Users with duplicate wallets: '.$duplicateWallets.'.',
            true
        );
    }

    private function auditRouteProtection(): void
    {
        foreach ([
            'broker.orders.submit',
            'broker.positions.close',
            'money.withdraw.request',
            'money.send.submit',
            'account.investments.subscribe',
            'account.investments.redeem',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            if (! $route) {
                $this->check(
                    'Route security',
                    false,
                    $name.' route missing.',
                    true
                );
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->check(
                'Route security',
                in_array('auth', $middleware, true)
                    && in_array('account.active', $middleware, true)
                    && in_array('demo.readonly', $middleware, true),
                $name.' includes auth/account/demo mutation boundaries.',
                true
            );
        }

        $adminRoute = Route::getRoutes()->getByName('admin.dashboard');

        $this->check(
            'Route security',
            $adminRoute
                && in_array('auth', $adminRoute->gatherMiddleware(), true)
                && in_array('admin', $adminRoute->gatherMiddleware(), true),
            'Admin dashboard requires auth + admin middleware.',
            true
        );
    }

    private function auditSecurityConfiguration(bool $strict): void
    {
        $production = app()->environment('production');

        if ($strict) {
            $this->check(
                'Security config',
                $production,
                'APP_ENV='.$this->safe((string) app()->environment()).'.',
                true
            );

            $this->check(
                'Security config',
                ! (bool) config('app.debug'),
                'APP_DEBUG must be false for production.',
                true
            );

            $url = (string) config('app.url');

            $this->check(
                'Security config',
                str_starts_with(strtolower($url), 'https://'),
                'Production APP_URL uses HTTPS.',
                true
            );

            $this->check(
                'Security config',
                (bool) config('session.secure'),
                'Secure session cookie enabled.',
                true
            );

            $this->check(
                'Security config',
                (bool) config('session.encrypt'),
                'Session payload encryption enabled.',
                true
            );
        } else {
            $this->rows[] = [
                'Security config',
                'INFO',
                'Deployment-only APP_ENV/APP_DEBUG/HTTPS/session-cookie checks are deferred until --production.'
            ];
        }

        $this->check(
            'Security config',
            (bool) config('release.production_demo.read_only', true),
            'Protected production demos are read-only.',
            true
        );

        $cronToken = (string) config('app.cron_token', '');

        $this->check(
            'Security config',
            ! $strict || strlen($cronToken) >= 32,
            'CRON_TOKEN is configured with at least 32 characters.',
            $strict
        );
    }

    private function check(
        string $area,
        bool $ok,
        string $detail,
        bool $critical
    ): void {
        if ($ok) {
            $this->rows[] = [$area, 'PASS', $detail];
            return;
        }

        if ($critical) {
            $this->failures++;
            $status = 'FAIL';
        } else {
            $this->warnings++;
            $status = 'WARN';
        }

        $this->rows[] = [$area, $status, $detail];
    }

    private function safe(string $value): string
    {
        return $value !== '' ? $value : 'unset';
    }
}
