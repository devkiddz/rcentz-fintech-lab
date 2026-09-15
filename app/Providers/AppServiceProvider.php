<?php

namespace App\Providers;

use App\Models\StockQuote;
use App\Models\User;
use App\Observers\StockQuoteObserver;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        User::observe(UserObserver::class);
        StockQuote::observe(StockQuoteObserver::class);

        View::addNamespace('mail', resource_path('views/vendor/mail/html'));
    }
}
