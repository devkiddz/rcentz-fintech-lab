<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\StockApiInterface;
use App\Services\FinnhubApiService;
use App\Services\YahooFinanceApiService;
use App\Services\StockDataService;

class StockApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(StockApiInterface::class, function ($app) {
            return new FinnhubApiService();
        });

        $this->app->singleton(YahooFinanceApiService::class, function ($app) {
            return new YahooFinanceApiService();
        });

        $this->app->singleton(StockDataService::class, function ($app) {
            return new StockDataService(
                $app->make(StockApiInterface::class),
                $app->make(YahooFinanceApiService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
