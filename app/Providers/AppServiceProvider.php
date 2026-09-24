<?php

namespace App\Providers;

use App\Contracts\CryptoExecutionQuoteProvider;
use App\Contracts\ForexExecutionQuoteProvider;
use App\Models\MembershipType;
use App\Models\SignalEvent;
use App\Models\StockQuote;
use App\Models\User;
use App\Observers\SignalEventObserver;
use App\Observers\StockQuoteObserver;
use App\Observers\UserObserver;
use App\Services\Execution\AlphaVantageCryptoExecutionQuoteProvider;
use App\Services\Execution\AlphaVantageForexExecutionQuoteProvider;
use App\Services\MailConfigurationService;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One request/process should resolve one marketplace decision consistently.
        $this->app->singleton(MarketPriceRouter::class);
        $this->app->singleton(ForexExecutionQuoteProvider::class, AlphaVantageForexExecutionQuoteProvider::class);
        $this->app->singleton(CryptoExecutionQuoteProvider::class, AlphaVantageCryptoExecutionQuoteProvider::class);
        $this->app->singleton(MailConfigurationService::class);
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        StockQuote::observe(StockQuoteObserver::class);
        SignalEvent::observe(SignalEventObserver::class);

        // Synthetic QA seeders are development tools only. Production keeps
        // exactly the installer-owned protected demonstration identities.
        Event::listen(CommandStarting::class, function (CommandStarting $event): void {
            if (! app()->environment('production')) {
                return;
            }

            $blockedCommands = [
                'investment:seed-demo-holdings',
                'broker:seed-real-cases',
                'communication:seed-ms7-real-cases',
                'copy-trading:seed-real-cases',
                'membership:seed-ms5-real-cases',
                'investment:seed-ms4-real-cases',
                'reward:seed-ms6-real-cases',
                'trading-intelligence:seed-preview',
            ];

            if (in_array($event->command, $blockedCommands, true)) {
                throw new \RuntimeException(
                    'Synthetic QA seed commands are disabled in production.'
                );
            }

            if ($event->command !== 'db:seed') {
                return;
            }

            try {
                $class = ltrim((string) $event->input->getOption('class'), '\\');
            } catch (\Throwable) {
                $class = '';
            }

            $blockedSeeders = [
                'Database\\Seeders\\LiveTestDataSeeder',
                'Database\\Seeders\\PrivateInvestmentDemoSeeder',
                'Database\\Seeders\\TradingIntelligenceDemoSeeder',
                'LiveTestDataSeeder',
                'PrivateInvestmentDemoSeeder',
                'TradingIntelligenceDemoSeeder',
            ];

            if (in_array($class, $blockedSeeders, true)) {
                throw new \RuntimeException(
                    'Synthetic QA database seeders are disabled in production.'
                );
            }
        });

        // Database-backed mail configuration is an operational override. The
        // service fails safely so unavailable databases/settings never block boot.
        $this->app->make(MailConfigurationService::class)->apply();

        View::composer([
            'partials.shell.customer-sidebar-nav',
            'partials.shell.admin-sidebar-nav',
        ], function ($view) {
            try {
                $membershipNavTypes = Schema::hasTable('membership_types')
                    ? MembershipType::query()->active()->orderBy('sort_order')->orderBy('name')->get()
                    : collect();
            } catch (\Throwable) {
                $membershipNavTypes = collect();
            }

            $view->with('membershipNavTypes', $membershipNavTypes);
        });

        View::addNamespace('mail', resource_path('views/vendor/mail/html'));
    }
}
