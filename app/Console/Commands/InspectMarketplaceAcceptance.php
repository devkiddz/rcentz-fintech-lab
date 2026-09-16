<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\Stock;
use App\Models\StockHolding;
use App\Models\StockTransaction;
use App\Models\TradePosition;
use App\Services\MarketPriceRouter;
use Illuminate\Console\Command;

class InspectMarketplaceAcceptance extends Command
{
    protected $signature = 'market:acceptance';
    protected $description = 'Read-only acceptance check for Live / Controlled price and financial isolation.';

    public function handle(MarketPriceRouter $prices): int
    {
        $errors = [];
        $rows = [];

        $stocks = Stock::query()
            ->where('is_active', true)
            ->orderBy('symbol')
            ->get();

        foreach ($stocks as $stock) {
            $live = null;
            $controlled = null;
            $liveOk = true;
            $controlledOk = true;

            try { $live = $prices->price($stock, 'live'); }
            catch (\Throwable $e) { $liveOk = false; }

            if (ControlledMarketInstrument::query()->where('stock_id', $stock->id)->where('is_active', true)->exists()) {
                try { $controlled = $prices->price($stock, 'controlled'); }
                catch (\Throwable $e) { $controlledOk = false; }
            } else {
                $controlledOk = false;
            }

            if (! $liveOk) $errors[] = $stock->symbol.' has no valid Live price.';
            if (! $controlledOk) $errors[] = $stock->symbol.' has no active Controlled price.';

            $rows[] = [
                $stock->symbol,
                $live !== null ? number_format($live, 6) : 'UNAVAILABLE',
                $controlled !== null ? number_format($controlled, 6) : 'UNAVAILABLE',
                ($liveOk && $controlledOk) ? 'DUAL' : 'CHECK',
            ];
        }

        $invalidHoldings = StockHolding::query()
            ->whereNotIn('marketplace', ['live','controlled'])
            ->count();
        $invalidPositions = TradePosition::query()
            ->whereNotIn('marketplace', ['live','controlled'])
            ->count();
        $invalidTransactions = StockTransaction::query()
            ->whereNotIn('marketplace', ['live','controlled'])
            ->count();

        if ($invalidHoldings) $errors[] = $invalidHoldings.' holding(s) have invalid marketplace identity.';
        if ($invalidPositions) $errors[] = $invalidPositions.' position(s) have invalid marketplace identity.';
        if ($invalidTransactions) $errors[] = $invalidTransactions.' transaction(s) have invalid marketplace identity.';

        $this->info('Rcentz Dual Marketplace acceptance');
        $this->newLine();
        $this->table(['Instrument','Live price','Controlled price','Authority'], $rows);
        $this->newLine();
        $this->table(['Financial boundary','Live','Controlled'], [[
            'Holdings',
            StockHolding::query()->where('marketplace','live')->where('quantity','>',0)->count(),
            StockHolding::query()->where('marketplace','controlled')->where('quantity','>',0)->count(),
        ],[
            'Open positions',
            TradePosition::query()->where('marketplace','live')->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->count(),
            TradePosition::query()->where('marketplace','controlled')->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->count(),
        ],[
            'Transactions',
            StockTransaction::query()->where('marketplace','live')->count(),
            StockTransaction::query()->where('marketplace','controlled')->count(),
        ]]);

        $this->newLine();
        $this->line('Active marketplace: '.strtoupper($prices->activeMarketplace()));
        $this->line('This command does not switch markets or mutate wallets/trades.');

        if ($errors) {
            foreach ($errors as $error) $this->error($error);
            return self::FAILURE;
        }

        $this->info('PASS: Live and Controlled market authorities are independently resolvable and financial rows are marketplace-scoped.');
        return self::SUCCESS;
    }
}
