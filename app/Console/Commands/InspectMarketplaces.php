<?php

namespace App\Console\Commands;

use App\Models\ControlledMarketInstrument;
use App\Models\MarketEnvironment;
use App\Models\StockHolding;
use App\Models\TradePosition;
use App\Services\LiveMarketHealthService;
use App\Services\MarketPriceRouter;
use App\Services\StockDataService;
use Illuminate\Console\Command;

class InspectMarketplaces extends Command
{
    protected $signature = 'market:status';
    protected $description = 'Inspect the live and controlled marketplace price authorities.';

    public function handle(
        MarketPriceRouter $prices,
        LiveMarketHealthService $health,
        StockDataService $stockData
    ): int {
        $environment = MarketEnvironment::current();
        $live = $health->snapshot($stockData);

        $this->info('Rcentz marketplace status');
        $this->newLine();
        $this->table(['Setting','Value'], [
            ['Active marketplace', strtoupper($prices->activeMarketplace())],
            ['Controlled drive', strtoupper($environment->controlled_drive_mode)],
            ['Controlled strength', number_format((float)$environment->controlled_drive_strength, 2).'x'],
            ['Controlled instruments', ControlledMarketInstrument::query()->where('is_active', true)->count()],
            ['Live open positions', TradePosition::query()->where('marketplace','live')->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->count()],
            ['Controlled open positions', TradePosition::query()->where('marketplace','controlled')->whereIn('status',['open','exit_queued'])->where('open_quantity','>',0)->count()],
            ['Live holdings', StockHolding::query()->where('marketplace','live')->where('quantity','>',0)->count()],
            ['Controlled holdings', StockHolding::query()->where('marketplace','controlled')->where('quantity','>',0)->count()],
        ]);

        $this->newLine();
        $this->table(['Live feed check','Value'], [
            ['Finnhub configured', $live['finnhub_available'] ? 'YES' : 'NO'],
            ['Yahoo available', $live['yahoo_available'] ? 'YES' : 'NO'],
            ['Active stocks', $live['active_stocks']],
            ['Fresh <= 15m', $live['fresh_stocks']],
            ['Latest quote', $live['latest_quote_at'] ? $live['latest_quote_symbol'].' @ '.$live['latest_quote_at'] : 'NONE'],
        ]);

        $this->line('Live and Controlled prices are stored independently.');
        $this->line('The active marketplace controls display price and NEW execution price.');
        $this->line('Marketplace switching changes the active desk/new executions only. Existing holdings and positions retain their marketplace identity.');

        return self::SUCCESS;
    }
}
