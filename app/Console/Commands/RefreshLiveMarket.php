<?php

namespace App\Console\Commands;

use App\Jobs\UpdateStockQuotesJob;
use App\Models\Stock;
use App\Models\StockQuote;
use Illuminate\Console\Command;

class RefreshLiveMarket extends Command
{
    protected $signature = 'market:live-refresh';
    protected $description = 'Run one synchronous Live Market quote refresh and report the instruments refreshed by that run.';

    public function handle(): int
    {
        $startedAt = now();
        $active = Stock::query()->where('is_active', true)->count();

        $this->info('Refreshing Live Market quotes...');

        try {
            UpdateStockQuotesJob::dispatchSync();
        } catch (\Throwable $e) {
            $this->error('Live Market refresh failed: '.$e->getMessage());
            return self::FAILURE;
        }

        $quotes = StockQuote::query()
            ->where('fetched_at', '>=', $startedAt->copy()->subSecond())
            ->orderBy('symbol')
            ->get(['symbol','current_price','fetched_at']);

        if ($quotes->isEmpty()) {
            $this->error('No persisted Live Market quote was produced by this run. Check API credentials/rate limits.');
            return self::FAILURE;
        }

        $this->table(
            ['Symbol', 'Price', 'Fetched at'],
            $quotes->map(fn ($quote) => [
                $quote->symbol,
                number_format((float) $quote->current_price, 6),
                optional($quote->fetched_at)?->toDateTimeString() ?: 'unknown',
            ])->all()
        );

        $refreshed = $quotes->pluck('symbol')->unique()->count();
        $this->info('Live Market quote refresh completed: '.$refreshed.' / '.$active.' active instruments persisted fresh quotes.');

        if ($refreshed < $active) {
            $this->warn('Some active instruments did not persist a quote in this run. Check storage/logs/laravel.log for symbol-specific API errors.');
        }

        return self::SUCCESS;
    }
}
