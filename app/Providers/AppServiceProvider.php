<?php

namespace App\Providers;

use App\Models\MembershipType;
use App\Models\SignalEvent;
use App\Models\StockQuote;
use App\Models\User;
use App\Observers\SignalEventObserver;
use App\Observers\StockQuoteObserver;
use App\Observers\UserObserver;
use App\Services\MailConfigurationService;
use App\Services\MarketPriceRouter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One request/process should resolve one marketplace decision consistently.
        $this->app->singleton(MarketPriceRouter::class);
        $this->app->singleton(MailConfigurationService::class);
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        StockQuote::observe(StockQuoteObserver::class);
        SignalEvent::observe(SignalEventObserver::class);

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
